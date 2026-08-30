<?php

declare(strict_types=1);

define('_JEXEC', 1);
define('JPATH_BASE', '/var/www/html');
$_SERVER['HTTP_HOST'] = 'localhost';
$_SERVER['SERVER_NAME'] = 'localhost';
$_SERVER['SERVER_PORT'] = '80';
$_SERVER['REQUEST_URI'] = '/';
$_SERVER['SCRIPT_NAME'] = '/index.php';
require_once JPATH_BASE . '/includes/defines.php';
require_once JPATH_BASE . '/includes/framework.php';

use Joomla\CMS\Factory;
use Joomla\Database\DatabaseInterface;

$db = Factory::getContainer()->get(DatabaseInterface::class);
$driver = str_contains(strtolower($db->getName()), 'pgsql') ? 'postgresql' : 'mysql';
$installFile = JPATH_ADMINISTRATOR . '/components/com_joomleague/sql/install.' . ($driver === 'mysql' ? 'mysql.utf8' : 'postgresql') . '.sql';
$schema = (string) file_get_contents($installFile);
$quote = $driver === 'mysql' ? '`' : '"';
$end = $driver === 'mysql' ? '\\) ENGINE=' : '\\);';
$types = 'BIGSERIAL|BIGINT|INTEGER|SMALLINT|NUMERIC|DECIMAL|VARCHAR|CHAR|TEXT|DATE|TIME|TIMESTAMP|JSON|BOOLEAN|TINYINT|INT|DATETIME|LONGTEXT';

preg_match_all('/CREATE TABLE IF NOT EXISTS ' . preg_quote($quote, '/') . '#__([a-z0-9_]+)' . preg_quote($quote, '/') . '\\s*\\((.*?)' . $end . '/si', $schema, $tableMatches, PREG_SET_ORDER);
$expectedTables = [];

foreach ($tableMatches as $tableMatch) {
	preg_match_all('/(?:^|,)\\s*' . preg_quote($quote, '/') . '([a-z][a-z0-9_]*)' . preg_quote($quote, '/') . '\\s+(?:' . $types . ')\\b/i', $tableMatch[2], $columnMatches);
	$expectedTables[$tableMatch[1]] = $columnMatches[1] ?? [];
}

foreach ($expectedTables as $table => $expectedColumns) {
	$physicalTable = $db->replacePrefix('#__' . $table);
	$actualColumns = array_keys($db->getTableColumns($physicalTable, false));
	$sortedExpectedColumns = $expectedColumns;
	$sortedActualColumns = $actualColumns;
	sort($sortedExpectedColumns);
	sort($sortedActualColumns);

	if ($sortedActualColumns !== $sortedExpectedColumns) {
		throw new RuntimeException(sprintf(
			'%s column contract differs: expected %s, found %s.',
			$table,
			implode(', ', $sortedExpectedColumns),
			implode(', ', $sortedActualColumns)
		));
	}
}

$updates = glob(JPATH_ADMINISTRATOR . '/components/com_joomleague/sql/updates/' . $driver . '/*.sql') ?: [];
sort($updates, SORT_STRING);
$expectedAnchor = pathinfo((string) end($updates), PATHINFO_FILENAME);
$query = $db->getQuery(true)
	->select($db->quoteName('schema.version_id'))
	->from($db->quoteName('#__schemas', 'schema'))
	->join('INNER', $db->quoteName('#__extensions', 'extension') . ' ON ' . $db->quoteName('extension.extension_id') . ' = ' . $db->quoteName('schema.extension_id'))
	->where($db->quoteName('extension.element') . ' = ' . $db->quote('com_joomleague'));
$actualAnchor = (string) $db->setQuery($query)->loadResult();

if ($actualAnchor !== $expectedAnchor) {
	throw new RuntimeException(sprintf('Schema anchor differs: expected %s, found %s.', $expectedAnchor, $actualAnchor));
}

printf("Database schema contract OK: %s, %d tables, anchor %s\n", $driver, count($expectedTables), $actualAnchor);
