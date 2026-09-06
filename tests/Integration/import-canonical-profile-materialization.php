<?php

declare(strict_types=1);

define('_JEXEC', 1);
define('JPATH_BASE', '/var/www/html');
require_once JPATH_BASE . '/includes/defines.php';
require_once JPATH_BASE . '/includes/framework.php';
require_once JPATH_ADMINISTRATOR . '/components/com_joomleague/src/Service/ComponentTableCatalog.php';
require_once JPATH_ADMINISTRATOR . '/components/com_joomleague/src/Service/CanonicalJson.php';
require_once JPATH_ADMINISTRATOR . '/components/com_joomleague/src/Service/UuidFactory.php';
require_once JPATH_ADMINISTRATOR . '/components/com_joomleague/src/Service/SportTypeProfileMaterializer.php';
require_once JPATH_ADMINISTRATOR . '/components/com_joomleague/src/Service/SqlDataExchangeService.php';

use Joomla\CMS\Factory;
use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;
use Joomleague\Component\Joomleague\Administrator\Service\SqlDataExchangeService;

$container = Factory::getContainer();
$container->alias('session', 'session.cli')
	->alias('JSession', 'session.cli')
	->alias(Joomla\CMS\Session\Session::class, 'session.cli')
	->alias(Joomla\Session\Session::class, 'session.cli')
	->alias(Joomla\Session\SessionInterface::class, 'session.cli');
Factory::$application = $container->get(Joomla\Console\Application::class);

$database = $container->get(DatabaseInterface::class);
$sportTypeId = 9000001;
$migrationBatchId = 9000001;
$sql = <<<'SQL'
-- Canonical JoomLeague 6.2 migration package
INSERT INTO `#__joomleague_sport_type` (`id`,`profile_version_id`,`code`,`name`,`ordering`) VALUES ('9000001',{{profile_version:football}},'canonical_import_test','Canonical import test','0');
INSERT INTO `#__joomleague_migration_batch` (`id`,`batch_uuid`,`source_product`,`source_version`,`source_fingerprint`,`state`,`total_records`,`processed_records`,`imported_records`,`skipped_records`,`failed_records`) VALUES ('9000001','00000000-0000-4000-8000-000090000001','integration-test','1.0','aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa','completed','2','2','2','0','0');
INSERT INTO `#__joomleague_migration_record` (`id`,`batch_id`,`source_table`,`source_identity_json`,`source_identity_hash`,`source_payload_json`,`source_payload_checksum`,`target_entity`,`target_identity`,`outcome`,`message_code`) VALUES ('9000001','9000001','test','{"id":1}','bbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbb','{}','cccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccc','test','1','imported','batch-test'),('9000002','9000001','test','{"id":2}','dddddddddddddddddddddddddddddddddddddddddddddddddddddddddddddddd','{}','eeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeee','test','2','imported','batch-test');
SQL;

try {
	(new SqlDataExchangeService($database, JPATH_ADMINISTRATOR . '/components/com_joomleague'))->import($sql);
	$counts = [];

	foreach (['positions' => '#__joomleague_sport_position', 'events' => '#__joomleague_event_type', 'statistics' => '#__joomleague_statistic'] as $key => $table) {
		$query = $database->getQuery(true)
			->select('COUNT(*)')
			->from($database->quoteName($table))
			->where($database->quoteName('sport_type_id') . ' = :sportTypeId')
			->bind(':sportTypeId', $sportTypeId, ParameterType::INTEGER);
		$counts[$key] = (int) $database->setQuery($query)->loadResult();

		if ($counts[$key] < 1) {
			throw new RuntimeException('Canonical import did not materialize ' . $key . '.');
		}
	}
	$query = $database->getQuery(true)
		->select('COUNT(*)')
		->from($database->quoteName('#__joomleague_migration_record'))
		->where($database->quoteName('batch_id') . ' = :batchId')
		->bind(':batchId', $migrationBatchId, ParameterType::INTEGER);
	$counts['batched_records'] = (int) $database->setQuery($query)->loadResult();
	if ($counts['batched_records'] !== 2) {
		throw new RuntimeException('Canonical importer did not preserve the multi-row INSERT.');
	}

	if ($database->getName() === 'pgsql') {
		$table = $database->replacePrefix('#__joomleague_sport_type');
		$sequence = (string) $database->setQuery(
			'SELECT pg_get_serial_sequence(' . $database->quote($table) . ', ' . $database->quote('id') . ')'
		)->loadResult();
		$nextId = (int) $database->setQuery('SELECT nextval(' . $database->quote($sequence) . ')')->loadResult();

		if ($nextId <= $sportTypeId) {
			throw new RuntimeException('Canonical import did not synchronize PostgreSQL identity sequences.');
		}
	}

	echo json_encode(['driver' => $database->getName(), 'catalogs' => $counts], JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT) . PHP_EOL;
} finally {
	$query = $database->getQuery(true)
		->delete($database->quoteName('#__joomleague_migration_batch'))
		->where($database->quoteName('id') . ' = :batchId')
		->bind(':batchId', $migrationBatchId, ParameterType::INTEGER);
	$database->setQuery($query)->execute();

	$query = $database->getQuery(true)
		->delete($database->quoteName('#__joomleague_sport_type'))
		->where($database->quoteName('id') . ' = :sportTypeId')
		->bind(':sportTypeId', $sportTypeId, ParameterType::INTEGER);
	$database->setQuery($query)->execute();

	if ($database->getName() === 'pgsql') {
		$table = $database->replacePrefix('#__joomleague_sport_type');
		$sequence = (string) $database->setQuery(
			'SELECT pg_get_serial_sequence(' . $database->quote($table) . ', ' . $database->quote('id') . ')'
		)->loadResult();
		$maximum = (int) $database->setQuery(
			'SELECT COALESCE(MAX(' . $database->quoteName('id') . '), 0) FROM ' . $database->quoteName('#__joomleague_sport_type')
		)->loadResult();
		$database->setQuery(
			'SELECT setval(' . $database->quote($sequence) . ', ' . max(1, $maximum) . ', ' . ($maximum > 0 ? 'TRUE' : 'FALSE') . ')'
		)->execute();
	}
}
