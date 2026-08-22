<?php

declare(strict_types=1);

define('_JEXEC', 1);
define('JPATH_BASE', '/var/www/html');
require_once JPATH_BASE . '/includes/defines.php';
require_once JPATH_BASE . '/includes/framework.php';

use Joomla\CMS\Factory;
use Joomla\Database\DatabaseInterface;
use Joomleague\Component\Joomleague\Administrator\Service\ProjectRuleConfigRepository;

$container = Factory::getContainer();
$container->alias('session', 'session.cli')
	->alias('JSession', 'session.cli')
	->alias(Joomla\CMS\Session\Session::class, 'session.cli')
	->alias(Joomla\Session\Session::class, 'session.cli')
	->alias(Joomla\Session\SessionInterface::class, 'session.cli');
Factory::$application = $container->get(Joomla\Console\Application::class);

foreach (['CanonicalJson', 'ProjectRuleValidator', 'ProjectRuleConfigRepository'] as $service) {
	require_once JPATH_ADMINISTRATOR . '/components/com_joomleague/src/Service/' . $service . '.php';
}

$database = $container->get(DatabaseInterface::class);
$suffix = bin2hex(random_bytes(6));
$uuid = sprintf('%s-%s-4%s-a%s-%s', substr($suffix, 0, 8), substr($suffix, 8, 4), substr($suffix, 0, 3), substr($suffix, 3, 3), str_pad($suffix, 12, '0'));
$seasonUuid = strrev($uuid);
$projectUuid = substr($uuid, 0, 24) . substr($seasonUuid, 0, 12);
$projectType = 'league';
$competitionId = $seasonId = $sportTypeId = $projectId = null;

$delete = static function (DatabaseInterface $db, string $table, int $id): void {
	$query = $db->getQuery(true)
		->delete($db->quoteName($table))
		->where($db->quoteName('id') . ' = :id')
		->bind(':id', $id);
	$db->setQuery($query)->execute();
};

try {
	$query = $database->getQuery(true)
		->select($database->quoteName('version.id'))
		->from($database->quoteName('#__joomleague_sport_profile_version', 'version'))
		->innerJoin($database->quoteName('#__joomleague_sport_profile', 'profile') . ' ON profile.id = version.profile_id')
		->where($database->quoteName('profile.code') . ' = ' . $database->quote('football'))
		->where($database->quoteName('version.profile_version') . ' = ' . $database->quote('1.3.0'));
	$profileVersionId = (int) $database->setQuery($query)->loadResult();

	if ($profileVersionId < 1) {
		throw new RuntimeException('Football profile 1.3.0 is not installed.');
	}

	$query = $database->getQuery(true)
		->insert($database->quoteName('#__joomleague_competition'))
		->columns($database->quoteName(['uuid', 'name', 'alias']))
		->values(':uuid, :name, :alias')
		->bind(':uuid', $uuid)
		->bind(':name', $suffix)
		->bind(':alias', $suffix);
	$database->setQuery($query)->execute();
	$competitionId = (int) $database->insertid();

	$query = $database->getQuery(true)
		->insert($database->quoteName('#__joomleague_season'))
		->columns($database->quoteName(['uuid', 'name', 'alias']))
		->values(':uuid, :name, :alias')
		->bind(':uuid', $seasonUuid)
		->bind(':name', $suffix)
		->bind(':alias', $suffix);
	$database->setQuery($query)->execute();
	$seasonId = (int) $database->insertid();

	$query = $database->getQuery(true)
		->insert($database->quoteName('#__joomleague_sport_type'))
		->columns($database->quoteName(['profile_version_id', 'code', 'name', 'alias']))
		->values(':profile_version_id, :code, :name, :alias')
		->bind(':profile_version_id', $profileVersionId)
		->bind(':code', $suffix)
		->bind(':name', $suffix)
		->bind(':alias', $suffix);
	$database->setQuery($query)->execute();
	$sportTypeId = (int) $database->insertid();

	$query = $database->getQuery(true)
		->insert($database->quoteName('#__joomleague_project'))
		->columns($database->quoteName([
			'uuid', 'competition_id', 'season_id', 'sport_type_id', 'profile_version_id',
			'name', 'alias', 'project_type',
		]))
		->values(':uuid, :competition_id, :season_id, :sport_type_id, :profile_version_id, :name, :alias, :project_type')
		->bind(':uuid', $projectUuid)
		->bind(':competition_id', $competitionId)
		->bind(':season_id', $seasonId)
		->bind(':sport_type_id', $sportTypeId)
		->bind(':profile_version_id', $profileVersionId)
		->bind(':name', $suffix)
		->bind(':alias', $suffix)
		->bind(':project_type', $projectType);
	$database->setQuery($query)->execute();
	$projectId = (int) $database->insertid();

	$repository = new ProjectRuleConfigRepository($database);

	if ($repository->get($projectId) !== []) {
		throw new RuntimeException('Missing configuration must resolve to an empty object.');
	}

	$first = ['match_structure' => ['period_length_minutes' => 40], 'lineup' => ['minimum_players_to_start' => 7]];
	$record = $repository->save($projectId, $first, 0);

	if ($record === null || $repository->get($projectId) != $first) {
		throw new RuntimeException('Project-rule configuration insert/read failed.');
	}

	$second = ['match_structure' => ['period_length_minutes' => 35], 'lineup' => ['minimum_players_to_start' => 6]];
	$repository->save($projectId, $second, 0);

	if ($repository->get($projectId) != $second) {
		throw new RuntimeException('Project-rule configuration update failed.');
	}

	try {
		$repository->save($projectId, ['lineup' => ['minimum_players_to_start' => 12]], 0);
		throw new RuntimeException('Invalid relational override was persisted.');
	} catch (InvalidArgumentException) {
	}

	if ($repository->get($projectId) != $second) {
		throw new RuntimeException('Rejected write did not roll back cleanly.');
	}

	$tamperedProjectId = $projectId;
	$query = $database->getQuery(true)
		->update($database->quoteName('#__joomleague_project_rule_config'))
		->set($database->quoteName('overrides_checksum') . ' = ' . $database->quote(str_repeat('0', 64)))
		->where($database->quoteName('project_id') . ' = :project_id')
		->bind(':project_id', $tamperedProjectId);
	$database->setQuery($query)->execute();

	try {
		$repository->get($projectId);
		throw new RuntimeException('Tampered checksum was accepted.');
	} catch (UnexpectedValueException) {
	}

	$repository->save($projectId, [], 0);

	if ($repository->get($projectId) !== []) {
		throw new RuntimeException('Empty override did not restore inheritance.');
	}

	printf("Project-rule repository OK on %s: insert, update, rollback, tamper detection and delete validated\n", $database->getName());
} finally {
	if ($projectId !== null) {
		$delete($database, '#__joomleague_project', (int) $projectId);
	}
	if ($sportTypeId !== null) {
		$delete($database, '#__joomleague_sport_type', (int) $sportTypeId);
	}
	if ($seasonId !== null) {
		$delete($database, '#__joomleague_season', (int) $seasonId);
	}
	if ($competitionId !== null) {
		$delete($database, '#__joomleague_competition', (int) $competitionId);
	}
}
