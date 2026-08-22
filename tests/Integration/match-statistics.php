<?php

declare(strict_types=1);

define('_JEXEC', 1);
define('JPATH_BASE', '/var/www/html');
require_once JPATH_BASE . '/includes/defines.php';
require_once JPATH_BASE . '/includes/framework.php';
foreach (['UuidFactory.php', 'EntryModelValidator.php', 'MatchResultDuration.php', 'MatchStatisticRepository.php'] as $service) require_once JPATH_ADMINISTRATOR . '/components/com_joomleague/src/Service/' . $service;

use Joomla\CMS\Factory;
use Joomla\Database\DatabaseInterface;
use Joomleague\Component\Joomleague\Administrator\Service\MatchStatisticRepository;
use Joomleague\Component\Joomleague\Domain\Service\UuidFactory;

$database = Factory::getContainer()->get(DatabaseInterface::class);
$uuid = static fn (): string => UuidFactory::v4();
$insert = static function (string $table, array $data) use ($database): int {
	$query = $database->getQuery(true)->insert($database->quoteName($table))->columns($database->quoteName(array_keys($data))); $values = [];
	foreach ($data as $key => &$value) { $values[] = ':' . $key; $query->bind(':' . $key, $value); }
	$query->values(implode(',', $values)); $database->setQuery($query)->execute(); return (int) $database->insertid();
};
$profileId = static function (string $code) use ($database): int {
	return (int) $database->setQuery($database->getQuery(true)->select('v.id')->from($database->quoteName('#__joomleague_sport_profile_version', 'v'))->innerJoin($database->quoteName('#__joomleague_sport_profile', 'p') . ' ON p.id=v.profile_id')->where('p.code=' . $database->quote($code))->where('v.state=' . $database->quote('active')))->loadResult();
};
$name = 'Match statistics fixture ' . bin2hex(random_bytes(3));
$competition = $insert('#__joomleague_competition', ['uuid' => $uuid(), 'name' => $name]);
$season = $insert('#__joomleague_season', ['uuid' => $uuid(), 'name' => $name]);
$club = $insert('#__joomleague_club', ['uuid' => $uuid(), 'name' => $name]);
$teams = [$insert('#__joomleague_team', ['uuid' => $uuid(), 'club_id' => $club, 'name' => $name . ' A']), $insert('#__joomleague_team', ['uuid' => $uuid(), 'club_id' => $club, 'name' => $name . ' B'])];
$people = [$insert('#__joomleague_person', ['uuid' => $uuid(), 'first_name' => 'Player', 'last_name' => $name]), $insert('#__joomleague_person', ['uuid' => $uuid(), 'first_name' => 'Runner', 'last_name' => $name])];
$sports = []; $projects = [];

$createMatch = static function (string $profileCode, string $entryKind, array $targetIds) use ($insert, $profileId, $competition, $season, $name, $uuid, &$sports, &$projects): array {
	$profile = $profileId($profileCode); $sport = $insert('#__joomleague_sport_type', ['profile_version_id' => $profile, 'code' => 'stat-' . $profileCode . '-' . bin2hex(random_bytes(2)), 'name' => $name . ' ' . $profileCode]); $sports[] = $sport;
	$project = $insert('#__joomleague_project', ['uuid' => $uuid(), 'competition_id' => $competition, 'season_id' => $season, 'sport_type_id' => $sport, 'profile_version_id' => $profile, 'name' => $name . ' ' . $profileCode, 'project_type' => 'league', 'timezone' => 'UTC']); $projects[] = $project;
	$entries = [];
	foreach ($targetIds as $index => $targetId) $entries[] = $insert('#__joomleague_project_entry', ['uuid' => $uuid(), 'project_id' => $project, 'entry_kind' => $entryKind, $entryKind . '_id' => $targetId, 'display_name' => $name . ' entry ' . ($index + 1)]);
	$stage = $insert('#__joomleague_project_stage', ['uuid' => $uuid(), 'project_id' => $project, 'name' => 'Stage', 'code' => 'stage', 'stage_type' => 'league']);
	$round = $insert('#__joomleague_project_round', ['uuid' => $uuid(), 'project_id' => $project, 'stage_id' => $stage, 'name' => 'Round', 'code' => 'round', 'round_type' => 'regular', 'sequence_number' => 1]);
	$match = $insert('#__joomleague_project_match', ['uuid' => $uuid(), 'project_id' => $project, 'stage_id' => $stage, 'round_id' => $round, 'match_number' => '1', 'contest_type' => 'head_to_head']);
	$participants = [];
	foreach ($entries as $index => $entry) $participants[] = $insert('#__joomleague_match_participant', ['uuid' => $uuid(), 'match_id' => $match, 'project_id' => $project, 'project_entry_id' => $entry, 'slot_number' => $index + 1]);
	return compact('sport', 'project', 'match', 'participants');
};

