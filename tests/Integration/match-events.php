<?php

declare(strict_types=1);

define('_JEXEC', 1);
define('JPATH_BASE', '/var/www/html');
require_once JPATH_BASE . '/includes/defines.php';
require_once JPATH_BASE . '/includes/framework.php';
foreach (['UuidFactory.php', 'EntryModelValidator.php', 'MatchEventRepository.php', 'EventRankingReader.php'] as $service) require_once JPATH_ADMINISTRATOR . '/components/com_joomleague/src/Service/' . $service;

use Joomla\CMS\Factory;
use Joomla\Database\DatabaseInterface;
use Joomleague\Component\Joomleague\Administrator\Service\MatchEventRepository;
use Joomleague\Component\Joomleague\Domain\Service\UuidFactory;
use Joomleague\Component\Joomleague\Domain\Service\EventRankingReader;

$database = Factory::getContainer()->get(DatabaseInterface::class);
$uuid = static fn (): string => UuidFactory::v4();
$insert = static function (string $table, array $data) use ($database): int {
	$query = $database->getQuery(true)->insert($database->quoteName($table))->columns($database->quoteName(array_keys($data))); $values = [];
	foreach ($data as $key => &$value) { $values[] = ':' . $key; $query->bind(':' . $key, $value); }
	$query->values(implode(',', $values)); $database->setQuery($query)->execute(); return (int) $database->insertid();
};
$name = 'Match events fixture ' . bin2hex(random_bytes(3));
$profileVersion = (int) $database->setQuery($database->getQuery(true)->select('v.id')->from($database->quoteName('#__joomleague_sport_profile_version', 'v'))->innerJoin($database->quoteName('#__joomleague_sport_profile', 'p') . ' ON p.id=v.profile_id')->where('p.code=' . $database->quote('football'))->where('v.state=' . $database->quote('active')))->loadResult();
$competition = $insert('#__joomleague_competition', ['uuid' => $uuid(), 'name' => $name, 'published' => 1, 'access' => 1]);
$season = $insert('#__joomleague_season', ['uuid' => $uuid(), 'name' => $name, 'published' => 1, 'access' => 1]);
$sport = $insert('#__joomleague_sport_type', ['profile_version_id' => $profileVersion, 'code' => 'event-' . bin2hex(random_bytes(3)), 'name' => $name]);
$project = $insert('#__joomleague_project', ['uuid' => $uuid(), 'competition_id' => $competition, 'season_id' => $season, 'sport_type_id' => $sport, 'profile_version_id' => $profileVersion, 'name' => $name, 'project_type' => 'league', 'timezone' => 'Europe/Prague', 'published' => 1, 'access' => 1]);
$club = $insert('#__joomleague_club', ['uuid' => $uuid(), 'name' => $name]);
$teams = [$insert('#__joomleague_team', ['uuid' => $uuid(), 'club_id' => $club, 'name' => $name . ' A']), $insert('#__joomleague_team', ['uuid' => $uuid(), 'club_id' => $club, 'name' => $name . ' B'])];
$people = [];
foreach (['Primary', 'Second', 'Opponent'] as $personName) $people[] = $insert('#__joomleague_person', ['uuid' => $uuid(), 'first_name' => $personName, 'last_name' => $name, 'published' => 1, 'access' => 1]);

