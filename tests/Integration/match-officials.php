<?php

declare(strict_types=1);

define('_JEXEC', 1);
define('JPATH_BASE', '/var/www/html');
$_SERVER['HTTP_HOST'] ??= 'localhost';
$_SERVER['REQUEST_URI'] ??= '/';
$_SERVER['SCRIPT_NAME'] = '/index.php';
require_once JPATH_BASE . '/includes/defines.php';
require_once JPATH_BASE . '/includes/framework.php';
$_SERVER['HTTP_HOST'] ??= 'localhost';
$_SERVER['REQUEST_URI'] ??= '/';
$_SERVER['SCRIPT_NAME'] = '/index.php';
$bootstrap = Joomla\CMS\Factory::getContainer();
$bootstrap->alias('session', 'session.cli')->alias(Joomla\Session\SessionInterface::class, 'session.cli');
Joomla\CMS\Factory::$application = $bootstrap->get(Joomla\CMS\Application\SiteApplication::class);
Joomla\CMS\Factory::getApplication()->createExtensionNamespaceMap();
Joomla\CMS\Factory::getApplication()->bootComponent('com_joomleague');
$configuration = Joomla\CMS\Factory::getApplication();
if (!str_starts_with((string) getenv('JL_R1_DB_HOST'), 'jl-r1-') || $configuration->get('host') !== getenv('JL_R1_DB_HOST') || $configuration->get('db') !== 'r1_test' || $configuration->get('dbprefix') !== 'r1_') {
    throw new RuntimeException('Disposable workflow database required.');
}
require_once JPATH_ADMINISTRATOR . '/components/com_joomleague/src/Extension/JoomleagueComponent.php';
require_once JPATH_SITE . '/components/com_joomleague/src/Service/PublicAccess.php';
foreach (['UuidFactory.php', 'EntryModelValidator.php', 'RoleDomain.php', 'MatchActorRoleRepository.php'] as $service) require_once JPATH_ADMINISTRATOR . '/components/com_joomleague/src/Service/' . $service;
require_once JPATH_ADMINISTRATOR . '/components/com_joomleague/src/Service/StandingsDecimal.php';
require_once JPATH_ADMINISTRATOR . '/components/com_joomleague/src/Service/ClubRivalHistoryReader.php';
require_once JPATH_SITE . '/components/com_joomleague/src/Model/PersonModel.php';

use Joomla\CMS\Factory;
use Joomla\Database\DatabaseInterface;
use Joomleague\Component\Joomleague\Administrator\Service\MatchActorRoleRepository;
use Joomleague\Component\Joomleague\Domain\Service\UuidFactory;
use Joomleague\Component\Joomleague\Domain\Service\ClubRivalHistoryReader;
use Joomleague\Component\Joomleague\Site\Model\PersonModel;

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
$project = $insert('#__joomleague_project', ['uuid' => $uuid(), 'competition_id' => $competition, 'season_id' => $season, 'sport_type_id' => $sport, 'profile_version_id' => $profileVersion, 'name' => $name, 'project_type' => 'league', 'timezone' => 'Europe/Prague', 'published' => 1, 'access' => 1]);
$foreignProject = $insert('#__joomleague_project', ['uuid' => $uuid(), 'competition_id' => $competition, 'season_id' => $season, 'sport_type_id' => $sport, 'profile_version_id' => $profileVersion, 'name' => $name . ' foreign', 'project_type' => 'league', 'timezone' => 'Europe/Prague', 'published' => 1, 'access' => 1]);
$club = $insert('#__joomleague_club', ['uuid' => $uuid(), 'name' => $name]);
$opponentClub = $insert('#__joomleague_club', ['uuid' => $uuid(), 'name' => $name . ' opponent club']);
$team = $insert('#__joomleague_team', ['uuid' => $uuid(), 'club_id' => $club, 'name' => $name . ' team', 'published' => 1, 'access' => 1]);
$opponentTeam = $insert('#__joomleague_team', ['uuid' => $uuid(), 'club_id' => $opponentClub, 'name' => $name . ' opponent', 'published' => 1, 'access' => 1]);
$person = $insert('#__joomleague_person', ['uuid' => $uuid(), 'first_name' => 'Official', 'last_name' => $name]);
$delegate = $insert('#__joomleague_person', ['uuid' => $uuid(), 'first_name' => 'Delegate', 'last_name' => $name]);
$expiredPerson = $insert('#__joomleague_person', ['uuid' => $uuid(), 'first_name' => 'Expired', 'last_name' => $name]);
$assistantOne = $insert('#__joomleague_person', ['uuid' => $uuid(), 'first_name' => 'Assistant One', 'last_name' => $name]);
$assistantTwo = $insert('#__joomleague_person', ['uuid' => $uuid(), 'first_name' => 'Assistant Two', 'last_name' => $name]);
$emergency = $insert('#__joomleague_person', ['uuid' => $uuid(), 'first_name' => 'Emergency', 'last_name' => $name]);

