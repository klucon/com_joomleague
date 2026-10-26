<?php
declare(strict_types=1);
if (!defined('_JEXEC') || PHP_SAPI !== 'cli' || $db->getPrefix() !== 'r1_') throw new RuntimeException('Disposable fixture required');

$scopeFixture = (static function () use ($db, $reader, $insert, $a): array {
    $checks = [];
    $check = static function (bool $ok, string $name) use (&$checks): void { if (!$ok) throw new RuntimeException($name); $checks[] = $name; };
    $club = $insert('club', ['name' => 'Rival scope own club']);
    $opponent = $insert('club', ['name' => 'Rival scope opponent club']);
    $teams = [];
    foreach (['own' => $club, 'opponent' => $opponent] as $side => $owner) {
        foreach (['first', 'reserve'] as $squad) $teams[$side . '_' . $squad] = $insert('team', ['club_id' => $owner, 'name' => ucfirst($side) . ' ' . $squad]);
    }
    $baseProject = $db->setQuery('SELECT * FROM #__joomleague_project WHERE id=' . (int) $a['id'])->loadAssoc();
    $projects = [];
    foreach (['league1' => ['League 1', 'league'], 'league4' => ['League 4 <img src=x onerror=alert(1)>', 'league'], 'cup' => ['Cup', 'cup']] as $code => [$title, $type]) {
        $competition = $insert('competition', ['name' => $title]);
        $project = $insert('project', ['name' => $title . ' season', 'competition_id' => $competition, 'season_id' => $baseProject['season_id'], 'sport_type_id' => $baseProject['sport_type_id'], 'profile_version_id' => $baseProject['profile_version_id'], 'project_type' => $type, 'timezone' => 'UTC']);
        $stage = $insert('project_stage', ['project_id' => $project, 'name' => $title . ' stage', 'code' => 'r104_' . $code, 'stage_type' => 'league_phase']);
        $round = $insert('project_round', ['project_id' => $project, 'stage_id' => $stage, 'name' => $title . ' round', 'code' => 'r104_round', 'sequence_number' => 1]);
        $entries = [];
        foreach ($teams as $key => $team) $entries[$key] = $insert('project_entry', ['project_id' => $project, 'entry_kind' => 'team', 'team_id' => $team]);
        $projects[$code] = compact('project', 'stage', 'round', 'entries', 'competition');
    }
    $create = static function (string $competition, string $own, string $other, int $scored, int $conceded, bool $away, string $date) use ($insert, $projects): int {
        $p = $projects[$competition];
        $match = $insert('project_match', ['project_id' => $p['project'], 'stage_id' => $p['stage'], 'round_id' => $p['round'], 'scheduled_start' => $date, 'timezone' => 'UTC']);
        $insert('match_result', ['match_id' => $match, 'result_type' => 'numeric_score', 'status_code' => 'final'], false);
        $root = $insert('match_score_segment', ['match_id' => $match, 'level_code' => 'result', 'sequence_number' => 1], false);
        foreach ([[$own, $away ? 2 : 1, $scored], [$other, $away ? 1 : 2, $conceded]] as [$key, $slot, $score]) {
            $participant = $insert('match_participant', ['match_id' => $match, 'project_id' => $p['project'], 'project_entry_id' => $p['entries'][$key], 'slot_number' => $slot]);
            $insert('match_score_value', ['match_id' => $match, 'segment_id' => $root, 'participant_id' => $participant, 'numeric_value' => $score], false);
        }
        return $match;
    };
    $matches = [];
    for ($i = 0; $i < 30; $i++) {
        [$scored, $conceded] = [[2, 1], [0, 0], [1, 3]][$i % 3];
        $matches[] = $create('league1', 'own_first', 'opponent_first', $scored, $conceded, $i % 2 === 1, sprintf('2026-09-%02d 12:00:00', $i + 1));
    }
    $matches[] = $create('league4', 'own_first', 'opponent_first', 4, 1, true, '2026-10-01 12:00:00');
    $matches[] = $create('cup', 'own_first', 'opponent_first', 0, 1, false, '2026-10-02 12:00:00');
    $create('league1', 'own_first', 'opponent_reserve', 3, 0, false, '2026-10-03 12:00:00');
    $create('league1', 'own_reserve', 'opponent_first', 8, 0, false, '2026-10-04 12:00:00');
    $filters = ['team_id' => $teams['own_first'], 'opponent_team_id' => $teams['opponent_first']];
    $data = $reader->read($club, $filters, [1]);
    $record = $data['opponents'][0];
    $check([$record['played'], $record['wins'], $record['draws'], $record['losses'], $record['scored'], $record['conceded']] === [32, 11, 10, 11, '34', '42'], 'exact-pair-totals');
    $check(count($reader->read($club, [], [1])['opponents']) === 3, 'all-squads-remain-separate-pairs');
    $aggregate = $reader->read($club, ['grouping' => 'club'], [1]);
    $check(count($aggregate['opponents']) === 1 && $aggregate['opponents'][0]['played'] === 34, 'explicit-club-aggregate');
    $check(count($reader->read($club, ['team_id' => $teams['own_first']], [1])['opponents']) === 2, 'opponent-squads-remain-separate');
    $check(count($data['matches']) === 25 && $data['detail_state']['total'] === 32, 'bounded-detail-all-record-totals');
    $last = $reader->read($club, $filters + ['detail_start' => 999999], [1]);
    $check($last['detail_state']['start'] === 25 && count($last['matches']) === 7, 'detail-page-clamp');
    $check(count(array_intersect(array_column($data['matches'], 'match_id'), array_column($last['matches'], 'match_id'))) === 0, 'detail-pages-do-not-overlap');
    $check($data['breakdowns']['venue']['home']['played'] === 16 && $data['breakdowns']['venue']['away']['played'] === 16, 'home-away-counts');
    $check($data['breakdowns']['venue']['home']['scored'] === '15' && $data['breakdowns']['venue']['away']['scored'] === '19', 'home-away-own-perspective');
    $check(array_column(array_values($data['breakdowns']['competition']), 'played') === [1, 30, 1], 'competition-level-counts');
    $check($data['breakdowns']['project_type']['league']['played'] === 31 && $data['breakdowns']['project_type']['cup']['played'] === 1, 'league-cup-counts');
    foreach (['venue', 'competition', 'project_type'] as $dimension) $check(array_sum(array_column($data['breakdowns'][$dimension], 'played')) === 32, 'breakdown-reconciles-' . $dimension);
    $check($reader->read($club, $filters + ['project_type' => 'cup'], [1])['opponents'][0]['played'] === 1, 'competition-format-filter');
    $check($reader->read($club, $filters + ['project_id' => $projects['league1']['project']], [1])['opponents'][0]['played'] === 30, 'project-filter-independent-binding');
    $check($reader->read($club, $filters + ['season_id' => (int) $baseProject['season_id']], [1])['opponents'][0]['played'] === 32, 'season-filter-independent-binding');
    $check($reader->read($club, $filters + ['project_id' => $projects['league4']['project'], 'season_id' => (int) $baseProject['season_id']], [1])['opponents'][0]['played'] === 1, 'project-season-combined-binding');
    $check($reader->read($club, ['team_id' => $teams['opponent_first']], [1])['opponents'] === [], 'foreign-own-team-rejected');
    foreach (['project_stage' => $projects['league1']['stage'], 'project_round' => $projects['league1']['round'], 'competition' => $projects['league1']['competition'], 'project' => $projects['league1']['project']] as $table => $id) {
        $row = (object) ['id' => $id, 'published' => 0]; $db->updateObject('#__joomleague_' . $table, $row, 'id');
        $check($reader->read($club, $filters, [1])['opponents'][0]['played'] === 2, 'unpublished-' . $table . '-excluded');
        $row->published = 1; $db->updateObject('#__joomleague_' . $table, $row, 'id');
    }
    foreach (['team' => $teams['opponent_first'], 'club' => $opponent] as $table => $id) {
        $row = (object) ['id' => $id, 'access' => 2]; $db->updateObject('#__joomleague_' . $table, $row, 'id');
        $check($reader->read($club, $filters, [1])['opponents'] === [] && $reader->read($club, $filters, [1])['breakdowns']['venue'] === [], 'private-' . $table . '-excluded');
        $check($reader->read($club, $filters, [1, 2])['opponents'][0]['played'] === 32, 'authorised-' . $table . '-included');
        $row->access = 1; $db->updateObject('#__joomleague_' . $table, $row, 'id');
    }
    $target = $matches[0];
    $extra = $insert('match_participant', ['match_id' => $target, 'project_id' => $projects['league1']['project'], 'project_entry_id' => $projects['league1']['entries']['opponent_reserve'], 'slot_number' => 3]);
    $row = (object) ['id' => $teams['opponent_reserve'], 'access' => 2]; $db->updateObject('#__joomleague_team', $row, 'id');
    $check($reader->read($club, $filters, [1])['opponents'][0]['played'] === 31, 'hidden-third-participant-cannot-fake-pair');
    $row->access = 1; $db->updateObject('#__joomleague_team', $row, 'id');
    $retire = (object) ['id' => $extra, 'published' => 0]; $db->updateObject('#__joomleague_match_participant', $retire, 'id');
    $row = (object) ['id' => $target, 'status_code' => 'cancelled']; $db->updateObject('#__joomleague_project_match', $row, 'id');
    $check($reader->read($club, $filters, [1])['opponents'][0]['played'] === 31, 'cancelled-final-excluded');
    $row->status_code = 'scheduled'; $row->scheduled_start = null; $db->updateObject('#__joomleague_project_match', $row, 'id', true);
    $undated = $reader->read($club, $filters + ['detail_start' => 25], [1]);
    $check(end($undated['matches'])['match_id'] === $target && $undated['opponents'][0]['played'] === 32, 'undated-final-counted-and-sorted-last');
    $row->scheduled_start = '2026-09-01 12:00:00'; $db->updateObject('#__joomleague_project_match', $row, 'id');
    $componentId = (int) $db->setQuery("SELECT extension_id FROM #__extensions WHERE element='com_joomleague' AND type='component'")->loadResult();
    $menu = new Joomla\CMS\Table\Menu($db); $menu->setLocation(1, 'last-child');
    $check($menu->bind(['menutype' => 'mainmenu', 'title' => 'Rival scope settings', 'alias' => 'rival-scope', 'type' => 'component', 'link' => 'index.php?option=com_joomleague&view=clubrivals&club_id=' . $club . '&team_id=' . $teams['own_first'], 'component_id' => $componentId, 'published' => 1, 'access' => 1, 'language' => '*', 'client_id' => 0, 'params' => '{"rival_grouping":"team","rival_date_format":"compact","show_rival_breakdowns":1}']) && $menu->check() && $menu->store(), 'native-menu-persisted');
    file_put_contents('/tmp/jl-rival-scope-native.json', json_encode(['passed' => true, 'checks' => $checks], JSON_THROW_ON_ERROR));
    return ['club' => $club, 'opponent' => $opponent, 'teams' => $teams, 'projects' => $projects, 'menu' => (int) $menu->id];
})();
file_put_contents('/tmp/jl-rival-scope.json', json_encode($scopeFixture, JSON_THROW_ON_ERROR));
echo "PASS: rival scope, breakdowns, pagination and visibility acceptance.\n";
