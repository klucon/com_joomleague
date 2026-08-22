<?php

declare(strict_types=1);

define('_JEXEC', 1);
define('JPATH_BASE', '/var/www/html');
require_once JPATH_BASE . '/includes/defines.php';
require_once JPATH_BASE . '/includes/framework.php';

use Joomla\CMS\Factory;
use Joomla\Database\DatabaseInterface;

$container = Factory::getContainer();
$container->alias('session', 'session.cli')->alias('JSession', 'session.cli')->alias(Joomla\CMS\Session\Session::class, 'session.cli')->alias(Joomla\Session\Session::class, 'session.cli')->alias(Joomla\Session\SessionInterface::class, 'session.cli');
Factory::$application = $container->get(Joomla\Console\Application::class);
$db = $container->get(DatabaseInterface::class);
$action = $argv[1] ?? '';
$suffix = preg_replace('/[^a-z0-9]/', '', strtolower($argv[2] ?? ''));
$name = 'Stage assignment ' . $suffix;

if ($suffix === '' || !in_array($action, ['setup', 'cleanup'], true)) throw new InvalidArgumentException('Usage: stage-entry-fixture.php setup|cleanup suffix');

if ($action === 'cleanup') {
	foreach ([['#__joomleague_project', 'name'], ['#__joomleague_team', 'name'], ['#__joomleague_person', 'last_name'], ['#__joomleague_sport_type', 'name'], ['#__joomleague_season', 'name'], ['#__joomleague_competition', 'name']] as [$table, $column]) {
		$query = $db->getQuery(true)->delete($db->quoteName($table))->where($db->quoteName($column) . ' = :name')->bind(':name', $name);
		$db->setQuery($query)->execute();
	}
	echo "cleanup ok\n";
	return;
}

$query = $db->getQuery(true)->select($db->quoteName('version.id'))->from($db->quoteName('#__joomleague_sport_profile_version', 'version'))->innerJoin($db->quoteName('#__joomleague_sport_profile', 'profile') . ' ON profile.id = version.profile_id')->where($db->quoteName('profile.code') . ' = ' . $db->quote('football'))->order($db->quoteName('version.id') . ' DESC');
$profileVersionId = (int) $db->setQuery($query, 0, 1)->loadResult();
if ($profileVersionId < 1) throw new RuntimeException('Football profile is unavailable.');

$uuid = static fn (): string => sprintf('%s-%s-4%s-%s%s-%s', bin2hex(random_bytes(4)), bin2hex(random_bytes(2)), substr(bin2hex(random_bytes(2)), 1), dechex(random_int(8, 11)), substr(bin2hex(random_bytes(2)), 1), bin2hex(random_bytes(6)));
$insert = static function (DatabaseInterface $db, string $table, array $values): int {
	$query = $db->getQuery(true)->insert($db->quoteName($table))->columns($db->quoteName(array_keys($values)));
	$holders = [];
	foreach ($values as $key => &$value) { $holders[] = ':' . $key; $query->bind(':' . $key, $value); }
	$query->values(implode(', ', $holders));
	$db->setQuery($query)->execute();
	return (int) $db->insertid();
};

$competitionId = $insert($db, '#__joomleague_competition', ['uuid' => $uuid(), 'name' => $name]);
$seasonId = $insert($db, '#__joomleague_season', ['uuid' => $uuid(), 'name' => $name]);
$sportTypeId = $insert($db, '#__joomleague_sport_type', ['profile_version_id' => $profileVersionId, 'code' => 'stage-assignment-' . $suffix, 'name' => $name]);
$projectId = $insert($db, '#__joomleague_project', ['uuid' => $uuid(), 'competition_id' => $competitionId, 'season_id' => $seasonId, 'sport_type_id' => $sportTypeId, 'profile_version_id' => $profileVersionId, 'name' => $name, 'project_type' => 'league']);
$teamId = $insert($db, '#__joomleague_team', ['uuid' => $uuid(), 'name' => $name]);
$personId = $insert($db, '#__joomleague_person', ['uuid' => $uuid(), 'first_name' => 'Individual', 'last_name' => $name]);
$insert($db, '#__joomleague_project_entry', ['uuid' => $uuid(), 'project_id' => $projectId, 'entry_kind' => 'team', 'team_id' => $teamId]);
$insert($db, '#__joomleague_project_entry', ['uuid' => $uuid(), 'project_id' => $projectId, 'entry_kind' => 'person', 'person_id' => $personId]);

echo json_encode(['project_id' => $projectId, 'team_name' => $name, 'person_name' => 'Individual ' . $name], JSON_THROW_ON_ERROR) . "\n";
