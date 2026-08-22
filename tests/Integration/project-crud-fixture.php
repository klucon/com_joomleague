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
$db = $container->get(DatabaseInterface::class);
$action = $argv[1] ?? '';
$suffix = preg_replace('/[^a-z0-9]/', '', strtolower($argv[2] ?? ''));

if ($suffix === '' || !in_array($action, ['setup', 'verify', 'verify_rules', 'verify_inherited', 'cleanup'], true)) {
	throw new InvalidArgumentException('Usage: project-crud-fixture.php setup|verify|verify_rules|verify_inherited|cleanup suffix');
}

$names = ['competition' => 'Project CRUD competition ' . $suffix, 'season' => 'Project CRUD season ' . $suffix, 'sport_type' => 'Project CRUD sport ' . $suffix];

if ($action === 'cleanup') {
	$query = $db->getQuery(true)->delete($db->quoteName('#__joomleague_project'))->where($db->quoteName('name') . ' = :name');
	$invalidName = 'Project CRUD invalid ' . $suffix;
	$query->bind(':name', $invalidName);
	$db->setQuery($query)->execute();
	foreach ([['#__joomleague_project', 'name'], ['#__joomleague_sport_type', 'name'], ['#__joomleague_season', 'name'], ['#__joomleague_competition', 'name']] as [$table, $column]) {
		$value = $table === '#__joomleague_project' ? 'Project CRUD ' . $suffix : $names[match ($table) { '#__joomleague_sport_type' => 'sport_type', '#__joomleague_season' => 'season', default => 'competition' }];
		$query = $db->getQuery(true)->delete($db->quoteName($table))->where($db->quoteName($column) . ' = :value')->bind(':value', $value);
		$db->setQuery($query)->execute();
	}
	echo "cleanup ok\n";
	return;
}

if ($action === 'verify') {
	$query = $db->getQuery(true)
		->select(['project.profile_version_id', 'project.default_start_time', 'project.timezone', 'project.project_type'])
		->from($db->quoteName('#__joomleague_project', 'project'))
		->where('project.name = :name');
	$name = 'Project CRUD ' . $suffix;
	$query->bind(':name', $name);
	$row = $db->setQuery($query)->loadAssoc();
	if (!$row || (int) $row['profile_version_id'] < 1 || $row['default_start_time'] !== '17:00' || $row['project_type'] !== 'league') {
		throw new RuntimeException('Saved project does not contain the derived profile defaults.');
	}
	echo json_encode($row, JSON_THROW_ON_ERROR) . "\n";
	return;
}

if ($action === 'verify_rules') {
	$query = $db->getQuery(true)
		->select('config.overrides_json')
		->from($db->quoteName('#__joomleague_project_rule_config', 'config'))
		->innerJoin($db->quoteName('#__joomleague_project', 'project') . ' ON project.id = config.project_id')
		->where('project.name = :name');
	$name = 'Project CRUD ' . $suffix;
	$query->bind(':name', $name);
	$json = $db->setQuery($query)->loadResult();
	$expected = ['match_structure' => ['period_length_minutes' => 40, 'stoppage_time' => false], 'standings' => ['points_regular' => [4.0, 2.0, 0.0]]];
	if ($json === null || json_decode((string) $json, true, 512, JSON_THROW_ON_ERROR) !== $expected) {
		throw new RuntimeException('Project rule overrides do not match the expected sparse object: ' . (string) $json);
	}
	echo $json . "\n";
	return;
}

if ($action === 'verify_inherited') {
	$query = $db->getQuery(true)
		->select('COUNT(*)')
		->from($db->quoteName('#__joomleague_project_rule_config', 'config'))
		->innerJoin($db->quoteName('#__joomleague_project', 'project') . ' ON project.id = config.project_id')
		->where('project.name = :name');
	$name = 'Project CRUD ' . $suffix;
	$query->bind(':name', $name);
	if ((int) $db->setQuery($query)->loadResult() !== 0) throw new RuntimeException('Project rule inheritance was not restored.');
	echo "inheritance ok\n";
	return;
}

$query = $db->getQuery(true)
	->select('version.id')
	->from($db->quoteName('#__joomleague_sport_profile_version', 'version'))
	->innerJoin($db->quoteName('#__joomleague_sport_profile', 'profile') . ' ON profile.id = version.profile_id')
	->where('profile.code = ' . $db->quote('football'))
	->order('version.id DESC');
$profileVersionId = (int) $db->setQuery($query, 0, 1)->loadResult();
if ($profileVersionId < 1) throw new RuntimeException('Football profile is unavailable.');

$uuid = static fn (): string => sprintf('%s-%s-4%s-%s%s-%s', bin2hex(random_bytes(4)), bin2hex(random_bytes(2)), substr(bin2hex(random_bytes(2)), 1), dechex(random_int(8, 11)), substr(bin2hex(random_bytes(2)), 1), bin2hex(random_bytes(6)));

$insert = static function (DatabaseInterface $db, string $table, array $values): int {
	$query = $db->getQuery(true)->insert($db->quoteName($table))->columns($db->quoteName(array_keys($values)));
	$placeholders = [];
	foreach ($values as $key => &$value) {
		$placeholders[] = ':' . $key;
		$query->bind(':' . $key, $value);
	}
	$query->values(implode(', ', $placeholders));
	$db->setQuery($query)->execute();
	return (int) $db->insertid();
};

$competitionId = $insert($db, '#__joomleague_competition', ['uuid' => $uuid(), 'name' => $names['competition'], 'alias' => 'project-crud-competition-' . $suffix]);
$seasonId = $insert($db, '#__joomleague_season', ['uuid' => $uuid(), 'name' => $names['season'], 'alias' => 'project-crud-season-' . $suffix]);
$sportTypeId = $insert($db, '#__joomleague_sport_type', ['profile_version_id' => $profileVersionId, 'code' => 'project-crud-' . $suffix, 'name' => $names['sport_type'], 'alias' => 'project-crud-sport-' . $suffix]);

echo json_encode(['competition_id' => $competitionId, 'season_id' => $seasonId, 'sport_type_id' => $sportTypeId, 'profile_version_id' => $profileVersionId], JSON_THROW_ON_ERROR) . "\n";
