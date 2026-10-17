<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli' || getenv('JOOMLEAGUE_AUDIT_PG_HOST') !== 'jl-atomicity-pg-20261011') exit(1);
define('_JEXEC', 1);
define('JPATH_BASE', '/var/www/html');
require JPATH_BASE . '/includes/defines.php';
require JPATH_BASE . '/includes/framework.php';
$db = (new Joomla\Database\DatabaseFactory())->getDriver('pgsql', [
    'host' => getenv('JOOMLEAGUE_AUDIT_PG_HOST'), 'user' => 'postgres',
    'password' => getenv('JOOMLEAGUE_AUDIT_PG_PASSWORD'), 'database' => 'atomicity_audit', 'prefix' => '',
]);
$db->setQuery('CREATE TEMPORARY TABLE audit_transaction (id INTEGER PRIMARY KEY, value INTEGER)')->execute();
$db->setQuery('INSERT INTO audit_transaction VALUES (1, 0)')->execute();
$value = static fn (): int => (int) $db->setQuery('SELECT value FROM audit_transaction WHERE id=1')->loadResult();
$db->transactionStart();
try {
    $db->transactionStart(true);
    $db->setQuery('UPDATE audit_transaction SET value=1')->execute();
    $db->transactionStart(true);
    $db->setQuery('UPDATE audit_transaction SET value=2')->execute();
    $db->transactionCommit(true);
    $db->transactionCommit(true);
    if ($value() !== 2) throw new RuntimeException('Nested commit lost writes.');
    $db->transactionStart(true);
    $db->setQuery('UPDATE audit_transaction SET value=3')->execute();
    $db->transactionStart(true);
    $db->setQuery('UPDATE audit_transaction SET value=4')->execute();
    $db->transactionRollback(true);
    $db->transactionRollback(true);
    if ($value() !== 2) throw new RuntimeException('Nested rollback changed previous successful save.');
} finally { $db->transactionRollback(); }
if ($value() !== 0) throw new RuntimeException('Nested operation committed caller transaction.');
echo "PASS: native PostgreSQL driver preserves outer transaction across nested commits and rollbacks\n";
