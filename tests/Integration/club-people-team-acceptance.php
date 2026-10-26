<?php
declare(strict_types=1);

if (!defined('_JEXEC') || PHP_SAPI !== 'cli' || !str_starts_with((string) getenv('JL_R1_DB_HOST'), 'jl-r1-')) {
    throw new RuntimeException('Disposable history fixture required');
}

$peopleScope = (static function () use ($db, $reader, $insert, $a, $assert, $componentId): array {
    $clubId = $insert('club', ['name' => 'Member scope club']);
    $firstTeam = $insert('team', ['club_id' => $clubId, 'name' => 'First squad']);
    $reserveTeam = $insert('team', ['club_id' => $clubId, 'name' => 'Reserves <img src=x onerror=alert(1)>']);
    $entries = [];
    foreach ([$firstTeam, $reserveTeam] as $teamId) {
        $entries[$teamId] = $insert('project_entry', ['project_id' => $a['id'], 'entry_kind' => 'team', 'team_id' => $teamId]);
    }
    $people = [];
    for ($i = 0; $i < 35; $i++) {
        $people[$i] = $insert('person', ['first_name' => 'Member', 'last_name' => sprintf('SquadPerson%02d', $i)]);
        $teamId = $i < 30 ? $firstTeam : $reserveTeam;
        $insert('project_entry_member', ['entry_id' => $entries[$teamId], 'person_id' => $people[$i], 'member_person_type' => 'player', 'role_code' => 'player', 'valid_from' => $i < 30 ? '2020-01-01' : '2023-01-01']);
    }
    $insert('project_entry_member', ['entry_id' => $entries[$reserveTeam], 'person_id' => $people[0], 'member_person_type' => 'player', 'role_code' => 'player', 'valid_from' => '2023-01-01']);
    foreach ([$firstTeam, $firstTeam, $reserveTeam] as $index => $teamId) {
        $matchId = $insert('project_match', ['project_id' => $a['id'], 'stage_id' => $a['stage'], 'round_id' => $a['round'], 'scheduled_start' => '2026-01-0' . ($index + 1) . ' 12:00:00']);
        $participant = $insert('match_participant', ['match_id' => $matchId, 'project_id' => $a['id'], 'project_entry_id' => $entries[$teamId], 'slot_number' => 1]);
        $insert('match_participant', ['match_id' => $matchId, 'project_id' => $a['id'], 'project_entry_id' => $a['entries'][1], 'slot_number' => 2]);
        $insert('match_result', ['match_id' => $matchId, 'result_type' => 'score', 'status_code' => 'final'], false);
        $lineupId = $insert('match_lineup_member', ['match_id' => $matchId, 'match_participant_id' => $participant, 'person_id' => $people[0], 'member_person_type' => 'player', 'role_code' => 'player']);
        $insert('match_event', ['match_id' => $matchId, 'project_id' => $a['id'], 'match_participant_id' => $participant, 'event_code' => 'goal', 'event_name_key' => 'COM_JOOMLEAGUE_EVENT_GOAL', 'sequence_number' => 1, 'primary_person_id' => $people[0], 'primary_lineup_member_id' => $lineupId, 'primary_name_snapshot' => 'SquadPerson00 Member', 'profile_metadata_json' => '{"statistic_code":"goals"}']);
    }
    $first = $reader->read($clubId, ['team_id' => $firstTeam], [1]);
    $reserve = $reader->read($clubId, ['team_id' => $reserveTeam], [1]);
    $assert($first['pagination_state']['total'] === 30 && $reserve['pagination_state']['total'] === 6, 'Membership totals mixed club squads');
    $assert($first['people'][0]['appearances'] === 2 && $reserve['people'][0]['appearances'] === 1, 'Appearances crossed team scope');
    $assert($first['people'][0]['metrics']['goals'] === 2 && $reserve['people'][0]['metrics']['goals'] === 1, 'Event statistics crossed team scope');
    $assert($first['people'][0]['first_date'] === '2020-01-01' && $reserve['people'][0]['first_date'] === '2023-01-01', 'Membership history crossed team scope');
    $assert($reader->read($clubId, [], [1])['pagination_state']['total'] === 35, 'Club aggregate duplicated a member of two squads');
    $assert(count($reader->read($clubId, ['team_id' => $firstTeam, 'start' => 25], [1])['people']) === 5, 'Team-scoped pagination failed');
    $assert($reader->read($clubId, ['team_id' => $firstTeam, 'search' => 'SquadPerson34'], [1])['people'] === [], 'Search escaped selected team');
    $foreignTeam = (int) $db->setQuery('SELECT team_id FROM #__joomleague_project_entry WHERE id=' . (int) $a['entries'][1])->loadResult();
    $assert($reader->read($clubId, ['team_id' => $foreignTeam], [1])['people'] === [], 'Foreign-club team leaked members');
    $teamRow = (object) ['id' => $reserveTeam, 'access' => 2];
    $db->updateObject('#__joomleague_team', $teamRow, 'id');
    $private = $reader->read($clubId, ['team_id' => $reserveTeam], [1]);
    $assert($private['people'] === [] && $private['metric_labels'] === [] && !isset($private['options']['teams'][$reserveTeam]), 'Private squad leaked names, metrics or selector option');
    $assert($reader->read($clubId, ['team_id' => $reserveTeam], [1, 2])['pagination_state']['total'] === 6, 'Authorised private squad inaccessible');
    $teamRow->access = 1; $db->updateObject('#__joomleague_team', $teamRow, 'id');
    foreach (['project_stage' => $a['stage'], 'project_round' => $a['round']] as $table => $id) {
        $row = (object) ['id' => $id, 'published' => 0]; $db->updateObject('#__joomleague_' . $table, $row, 'id');
        $hidden = $reader->read($clubId, ['team_id' => $firstTeam], [1]);
        $assert($hidden['pagination_state']['total'] === 30 && $hidden['people'][0]['appearances'] === 0 && $hidden['metric_labels'] === [], 'Hidden event parent leaked appearances or metrics');
        $row->published = 1; $db->updateObject('#__joomleague_' . $table, $row, 'id');
    }
    $menu = new Joomla\CMS\Table\Menu($db);
    $menu->setLocation(1, 'last-child');
    $assert($menu->bind(['menutype' => 'mainmenu', 'title' => 'Club members by team', 'alias' => 'members-by-team', 'type' => 'component', 'link' => 'index.php?option=com_joomleague&view=clubpeople&club_id=' . $clubId . '&team_id=' . $firstTeam, 'component_id' => $componentId, 'published' => 1, 'access' => 1, 'language' => '*', 'client_id' => 0, 'params' => '{"enable_column_sorting":1}']) && $menu->check() && $menu->store(), 'Cannot persist team-scoped members menu');
    return ['menu_id' => (int) $menu->id, 'club_id' => $clubId, 'first_team' => $firstTeam, 'reserve_team' => $reserveTeam, 'foreign_team' => $foreignTeam];
})();
file_put_contents('/tmp/jl-people-team.json', json_encode($peopleScope, JSON_THROW_ON_ERROR));
echo "PASS: member squads, shared person deduplication, team-scoped dates/metrics/paging/search, hidden parent and private/foreign team ACL\n";
