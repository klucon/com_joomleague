<?php

declare(strict_types=1);

define('_JEXEC', 1);
define('JPATH_BASE', '/var/www/html');
require_once JPATH_BASE . '/includes/defines.php';
require_once JPATH_BASE . '/includes/framework.php';

use Joomla\CMS\Factory;
use Joomla\Database\DatabaseInterface;
use Joomleague\Component\Joomleague\Administrator\Service\ProjectTemplateConfigRepository;
use Joomleague\Component\Joomleague\Administrator\Service\ProfileTemplateConfigRepository;
use Joomleague\Component\Joomleague\Site\Service\ProjectTemplateProvider;

$container = Factory::getContainer();
$container->alias('session', 'session.cli')
	->alias('JSession', 'session.cli')
	->alias(Joomla\CMS\Session\Session::class, 'session.cli')
	->alias(Joomla\Session\Session::class, 'session.cli')
	->alias(Joomla\Session\SessionInterface::class, 'session.cli');
Factory::$application = $container->get(Joomla\Console\Application::class);

foreach (['CanonicalJson', 'TemplateDefinitionRegistry', 'ProfileTemplateConfigRepository', 'ProjectTemplateConfigRepository'] as $service) {
	require_once JPATH_ADMINISTRATOR . '/components/com_joomleague/src/Service/' . $service . '.php';
}
require_once JPATH_ROOT . '/components/com_joomleague/src/Service/ProjectTemplateProvider.php';

$database = $container->get(DatabaseInterface::class);
foreach (['Profiletemplates', 'Projecttemplates'] as $modelName) {
	require_once JPATH_ADMINISTRATOR . '/components/com_joomleague/src/Model/' . $modelName . 'Model.php';
	$class = new ReflectionClass('Joomleague\\Component\\Joomleague\\Administrator\\Model\\' . $modelName . 'Model');
	$model = $class->newInstanceWithoutConstructor();
	$parser = $class->getMethod('parseValue');
	foreach (['0' => false, '1' => true] as $input => $expected) {
		if ($parser->invoke($model, (string) $input, ['type' => 'boolean'], 'JYES') !== $expected) {
			throw new RuntimeException('Template boolean parsing failed.');
		}
	}
	foreach ([[], new stdClass(), null, 'yes', '2'] as $input) {
		try {
			$parser->invoke($model, $input, ['type' => 'boolean'], 'JYES');
			throw new RuntimeException('Malformed template boolean was accepted.');
		} catch (InvalidArgumentException) {
		}
	}
}
$suffix = bin2hex(random_bytes(6));
$uuid = static fn (): string => sprintf('%s-%s-4%s-%s%s-%s', bin2hex(random_bytes(4)), bin2hex(random_bytes(2)), substr(bin2hex(random_bytes(2)), 1), dechex(random_int(8, 11)), substr(bin2hex(random_bytes(2)), 1), bin2hex(random_bytes(6)));
$profileId = $profileVersionId = $competitionId = $seasonId = $sportTypeId = $projectId = null;

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
	$payload = [
		'template_defaults' => [
			'project' => ['show_hero' => true, 'show_sport' => true, 'show_season' => true, 'show_competition_info' => true],
			'ranking' => ['show_score' => true, 'show_goal_difference' => true, 'show_sets' => false, 'show_points' => true, 'favorite_highlight_mode' => 'row'],
		],
	];
	$payloadJson = Joomleague\Component\Joomleague\Domain\Service\CanonicalJson::encodeObject($payload);
	$profileId = $insert($database, '#__joomleague_sport_profile', [
		'code' => 'template-' . $suffix,
		'name_key' => 'COM_JOOMLEAGUE_TEMPLATE_PROJECT',
		'description_key' => 'COM_JOOMLEAGUE_TEMPLATE_PROJECT_DESC',
	]);
	$profileVersionId = $insert($database, '#__joomleague_sport_profile_version', [
		'profile_id' => $profileId,
		'schema_version' => 'test',
		'profile_version' => 'test-' . $suffix,
		'payload_json' => $payloadJson,
		'payload_checksum' => hash('sha256', $payloadJson),
		'source' => 'test',
	]);

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
	$profileRepository = new ProfileTemplateConfigRepository($database);
	$provider = new ProjectTemplateProvider($database);

	if ($profileRepository->getAll($profileVersionId) !== []) {
		throw new RuntimeException('Missing profile template configuration must inherit bundled values.');
	}
	$profileRepository->saveAll($profileVersionId, ['project' => ['show_sport' => false]], 0);
	if ($profileRepository->getAll($profileVersionId) !== ['project' => ['show_sport' => false]]) {
		throw new RuntimeException('Profile-template insert/read failed.');
	}
	if ($provider->resolve($projectId, 'project')['show_sport'] !== false) {
		throw new RuntimeException('Frontend resolver did not inherit the profile template override.');
	}

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
	$resolvedProject = $provider->resolve($projectId, 'project');
	if ($resolvedProject['show_hero'] !== false || $resolvedProject['show_sport'] !== false) {
		throw new RuntimeException('Frontend project-template resolution did not combine inherited and project values.');
	}
	$presentedProject = $provider->resolve($projectId, 'project', ['show_hero' => true]);
	if ($presentedProject['show_hero'] !== true) {
		throw new RuntimeException('Presentation override did not win as the final template layer.');
	}
	if (!$provider->supports($projectId, 'ranking') || $provider->supports($projectId, 'race_results')) {
		throw new RuntimeException('Frontend template support detection does not match the bound profile.');
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
	if ($provider->resolve($projectId, 'project')['show_hero'] !== true) {
		throw new RuntimeException('Frontend resolver did not restore profile inheritance.');
	}

	$profileRepository->saveAll($profileVersionId, ['project' => []], 0);
	if ($profileRepository->getAll($profileVersionId) !== [] || $provider->resolve($projectId, 'project')['show_sport'] !== true) {
		throw new RuntimeException('Empty profile override did not restore bundled inheritance.');
	}

	printf("Profile/project template repositories OK on %s: layered inheritance, rollback and reset validated\n", $database->getName());
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

	if ($profileId !== null) {
		$delete($database, '#__joomleague_sport_profile', (int) $profileId);
	}
}
