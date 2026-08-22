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
$database = $container->get(DatabaseInterface::class);
$action = $argv[1] ?? '';
$suffix = preg_replace('/[^a-z0-9]/', '', strtolower($argv[2] ?? ''));

if (!in_array($action, ['setup', 'cleanup'], true) || $suffix === '') throw new InvalidArgumentException('Usage: match-browser-fixture.php setup|cleanup suffix');

$projectName = 'Match browser fixture ' . $suffix;

if ($action === 'cleanup') {
	$query = $database->getQuery(true)->delete($database->quoteName('#__joomleague_project'))->where('name = :name')->bind(':name', $projectName);
	$database->setQuery($query)->execute();
	foreach (['#__joomleague_sport_type', '#__joomleague_season', '#__joomleague_competition'] as $table) {
		$query = $database->getQuery(true)->delete($database->quoteName($table))->where('name = :name')->bind(':name', $projectName);
		$database->setQuery($query)->execute();
	}
	echo "Match browser fixture cleanup OK\n";
	return;
}

$profileQuery = $database->getQuery(true)->select('version.id')->from($database->quoteName('#__joomleague_sport_profile_version', 'version'))->innerJoin($database->quoteName('#__joomleague_sport_profile', 'profile') . ' ON profile.id = version.profile_id')->where('profile.code = ' . $database->quote('football'))->where('version.state = ' . $database->quote('active'));
$profileVersionId = (int) $database->setQuery($profileQuery)->loadResult();
if ($profileVersionId < 1) throw new RuntimeException('Active football profile is unavailable.');

$uuid = static fn (): string => sprintf('%s-%s-4%s-%s%s-%s', bin2hex(random_bytes(4)), bin2hex(random_bytes(2)), substr(bin2hex(random_bytes(2)), 1), dechex(random_int(8, 11)), substr(bin2hex(random_bytes(2)), 1), bin2hex(random_bytes(6)));
$insert = static function (string $table, array $values) use ($database): int {
	$query = $database->getQuery(true)->insert($database->quoteName($table))->columns($database->quoteName(array_keys($values)));
	$placeholders = [];
	foreach ($values as $key => &$value) { $placeholders[] = ':' . $key; $query->bind(':' . $key, $value); }
	$query->values(implode(',', $placeholders));
	$database->setQuery($query)->execute();
	return (int) $database->insertid();
};

$competitionId = $insert('#__joomleague_competition', ['uuid' => $uuid(), 'name' => $projectName]);
$seasonId = $insert('#__joomleague_season', ['uuid' => $uuid(), 'name' => $projectName]);
$sportTypeId = $insert('#__joomleague_sport_type', ['profile_version_id' => $profileVersionId, 'code' => 'match-browser-' . $suffix, 'name' => $projectName]);
$projectId = $insert('#__joomleague_project', ['uuid' => $uuid(), 'competition_id' => $competitionId, 'season_id' => $seasonId, 'sport_type_id' => $sportTypeId, 'profile_version_id' => $profileVersionId, 'name' => $projectName, 'project_type' => 'league']);

echo $projectId . "\n";
