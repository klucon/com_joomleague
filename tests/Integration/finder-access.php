<?php

declare(strict_types=1);

define('_JEXEC', 1);
define('JPATH_BASE', '/var/www/html');
$_SERVER['HTTP_HOST'] ??= 'localhost';
$_SERVER['REQUEST_URI'] ??= '/';
$_SERVER['SCRIPT_NAME'] = '/index.php';
require_once JPATH_BASE . '/includes/defines.php';
require_once JPATH_BASE . '/includes/framework.php';
JLoader::registerNamespace('Joomla\\Component\\Finder\\Administrator', JPATH_ADMINISTRATOR . '/components/com_finder/src');
require_once JPATH_PLUGINS . '/finder/joomleague/src/Extension/Joomleague.php';

use Joomla\CMS\Factory;
use Joomla\Database\DatabaseInterface;
use Joomleague\Plugin\Finder\Joomleague\Extension\Joomleague;

$container = Factory::getContainer();
$container->alias('session', 'session.cli')
	->alias('JSession', 'session.cli')
	->alias(Joomla\CMS\Session\Session::class, 'session.cli')
	->alias(Joomla\Session\Session::class, 'session.cli')
	->alias(Joomla\Session\SessionInterface::class, 'session.cli');
Factory::$application = $container->get(Joomla\Console\Application::class);
$database = $container->get(DatabaseInterface::class);
$database->transactionStart();

try {
	$insert = static function (string $table, array $values) use ($database): int {
		$query = $database->getQuery(true)->insert($database->quoteName($table))->columns($database->quoteName(array_keys($values)));
		$parameters = [];
		foreach ($values as $key => &$value) {
			$parameters[] = ':' . $key;
			$query->bind(':' . $key, $value);
		}
		$query->values(implode(',', $parameters));
		$database->setQuery($query)->execute();

		return (int) $database->insertid();
	};
	$uuid = static fn (): string => sprintf(
		'%s-%s-4%s-%s%s-%s',
		bin2hex(random_bytes(4)),
		bin2hex(random_bytes(2)),
		substr(bin2hex(random_bytes(2)), 1),
		dechex(random_int(8, 11)),
		substr(bin2hex(random_bytes(2)), 1),
		bin2hex(random_bytes(6))
	);
	$profileVersionId = (int) $database->setQuery(
		$database->getQuery(true)->select('id')->from($database->quoteName('#__joomleague_sport_profile_version'))->order('id ASC'),
		0,
		1
	)->loadResult();
	if ($profileVersionId < 1) {
		throw new RuntimeException('Finder access fixture requires a bundled profile.');
	}
	$suffix = bin2hex(random_bytes(4));
	$sportTypeId = $insert('#__joomleague_sport_type', [
		'profile_version_id' => $profileVersionId,
		'code' => 'finder-' . $suffix,
		'name' => 'Finder access fixture',
		'published' => 1,
	]);
	$matchingCompetition = $insert('#__joomleague_competition', ['uuid' => $uuid(), 'name' => 'Matching competition', 'access' => 91, 'published' => 1]);
	$matchingSeason = $insert('#__joomleague_season', ['uuid' => $uuid(), 'name' => 'Matching season', 'access' => 91, 'published' => 1]);
	$mixedCompetition = $insert('#__joomleague_competition', ['uuid' => $uuid(), 'name' => 'Mixed competition', 'access' => 91, 'published' => 1]);
	$mixedSeason = $insert('#__joomleague_season', ['uuid' => $uuid(), 'name' => 'Mixed season', 'access' => 92, 'published' => 1]);
	$matchingProject = $insert('#__joomleague_project', [
		'uuid' => $uuid(), 'competition_id' => $matchingCompetition, 'season_id' => $matchingSeason,
		'sport_type_id' => $sportTypeId, 'profile_version_id' => $profileVersionId,
		'name' => 'Matching access project', 'project_type' => 'league', 'access' => 91, 'published' => 1,
	]);
	$mixedProject = $insert('#__joomleague_project', [
		'uuid' => $uuid(), 'competition_id' => $mixedCompetition, 'season_id' => $mixedSeason,
		'sport_type_id' => $sportTypeId, 'profile_version_id' => $profileVersionId,
		'name' => 'Mixed access project', 'project_type' => 'league', 'access' => 91, 'published' => 1,
	]);

	$reflection = new ReflectionClass(Joomleague::class);
	$plugin = $reflection->newInstanceWithoutConstructor();
	$dbProperty = $reflection->getProperty('db');
	$dbProperty->setValue($plugin, $database);
	$queryMethod = $reflection->getMethod('queryFor');
	$query = $queryMethod->invoke($plugin, 'project');
	$query->where('project.id IN (' . $matchingProject . ',' . $mixedProject . ')');
	$rows = $database->setQuery($query)->loadObjectList();

	if (count($rows) !== 1 || (int) $rows[0]->entity_id !== $matchingProject || (int) $rows[0]->access !== 91) {
		throw new RuntimeException('Finder did not enforce a representable common parent access level.');
	}

	echo sprintf("Finder access contract OK on %s\n", $database->getServerType());
} finally {
	$database->transactionRollback();
}
