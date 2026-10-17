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
use Joomleague\Component\Joomleague\Administrator\Service\ProjectProfileMigrationService;

$container = Factory::getContainer();
$container->alias('session', 'session.cli')->alias(Joomla\Session\SessionInterface::class, 'session.cli');
Factory::$application = $container->get(Joomla\CMS\Application\SiteApplication::class);
Factory::getApplication()->createExtensionNamespaceMap();
Factory::getApplication()->bootComponent('com_joomleague');
$db = $container->get(DatabaseInterface::class);
$project = $db->setQuery('SELECT * FROM #__joomleague_project ORDER BY id', 0, 1)->loadObject();
if (!$project) throw new RuntimeException('The development fixture requires an existing project.');
$originalId = (int) $project->id;
$originalVersionId = (int) $project->profile_version_id;
$version = $db->setQuery('SELECT * FROM #__joomleague_sport_profile_version WHERE id = ' . $originalVersionId)->loadObject();
$originalChecksum = $version->payload_checksum;
$fixtureId = null;
$targetId = null;

try {
    unset($project->id);
    $project->uuid = Joomleague\Component\Joomleague\Domain\Service\UuidFactory::v4();
    $project->name = 'Profile migration regression fixture';
    $project->alias = 'profile-migration-regression-' . bin2hex(random_bytes(5));
    $project->asset_id = 0;
    $project->checked_out = 0;
    $project->checked_out_time = null;
    $db->insertObject('#__joomleague_project', $project);
    $fixtureId = (int) $db->insertid();
    unset($version->id);
    $payload = json_decode($version->payload_json, true, 512, JSON_THROW_ON_ERROR);
    $payload['version'] = '999.0.0';
    $version->profile_version = $payload['version'];
    $version->payload_json = json_encode($payload, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
    $version->payload_checksum = hash('sha256', $version->payload_json);
    $version->state = 'active';
    $db->insertObject('#__joomleague_sport_profile_version', $version);
    $targetId = (int) $db->insertid();
    $actor = (int) $db->setQuery('SELECT id FROM #__users ORDER BY id', 0, 1)->loadResult();
    $model = Factory::getApplication()->bootComponent('com_joomleague')->getMVCFactory()->createModel('Project', 'Administrator', ['ignore_request' => true]);
    $model->setCurrentUser($container->get(Joomla\CMS\User\UserFactoryInterface::class)->loadUserById($actor));
    $saveData = (array) $db->setQuery('SELECT * FROM #__joomleague_project WHERE id = ' . $fixtureId)->loadObject();
    $saveData['profile_version_id'] = $targetId;
    if (!$model->save($saveData)) throw new RuntimeException('Ordinary project save failed: ' . $model->getError());
    $savedVersion = $db->setQuery('SELECT profile_version_id FROM #__joomleague_project WHERE id = ' . $fixtureId)->loadResult();
    if ((int) $savedVersion !== $originalVersionId) throw new RuntimeException('Ordinary save silently upgraded the project profile.');
    $service = new ProjectProfileMigrationService($db);
    $review = $service->inspect($fixtureId, $targetId);
    if ($review['blockers'] !== []) throw new RuntimeException('Metadata migration blocked: ' . implode(', ', $review['blockers']));
    $savedPayload = $version->payload_json;
    $savedHash = $version->payload_checksum;
    $payload['code'] = 'different_sport';
    $version->id = $targetId;
    $version->payload_json = json_encode($payload, JSON_THROW_ON_ERROR);
    $version->payload_checksum = hash('sha256', $version->payload_json);
    $db->updateObject('#__joomleague_sport_profile_version', $version, 'id');
    if ($service->inspect($fixtureId, $targetId)['blockers'] === []) throw new RuntimeException('Semantic changes must be blocked.');
    $version->payload_json = $savedPayload;
    $version->payload_checksum = $savedHash;
    $db->updateObject('#__joomleague_sport_profile_version', $version, 'id');
    try {
        $service->migrate($fixtureId, $targetId, str_repeat('0', 64), $actor);
        throw new LogicException('Stale review was accepted.');
    } catch (RuntimeException $expected) {
        if ($expected instanceof LogicException) throw $expected;
    }
    if (in_array('--browser', $argv, true)) {
        echo "BROWSER_FIXTURE_ID=$fixtureId\n";
        flush();
        $completed = false;
        for ($attempt = 0; $attempt < 180; $attempt++) {
            sleep(1);
            $bound = $db->setQuery('SELECT profile_version_id FROM #__joomleague_project WHERE id = ' . $fixtureId)->loadResult();
            if ((int) $bound === $targetId) { $completed = true; break; }
        }
        if (!$completed) throw new RuntimeException('Browser migration did not finish within three minutes.');
    } else {
        $service->migrate($fixtureId, $targetId, $review['checksum'], $actor);
    }
    $after = $service->inspect($fixtureId);
    if ((int) $after['current']['profile_version_id'] !== $targetId || count($after['history']) !== 1) throw new RuntimeException('Migration or audit did not persist.');
    $old = $db->setQuery('SELECT profile_version_id FROM #__joomleague_project WHERE id = ' . $originalId)->loadResult();
    $hash = $db->setQuery('SELECT payload_checksum FROM #__joomleague_sport_profile_version WHERE id = ' . $originalVersionId)->loadResult();
    if ((int) $old !== $originalVersionId || $hash !== $originalChecksum) throw new RuntimeException('Historical project or profile was changed.');
    echo "Project profile migration, stale review rejection, audit and historical pin preservation OK\n";
    if (in_array('--browser', $argv, true)) sleep(10);
} finally {
    if ($fixtureId !== null) {
        $table = Factory::getApplication()->bootComponent('com_joomleague')->getMVCFactory()->createTable('Project', 'Administrator');
        if (!$table->delete($fixtureId)) throw new RuntimeException('Fixture cleanup failed: ' . $table->getError());
    }
    if ($targetId !== null) $db->setQuery('DELETE FROM #__joomleague_sport_profile_version WHERE id = ' . $targetId)->execute();
}
