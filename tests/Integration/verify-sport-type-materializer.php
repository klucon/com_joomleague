<?php

declare(strict_types=1);

define('_JEXEC', 1);
define('JPATH_BASE', '/var/www/html');
require_once JPATH_BASE . '/includes/defines.php';
require_once JPATH_BASE . '/includes/framework.php';

use Joomla\CMS\Factory;
use Joomla\Database\DatabaseInterface;
use Joomleague\Component\Joomleague\Administrator\Service\SportTypeProfileMaterializer;

$container = Factory::getContainer();
$container->alias('session', 'session.cli')
	->alias('JSession', 'session.cli')
	->alias(Joomla\CMS\Session\Session::class, 'session.cli')
	->alias(Joomla\Session\Session::class, 'session.cli')
	->alias(Joomla\Session\SessionInterface::class, 'session.cli');
Factory::$application = $container->get(Joomla\Console\Application::class);

foreach (['CanonicalJson', 'UuidFactory', 'RoleDomain', 'SportTypeProfileMaterializer'] as $service) {
	require_once JPATH_ADMINISTRATOR . '/components/com_joomleague/src/Service/' . $service . '.php';
}

$database = $container->get(DatabaseInterface::class);
$query = $database->getQuery(true)
	->select('version.id')
	->from($database->quoteName('#__joomleague_sport_profile_version', 'version'))
	->innerJoin($database->quoteName('#__joomleague_sport_profile', 'profile') . ' ON profile.id = version.profile_id')
	->where('profile.code = ' . $database->quote('basketball'))
	->where('version.state = ' . $database->quote('active'))
	->order('version.id DESC');
$profileVersionId = (int) $database->setQuery($query, 0, 1)->loadResult();
if ($profileVersionId < 1) throw new RuntimeException('Basketball profile is unavailable.');

$database->transactionStart();
try {
	if (isset($argv[1])) {
		$payload = file_get_contents($argv[1]);
		$profile = json_decode($payload, true, 512, JSON_THROW_ON_ERROR);
		$profileId = (int) $database->setQuery('SELECT profile_id FROM #__joomleague_sport_profile_version WHERE id = ' . $profileVersionId)->loadResult();
		$version = (object) ['profile_id' => $profileId, 'schema_version' => $profile['schema_version'], 'profile_version' => $profile['version'], 'payload_json' => $payload, 'payload_checksum' => hash('sha256', $payload), 'source' => 'bundled', 'state' => 'superseded'];
		$database->insertObject('#__joomleague_sport_profile_version', $version, 'id');
		$profileVersionId = (int) $version->id;
	} else {
		$profile = json_decode((string) $database->setQuery('SELECT payload_json FROM #__joomleague_sport_profile_version WHERE id = ' . $profileVersionId)->loadResult(), true, 512, JSON_THROW_ON_ERROR);
	}
	$expectedPositions = count($profile['positions']);
	$expectedEvents = count($profile['event_types']);
	$expectedStatistics = count($profile['statistics']);
	$row = (object) ['profile_version_id' => $profileVersionId, 'code' => 'materializer_' . bin2hex(random_bytes(4)), 'name' => 'Materializer test'];
	$database->insertObject('#__joomleague_sport_type', $row, 'id');
	$sportTypeId = (int) $row->id;
	$counts = (new SportTypeProfileMaterializer($database))->materialize($sportTypeId, $profileVersionId, [
		'positions' => true,
		'event_types' => false,
		'statistics' => true,
	], 0);

	if ($counts !== ['positions' => $expectedPositions, 'event_types' => 0, 'statistics' => $expectedStatistics]) {
		throw new RuntimeException('Unexpected materialization counts: ' . json_encode($counts));
	}
	$repeated = (new SportTypeProfileMaterializer($database))->materialize($sportTypeId, $profileVersionId, [
		'positions' => true,
		'event_types' => false,
		'statistics' => true,
	], 0);
	if ($repeated !== ['positions' => 0, 'event_types' => 0, 'statistics' => 0]) {
		throw new RuntimeException('Repeated materialization is not idempotent: ' . json_encode($repeated));
	}

	$events = (new SportTypeProfileMaterializer($database))->materialize($sportTypeId, $profileVersionId, ['positions' => false, 'event_types' => true, 'statistics' => false], 0);
	if ($events !== ['positions' => 0, 'event_types' => $expectedEvents, 'statistics' => 0]) throw new RuntimeException('Event definitions were not materialized independently.');
	foreach (['sport_position' => $expectedPositions, 'event_type' => $expectedEvents, 'statistic' => $expectedStatistics] as $table => $expected) {
		$query = $database->getQuery(true)->select('COUNT(*)')->from($database->quoteName('#__joomleague_' . $table))->where('sport_type_id = :sportTypeId')->bind(':sportTypeId', $sportTypeId, Joomla\Database\ParameterType::INTEGER);
		if ((int) $database->setQuery($query)->loadResult() !== $expected) throw new RuntimeException('Selective materialization failed for ' . $table . '.');
	}
	$query = $database->getQuery(true)->select(['code', 'scope', 'value_type', 'calculation_source', 'metadata_json'])->from($database->quoteName('#__joomleague_statistic'))->where('sport_type_id = ' . (int) $sportTypeId);
	$storedStatistics = $database->setQuery($query)->loadAssocList('code');
	foreach ($profile['statistics'] as $definition) {
		$stored = $storedStatistics[$definition['code']];
		if ($stored['scope'] !== $definition['scope'] || $stored['value_type'] !== ($definition['value_type'] ?? 'integer') || $stored['calculation_source'] !== $definition['source'] || Joomleague\Component\Joomleague\Domain\Service\CanonicalJson::checksum(json_decode($stored['metadata_json'], true)) !== Joomleague\Component\Joomleague\Domain\Service\CanonicalJson::checksum($definition)) throw new RuntimeException('Stored statistic metadata differs from the profile: ' . $definition['code']);
	}

	printf("Sport-type materializer OK on %s: %d positions, %d events, %d statistics; switches, metadata and idempotence verified\n", $database->getName(), $expectedPositions, $expectedEvents, $expectedStatistics);
} finally {
	$database->transactionRollback();
}
