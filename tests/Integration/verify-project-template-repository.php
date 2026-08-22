<?php

declare(strict_types=1);

define('_JEXEC', 1);
define('JPATH_BASE', '/var/www/html');
require_once JPATH_BASE . '/includes/defines.php';
require_once JPATH_BASE . '/includes/framework.php';

use Joomla\CMS\Factory;
use Joomla\Database\DatabaseInterface;
use Joomleague\Component\Joomleague\Administrator\Service\ProjectTemplateConfigRepository;

$container = Factory::getContainer();
$container->alias('session', 'session.cli')
	->alias('JSession', 'session.cli')
	->alias(Joomla\CMS\Session\Session::class, 'session.cli')
	->alias(Joomla\Session\Session::class, 'session.cli')
	->alias(Joomla\Session\SessionInterface::class, 'session.cli');
Factory::$application = $container->get(Joomla\Console\Application::class);

foreach (['CanonicalJson', 'TemplateDefinitionRegistry', 'ProjectTemplateConfigRepository'] as $service) {
	require_once JPATH_ADMINISTRATOR . '/components/com_joomleague/src/Service/' . $service . '.php';
}

$database = $container->get(DatabaseInterface::class);
$suffix = bin2hex(random_bytes(6));
$uuid = static fn (): string => sprintf('%s-%s-4%s-%s%s-%s', bin2hex(random_bytes(4)), bin2hex(random_bytes(2)), substr(bin2hex(random_bytes(2)), 1), dechex(random_int(8, 11)), substr(bin2hex(random_bytes(2)), 1), bin2hex(random_bytes(6)));
$competitionId = $seasonId = $sportTypeId = $projectId = null;

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
		->order($database->quoteName('version.id') . ' DESC');
	$profileVersionId = (int) $database->setQuery($query, 0, 1)->loadResult();

	if ($profileVersionId < 1) {
		throw new RuntimeException('Football profile is not installed.');
	}

	$competitionId = $insert($database, '#__joomleague_competition', ['uuid' => $uuid(), 'name' => $suffix, 'alias' => 'template-' . $suffix]);
	$seasonId = $insert($database, '#__joomleague_season', ['uuid' => $uuid(), 'name' => $suffix, 'alias' => 'template-' . $suffix]);
	$sportTypeId = $insert($database, '#__joomleague_sport_type', ['profile_version_id' => $profileVersionId, 'code' => 'template-' . $suffix, 'name' => $suffix, 'alias' => 'template-' . $suffix]);
	$projectId = $insert($database, '#__joomleague_project', [
		'uuid' => $uuid(),
		'competition_id' => $competitionId,
		'season_id' => $seasonId,
		'sport_type_id' => $sportTypeId,
		'profile_version_id' => $profileVersionId,
		'name' => $suffix,
		'alias' => 'template-' . $suffix,
		'project_type' => 'league',
	]);

	$repository = new ProjectTemplateConfigRepository($database);

	if ($repository->getAll($projectId) !== []) {
		throw new RuntimeException('Missing template configuration must inherit completely.');
	}

	$first = [
		'project' => ['show_hero' => false],
		'ranking' => ['favorite_highlight_mode' => 'name'],
	];
	$repository->saveAll($projectId, $first, 0);

	if ($repository->getAll($projectId) !== $first) {
		throw new RuntimeException('Atomic project-template insert/read failed.');
	}

	try {
		$repository->saveAll($projectId, [
			'project' => ['show_hero' => true],
			'ranking' => ['favorite_highlight_mode' => 'unsupported'],
		], 0);
		throw new RuntimeException('Invalid project-template value was persisted.');
	} catch (InvalidArgumentException) {
	}

	if ($repository->getAll($projectId) !== $first) {
		throw new RuntimeException('Rejected multi-template write did not roll back atomically.');
	}

	$repository->saveAll($projectId, ['project' => [], 'ranking' => []], 0);

	if ($repository->getAll($projectId) !== []) {
		throw new RuntimeException('Empty template overrides did not restore inheritance.');
	}

	printf("Project-template repository OK on %s: atomic insert, rollback and inheritance validated\n", $database->getName());
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
