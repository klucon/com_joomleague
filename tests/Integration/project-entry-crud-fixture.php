<?php

declare(strict_types=1);

define('_JEXEC', 1);
define('JPATH_BASE', '/var/www/html');
require_once JPATH_BASE . '/includes/defines.php';
require_once JPATH_BASE . '/includes/framework.php';

use Joomla\CMS\Factory;
use Joomla\Database\DatabaseInterface;

$container = Factory::getContainer();
$container->alias('session', 'session.cli')
	->alias('JSession', 'session.cli')
	->alias(Joomla\CMS\Session\Session::class, 'session.cli')
	->alias(Joomla\Session\Session::class, 'session.cli')
	->alias(Joomla\Session\SessionInterface::class, 'session.cli');
Factory::$application = $container->get(Joomla\Console\Application::class);
$database = $container->get(DatabaseInterface::class);
$action = $argv[1] ?? '';
$suffix = preg_replace('/[^a-z0-9]/', '', strtolower($argv[2] ?? ''));

if ($suffix === '' || !in_array($action, ['setup', 'cleanup'], true)) {
	throw new InvalidArgumentException('Usage: project-entry-crud-fixture.php setup|cleanup suffix');
}

$name = 'Entry UI ' . $suffix;

if ($action === 'cleanup') {
	foreach ([
		['#__joomleague_project', 'name'], ['#__joomleague_team', 'name'], ['#__joomleague_person', 'last_name'],
		['#__joomleague_sport_type', 'name'], ['#__joomleague_season', 'name'], ['#__joomleague_competition', 'name'],
	] as [$table, $column]) {
		$query = $database->getQuery(true)
			->delete($database->quoteName($table))
			->where($database->quoteName($column) . ' = :name')
			->bind(':name', $name);
		$database->setQuery($query)->execute();
	}

	echo "cleanup ok\n";
	return;
}

$query = $database->getQuery(true)
	->select($database->quoteName('version.id'))
	->from($database->quoteName('#__joomleague_sport_profile_version', 'version'))
	->innerJoin($database->quoteName('#__joomleague_sport_profile', 'profile') . ' ON profile.id = version.profile_id')
	->where($database->quoteName('profile.code') . ' = ' . $database->quote('football'))
	->order($database->quoteName('version.id') . ' DESC');
$profileVersionId = (int) $database->setQuery($query, 0, 1)->loadResult();

if ($profileVersionId < 1) {
	throw new RuntimeException('Football profile is unavailable.');
}

$uuid = static fn (): string => sprintf(
	'%s-%s-4%s-%s%s-%s',
	bin2hex(random_bytes(4)),
	bin2hex(random_bytes(2)),
	substr(bin2hex(random_bytes(2)), 1),
	dechex(random_int(8, 11)),
	substr(bin2hex(random_bytes(2)), 1),
	bin2hex(random_bytes(6))
);
$insert = static function (DatabaseInterface $database, string $table, array $values): int {
	$query = $database->getQuery(true)->insert($database->quoteName($table))->columns($database->quoteName(array_keys($values)));
	$placeholders = [];

	foreach ($values as $key => &$value) {
		$placeholders[] = ':' . $key;
		$query->bind(':' . $key, $value);
	}

	$query->values(implode(', ', $placeholders));
	$database->setQuery($query)->execute();

	return (int) $database->insertid();
};

$competitionId = $insert($database, '#__joomleague_competition', ['uuid' => $uuid(), 'name' => $name]);
$seasonId = $insert($database, '#__joomleague_season', ['uuid' => $uuid(), 'name' => $name]);
$sportTypeId = $insert($database, '#__joomleague_sport_type', ['profile_version_id' => $profileVersionId, 'code' => 'entry-ui-' . $suffix, 'name' => $name]);
$projectId = $insert($database, '#__joomleague_project', [
	'uuid' => $uuid(),
	'competition_id' => $competitionId,
	'season_id' => $seasonId,
	'sport_type_id' => $sportTypeId,
	'profile_version_id' => $profileVersionId,
	'name' => $name,
	'project_type' => 'league',
]);
$teamId = $insert($database, '#__joomleague_team', ['uuid' => $uuid(), 'name' => $name]);
$personId = $insert($database, '#__joomleague_person', ['uuid' => $uuid(), 'first_name' => 'Member', 'last_name' => $name]);

echo json_encode(['project_id' => $projectId, 'team_id' => $teamId, 'person_id' => $personId, 'name' => $name, 'person_name' => $name . ', Member'], JSON_THROW_ON_ERROR) . "\n";
