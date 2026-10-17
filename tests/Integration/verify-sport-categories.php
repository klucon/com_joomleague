<?php

declare(strict_types=1);
define('_JEXEC', 1);
define('JPATH_BASE', '/var/www/html');
$_SERVER['HTTP_HOST'] = 'localhost';
$_SERVER['REQUEST_URI'] = '/';
require JPATH_BASE . '/includes/defines.php';
require JPATH_BASE . '/includes/framework.php';
JLoader::registerNamespace('Joomleague\\Component\\Joomleague\\Administrator', JPATH_ADMINISTRATOR . '/components/com_joomleague/src', false, false, 'psr4');
JLoader::registerNamespace('Joomleague\\Component\\Joomleague\\Domain', JPATH_ADMINISTRATOR . '/components/com_joomleague/src', false, false, 'psr4');

use Joomla\CMS\Factory;
use Joomla\Database\DatabaseInterface;
use Joomleague\Component\Joomleague\Administrator\Service\SportCategoryService;

$container = Factory::getContainer();
$container->alias('session', 'session.cli')->alias(Joomla\Session\SessionInterface::class, 'session.cli');
Factory::$application = $container->get(Joomla\CMS\Application\SiteApplication::class);
$db = $container->get(DatabaseInterface::class);
if (in_array('--hold-lock', $argv, true)) {
	$db->transactionStart();
	try {
		$db->setQuery('SELECT id FROM #__joomleague_sport_category_lock WHERE id = 1 FOR UPDATE')->loadResult();
		echo "LOCK_READY\n";
		flush();
		sleep(5);
	} finally { $db->transactionRollback(); }
	exit;
}
$service = new SportCategoryService($db);
$suffix = bin2hex(random_bytes(5));
$profileId = null;
$categories = [];
$state = static fn (): string => hash('sha256', json_encode([
	$db->setQuery('SELECT id, profile_version_id FROM #__joomleague_project ORDER BY id')->loadAssocList(),
	$db->setQuery('SELECT id, payload_checksum FROM #__joomleague_sport_profile_version ORDER BY id')->loadAssocList(),
], JSON_THROW_ON_ERROR));
$before = $state();
$expect = static function (callable $operation): void {
	try { $operation(); }
	catch (InvalidArgumentException $error) { return; }
	throw new RuntimeException('Expected invalid category operation to be rejected.');
};
try {
	$profile = (object) ['code' => 'category-fixture-' . $suffix, 'name_key' => 'TEST', 'description_key' => 'TEST', 'published' => 0];
	$db->insertObject('#__joomleague_sport_profile', $profile, 'id');
	$profileId = (int) $profile->id;
	$started = microtime(true);
	$root = $service->save(['code' => 'category-root-' . $suffix, 'name' => 'Root'], 0);
	$categories[] = $root;
	if (in_array('--assert-lock', $argv, true) && microtime(true) - $started < 2) throw new RuntimeException('Concurrent category write did not wait for the taxonomy lock.');
	$child = $service->save(['code' => 'category-child-' . $suffix, 'name' => 'Child', 'parent_id' => $root], 0);
	$categories[] = $child;
	$service->save(['id' => $child, 'code' => 'category-child-' . $suffix, 'name' => 'Child', 'parent_id' => $root, 'published' => 0], 0, false);
	if ((int) $db->setQuery('SELECT published FROM #__joomleague_sport_category WHERE id = ' . $child)->loadResult() !== 1) throw new RuntimeException('Edit without state permission changed publication.');
	$original = $db->setQuery('SELECT uuid FROM #__joomleague_sport_category WHERE id = ' . $child)->loadResult();
	$service->save(['id' => $child, 'code' => 'category-child-' . $suffix, 'name' => 'Updated', 'parent_id' => $root, 'uuid' => 'tampered'], 0);
	if ($original !== $db->setQuery('SELECT uuid FROM #__joomleague_sport_category WHERE id = ' . $child)->loadResult()) throw new RuntimeException('Category UUID changed.');
	$expect(fn () => $service->save(['id' => $root, 'code' => 'category-root-' . $suffix, 'name' => 'Root', 'parent_id' => $child], 0));
	$expect(fn () => $service->save(['code' => 'category-root-' . $suffix, 'name' => 'Duplicate'], 0));
	$expect(fn () => $service->save(['code' => 'category-orphan-' . $suffix, 'name' => 'Orphan', 'parent_id' => 999999999], 0));
	$expect(fn () => $service->save(['code' => 'category-invalid-' . $suffix, 'name' => ['bad']], 0));
	$expect(fn () => $service->delete($root));
	$service->assign([$profileId], [$root, $child, $child]);
	$count = (int) $db->setQuery('SELECT COUNT(*) FROM #__joomleague_sport_profile_category WHERE profile_id = ' . $profileId)->loadResult();
	if ($count !== 2) throw new RuntimeException('Multiple category assignment failed.');
	$expect(fn () => $service->delete($child));
	$expect(fn () => $service->assign([$profileId], [999999999]));
	$expect(fn () => $service->assign([[$profileId]], []));
	if ((int) $db->setQuery('SELECT COUNT(*) FROM #__joomleague_sport_profile_category WHERE profile_id = ' . $profileId)->loadResult() !== 2) throw new RuntimeException('Rejected assignment modified existing classification.');
	$items = array_column($service->categories(), null, 'id');
	if ($items[$child]->path !== 'Root / Updated') throw new RuntimeException('Category path was not built.');
	$service->assign([$profileId], []);
	if ($state() !== $before) throw new RuntimeException('Classification changed project pins or historical contracts.');
	echo "Sport categories: CRUD, UUID, cycles, duplicates, orphan rejection, multiple assignments and immutable project contracts OK\n";
	if (in_array('--browser', $argv, true)) {
		echo 'BROWSER_FIXTURE=' . json_encode(['profile' => $profileId, 'root' => $root, 'child' => $child], JSON_THROW_ON_ERROR) . "\n";
		flush();
		$complete = false;
		for ($attempt = 0; $attempt < 120; ++$attempt) {
			if ($db->setQuery('SELECT name FROM #__joomleague_sport_category WHERE id = ' . $root)->loadResult() === 'Browser validated') {
				$complete = true;
				break;
			}
			sleep(1);
		}
		if (!$complete || $state() !== $before) throw new RuntimeException('Browser assignment test did not complete or changed historical contracts.');
	}
} finally {
	if ($profileId !== null) {
		$service->assign([$profileId], []);
		$db->setQuery('DELETE FROM #__joomleague_sport_profile WHERE id = ' . $profileId)->execute();
	}
	foreach (array_reverse($categories) as $id) $service->delete($id);
}
