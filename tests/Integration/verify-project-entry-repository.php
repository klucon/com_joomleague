<?php

declare(strict_types=1);

define('_JEXEC', 1);
define('JPATH_BASE', '/var/www/html');
require_once JPATH_BASE . '/includes/defines.php';
require_once JPATH_BASE . '/includes/framework.php';

use Joomla\CMS\Factory;
use Joomla\Database\DatabaseInterface;
use Joomleague\Component\Joomleague\Administrator\Service\ProjectEntryRepository;
use Joomleague\Component\Joomleague\Domain\Service\UuidFactory;

$container = Factory::getContainer();
$container->alias('session', 'session.cli')
	->alias('JSession', 'session.cli')
	->alias(Joomla\CMS\Session\Session::class, 'session.cli')
	->alias(Joomla\Session\Session::class, 'session.cli')
	->alias(Joomla\Session\SessionInterface::class, 'session.cli');
Factory::$application = $container->get(Joomla\Console\Application::class);

foreach (['EntryModelValidator', 'UuidFactory', 'ProjectEntryRepository'] as $service) {
	require_once JPATH_ADMINISTRATOR . '/components/com_joomleague/src/Service/' . $service . '.php';
}

$database = $container->get(DatabaseInterface::class);
$suffix = bin2hex(random_bytes(5));

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

$database->transactionStart();

try {
	$query = $database->getQuery(true)
		->select('version.id')
		->from($database->quoteName('#__joomleague_sport_profile_version', 'version'))
		->innerJoin($database->quoteName('#__joomleague_sport_profile', 'profile') . ' ON profile.id = version.profile_id')
		->where('profile.code = ' . $database->quote('football'))
		->order('version.id DESC');
	$profileVersionId = (int) $database->setQuery($query, 0, 1)->loadResult();

	if ($profileVersionId < 1) {
		throw new RuntimeException('No current football profile is available.');
	}

	$competitionId = $insert($database, '#__joomleague_competition', ['uuid' => UuidFactory::v4(), 'name' => 'Entry fixture ' . $suffix]);
	$seasonId = $insert($database, '#__joomleague_season', ['uuid' => UuidFactory::v4(), 'name' => 'Entry fixture ' . $suffix]);
	$sportTypeId = $insert($database, '#__joomleague_sport_type', ['profile_version_id' => $profileVersionId, 'code' => 'entry-' . $suffix, 'name' => 'Entry fixture ' . $suffix]);
	$projectId = $insert($database, '#__joomleague_project', [
		'uuid' => UuidFactory::v4(), 'competition_id' => $competitionId, 'season_id' => $seasonId,
		'sport_type_id' => $sportTypeId, 'profile_version_id' => $profileVersionId,
		'name' => 'Entry fixture ' . $suffix, 'project_type' => 'league',
	]);
	$clubId = $insert($database, '#__joomleague_club', ['uuid' => UuidFactory::v4(), 'name' => 'Entry fixture club ' . $suffix]);
	$teamId = $insert($database, '#__joomleague_team', ['uuid' => UuidFactory::v4(), 'club_id' => $clubId, 'name' => 'Entry fixture team ' . $suffix]);
	$personId = $insert($database, '#__joomleague_person', ['uuid' => UuidFactory::v4(), 'first_name' => 'Entry', 'last_name' => 'Fixture ' . $suffix]);
	$repository = new ProjectEntryRepository($database);
	$entryId = $repository->createEntry($projectId, ['entry_kind' => 'team', 'team_id' => $teamId, 'seed_number' => 7], 0);
	$memberId = $repository->addMember($entryId, $personId, [
		'member_person_type' => 'player', 'role_code' => 'goalkeeper', 'shirt_number' => '1',
		'valid_from' => '2026-07-01', 'valid_until' => '2027-06-30',
	], 0);

	if ($entryId < 1 || $memberId < 1 || count($repository->getEntries($projectId)) !== 1) {
		throw new RuntimeException('Valid project entry or member was not persisted.');
	}

	foreach (
		[
			fn () => $repository->createEntry($projectId, ['entry_kind' => 'person', 'person_id' => $personId], 0),
			fn () => $repository->addMember($entryId, $personId, ['member_person_type' => 'participant'], 0),
			fn () => $repository->addMember($entryId, $personId, ['member_person_type' => 'player', 'role_code' => 'head_coach'], 0),
			fn () => $repository->addMember($entryId, $personId, ['member_person_type' => 'player', 'valid_from' => '2027-01-01', 'valid_until' => '2026-01-01'], 0),
		] as $invalidWrite
	) {
		try {
			$invalidWrite();
			throw new RuntimeException('An invalid profile-dependent write was accepted.');
		} catch (InvalidArgumentException) {
		}
	}

	printf("Project-entry repository OK on %s: profile kinds, member types, roles and dates validated\n", $database->getName());
} finally {
	$database->transactionRollback();
}