try {
	$stage = $insert('#__joomleague_project_stage', ['uuid' => $uuid(), 'project_id' => $project, 'name' => 'League', 'code' => 'league', 'stage_type' => 'league', 'published' => 1]);
	$round = $insert('#__joomleague_project_round', ['uuid' => $uuid(), 'project_id' => $project, 'stage_id' => $stage, 'name' => 'Round 1', 'code' => 'round_1', 'round_type' => 'regular', 'sequence_number' => 1, 'published' => 1]);
	$match = $insert('#__joomleague_project_match', ['uuid' => $uuid(), 'project_id' => $project, 'stage_id' => $stage, 'round_id' => $round, 'match_number' => '1', 'contest_type' => 'head_to_head', 'scheduled_start' => '2026-08-15 15:00:00', 'published' => 1]);
	$secondMatch = $insert('#__joomleague_project_match', ['uuid' => $uuid(), 'project_id' => $project, 'stage_id' => $stage, 'round_id' => $round, 'match_number' => '2', 'contest_type' => 'head_to_head', 'scheduled_start' => '2026-08-16 15:00:00']);
	$entries = [
		$insert('#__joomleague_project_entry', ['uuid' => $uuid(), 'project_id' => $project, 'entry_kind' => 'team', 'team_id' => $team, 'display_name' => '', 'published' => 1]),
		$insert('#__joomleague_project_entry', ['uuid' => $uuid(), 'project_id' => $project, 'entry_kind' => 'team', 'team_id' => $opponentTeam, 'display_name' => '', 'published' => 1]),
	];
	$participants = [];
	foreach ($entries as $index => $entry) {
		$participants[] = $insert('#__joomleague_match_participant', ['uuid' => $uuid(), 'match_id' => $match, 'project_id' => $project, 'project_entry_id' => $entry, 'slot_number' => $index + 1, 'published' => 1]);
	}
	$insert('#__joomleague_match_result', ['uuid' => $uuid(), 'match_id' => $match, 'result_type' => 'numeric_score', 'status_code' => 'final', 'outcome_code' => 'completed']);
	$rootSegment = $insert('#__joomleague_match_score_segment', ['uuid' => $uuid(), 'match_id' => $match, 'level_code' => 'result', 'sequence_number' => 1]);
	foreach ($participants as $index => $participant) {
		$insert('#__joomleague_match_score_value', ['uuid' => $uuid(), 'match_id' => $match, 'segment_id' => $rootSegment, 'participant_id' => $participant, 'numeric_value' => $index === 0 ? '2' : '1', 'result_rank' => $index + 1]);
	}
	$repository = new MatchActorRoleRepository($database, 'UTC');
	$personRole = $repository->addProjectAssignment($project, 'person:' . $person, 'referee', '2026-08-01', '2026-08-31', 'Primary official', 0);
	$delegateRole = $repository->addProjectAssignment($project, 'person:' . $delegate, 'delegate', null, null, null, 0);
	$expiredRole = $repository->addProjectAssignment($project, 'person:' . $expiredPerson, 'referee', null, '2026-07-31', null, 0);
	$foreignRole = $repository->addProjectAssignment($foreignProject, 'person:' . $person, 'referee', null, null, null, 0);
	$assistantOneRole = $repository->addProjectAssignment($project, 'person:' . $assistantOne, 'assistant_referee_1', null, null, null, 0);
	$assistantTwoRole = $repository->addProjectAssignment($project, 'person:' . $assistantTwo, 'assistant_referee_2', null, null, null, 0);
	try { $repository->addProjectAssignment($project, 'person:' . $person, 'referee', '2026-08-15', null, null, 0); throw new RuntimeException('An overlapping project role was accepted.'); } catch (InvalidArgumentException) {}
	try { $repository->addProjectAssignment($project, 'person:' . $person, 'goalkeeper', null, null, null, 0); throw new RuntimeException('A non-official profile role was accepted.'); } catch (InvalidArgumentException) {}
	try { $repository->addProjectAssignment($project, 'team:' . $team, 'delegate', null, null, null, 0); throw new RuntimeException('A team was accepted for a person-only competition role.'); } catch (InvalidArgumentException) {}
	$availableIds = array_map(static fn (object $row): int => (int) $row->id, $repository->getAvailableForMatch($match)); sort($availableIds);
	$expectedIds = [$personRole, $delegateRole, $assistantOneRole, $assistantTwoRole]; sort($expectedIds);
	if ($availableIds !== $expectedIds || in_array($expiredRole, $availableIds, true)) throw new RuntimeException('Match-date official availability is invalid.');
	try { $repository->assignToMatch($match, $foreignRole, null, 0); throw new RuntimeException('A foreign project role was assigned to the match.'); } catch (InvalidArgumentException) {}
	$matchRole = $repository->assignToMatch($match, $personRole, 'Match note', 0);
	$delegateMatchRole = $repository->assignToMatch($match, $delegateRole, null, 0);
	$assistantOneMatchRole = $repository->assignToMatch($match, $assistantOneRole, null, 0);
	$assistantTwoMatchRole = $repository->assignToMatch($match, $assistantTwoRole, null, 0);
	$emergencyMatchRole = $repository->addDirectMatchAssignment($match, 'person:' . $emergency, 'assistant_referee_2', 'non_delegated', 'Emergency replacement', 0);
	$assigned = $repository->getMatchAssignments($match);
	if (count($assigned) !== 5 || (int) $assigned[0]->id !== $matchRole || $assigned[0]->display_name_snapshot !== 'Official ' . $name || $assigned[0]->role_code !== 'referee' || $assigned[0]->role_domain !== 'adjudicator') throw new RuntimeException('The match competition-role snapshot was not stored.');
	$repository->recordMatchRoleChange($match, $matchRole, $assistantOneMatchRole, 'referee', 'injury', 'period', 2, '61', 'minute', null, 0);
	$repository->recordMatchRoleChange($match, $assistantOneMatchRole, $assistantTwoMatchRole, 'assistant_referee_1', 'other', 'period', 2, '61', 'minute', null, 0);
	$repository->recordMatchRoleChange($match, $assistantTwoMatchRole, $emergencyMatchRole, 'assistant_referee_2', 'other', 'period', 2, '61', 'minute', null, 0);
	$effective = $repository->getEffectiveMatchRoles($match);
	if (($effective['referee'] ?? 0) !== $assistantOneMatchRole || ($effective['assistant_referee_1'] ?? 0) !== $assistantTwoMatchRole || ($effective['assistant_referee_2'] ?? 0) !== $emergencyMatchRole) throw new RuntimeException('The referee replacement chain was not replayed correctly.');
	$changes = $repository->getMatchRoleChanges($match);
	if (count($changes) !== 3 || count($repository->getMatchAssignments($match)) !== 5 || $changes[0]->phase_code !== 'period' || (float) $changes[0]->clock_value !== 61.0) throw new RuntimeException('Role changes altered the original nominations or timeline metadata.');
	$container = Factory::getContainer();
	$container->alias('session', 'session.cli')
		->alias('JSession', 'session.cli')
		->alias(Joomla\CMS\Session\Session::class, 'session.cli')
		->alias(Joomla\Session\Session::class, 'session.cli')
		->alias(Joomla\Session\SessionInterface::class, 'session.cli');
	Factory::$application = $container->get(Joomla\CMS\Application\SiteApplication::class);
	Factory::getApplication()->loadIdentity(new Joomla\CMS\User\User());
	Factory::getApplication()->bootComponent('com_joomleague');
	$personModel = new PersonModel(['ignore_request' => true]);
	$personModel->setState('person_id', $person);
	$personModel->setState('site_club_id', $club);
	$profile = $personModel->getPerson();
	if (count($profile['competition_role_history'] ?? []) !== 1 || ($profile['competition_role_history'][0]->role_code ?? '') !== 'referee') throw new RuntimeException('The public person profile did not expose the historical referee role.');
	if (count($profile['event_assignments'] ?? []) !== 1 || (int) ($profile['event_assignments'][0]->id ?? 0) !== $matchRole) throw new RuntimeException('The public person profile did not expose the referee nomination.');
	if (count($profile['role_changes_by_match'][$match] ?? []) !== 1 || (int) ($profile['role_changes_by_match'][$match][0]->outgoing_match_actor_role_id ?? 0) !== $matchRole) throw new RuntimeException('The public person profile did not expose the referee replacement history.');
	$clubStats = $profile['officiating_summary']['projects'][0]['site_club_stats'] ?? null;
	if ($clubStats === null || (int) $clubStats['played'] !== 1 || (int) $clubStats['wins'] !== 1 || (int) $clubStats['draws'] !== 0 || (int) $clubStats['losses'] !== 0 || count($clubStats['opponents']) !== 1 || (int) $clubStats['opponents'][0]['club_id'] !== $opponentClub) throw new RuntimeException('The public referee profile site-club balance is invalid.');
	if ((int) ($profile['officiating_summary']['site_club_stats']['played'] ?? 0) !== 1) throw new RuntimeException('The all-project referee site-club balance is invalid.');
	$rivals = (new ClubRivalHistoryReader($database))->read($club, [], [1]);
	if (count($rivals['opponents'] ?? []) !== 1 || (int) $rivals['opponents'][0]['club_id'] !== $opponentClub || (int) $rivals['opponents'][0]['played'] !== 1 || (int) $rivals['opponents'][0]['wins'] !== 1 || $rivals['opponents'][0]['scored'] !== '2' || $rivals['opponents'][0]['conceded'] !== '1') throw new RuntimeException('Club rival history did not aggregate the final event correctly: ' . json_encode($rivals, JSON_THROW_ON_ERROR));
	$rivalDetail = (new ClubRivalHistoryReader($database))->read($club, ['opponent_club_id' => $opponentClub, 'project_type' => 'league'], [1]);
	if (count($rivalDetail['matches'] ?? []) !== 1 || (int) $rivalDetail['matches'][0]['match_id'] !== $match || ($rivalDetail['matches'][0]['outcome'] ?? '') !== 'win') throw new RuntimeException('Club rival detail filters are invalid.');
	$excluded = (new ClubRivalHistoryReader($database))->read($club, ['project_type' => 'cup'], [1]);
	if (($excluded['opponents'] ?? []) !== [] || ($excluded['matches'] ?? []) !== []) throw new RuntimeException('Club rival project-type filter leaked another format.');
	$duplicateRejected = false;
	try { $repository->assignToMatch($match, $personRole, null, 0); } catch (Throwable) { $duplicateRejected = true; }
	if (!$duplicateRejected) throw new RuntimeException('A duplicate match assignment was accepted.');
	try { $repository->removeFromMatch($secondMatch, $matchRole); throw new RuntimeException('Another match removed the assignment.'); } catch (InvalidArgumentException) {}
	$repository->removeProjectAssignment($project, $personRole);
	$assigned = $repository->getMatchAssignments($match);
	if ((int) $assigned[0]->id !== $matchRole || $assigned[0]->source_project_actor_role_id !== null || $assigned[0]->display_name_snapshot !== 'Official ' . $name) throw new RuntimeException('The historical match snapshot did not survive project-role removal.');
	$repository->removeFromMatch($match, $matchRole);
	$repository->removeFromMatch($match, $delegateMatchRole);
	$removed = array_filter($repository->getMatchAssignments($match), static fn (object $row): bool => in_array((int) $row->id, [$matchRole, $delegateMatchRole], true) && (int) $row->published === 0);
	if (count($removed) !== 2) throw new RuntimeException('Removed match assignments did not remain as inactive historical nominations.');
	echo "Competition-role ownership, replacement chain, delegation status and immutable nominations OK\n";
} finally {
	foreach ([$project, $foreignProject] as $projectId) { $query = $database->getQuery(true)->delete($database->quoteName('#__joomleague_project'))->where('id=' . $projectId); $database->setQuery($query)->execute(); }
	foreach ([['#__joomleague_sport_type', $sport], ['#__joomleague_competition', $competition], ['#__joomleague_season', $season], ['#__joomleague_team', $team], ['#__joomleague_team', $opponentTeam], ['#__joomleague_person', $person], ['#__joomleague_person', $delegate], ['#__joomleague_person', $expiredPerson], ['#__joomleague_person', $assistantOne], ['#__joomleague_person', $assistantTwo], ['#__joomleague_person', $emergency], ['#__joomleague_club', $club], ['#__joomleague_club', $opponentClub]] as [$table, $id]) {
		$query = $database->getQuery(true)->delete($database->quoteName($table))->where('id=' . (int) $id); $database->setQuery($query)->execute();
	}
}
