<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli' || getenv('JL_AUDIT_PG_HOST') !== 'jl-rc-audit-pg-20261010') exit(1);
define('_JEXEC', 1);
define('JPATH_BASE', '/var/www/html');
require JPATH_BASE . '/includes/defines.php';
require JPATH_BASE . '/includes/framework.php';
$container = Joomla\CMS\Factory::getContainer();
$container->alias('session', 'session.cli')->alias(Joomla\Session\SessionInterface::class, 'session.cli');
Joomla\CMS\Factory::$application = $container->get(Joomla\Console\Application::class);
$db = (new Joomla\Database\DatabaseFactory())->getDriver('pgsql', [
    'host' => getenv('JL_AUDIT_PG_HOST'), 'user' => 'postgres', 'password' => getenv('JL_TEST_PG_PASSWORD'),
    'database' => 'encounter', 'prefix' => 'test_',
]);
$db->setQuery("SET statement_timeout = '5s'")->execute();
$db->setQuery('CREATE TABLE audit_external_data (marker TEXT NOT NULL)')->execute();
$db->setQuery("INSERT INTO audit_external_data (marker) VALUES ('synthetic audit marker')")->execute();
$uuid = Joomleague\Component\Joomleague\Domain\Service\UuidFactory::v4();
$service = new Joomleague\Component\Joomleague\Administrator\Service\SqlDataExchangeService($db, JPATH_ADMINISTRATOR . '/components/com_joomleague');
$sql = 'INSERT INTO "#__joomleague_club" ("uuid", "name") SELECT ' . $db->quote($uuid) . ', marker FROM audit_external_data WHERE true;';
try {
    $result = $service->import($sql);
    $copied = $db->setQuery('SELECT name FROM #__joomleague_club WHERE uuid=' . $db->quote($uuid))->loadResult();
    if ($copied !== 'synthetic audit marker') throw new RuntimeException('Expected synthetic marker was not copied.');
    echo json_encode(['mode' => 'disposable PostgreSQL only; synthetic non-component source table', 'crossTableReadExecuted' => true, 'importExecutedStatements' => $result['executed']], JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR) . "\n";
} finally {
    $db->setQuery('DELETE FROM #__joomleague_club WHERE uuid=' . $db->quote($uuid))->execute();
    $db->setQuery('DROP TABLE audit_external_data')->execute();
}
