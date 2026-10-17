<?php

declare(strict_types=1);

define('_JEXEC', 1);
define('JPATH_BASE', '/var/www/html');
require_once JPATH_BASE . '/includes/defines.php';
require_once JPATH_BASE . '/includes/framework.php';
$source = getenv('JOOMLEAGUE_AUDIT_SQL_DIR') ?: JPATH_ADMINISTRATOR . '/components/com_joomleague/src/Service';
foreach (['ComponentTableCatalog', 'SqlInsertDataParser', 'SqlDataExchangeService'] as $name) require_once $source . '/' . $name . '.php';

use Joomla\CMS\Factory;
use Joomla\Database\DatabaseInterface;
use Joomleague\Component\Joomleague\Administrator\Service\SqlDataExchangeService;

$container = Factory::getContainer();
$container->alias('session', 'session.cli')
	->alias('JSession', 'session.cli')
	->alias(Joomla\CMS\Session\Session::class, 'session.cli')
	->alias(Joomla\Session\Session::class, 'session.cli')
	->alias(Joomla\Session\SessionInterface::class, 'session.cli');
Factory::$application = $container->get(Joomla\Console\Application::class);
$database = $container->get(DatabaseInterface::class);
if (in_array('--isolated-pgsql', $argv ?? [], true)) {
	if (PHP_SAPI !== 'cli' || getenv('JL_AUDIT_PG_HOST') !== 'jl-rc-audit-pg-20261010') {
		throw new RuntimeException('Disposable PostgreSQL audit environment required.');
	}
	$database = (new Joomla\Database\DatabaseFactory())->getDriver('pgsql', [
		'host' => getenv('JL_AUDIT_PG_HOST'), 'user' => 'postgres', 'password' => getenv('JL_TEST_PG_PASSWORD'),
		'database' => 'encounter', 'prefix' => 'test_',
	]);
}
$service = new SqlDataExchangeService($database, JPATH_ADMINISTRATOR . '/components/com_joomleague');
$table = '#__joomleague_import_recovery_test';
$physicalTable = $database->replacePrefix($table);
$index = 'idx_jl_import_recovery_value';
$quotedTable = $database->getName() === 'pgsql' ? '"' . $table . '"' : '`' . $table . '`';
$quotedIndex = $database->getName() === 'pgsql' ? '"' . $index . '"' : '`' . $index . '`';
$quote = $database->getName() === 'pgsql' ? '"' : '`';
$integer = $database->getName() === 'pgsql' ? 'INTEGER' : 'INT';

$dropTestTable = static function () use ($database, $table): void {
	if (in_array($database->replacePrefix($table), $database->getTableList(), true)) {
		$database->setQuery('DROP TABLE ' . $database->quoteName($table))->execute();
	}
};

$failingImport = implode(";\n", [
	'CREATE TABLE IF NOT EXISTS ' . $quotedTable . ' (' . $quote . 'id' . $quote . ' ' . $integer . ' NOT NULL, ' . $quote . 'value' . $quote . ' ' . $integer . ' NOT NULL, PRIMARY KEY (' . $quote . 'id' . $quote . '))',
	'CREATE INDEX ' . ($database->getName() === 'pgsql' ? 'IF NOT EXISTS ' : '') . $quotedIndex . ' ON ' . $quotedTable . ' (' . $quote . 'value' . $quote . ')',
	'INSERT INTO ' . $quotedTable . ' (' . $quote . 'id' . $quote . ', ' . $quote . 'missing_column' . $quote . ') VALUES (1, 1)',
]) . ';';

if (in_array($physicalTable, $database->getTableList(), true)) {
	throw new RuntimeException('Refusing to overwrite a pre-existing recovery fixture table.');
}

try {
	$service->import($failingImport);
	throw new RuntimeException('The deliberately invalid import unexpectedly succeeded.');
} catch (RuntimeException $error) {
	if ($error->getMessage() !== 'COM_JOOMLEAGUE_DATAIMPORT_ERROR_STATEMENT') {
		throw $error;
	}
}

if (in_array($physicalTable, $database->getTableList(), true)) {
	throw new RuntimeException('A table created by the failed import was not recovered.');
}

$database->setQuery(
	'CREATE TABLE ' . $database->quoteName($table)
	. ' (' . $database->quoteName('id') . ' ' . $integer . ' NOT NULL, PRIMARY KEY (' . $database->quoteName('id') . '))'
)->execute();

try {
	$service->import(implode(";\n", [
		'CREATE TABLE IF NOT EXISTS ' . $quotedTable . ' (' . $quote . 'id' . $quote . ' ' . $integer . ' NOT NULL, PRIMARY KEY (' . $quote . 'id' . $quote . '))',
		'INSERT INTO ' . $quotedTable . ' (' . $quote . 'missing_column' . $quote . ') VALUES (1)',
	]) . ';');
	throw new RuntimeException('The invalid import into a pre-existing table unexpectedly succeeded.');
} catch (RuntimeException $error) {
	if ($error->getMessage() !== 'COM_JOOMLEAGUE_DATAIMPORT_ERROR_STATEMENT') {
		throw $error;
	}
}

if (!in_array($physicalTable, $database->getTableList(), true)) {
	throw new RuntimeException('Recovery removed a pre-existing table.');
}

$dropTestTable();
echo 'SQL import rejects custom DDL without creating or removing tables for ' . $database->getName() . ".\n";
