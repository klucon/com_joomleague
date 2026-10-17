<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli' || getenv('HISTORY_PG_HOST') !== 'jl-history-pg-test') exit(1);
define('_JEXEC', 1);
define('JPATH_BASE', '/var/www/html');
require JPATH_BASE . '/includes/defines.php';
require JPATH_BASE . '/includes/framework.php';
require '/tmp/jl-history-reader.php';
require JPATH_ADMINISTRATOR . '/components/com_joomleague/src/Service/StandingsDecimal.php';
$db = (new Joomla\Database\DatabaseFactory())->getDriver('pgsql', ['host' => getenv('HISTORY_PG_HOST'), 'user' => 'postgres', 'password' => getenv('HISTORY_PG_PASSWORD'), 'database' => 'history_test', 'prefix' => 'history_']);
foreach ($db->splitSql(file_get_contents(JPATH_ADMINISTRATOR . '/components/com_joomleague/sql/install.postgresql.sql')) as $sql) if (trim($sql) !== '') $db->setQuery($sql)->execute();
$insert = static function (string $table, array $data, bool $uuid = true) use ($db): int {
    if ($uuid) { $data['uuid'] = sprintf('%s-%s-4%s-8%s-%s', bin2hex(random_bytes(4)), bin2hex(random_bytes(2)), substr(bin2hex(random_bytes(2)), 1), substr(bin2hex(random_bytes(2)), 1), bin2hex(random_bytes(6))); $data['published'] ??= 1; }
    $object = (object) $data;
    $db->insertObject('#__joomleague_' . $table, $object, 'id');
    return (int) $object->id;
};
$assert = static function (bool $ok, string $message): void { if (!$ok) throw new RuntimeException($message); };
$profile = $insert('sport_profile', ['code' => 'history_test', 'name_key' => 'History', 'description_key' => 'History'], false);
$payload = json_encode(['match' => ['score' => ['higher_is_better' => true]], 'positions' => [['code' => 'coach', 'tracks_team_record' => true], ['code' => 'assistant', 'tracks_team_record' => false]]]);
$version = $insert('sport_profile_version', ['profile_id' => $profile, 'schema_version' => '1', 'profile_version' => '1', 'payload_json' => $payload, 'payload_checksum' => hash('sha256', $payload)], false);
$competition = $insert('competition', ['name' => 'History']);
$season = $insert('season', ['name' => 'History']);
$sport = $insert('sport_type', ['name' => 'History', 'code' => 'history_test', 'profile_version_id' => $version], false);
$club = $insert('club', ['name' => 'History']);
$team = $insert('team', ['name' => 'Home', 'club_id' => $club]);
$away = $insert('team', ['name' => 'Away']);
$project = $insert('project', ['name' => 'History', 'competition_id' => $competition, 'season_id' => $season, 'sport_type_id' => $sport, 'profile_version_id' => $version, 'project_type' => 'league']);
$entry = $insert('project_entry', ['project_id' => $project, 'entry_kind' => 'team', 'team_id' => $team]);
$opponent = $insert('project_entry', ['project_id' => $project, 'entry_kind' => 'team', 'team_id' => $away]);
$stage = $insert('project_stage', ['project_id' => $project, 'name' => 'History', 'code' => 'history', 'stage_type' => 'league']);
$round = $insert('project_round', ['project_id' => $project, 'stage_id' => $stage, 'name' => 'History', 'code' => 'history', 'sequence_number' => 1]);
$match = $insert('project_match', ['project_id' => $project, 'stage_id' => $stage, 'round_id' => $round, 'contest_type' => 'head_to_head']);
$homeParticipant = $insert('match_participant', ['project_id' => $project, 'match_id' => $match, 'project_entry_id' => $entry, 'slot_number' => 1]);
$awayParticipant = $insert('match_participant', ['project_id' => $project, 'match_id' => $match, 'project_entry_id' => $opponent, 'slot_number' => 2]);
// Result/score tables have no publication column.
$result = (object) ['uuid' => bin2hex(random_bytes(18)), 'match_id' => $match, 'result_type' => 'score', 'status_code' => 'final'];
$db->insertObject('#__joomleague_match_result', $result, 'id');
$segment = (object) ['uuid' => bin2hex(random_bytes(18)), 'match_id' => $match, 'level_code' => 'match', 'sequence_number' => 1];
$db->insertObject('#__joomleague_match_score_segment', $segment, 'id');
foreach ([$homeParticipant => 3, $awayParticipant => 2] as $participant => $score) {
    $value = (object) ['uuid' => bin2hex(random_bytes(18)), 'match_id' => $match, 'segment_id' => $segment->id, 'participant_id' => $participant, 'numeric_value' => $score];
    $db->insertObject('#__joomleague_match_score_value', $value, 'id');
}
foreach (['coach', 'assistant', 'player'] as $role) {
    $person = $insert('person', ['first_name' => 'Test', 'last_name' => $role]);
    $type = $role === 'player' ? 'player' : 'staff';
    $insert('project_entry_member', ['entry_id' => $entry, 'person_id' => $person, 'member_person_type' => $type, 'role_code' => $role]);
    $insert('match_lineup_member', ['match_id' => $match, 'match_participant_id' => $homeParticipant, 'person_id' => $person, 'member_person_type' => $type, 'role_code' => $role]);
}
$reader = new Joomleague\Component\Joomleague\Domain\Service\ClubPersonHistoryReader($db);
$data = $reader->read($club, [], [1]);
$assert(count($data['people']) === 3, 'People missing.');
foreach ($data['people'] as $person) {
    $record = $person['manager_record'];
    if ($person['last_name'] === 'coach') {
        $assert($record['played'] === 1 && $record['wins'] === 1 && $record['matches'] === [], 'Coach summary incorrect.');
        $detail=$reader->read($club,['manager_id'=>$person['person_id']],[1]);
        $assert(count($detail['selected_manager']['manager_record']['matches'])===1,'Coach detail incorrect.');
    }
    else $assert($record === null, 'Non-tracking role counted.');
}
$assert(count($reader->read($club, ['person_type' => 'staff'], [1])['people']) === 2, 'Staff filter incorrect.');
$db->setQuery('UPDATE #__joomleague_project SET access=2')->execute();
$assert($reader->read($club, [], [1])['people'] === [], 'Restricted project exposed.');
$assert(count($reader->read($club, [], [1,2])['people']) === 3, 'Authorized project missing.');
echo "PASS: PostgreSQL fresh schema, coach record, excluded roles, staff filter and project ACL.\n";
