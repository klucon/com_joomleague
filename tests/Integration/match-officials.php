<?php

declare(strict_types=1);

define('_JEXEC', 1);
define('JPATH_BASE', '/var/www/html');
require_once JPATH_BASE . '/includes/defines.php';
require_once JPATH_BASE . '/includes/framework.php';
foreach (['UuidFactory.php', 'EntryModelValidator.php', 'MatchActorRoleRepository.php'] as $service) require_once JPATH_ADMINISTRATOR . '/components/com_joomleague/src/Service/' . $service;

use Joomla\CMS\Factory;
use Joomla\Database\DatabaseInterface;
use Joomleague\Component\Joomleague\Administrator\Service\MatchActorRoleRepository;
use Joomleague\Component\Joomleague\Domain\Service\UuidFactory;

$database = Factory::getContainer()->get(DatabaseInterface::class);
$uuid = static fn (): string => UuidFactory::v4();
$insert = static function (string $table, array $data) use ($database): int {
	$query = $database->getQuery(true)->insert($database->quoteName($table))->columns($database->quoteName(array_keys($data)));
	$values = [];
	foreach ($data as $key => &$value) { $values[] = ':' . $key; $query->bind(':' . $key, $value); }
	$query->values(implode(',', $values)); $database->setQuery($query)->execute(); return (int) $database->insertid();
};
$name = 'Match officials fixture ' . bin2hex(random_bytes(3));
$profileVersion = (int) $database->setQuery($database->getQuery(true)->select('v.id')->from($database->quoteName('#__joomleague_sport_profile_version', 'v'))->innerJoin($database->quoteName('#__joomleague_sport_profile', 'p') . ' ON p.id=v.profile_id')->where('p.code=' . $database->quote('football'))->where('v.state=' . $database->quote('active')))->loadResult();
$competition = $insert('#__joomleague_competition', ['uuid' => $uuid(), 'name' => $name]);
$season = $insert('#__joomleague_season', ['uuid' => $uuid(), 'name' => $name]);
$sport = $insert('#__joomleague_sport_type', ['profile_version_id' => $profileVersion, 'code' => 'official-' . bin2hex(random_bytes(3)), 'name' => $name]);
$project = $insert('#__joomleague_project', ['uuid' => $uuid(), 'competition_id' => $competition, 'season_id' => $season, 'sport_type_id' => $sport, 'profile_version_id' => $profileVersion, 'name' => $name, 'project_type' => 'league', 'timezone' => 'Europe/Prague']);
$foreignProject = $insert('#__joomleague_project', ['uuid' => $uuid(), 'competition_id' => $competition, 'season_id' => $season, 'sport_type_id' => $sport, 'profile_version_id' => $profileVersion, 'name' => $name . ' foreign', 'project_type' => 'league', 'timezone' => 'Europe/Prague']);
$club = $insert('#__joomleague_club', ['uuid' => $uuid(), 'name' => $name]);
$team = $insert('#__joomleague_team', ['uuid' => $uuid(), 'club_id' => $club, 'name' => $name . ' team']);
$person = $insert('#__joomleague_person', ['uuid' => $uuid(), 'first_name' => 'Official', 'last_name' => $name]);
$expiredPerson = $insert('#__joomleague_person', ['uuid' => $uuid(), 'first_name' => 'Expired', 'last_name' => $name]);