try {
	$entries = [$insert('#__joomleague_project_entry', ['uuid' => $uuid(), 'project_id' => $project, 'entry_kind' => 'team', 'team_id' => $teams[0], 'display_name' => $name . ' A']), $insert('#__joomleague_project_entry', ['uuid' => $uuid(), 'project_id' => $project, 'entry_kind' => 'team', 'team_id' => $teams[1], 'display_name' => $name . ' B'])];
	$stage = $insert('#__joomleague_project_stage', ['uuid' => $uuid(), 'project_id' => $project, 'name' => 'League', 'code' => 'league', 'stage_type' => 'league']);
	$round = $insert('#__joomleague_project_round', ['uuid' => $uuid(), 'project_id' => $project, 'stage_id' => $stage, 'name' => 'Round 1', 'code' => 'round_1', 'round_type' => 'regular', 'sequence_number' => 1]);
	$match = $insert('#__joomleague_project_match', ['uuid' => $uuid(), 'project_id' => $project, 'stage_id' => $stage, 'round_id' => $round, 'match_number' => '1', 'contest_type' => 'head_to_head', 'published' => 1]);
	$secondMatch = $insert('#__joomleague_project_match', ['uuid' => $uuid(), 'project_id' => $project, 'stage_id' => $stage, 'round_id' => $round, 'match_number' => '2', 'contest_type' => 'head_to_head']);
	$participants = [$insert('#__joomleague_match_participant', ['uuid' => $uuid(), 'match_id' => $match, 'project_id' => $project, 'project_entry_id' => $entries[0], 'slot_number' => 1]), $insert('#__joomleague_match_participant', ['uuid' => $uuid(), 'match_id' => $match, 'project_id' => $project, 'project_entry_id' => $entries[1], 'slot_number' => 2])];
	$foreignParticipant = $insert('#__joomleague_match_participant', ['uuid' => $uuid(), 'match_id' => $secondMatch, 'project_id' => $project, 'project_entry_id' => $entries[0], 'slot_number' => 1]);
	$lineup = [
		$insert('#__joomleague_match_lineup_member', ['uuid' => $uuid(), 'match_id' => $match, 'match_participant_id' => $participants[0], 'person_id' => $people[0], 'member_person_type' => 'player', 'role_code' => 'forward']),
		$insert('#__joomleague_match_lineup_member', ['uuid' => $uuid(), 'match_id' => $match, 'match_participant_id' => $participants[0], 'person_id' => $people[1], 'member_person_type' => 'player', 'role_code' => 'midfielder']),
		$insert('#__joomleague_match_lineup_member', ['uuid' => $uuid(), 'match_id' => $match, 'match_participant_id' => $participants[1], 'person_id' => $people[2], 'member_person_type' => 'player', 'role_code' => 'forward']),
	];
	$repository = new MatchEventRepository($database);
	try { $repository->add($match, ['event_code' => 'unknown'], 0); throw new RuntimeException('An event outside the immutable profile was accepted.'); } catch (InvalidArgumentException) {}
	try { $repository->add($match, ['event_code' => 'goal', 'match_participant_id' => $foreignParticipant], 0); throw new RuntimeException('A participant from another match was accepted.'); } catch (InvalidArgumentException) {}
	try { $repository->add($match, ['event_code' => 'assist', 'primary_lineup_member_id' => $lineup[0]], 0); throw new RuntimeException('An event requiring a second person was accepted without one.'); } catch (InvalidArgumentException) {}
	try { $repository->add($match, ['event_code' => 'assist', 'primary_lineup_member_id' => $lineup[0], 'secondary_lineup_member_id' => $lineup[2]], 0); throw new RuntimeException('A second person from another participant was accepted.'); } catch (InvalidArgumentException) {}
	$goal = $repository->add($match, ['event_code' => 'goal', 'match_participant_id' => $participants[0], 'primary_lineup_member_id' => $lineup[0], 'phase_code' => 'period', 'phase_sequence' => 1, 'clock_value' => '12.500', 'clock_unit' => 'minutes'], 0);
	$assist = $repository->add($match, ['event_code' => 'assist', 'primary_lineup_member_id' => $lineup[0], 'secondary_lineup_member_id' => $lineup[1]], 0);
	$system = $repository->add($match, ['event_code' => 'match_started', 'occurred_at' => '2026-08-11T18:00'], 0);
	$insert('#__joomleague_match_result', ['uuid' => $uuid(), 'match_id' => $match, 'result_type' => 'numeric_score', 'status_code' => 'final', 'outcome_code' => 'completed']);
	try { $repository->add($match, ['event_code' => 'match_started', 'match_participant_id' => $participants[0]], 0); throw new RuntimeException('A system event was assigned to a participant.'); } catch (InvalidArgumentException) {}
	$events = $repository->getEvents($match);
	if (array_map(static fn (object $event): int => (int) $event->id, $events) !== [$goal, $assist, $system] || $events[0]->event_code !== 'goal' || $events[0]->primary_name_snapshot !== 'Primary ' . $name || $events[1]->secondary_name_snapshot !== 'Second ' . $name || $events[2]->occurred_at !== '2026-08-11 18:00:00') throw new RuntimeException('Stored event ordering or snapshots are invalid.');
	if (!str_contains((string) $events[0]->profile_metadata_json, 'COM_JOOMLEAGUE_PROFILE_FOOTBALL_EVENT_GOAL')) throw new RuntimeException('The immutable event metadata snapshot was not stored.');
	$ranking = (new EventRankingReader($database))->forProject($project, 'goal', 10, [1]);
	if (count($ranking['rows']) !== 1 || (int) $ranking['rows'][0]->target_id !== $people[0] || (int) $ranking['rows'][0]->total_value !== 1) throw new RuntimeException('The person event ranking is invalid.');
	try { $repository->remove($secondMatch, $goal); throw new RuntimeException('Another match removed the event.'); } catch (InvalidArgumentException) {}
	$repository->remove($match, $goal); $repository->remove($match, $assist); $repository->remove($match, $system);
	if ($repository->getEvents($match) !== []) throw new RuntimeException('Owned match events were not removed.');
	echo "Profile-defined match events, ownership, timeline and snapshots OK\n";
} finally {
	$query = $database->getQuery(true)->delete($database->quoteName('#__joomleague_project'))->where('id=' . $project); $database->setQuery($query)->execute();
	foreach ([['#__joomleague_sport_type', $sport], ['#__joomleague_competition', $competition], ['#__joomleague_season', $season], ['#__joomleague_team', $teams[0]], ['#__joomleague_team', $teams[1]], ['#__joomleague_person', $people[0]], ['#__joomleague_person', $people[1]], ['#__joomleague_person', $people[2]], ['#__joomleague_club', $club]] as [$table, $id]) { $query = $database->getQuery(true)->delete($database->quoteName($table))->where('id=' . (int) $id); $database->setQuery($query)->execute(); }
}
