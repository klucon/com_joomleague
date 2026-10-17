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
use Joomleague\Component\Joomleague\Administrator\Service\SportVariantService;

$container = Factory::getContainer();
$container->alias('session', 'session.cli')->alias(Joomla\Session\SessionInterface::class, 'session.cli');
Factory::$application = $container->get(Joomla\CMS\Application\SiteApplication::class);
Factory::getApplication()->createExtensionNamespaceMap();
$mvc = Factory::getApplication()->bootComponent('com_joomleague')->getMVCFactory();
$db = $container->get(DatabaseInterface::class);
$service = new SportVariantService($db);
$base = $db->setQuery("SELECT v.id, v.profile_id FROM #__joomleague_sport_profile_version v INNER JOIN #__joomleague_sport_profile p ON p.id = v.profile_id WHERE p.code = 'basketball' AND v.state = 'active'")->loadAssoc();
if (!$base) throw new RuntimeException('Basketball fixture base is not installed.');
$other = (int) $db->setQuery('SELECT id FROM #__joomleague_sport_profile_version WHERE profile_id <> ' . (int) $base['profile_id'])->loadResult();
$state = static fn (): string => hash('sha256', json_encode([
	$db->setQuery('SELECT id, profile_version_id FROM #__joomleague_project ORDER BY id')->loadAssocList(),
	$db->setQuery('SELECT id, payload_checksum, state FROM #__joomleague_sport_profile_version ORDER BY id')->loadAssocList(),
], JSON_THROW_ON_ERROR));
$before = $state();
$expect = static function (callable $operation): void {
	try { $operation(); }
	catch (InvalidArgumentException) { return; }
	throw new RuntimeException('Invalid variant operation was accepted.');
};
$variant = null;
$compiledIds = [];
$projectIds = [];
$sportId = null;
$suffix = bin2hex(random_bytes(5));
try {
	$variant = $service->create((int) $base['profile_id'], 'fixture-' . $suffix, 'Variant fixture', 0);
	$expect(fn () => $service->setPublished($variant, 1));
	$uuid = $db->setQuery('SELECT uuid FROM #__joomleague_sport_variant WHERE id = ' . $variant)->loadResult();
	if (!preg_match('/^[0-9a-f-]{36}$/D', $uuid)) throw new RuntimeException('Generated UUID is invalid.');
	if ((int) $db->setQuery('SELECT published FROM #__joomleague_sport_variant WHERE id = ' . $variant)->loadResult() !== 0) throw new RuntimeException('New variant must be unpublished.');
	$expect(fn () => $service->create((int) $base['profile_id'], 'fixture-' . $suffix, 'Duplicate', 0));
	$expect(fn () => $service->create(999999999, 'missing-' . $suffix, 'Missing sport', 0));
	$first = $service->addRevision($variant, (int) $base['id'], '1.0.0', ['lineup' => ['players_on_field' => 4]], 0);
	$snapshot = $service->revision($first);
	if ($snapshot['profile']['lineup']['players_on_field'] !== 4) throw new RuntimeException('Compiled revision is missing its rules.');
	$expect(fn () => $service->addRevision($variant, (int) $base['id'], '1.0.0', [], 0));
	$expect(fn () => $service->addRevision($variant, (int) $base['id'], '0.9.0', [], 0));
	$expect(fn () => $service->addRevision($variant, $other, '1.1.0', [], 0));
	$expect(fn () => $service->addRevision($variant, (int) $base['id'], '1.1.0', ['unknown' => true], 0));
	$second = $service->addRevision($variant, (int) $base['id'], '1.1.0', ['lineup' => ['players_on_field' => 5]], 0);
	if ($service->revision($first) !== $snapshot || $service->revision($second)['profile']['lineup']['players_on_field'] !== 5) throw new RuntimeException('A new revision changed historical rules.');
	if ($before !== $state()) throw new RuntimeException('Variant creation changed base profiles or project pins.');
	$secondHash = $service->revision($second)['payload_checksum'];
	$db->setQuery("UPDATE #__joomleague_sport_variant_version SET payload_checksum = '" . str_repeat('0', 64) . "' WHERE id = " . $second)->execute();
	try { $service->revision($second); throw new RuntimeException('Corrupted revision was accepted.'); }
	catch (UnexpectedValueException) {}
	$db->setQuery("UPDATE #__joomleague_sport_variant_version SET payload_checksum = '" . $secondHash . "' WHERE id = " . $second)->execute();
	$expect(fn () => $service->materialize($first, (int) $base['profile_id'], 0));
	$service->setPublished($variant, 1);
	$compiledId = $service->materialize($first, (int) $base['profile_id'], 0);
	$compiledIds[] = $compiledId;
	if ($service->materialize($first, (int) $base['profile_id'], 0) !== $compiledId) throw new RuntimeException('Materialization is not idempotent.');
	$actor = (int) $db->setQuery('SELECT id FROM #__users ORDER BY id', 0, 1)->loadResult();
	$user = $container->get(Joomla\CMS\User\UserFactoryInterface::class)->loadUserById($actor);
	$sport = (object) ['uuid' => Joomleague\Component\Joomleague\Domain\Service\UuidFactory::v4(), 'profile_version_id' => (int) $base['id'], 'code' => 'variant-fixture-' . $suffix, 'name' => 'Variant test sport', 'published' => 1];
	$db->insertObject('#__joomleague_sport_type', $sport, 'id');
	$sportId = (int) $sport->id;
	$sample = $db->setQuery('SELECT * FROM #__joomleague_project ORDER BY id', 0, 1)->loadAssoc();
	if (!$sample) throw new RuntimeException('Existing project required for competition and season fixture.');
	$create = $sample;
	$create['id'] = 0;
	$create['asset_id'] = 0;
	$create['name'] = 'Variant regression ' . $suffix;
	$create['alias'] = 'variant-regression-' . $suffix;
	$create['sport_type_id'] = $sportId;
	$create['sport_variant_version_id'] = $first;
	$create['project_type'] = 'league';
	$create['published'] = 0;
	$create['checked_out'] = null;
	$create['checked_out_time'] = null;
	$model = $mvc->createModel('Project', 'Administrator', ['ignore_request' => true]);
	$model->setCurrentUser($user);
	if (!$model->save($create)) throw new RuntimeException('Variant project save failed: ' . $model->getError());
	$projectId = (int) $model->getState('project.id');
	$projectIds[] = $projectId;
	$project = $db->setQuery('SELECT * FROM #__joomleague_project WHERE id = ' . $projectId)->loadAssoc();
	if ((int) $project['profile_version_id'] !== $compiledId || (int) $project['sport_variant_version_id'] !== $first) throw new RuntimeException('Project did not pin the compiled revision.');
	$context = (new Joomleague\Component\Joomleague\Administrator\Service\ProjectContextRepository($db))->get($projectId);
	if ($context->profile['lineup']['players_on_field'] !== 4) throw new RuntimeException('Runtime resolver did not read the compiled rules.');
	$edit = $mvc->createModel('Project', 'Administrator', ['ignore_request' => true]);
	$edit->setCurrentUser($user);
	$forged = $project;
	$forged['sport_variant_version_id'] = 0;
	if ($edit->save($forged)) throw new RuntimeException('Ordinary save removed the pinned variant.');
	$project['profile_version_id'] = (int) $base['id'];
	if (!$edit->save($project)) throw new RuntimeException('Ordinary variant project edit failed: ' . $edit->getError());
	if ((int) $db->setQuery('SELECT profile_version_id FROM #__joomleague_project WHERE id = ' . $projectId)->loadResult() !== $compiledId) throw new RuntimeException('Ordinary save replaced the pinned contract.');
	$review = (new Joomleague\Component\Joomleague\Administrator\Service\ProjectProfileMigrationService($db))->inspect($projectId);
	if (!isset($review['variant']) || $review['target'] !== null) throw new RuntimeException('Variant project was offered a base upgrade.');
	if ((int) ($review['variant_target']['id'] ?? 0) !== $second || $review['changes'] === [] || !in_array('/lineup/players_on_field', $review['blockers'], true)) throw new RuntimeException('Variant review did not identify the newer revision and changed lineup rules.');
	$baseRejected = false;
	try { (new Joomleague\Component\Joomleague\Administrator\Service\ProjectProfileMigrationService($db))->inspect($projectId, (int) $base['id']); }
	catch (RuntimeException) { $baseRejected = true; }
	if (!$baseRejected) throw new RuntimeException('Variant accepted the base migration endpoint.');
	$service->setPublished($variant, 0);
	$hiddenReview = (new Joomleague\Component\Joomleague\Administrator\Service\ProjectProfileMigrationService($db))->inspect($projectId);
	if ($hiddenReview['variant_target'] !== null) throw new RuntimeException('Unpublished variant was offered as a migration target.');
	$service->setPublished($variant, 1);
	$clone = (new Joomleague\Component\Joomleague\Administrator\Service\ProjectCloner($db))->clone($projectId, 'Variant clone ' . $suffix);
	$projectIds[] = $clone;
	if ((int) $db->setQuery('SELECT sport_variant_version_id FROM #__joomleague_project WHERE id = ' . $clone)->loadResult() !== $first) throw new RuntimeException('Cloning lost the pinned variant.');
	$asset = $db->setQuery('SELECT a.* FROM #__assets a JOIN #__joomleague_project p ON p.asset_id = a.id WHERE p.id = ' . $clone)->loadAssoc();
	if (!$asset || $asset['name'] !== 'com_joomleague.project.' . $clone || json_decode($asset['rules'], true) !== []) throw new RuntimeException('Clone did not receive a fresh ACL asset.');
	if ($db->getServerType() === 'postgresql') {
		$treeBefore = $db->setQuery('SELECT id, parent_id, lft, rgt, level FROM #__assets ORDER BY id')->loadAssocList();
		$db->transactionStart();
		try {
			$table = new Joomleague\Component\Joomleague\Administrator\Table\ProjectTable($db);
			$table->bind($db->setQuery('SELECT * FROM #__joomleague_project WHERE id = ' . $clone)->loadAssoc());
			$table->id = 0;
			$table->asset_id = 0;
			$table->uuid = Joomleague\Component\Joomleague\Domain\Service\UuidFactory::v4();
			if (!$table->check() || !$table->storeCloneInTransaction()) throw new RuntimeException('Rollback fixture storage failed.');
			$rolledBackId = (int) $table->id;
		} finally {
			$db->transactionRollback();
		}
		if ($db->setQuery('SELECT id FROM #__joomleague_project WHERE id = ' . $rolledBackId)->loadResult() !== null) throw new RuntimeException('Rollback retained the cloned project.');
		if ($treeBefore !== $db->setQuery('SELECT id, parent_id, lft, rgt, level FROM #__assets ORDER BY id')->loadAssocList()) throw new RuntimeException('Rollback changed the asset tree.');
	}
	$baseCreate = $create;
	$migration = new Joomleague\Component\Joomleague\Administrator\Service\ProjectProfileMigrationService($db);
	$blocked = false;
	try { $migration->migrateVariant($projectId, $second, $review['checksum'], $actor); }
	catch (RuntimeException) { $blocked = true; }
	if (!$blocked) throw new RuntimeException('Semantic rule changes were migrated without a data converter.');
	$compatible = $service->addRevision($variant, (int) $base['id'], '2.0.0', ['lineup' => ['players_on_field' => 4]], 0);
	$compatibleReview = $migration->inspect($projectId);
	if ($compatibleReview['blockers'] !== [] || (int) $compatibleReview['variant_target']['id'] !== $compatible) throw new RuntimeException('Equivalent rules should be migratable.');
	$staleRejected = false;
	try { $migration->migrateVariant($projectId, $compatible, str_repeat('0', 64), $actor); }
	catch (RuntimeException) { $staleRejected = true; }
	if (!$staleRejected) throw new RuntimeException('Stale migration confirmation was accepted.');
	$db->setQuery("UPDATE #__joomleague_project SET name = 'Changed after review " . $suffix . "' WHERE id = " . $projectId)->execute();
	$staleRejected = false;
	try { $migration->migrateVariant($projectId, $compatible, $compatibleReview['checksum'], $actor); }
	catch (RuntimeException) { $staleRejected = true; }
	if (!$staleRejected) throw new RuntimeException('Changed project accepted a previous migration review.');
	$compatibleReview = $migration->inspect($projectId);
	$migration->migrateVariant($projectId, $compatible, $compatibleReview['checksum'], $actor);
	$migrated = $db->setQuery('SELECT profile_version_id, sport_variant_version_id FROM #__joomleague_project WHERE id = ' . $projectId)->loadAssoc();
	$compiledIds[] = (int) $migrated['profile_version_id'];
	if ((int) $migrated['sport_variant_version_id'] !== $compatible || (int) $migrated['profile_version_id'] === $compiledId) throw new RuntimeException('Migration did not atomically replace both pins.');
	$migratedReview = $migration->inspect($projectId);
	if ($migratedReview['history'][0]['from_version'] !== '1.0.0' || $migratedReview['history'][0]['to_version'] !== '2.0.0') throw new RuntimeException('Variant migration history is missing readable revisions.');
	$baseCreate['sport_variant_version_id'] = 0;
	$baseCreate['alias'] .= '-base';
	$baseModel = $mvc->createModel('Project', 'Administrator', ['ignore_request' => true]);
	$baseModel->setCurrentUser($user);
	if (!$baseModel->save($baseCreate)) throw new RuntimeException('Base project creation failed: ' . $baseModel->getError());
	$baseProject = (int) $baseModel->getState('project.id');
	$projectIds[] = $baseProject;
	if ((int) $db->setQuery('SELECT profile_version_id FROM #__joomleague_project WHERE id = ' . $baseProject)->loadResult() !== (int) $base['id']) throw new RuntimeException('Compiled variant became the default base profile.');
	if (in_array('--browser', $argv, true)) {
		echo 'BROWSER_FIXTURE=' . json_encode(['variant' => $variant, 'project' => $projectId, 'sport' => $sportId, 'base' => (int) $base['id']], JSON_THROW_ON_ERROR) . "\n";
		flush();
		$complete = false;
		for ($attempt = 0; $attempt < 180; ++$attempt) {
			$latest = $service->revisions($variant);
			$published = (int) $db->setQuery('SELECT published FROM #__joomleague_sport_variant WHERE id = ' . $variant)->loadResult();
			if (($latest[0]['revision'] ?? '') === '4.0.0' && $published === 0) { $complete = true; break; }
			sleep(1);
		}
		if (!$complete) throw new RuntimeException('Browser variant test did not finish.');
	}
	echo "Sport variants OK: immutable revisions, UUID, checksum verification, rollback, cross-sport rejection, cloning with fresh ACL assets and unchanged project pins.\n";
} finally {
	foreach (array_reverse($projectIds) as $projectId) {
		$table = $mvc->createTable('Project', 'Administrator');
		if (!$table->delete($projectId)) throw new RuntimeException('Project fixture cleanup failed: ' . $table->getError());
	}
	if ($sportId !== null) $db->setQuery('DELETE FROM #__joomleague_sport_type WHERE id = ' . $sportId)->execute();
	if ($variant !== null) {
		$compiledIds = array_unique(array_merge($compiledIds, array_map('intval', $db->setQuery('SELECT compiled_profile_version_id FROM #__joomleague_sport_variant_version WHERE variant_id = ' . $variant . ' AND compiled_profile_version_id IS NOT NULL')->loadColumn())));
		$db->setQuery("DELETE FROM #__joomleague_sport_variant WHERE profile_id = " . (int) $base['profile_id'] . " AND code = 'browser-variant-create-" . $variant . "'")->execute();
		$db->setQuery('DELETE FROM #__joomleague_sport_variant_version WHERE variant_id = ' . $variant)->execute();
		$db->setQuery('DELETE FROM #__joomleague_sport_variant WHERE id = ' . $variant)->execute();
	}
	foreach ($compiledIds as $compiledId) $db->setQuery('DELETE FROM #__joomleague_sport_profile_version WHERE id = ' . $compiledId)->execute();
	if ($state() !== $before) throw new RuntimeException('Fixture cleanup did not restore project pins and profile versions.');
}