try {
	$stage = $insert('#__joomleague_project_stage', ['uuid' => $uuid(), 'project_id' => $project, 'name' => 'League', 'code' => 'league', 'stage_type' => 'league']);
	$round = $insert('#__joomleague_project_round', ['uuid' => $uuid(), 'project_id' => $project, 'stage_id' => $stage, 'name' => 'Round 1', 'code' => 'round_1', 'round_type' => 'regular', 'sequence_number' => 1]);
	$match = $insert('#__joomleague_project_match', ['uuid' => $uuid(), 'project_id' => $project, 'stage_id' => $stage, 'round_id' => $round, 'match_number' => '1', 'contest_type' => 'head_to_head', 'scheduled_start' => '2026-08-15 15:00:00']);
	$secondMatch = $insert('#__joomleague_project_match', ['uuid' => $uuid(), 'project_id' => $project, 'stage_id' => $stage, 'round_id' => $round, 'match_number' => '2', 'contest_type' => 'head_to_head', 'scheduled_start' => '2026-08-16 15:00:00']);
	$repository = new MatchActorRoleRepository($database, 'UTC');
	$personRole = $repository->addProjectAssignment($project, 'person:' . $person, 'referee', '2026-08-01', '2026-08-31', 'Primary official', 0);
	$teamRole = $repository->addProjectAssignment($project, 'team:' . $team, 'delegate', null, null, null, 0);
	$expiredRole = $repository->addProjectAssignment($project, 'person:' . $expiredPerson, 'referee', null, '2026-07-31', null, 0);
	$foreignRole = $repository->addProjectAssignment($foreignProject, 'person:' . $person, 'referee', null, null, null, 0);
	try { $repository->addProjectAssignment($project, 'person:' . $person, 'referee', '2026-08-15', null, null, 0); throw new RuntimeException('An overlapping project role was accepted.'); } catch (InvalidArgumentException) {}
	try { $repository->addProjectAssignment($project, 'person:' . $person, 'goalkeeper', null, null, null, 0); throw new RuntimeException('A non-official profile role was accepted.'); } catch (InvalidArgumentException) {}
	$availableIds = array_map(static fn (object $row): int => (int) $row->id, $repository->getAvailableForMatch($match)); sort($availableIds);
	$expectedIds = [$personRole, $teamRole]; sort($expectedIds);
	if ($availableIds !== $expectedIds || in_array($expiredRole, $availableIds, true)) throw new RuntimeException('Match-date official availability is invalid.');
	try { $repository->assignToMatch($match, $foreignRole, null, 0); throw new RuntimeException('A foreign project role was assigned to the match.'); } catch (InvalidArgumentException) {}
	$matchRole = $repository->assignToMatch($match, $personRole, 'Match note', 0);
	$teamMatchRole = $repository->assignToMatch($match, $teamRole, null, 0);
	$assigned = $repository->getMatchAssignments($match);
	if (count($assigned) !== 2 || (int) $assigned[0]->id !== $matchRole || $assigned[0]->display_name_snapshot !== 'Official ' . $name || $assigned[0]->role_code !== 'referee') throw new RuntimeException('The match official snapshot was not stored.');
	$duplicateRejected = false;
	try { $repository->assignToMatch($match, $personRole, null, 0); } catch (Throwable) { $duplicateRejected = true; }
	if (!$duplicateRejected) throw new RuntimeException('A duplicate match assignment was accepted.');
	try { $repository->removeFromMatch($secondMatch, $matchRole); throw new RuntimeException('Another match removed the assignment.'); } catch (InvalidArgumentException) {}
	$repository->removeProjectAssignment($project, $personRole);
	$assigned = $repository->getMatchAssignments($match);
	if ((int) $assigned[0]->id !== $matchRole || $assigned[0]->source_project_actor_role_id !== null || $assigned[0]->display_name_snapshot !== 'Official ' . $name) throw new RuntimeException('The historical match snapshot did not survive project-role removal.');
	$repository->removeFromMatch($match, $matchRole);
	$repository->removeFromMatch($match, $teamMatchRole);
	if ($repository->getMatchAssignments($match) !== []) throw new RuntimeException('Owned match assignments were not removed.');
	echo "Project and match official ownership, validity, polymorphic actors and snapshots OK\n";
} finally {
	foreach ([$project, $foreignProject] as $projectId) { $query = $database->getQuery(true)->delete($database->quoteName('#__joomleague_project'))->where('id=' . $projectId); $database->setQuery($query)->execute(); }
	foreach ([['#__joomleague_sport_type', $sport], ['#__joomleague_competition', $competition], ['#__joomleague_season', $season], ['#__joomleague_team', $team], ['#__joomleague_person', $person], ['#__joomleague_person', $expiredPerson], ['#__joomleague_club', $club]] as [$table, $id]) {
		$query = $database->getQuery(true)->delete($database->quoteName($table))->where('id=' . (int) $id); $database->setQuery($query)->execute();
	}
}
