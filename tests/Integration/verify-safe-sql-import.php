<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') exit(1);
define('_JEXEC', 1);
define('JPATH_BASE', '/var/www/html');
require JPATH_BASE . '/includes/defines.php';
require JPATH_BASE . '/includes/framework.php';
$root = getenv('JOOMLEAGUE_AUDIT_SQL_DIR');
if (!$root || !str_starts_with($root, '/tmp/')) throw new RuntimeException('Source candidate required.');
foreach (['ComponentTableCatalog', 'SqlInsertDataParser', 'SqlDataExchangeService'] as $name) require $root . '/' . $name . '.php';
$db = Joomla\CMS\Factory::getContainer()->get(Joomla\Database\DatabaseInterface::class);
$isolated = getenv('JOOMLEAGUE_AUDIT_PG_HOST');
if ($isolated) {
    if ($isolated !== 'jl-safe-import-pg-20261011') throw new RuntimeException('Disposable PG host required.');
    $db = (new Joomla\Database\DatabaseFactory())->getDriver('pgsql', ['host' => $isolated, 'user' => 'postgres', 'password' => getenv('JOOMLEAGUE_AUDIT_PG_PASSWORD'), 'database' => 'import_audit', 'prefix' => 'test_']);
}
$create = $isolated ? 'CREATE TABLE ' : 'CREATE TEMPORARY TABLE ';
$db->setQuery($create . $db->quoteName('#__joomleague_club') . ' (id INTEGER PRIMARY KEY, name TEXT)')->execute();
$db->setQuery($create . $db->quoteName('#__joomleague_standing_freshness') . ' (id INTEGER PRIMARY KEY)')->execute();
try {
    $service = new Joomleague\Component\Joomleague\Administrator\Service\SqlDataExchangeService($db, JPATH_ADMINISTRATOR . '/components/com_joomleague');
    $literal = "Quotes ' and backslash \\ and SELECT password FROM #__users";
    $result = $service->import('INSERT INTO `#__joomleague_club` (`id`,`name`) VALUES (90001,' . $db->quote($literal) . ');');
    if ($result['executed'] !== 1 || $db->setQuery('SELECT name FROM #__joomleague_club WHERE id=90001')->loadResult() !== $literal) throw new RuntimeException('Bound literal import failed.');
    echo 'PASS: native ' . $db->getName() . " import preserves quoted SQL-looking literal data\n";
    foreach ([
        'INSERT INTO `#__joomleague_club` (`id`,`name`) SELECT 90002,password FROM `#__users`',
        'INSERT INTO `#__joomleague_club` (`id`,`name`) VALUES (90002,SLEEP(1))',
        'INSERT INTO `#__joomleague_not_a_real_table` (`id`) VALUES (1)',
        'CREATE TABLE IF NOT EXISTS `#__joomleague_club` (`id` INT) AS SELECT * FROM `#__users`',
        'INSERT INTO `#__joomleague_club` (`id`,`unknown_column`) VALUES (90002,1)',
    ] as $attack) {
        try { $service->import($attack); throw new LogicException('Unsafe import accepted.'); }
        catch (RuntimeException $e) { if ($e->getMessage() !== 'COM_JOOMLEAGUE_DATAIMPORT_ERROR_STATEMENT') throw $e; }
    }
    if ((int) $db->setQuery('SELECT COUNT(*) FROM #__joomleague_club')->loadResult() !== 1) throw new RuntimeException('Rejected import changed data.');
    $duplicate = $service->import('INSERT INTO `#__joomleague_club` (`id`,`name`) VALUES (90001,\'replacement\');');
    if ($duplicate['skipped'] !== 1 || $db->setQuery('SELECT name FROM #__joomleague_club WHERE id=90001')->loadResult() !== $literal) throw new RuntimeException('Duplicate handling changed existing data.');
    echo "PASS: unsafe statements rejected and duplicate handling preserves original data\n";
    $export = $service->export(['#__joomleague_club']);
    if ($isolated) $export = implode(';', array_map(static fn ($statement) => rtrim(trim($statement), ';'), array_filter($db->splitSql($export), static fn ($statement) => preg_match('/^INSERT\s/i', trim($statement)) === 1))) . ';';
    $service->import($export);
    if ($db->setQuery('SELECT name FROM #__joomleague_club WHERE id=90001')->loadResult() !== $literal) throw new RuntimeException('Export/import round trip failed.');
    echo 'PASS: native export/import round trip ' . ($isolated ? '(exported data statements)' : '(with trusted schema)') . "\n";
} finally {
    $drop = $isolated ? 'DROP TABLE ' : 'DROP TEMPORARY TABLE ';
    $db->setQuery($drop . $db->quoteName('#__joomleague_club'))->execute();
    $db->setQuery($drop . $db->quoteName('#__joomleague_standing_freshness'))->execute();
}