try {
	$repository = new MatchStatisticRepository($database);
	$football = $createMatch('football', 'team', $teams);
	$cornerId = $repository->save($football['match'], ['statistic_code' => 'corners', 'target' => 'participant:' . $football['participants'][0], 'value' => '5'], 0);
	$updatedId = $repository->save($football['match'], ['statistic_code' => 'corners', 'target' => 'participant:' . $football['participants'][0], 'value' => '7'], 0);
	if ($cornerId !== $updatedId) throw new RuntimeException('Saving one statistic target did not update the existing value.');
	$possessionId = $repository->save($football['match'], ['statistic_code' => 'possession', 'target' => 'participant:' . $football['participants'][1], 'value' => '52.75'], 0);
	try { $repository->save($football['match'], ['statistic_code' => 'possession', 'target' => 'participant:' . $football['participants'][0], 'value' => '100.1'], 0); throw new RuntimeException('An invalid percentage was accepted.'); } catch (InvalidArgumentException) {}
	try { $repository->save($football['match'], ['statistic_code' => 'goals', 'target' => 'participant:' . $football['participants'][0], 'value' => '1'], 0); throw new RuntimeException('An event-sourced statistic was accepted manually.'); } catch (InvalidArgumentException) {}

	$basketball = $createMatch('basketball', 'team', $teams);
	$lineup = $insert('#__joomleague_match_lineup_member', ['uuid' => $uuid(), 'match_id' => $basketball['match'], 'match_participant_id' => $basketball['participants'][0], 'person_id' => $people[0], 'member_person_type' => 'player', 'role_code' => 'point_guard']);
	$reboundId = $repository->save($basketball['match'], ['statistic_code' => 'rebounds', 'target' => 'person:' . $lineup, 'value' => '9'], 0);
	try { $repository->save($basketball['match'], ['statistic_code' => 'rebounds', 'target' => 'participant:' . $basketball['participants'][0], 'value' => '9'], 0); throw new RuntimeException('A participant target was accepted for a player statistic.'); } catch (InvalidArgumentException) {}
	$database->setQuery($database->getQuery(true)->delete($database->quoteName('#__joomleague_match_lineup_member'))->where('id=' . $lineup))->execute();
	$historicalPersonValue = array_values(array_filter($repository->getValues($basketball['match']), static fn (object $row): bool => (int) $row->id === $reboundId))[0] ?? null;
	if (!$historicalPersonValue || $historicalPersonValue->lineup_member_id !== null || (int) $historicalPersonValue->person_id !== $people[0]) throw new RuntimeException('Removing a lineup assignment did not preserve its historical statistic snapshot.');

	$running = $createMatch('running_race', 'person', [$people[1]]);
	$gunTimeId = $repository->save($running['match'], ['statistic_code' => 'gun_time', 'target' => 'participant:' . $running['participants'][0], 'value' => '1:02.345'], 0);
	$runningValues = $repository->getValues($running['match']);
	if (count($runningValues) !== 1 || (int) $runningValues[0]->id !== $gunTimeId || $runningValues[0]->numeric_value !== '62345.000000000' || $runningValues[0]->value_type !== 'duration') throw new RuntimeException('A duration statistic was not stored as exact milliseconds.');
	$footballValues = $repository->getValues($football['match']);
	if (count($footballValues) !== 2 || !in_array($cornerId, array_map(static fn (object $row): int => (int) $row->id, $footballValues), true)) throw new RuntimeException('Stored football statistic values are invalid.');
	$corner = array_values(array_filter($footballValues, static fn (object $row): bool => (int) $row->id === $cornerId))[0];
	if ($corner->numeric_value !== '7.000000000' || $corner->target_name_snapshot !== $name . ' entry 1' || !str_contains($corner->profile_metadata_json, 'COM_JOOMLEAGUE_PROFILE_FOOTBALL_STAT_CORNERS')) throw new RuntimeException('The updated value or immutable snapshots are invalid.');
	try { $repository->remove($basketball['match'], $cornerId); throw new RuntimeException('Another match removed a statistic value.'); } catch (InvalidArgumentException) {}
	foreach ([[$football['match'], $cornerId], [$football['match'], $possessionId], [$basketball['match'], $reboundId], [$running['match'], $gunTimeId]] as [$match, $id]) $repository->remove($match, $id);
	echo "Profile-defined team, player and participant statistics with exact values and upsert semantics OK\n";
} finally {
	foreach ($projects as $project) { $query = $database->getQuery(true)->delete($database->quoteName('#__joomleague_project'))->where('id=' . $project); $database->setQuery($query)->execute(); }
	foreach ($sports as $sport) { $query = $database->getQuery(true)->delete($database->quoteName('#__joomleague_sport_type'))->where('id=' . $sport); $database->setQuery($query)->execute(); }
	foreach ([['#__joomleague_competition', $competition], ['#__joomleague_season', $season], ['#__joomleague_team', $teams[0]], ['#__joomleague_team', $teams[1]], ['#__joomleague_person', $people[0]], ['#__joomleague_person', $people[1]], ['#__joomleague_club', $club]] as [$table, $id]) { $query = $database->getQuery(true)->delete($database->quoteName($table))->where('id=' . (int) $id); $database->setQuery($query)->execute(); }
}
