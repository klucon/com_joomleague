<?php
declare(strict_types=1);
define('_JEXEC', 1);
define('JPATH_BASE', '/var/www/html');
require JPATH_BASE . '/includes/defines.php';
require JPATH_BASE . '/includes/framework.php';
foreach (['ExactDecimal', 'ProjectRuleValidator', 'ProjectRuleConfigRepository', 'StandingsContractValidator', 'RoleDomain', 'SportProfileSchemaValidator'] as $service) require_once JPATH_ADMINISTRATOR . '/components/com_joomleague/src/Service/' . $service . '.php';
foreach (['UuidFactory', 'CanonicalJson', 'EntryModelValidator', 'MatchLineupRepository', 'MatchResultValidationException', 'MatchResultDecimal', 'MatchResultAggregationValidator', 'EncounterPenaltySeries', 'TeamEncounterScoreNormalizer', 'TeamEncounterRosterValidator', 'TeamEncounterRepository', 'MatchResultPayloadValidator', 'MatchResultRepository', 'MatchResultEditorSchemaBuilder', 'MatchResultDuration', 'MatchResultFormStateBuilder', 'MatchResultEditorContext', 'MatchResultFormPayloadBuilder'] as $service) require_once JPATH_ADMINISTRATOR . '/components/com_joomleague/src/Service/' . $service . '.php';
use Joomla\CMS\Factory;
require_once JPATH_ADMINISTRATOR . '/components/com_joomleague/src/Service/StageEncounterRules.php';
require_once JPATH_ADMINISTRATOR . '/components/com_joomleague/src/Service/EncounterPenaltyNominations.php';
use Joomla\Database\DatabaseInterface;
use Joomleague\Component\Joomleague\Domain\Service\UuidFactory;
use Joomleague\Component\Joomleague\Domain\Service\CanonicalJson;
use Joomleague\Component\Joomleague\Administrator\Service\MatchResultRepository;
use Joomleague\Component\Joomleague\Administrator\Service\TeamEncounterRepository;
use Joomleague\Component\Joomleague\Administrator\Service\MatchResultEditorContext;
use Joomleague\Component\Joomleague\Administrator\Service\MatchResultValidationException;
$container = Factory::getContainer();
$container->alias('session', 'session.cli')->alias(Joomla\Session\SessionInterface::class, 'session.cli');
Factory::$application = $container->get(Joomla\Console\Application::class);
$pg = in_array('--pgsql', $argv, true);
$keep = in_array('--keep', $argv, true);
$db = $pg ? (new Joomla\Database\DatabaseFactory())->getDriver('pgsql', ['host' => 'jl-encounter-pg-test', 'user' => 'postgres', 'password' => getenv('JL_TEST_PG_PASSWORD') ?: '', 'database' => 'encounter', 'prefix' => 'test_']) : $container->get(DatabaseInterface::class);
if ($pg && $db->getTableList() === []) {
    $sql = file_get_contents(JPATH_ADMINISTRATOR . '/components/com_joomleague/sql/install.postgresql.sql');
    foreach (Joomla\Database\DatabaseDriver::splitSql($sql) as $statement) if (trim($statement) !== '') $db->setQuery($statement)->execute();
}
$insert = static function (string $table, array $data) use ($db): int {
    $q = $db->getQuery(true)->insert($db->quoteName($table))->columns($db->quoteName(array_keys($data)));
    $holders = [];
    foreach ($data as $key => &$value) { $holders[] = ':' . $key; $q->bind(':' . $key, $value); }
    unset($value);
    $q->values(implode(',', $holders)); $db->setQuery($q)->execute(); return (int) $db->insertid();
};
$delete = static function (string $table, int $id) use ($db): void { $db->setQuery($db->getQuery(true)->delete($db->quoteName($table))->where('id = ' . $id))->execute(); };
$cycles = in_array('--cycles', $argv, true);
$penalties = in_array('--penalties', $argv, true) || $cycles;
$carom = in_array('--carom', $argv, true) || $penalties;
$profile = json_decode(file_get_contents(JPATH_ADMINISTRATOR . '/components/com_joomleague/resources/sport-profiles/' . ($carom ? 'carom' : 'team-encounter') . '.json'), true, 512, JSON_THROW_ON_ERROR);
$code = 'encounter_test_' . bin2hex(random_bytes(4));
$profileId = $insert('#__joomleague_sport_profile', ['code' => $code, 'name_key' => $profile['name_key'], 'description_key' => $profile['description_key']]);
$version = $insert('#__joomleague_sport_profile_version', ['profile_id' => $profileId, 'schema_version' => $profile['schema_version'], 'profile_version' => $profile['version'], 'payload_json' => CanonicalJson::encodeObject($profile), 'payload_checksum' => CanonicalJson::checksum($profile)]);
$competition = $insert('#__joomleague_competition', ['uuid' => UuidFactory::v4(), 'name' => 'Team encounter example', 'published' => 1, 'access' => 1]);
$season = $insert('#__joomleague_season', ['uuid' => UuidFactory::v4(), 'name' => '2026', 'published' => 1, 'access' => 1]);
$sport = $insert('#__joomleague_sport_type', ['profile_version_id' => $version, 'code' => $code, 'name' => 'Team encounter', 'published' => 1]);
$project = $insert('#__joomleague_project', ['uuid' => UuidFactory::v4(), 'competition_id' => $competition, 'season_id' => $season, 'sport_type_id' => $sport, 'profile_version_id' => $version, 'name' => 'Team encounter example', 'project_type' => 'tournament', 'published' => 1, 'access' => 1]);
$teams = []; $people = [];
try {
    $stage = $insert('#__joomleague_project_stage', ['uuid' => UuidFactory::v4(), 'project_id' => $project, 'name' => 'Main', 'code' => 'main', 'stage_type' => 'league', 'published' => 1]);
    $round = $insert('#__joomleague_project_round', ['uuid' => UuidFactory::v4(), 'project_id' => $project, 'stage_id' => $stage, 'name' => 'Round 1', 'code' => 'round_1', 'sequence_number' => 1, 'published' => 1]);
    $match = $insert('#__joomleague_project_match', ['uuid' => UuidFactory::v4(), 'project_id' => $project, 'stage_id' => $stage, 'round_id' => $round, 'contest_type' => 'head_to_head', 'published' => 1]);
    $participants = []; $members = [];
    foreach (['Team A', 'Team B'] as $i => $name) {
        $team = $insert('#__joomleague_team', ['uuid' => UuidFactory::v4(), 'name' => $name, 'published' => 1, 'access' => 1]); $teams[] = $team;
        $entry = $insert('#__joomleague_project_entry', ['uuid' => UuidFactory::v4(), 'project_id' => $project, 'entry_kind' => 'team', 'team_id' => $team, 'published' => 1]);
        $participant = $insert('#__joomleague_match_participant', ['uuid' => UuidFactory::v4(), 'match_id' => $match, 'project_id' => $project, 'project_entry_id' => $entry, 'slot_number' => $i + 1, 'published' => 1]); $participants[] = $participant;
        for ($n = 1; $n <= 2; $n++) {
            $person = $insert('#__joomleague_person', ['uuid' => UuidFactory::v4(), 'first_name' => 'Player ' . $n, 'last_name' => $name, 'published' => 1, 'access' => 1]); $people[] = $person;
            $members[$participant][] = $insert('#__joomleague_match_lineup_member', ['uuid' => UuidFactory::v4(), 'match_id' => $match, 'match_participant_id' => $participant, 'person_id' => $person, 'member_person_type' => 'player', 'published' => 1]);
        }
    }
    $values = static fn(int $a, int $b): array => [['participant_id' => $participants[0], 'numeric_value' => (string) $a], ['participant_id' => $participants[1], 'numeric_value' => (string) $b]];
    $duels = [];
    foreach ([[21, 17], [21, 19], [11, 21], [2, 1]] as $i => [$a, $b]) $duels[] = ['level_code' => 'duel', 'sequence_number' => $i + 1, 'status_code' => 'completed', 'values' => $values($a, $b), 'children' => []];
    $duels[3]['children'] = [];
    foreach ([[6, 4], [3, 6], [7, 5]] as $i => [$a, $b]) $duels[3]['children'][] = ['level_code' => 'set', 'sequence_number' => $i + 1, 'values' => $values($a, $b)];
    $payload = ['result_type' => 'nested_score', 'status_code' => 'final', 'outcome_code' => 'completed', 'encounter_revision' => 'new', 'segments' => [['level_code' => 'result', 'values' => [], 'children' => $duels]], 'duels' => []];
    for ($i = 1; $i <= 4; $i++) $payload['duels'][] = ['sequence_number' => $i, 'format_code' => $i === 4 ? 'doubles' : 'singles', 'sides' => array_map(static fn(int $p): array => ['participant_id' => $p, 'lineup_member_ids' => $i === 4 ? $members[$p] : [$members[$p][0]]], $participants)];
    $repo = new MatchResultRepository($db); $encounter = new TeamEncounterRepository($db);
    if ($carom) {
        $payload['segments'][0]['children'][3]['children'] = [];
        $payload['segments'][0]['children'][3]['values'] = $values(40, 32);
        foreach ($payload['segments'][0]['children'] as &$game) foreach ($game['values'] as &$value) $value['metadata'] = ['innings' => 43, 'high_run' => 1];
        unset($game, $value);
        foreach ($payload['duels'] as &$duel) { $duel['format_code'] = 'singles'; foreach ($duel['sides'] as &$side) $side['lineup_member_ids'] = [$side['lineup_member_ids'][0]]; unset($side); }
        unset($duel);
        (new Joomleague\Component\Joomleague\Administrator\Service\ProjectRuleConfigRepository($db))->save($project, ['match' => ['encounter' => ['duel_points' => ['win' => 3]]]], 0);
    }
    $repo->replace($match, $payload, 0);
    $stored = $repo->get($match);
    if (array_column($stored['segments'][0]['values'], 'numeric_value') !== ($carom ? ['9.000000000', '3.000000000'] : ['3.000000000', '1.000000000'])) throw new RuntimeException('Wrong encounter total.');
    if ($carom && ($stored['segments'][0]['children'][0]['values'][0]['metadata'] !== ['high_run' => 1, 'innings' => 43] && $stored['segments'][0]['children'][0]['values'][0]['metadata'] !== ['innings' => 43, 'high_run' => 1])) throw new RuntimeException('Carom metadata lost during persistence.');
    if (count($encounter->get($match)) !== 4 || count($encounter->get($match)[3]['sides'][0]['lineup_member_ids']) !== ($carom ? 1 : 2)) throw new RuntimeException('Lost singles/doubles nominations.');
    $context = (new MatchResultEditorContext($db, $repo))->get($match);
    if ($carom) {
        foreach (['StandingsDecimal', 'StandingsCalculator', 'StandingsReader', 'StandingsRecalculator', 'StandingsFreshnessState', 'CaromStandingsFacts', 'CaromStandingsContract'] as $service) require_once JPATH_ADMINISTRATOR . '/components/com_joomleague/src/Service/' . $service . '.php';
        $standingReader = new Joomleague\Component\Joomleague\Domain\Service\StandingsReader($db);
        $standingRecalculator = new Joomleague\Component\Joomleague\Domain\Service\StandingsRecalculator($db, $standingReader);
        $standingContext = $standingReader->context($project, null, 'total');
        $standingInput = $standingRecalculator->inputForCalculation($standingContext, 'total');
        $standingRows = (new Joomleague\Component\Joomleague\Domain\Service\StandingsCalculator())->calculate($standingContext['contract'], $standingInput['entries'], $standingInput['matches']);
        if ($standingRows[0]['metrics']['caroms'] !== '93' || $standingRows[0]['metrics']['innings'] !== '172' || $standingRows[0]['metrics']['high_run'] !== '1' || $standingRows[0]['metrics']['average'] !== '0.540697674') throw new RuntimeException('Persisted standings lost board metadata or calculated the wrong average.');
        $insert('#__joomleague_standing_freshness', ['project_id' => $project, 'stage_key' => $stage, 'scope_code' => 'total', 'is_dirty' => 0, 'input_checksum' => str_repeat('b', 64)]);
        (new Joomleague\Component\Joomleague\Administrator\Service\ProjectRuleConfigRepository($db))->save($project, ['match' => ['encounter' => ['duel_points' => ['win' => 3]]], 'standings' => ['team_points' => ['win' => 2, 'draw' => 1, 'loss' => 0], 'tie_breaks' => ['first' => 'head_to_head_average', 'second' => 'head_to_head_caroms']]], 0);
        if ((int) $db->setQuery('SELECT is_dirty FROM ' . $db->quoteName('#__joomleague_standing_freshness') . ' WHERE project_id=' . $project . ' AND stage_key=' . $stage)->loadResult() !== 1) throw new RuntimeException('Rule changes did not invalidate stage standings.');
        $standingContext = $standingReader->context($project, null, 'total');
        if ($standingContext['contract']['ordering'][1]['metric'] !== 'average' || $standingContext['contract']['ordering'][1]['scope'] !== 'head_to_head' || $standingContext['contract']['awards']['rules'][0]['values']['winner'] !== 2) throw new RuntimeException('Persisted standings criteria or team awards ignored.');
        $tiedEntry = $standingInput['entries'][1]['id'];
        $headRows = (new Joomleague\Component\Joomleague\Domain\Service\StandingsCalculator())->calculate($standingContext['contract'], $standingInput['entries'], $standingInput['matches'], 'total', [['entry_id' => $tiedEntry, 'metric' => 'points', 'value' => '2']]);
        if ($headRows[0]['id'] !== $standingInput['entries'][0]['id'] || $headRows[0]['metrics']['points'] !== '2' || $headRows[1]['metrics']['points'] !== '2') throw new RuntimeException('Persisted head-to-head average did not break a corrected points tie.');
        $insert('#__joomleague_standing_adjustment', ['uuid' => UuidFactory::v4(), 'project_id' => $project, 'stage_id' => $stage, 'stage_key' => $stage, 'project_entry_id' => $tiedEntry, 'scope_code' => 'total', 'metric_code' => 'points', 'adjustment_value' => '2', 'reason' => 'Isolated head-to-head integration test', 'published' => 1]);
        $snapshotId = $standingRecalculator->recalculate($project, $stage, 'total', 0);
        $publishedStandings = $standingReader->current($project, $stage, 'total');
        $publishedContract = json_decode((string) $publishedStandings['snapshot']->contract_json, true, 512, JSON_THROW_ON_ERROR);
        if ((int) $publishedStandings['snapshot']->id !== $snapshotId || $publishedContract['ordering'][1]['scope'] !== 'head_to_head' || $publishedStandings['rows'][0]->metrics['points'] !== '2' || $publishedStandings['rows'][1]->metrics['points'] !== '2' || (int) $publishedStandings['rows'][0]->entry_id_snapshot !== $standingInput['entries'][0]['id']) throw new RuntimeException('Published snapshot lost head-to-head order or overall totals.');
        $pinnedChecksum = (string) $db->setQuery($db->getQuery(true)->select('payload_checksum')->from($db->quoteName('#__joomleague_sport_profile_version'))->where('id=' . (int) $version))->loadResult();
        (new Joomleague\Component\Joomleague\Administrator\Service\ProjectRuleConfigRepository($db))->save($project, ['match' => ['encounter' => ['duel_points' => ['win' => 3]]], 'standings' => ['team_points' => ['win' => 2, 'draw' => 1, 'loss' => 0], 'tie_breaks' => ['first' => 'best_average', 'second' => 'none', 'third' => 'none', 'fourth' => 'none', 'fifth' => 'none']]], 0);
        if ((int) $db->setQuery('SELECT is_dirty FROM ' . $db->quoteName('#__joomleague_standing_freshness') . ' WHERE project_id=' . $project . ' AND stage_key=' . $stage)->loadResult() !== 1) throw new RuntimeException('Best-average rule did not invalidate standings.');
        $bestSnapshotId = $standingRecalculator->recalculate($project, $stage, 'total', 0);
        $bestStandings = $standingReader->current($project, $stage, 'total');
        $bestContract = json_decode((string) $bestStandings['snapshot']->contract_json, true, 512, JSON_THROW_ON_ERROR);
        if ((int) $bestStandings['snapshot']->id !== $bestSnapshotId || $bestContract['ordering'][1]['metric'] !== 'best_average' || $bestStandings['rows'][0]->metrics['best_average'] !== '0.930232558' || (int) $bestStandings['rows'][0]->entry_id_snapshot !== $standingInput['entries'][0]['id']) throw new RuntimeException('Persisted best individual average was not used in the points tie.');
        if ((string) $db->setQuery($db->getQuery(true)->select('payload_checksum')->from($db->quoteName('#__joomleague_sport_profile_version'))->where('id=' . (int) $version))->loadResult() !== $pinnedChecksum) throw new RuntimeException('Rule selection mutated the pinned profile.');
        echo "Best individual average OK: metadata persistence, points tie, snapshot publication, invalidation and immutable profile.\n";
        require_once JPATH_ADMINISTRATOR . '/components/com_joomleague/src/Table/StandingadjustmentTable.php';
        $forgedAverage = new Joomleague\Component\Joomleague\Administrator\Table\StandingadjustmentTable($db);
        $forgedAverage->bind(['project_id' => $project, 'project_entry_id' => $standingInput['entries'][0]['id'], 'scope_code' => 'total', 'metric_code' => 'best_average', 'adjustment_value' => '999', 'reason' => 'Synthetic forbidden derived-metric adjustment']);
        if ($forgedAverage->check() || $forgedAverage->getError() !== Joomla\CMS\Language\Text::_('COM_JOOMLEAGUE_ERROR_STANDING_ADJUSTMENT_CONTRACT_INVALID')) throw new RuntimeException('Table validation accepted a forged best-average correction.');
        foreach (['CaromEncounterSummary', 'CaromPlayerSummary', 'ParticipantStatisticReader'] as $service) require_once JPATH_ADMINISTRATOR . '/components/com_joomleague/src/Service/' . $service . '.php';
        require_once JPATH_ROOT . '/components/com_joomleague/src/Service/CaromPlayerHistoryReader.php';
        $summary = (new Joomleague\Component\Joomleague\Domain\Service\ParticipantStatisticReader($db))->forEntry($project, (int) $db->setQuery('SELECT project_entry_id FROM ' . $db->quoteName('#__joomleague_match_participant') . ' WHERE id=' . $participants[0])->loadResult(), [1])['carom_summary'];
        if ($summary['games'] !== 4 || $summary['caroms'] !== 93 || $summary['innings'] !== 172 || abs($summary['average'] - 93 / 172) > 0.000001) throw new RuntimeException('Persisted project summary is incorrect.');
        if ($context['editor_schema']['encounter']['duel_points']['win'] !== 3) throw new RuntimeException('Editor ignored project awards.');
        $historyReader = new Joomleague\Component\Joomleague\Site\Service\CaromPlayerHistoryReader($db);
        $playerHistory = $historyReader->forPerson($people[0], [1]);
        $playerSummary = $playerHistory['projects'][0] ?? [];
        if (count($playerHistory['history']) !== 4 || ($playerSummary['wins'] ?? null) !== 3 || $playerSummary['losses'] !== 1 || $playerSummary['caroms'] !== 93 || $playerSummary['innings'] !== 172 || abs($playerSummary['average'] - 93 / 172) > 0.000001) throw new RuntimeException('Player career reader lost nominations or averages.');
        if ($historyReader->forPerson($people[1], [1])['history'] !== [] || $historyReader->forPerson($people[0], [])['history'] !== []) throw new RuntimeException('Non-nominated player / unauthorised levels received history.');
        $setField = static function (string $table, int $id, string $field, int $value) use ($db): void {
            $db->setQuery($db->getQuery(true)->update($db->quoteName($table))->set($db->quoteName($field) . '=' . $value)->where('id=' . $id))->execute();
        };
        $setField('#__joomleague_person', $people[2], 'published', 0);
        $hiddenOpponent = $historyReader->forPerson($people[0], [1]);
        if (count($hiddenOpponent['history']) !== 4 || array_filter($hiddenOpponent['history'], static fn (array $row): bool => $row['opponent_name'] !== '' || $row['opponent_id'] !== 0) !== []) throw new RuntimeException('Private opponent identity leaked.');
        $setField('#__joomleague_person', $people[2], 'published', 1);
        $targetEntry = (int) $db->setQuery('SELECT project_entry_id FROM ' . $db->quoteName('#__joomleague_match_participant') . ' WHERE id=' . $participants[0])->loadResult();
        foreach ([['#__joomleague_person', $people[0], 'published', 1], ['#__joomleague_project', $project, 'access', 1], ['#__joomleague_competition', $competition, 'access', 1], ['#__joomleague_season', $season, 'access', 1], ['#__joomleague_team', $teams[1], 'access', 1], ['#__joomleague_project_entry', $targetEntry, 'published', 1], ['#__joomleague_project_match', $match, 'published', 1], ['#__joomleague_project_stage', $stage, 'published', 1], ['#__joomleague_project_round', $round, 'published', 1]] as [$table, $id, $field, $original]) {
            $setField($table, $id, $field, $field === 'access' ? 2 : 0);
            if ($historyReader->forPerson($people[0], [1])['history'] !== []) throw new RuntimeException('Player history ignored access/publication: ' . $table);
            $setField($table, $id, $field, $original);
        }
        echo "Player history OK: persisted W/D/L, weighted season statistics, nominations, privacy and publication/access checks.\n";
    }
    if (count($context['encounter_roster']) !== 4 || count($context['form_state']['duels']) !== 4) throw new RuntimeException('Editor context lost nominations.');
    $beforeRevision = $encounter->revision($match);
    try { (new Joomleague\Component\Joomleague\Administrator\Service\MatchLineupRepository($db))->remove($match, $participants[0], $members[$participants[0]][0]); throw new RuntimeException('Nominated player could be removed.'); }
    catch (MatchResultValidationException $e) { if ($e->getLanguageKey() !== 'COM_JOOMLEAGUE_ENCOUNTER_ERROR_REFERENCED') throw $e; }
    try { $repo->replace($match, $payload, 0); throw new RuntimeException('Stale write accepted.'); } catch (MatchResultValidationException $e) { if ($e->getLanguageKey() !== 'COM_JOOMLEAGUE_ENCOUNTER_ERROR_STALE') throw $e; }
    $invalid = $payload; $invalid['encounter_revision'] = $beforeRevision; $invalid['duels'][0]['sides'][0]['lineup_member_ids'] = [$members[$participants[1]][0]];
    try { $repo->replace($match, $invalid, 0); throw new RuntimeException('Cross-team write accepted.'); } catch (MatchResultValidationException) {}
    if ($repo->get($match) !== $stored || $encounter->revision($match) !== $beforeRevision) throw new RuntimeException('Rejected write changed the encounter.');
    $payload['encounter_revision'] = $beforeRevision;
    $insert('#__joomleague_standing_freshness', ['project_id' => $project, 'stage_key' => 0, 'scope_code' => 'total', 'is_dirty' => 0, 'input_checksum' => str_repeat('a', 64)]);
    $payload['segments'][0]['children'][0]['values'] = $values(17, 21);
    $repo->replace($match, $payload, 0);
    if (array_column($repo->get($match)['segments'][0]['values'], 'numeric_value') !== ($carom ? ['6.000000000', '6.000000000'] : ['2.000000000', '2.000000000'])) throw new RuntimeException('Correction did not update parent result.');
    if ($carom) {
        $correctedPlayer = $historyReader->forPerson($people[0], [1])['projects'][0];
        if ($correctedPlayer['wins'] !== 2 || $correctedPlayer['losses'] !== 2 || $correctedPlayer['caroms'] !== 89 || $correctedPlayer['average'] !== null) throw new RuntimeException('Correction did not update player summary / missing innings.');
        $correctedInput = $standingRecalculator->inputForCalculation($standingReader->context($project, $stage, 'total'), 'total');
        $correctedRows = array_column((new Joomleague\Component\Joomleague\Domain\Service\StandingsCalculator())->calculate($bestContract, $correctedInput['entries'], $correctedInput['matches']), null, 'id');
        if ($correctedRows[$standingInput['entries'][0]['id']]['metrics']['best_average'] !== null) throw new RuntimeException('Persisted missing innings produced an invented best average.');
    }
    if ((int) $db->setQuery('SELECT is_dirty FROM ' . $db->quoteName('#__joomleague_standing_freshness') . ' WHERE project_id = ' . $project)->loadResult() !== 1) throw new RuntimeException('Correction did not atomically invalidate standings.');
    $count = (int) $db->setQuery('SELECT COUNT(*) FROM ' . $db->quoteName('#__joomleague_match_encounter_revision') . ' WHERE match_id = ' . $match)->loadResult();
    if ($count !== 2) throw new RuntimeException('Audit revisions missing or rejected writes audited.');
    $payload['encounter_revision'] = $encounter->revision($match); $payload['segments'][0]['children'][0]['values'] = $values(21, 17);
    $repo->replace($match, $payload, 0);
    echo ($pg ? 'PostgreSQL' : 'MariaDB') . ($carom ? " carom OK: metadata, project awards 9:3, project summary, editor overrides, correction 6:6 and rollback.\n" : " encounter OK: nominations, 3:1, nested sets, doubles, correction, stale writes and rollback.\n");
    if ($penalties) {
        (new Joomleague\Component\Joomleague\Administrator\Service\ProjectRuleConfigRepository($db))->save($project, ['match' => ['encounter' => ['penalty_series' => ['required' => true]]]], 0);
        $payload['encounter_revision'] = $encounter->revision($match);
        foreach ([[40, 30], [30, 40], [40, 30], [30, 40]] as $index => [$a, $b]) {
            $payload['segments'][0]['children'][$index]['values'] = $values($a, $b);
            foreach ($payload['segments'][0]['children'][$index]['values'] as &$value) $value['metadata'] = ['innings' => 20, 'high_run' => 1];
            unset($value);
        }
        foreach ([[2, 2], [3, 4]] as $index => [$a, $b]) $payload['segments'][0]['children'][] = ['level_code' => 'penalty_round', 'sequence_number' => $index + 1, 'status_code' => 'completed', 'values' => $values($a, $b), 'children' => []];
        $repo->replace($match, $payload, 0);
        $penaltyStored = $repo->get($match);
        if (array_column($penaltyStored['segments'][0]['values'], 'numeric_value') !== ['4.000000000', '4.000000000'] || array_column($penaltyStored['segments'][0]['values'], 'status_code') !== ['loser', 'winner'] || count($encounter->get($match)) !== 4) throw new RuntimeException('Persisted penalty winner, regular 4:4 or four nominations lost.');
        $entryId = (int) $db->setQuery('SELECT project_entry_id FROM ' . $db->quoteName('#__joomleague_match_participant') . ' WHERE id=' . $participants[0])->loadResult();
        $penaltySummary = (new Joomleague\Component\Joomleague\Domain\Service\ParticipantStatisticReader($db))->forEntry($project, $entryId, [1])['carom_summary'];
        if ($penaltySummary['caroms'] !== 140 || $penaltySummary['innings'] !== 80 || $penaltySummary['games'] !== 4) throw new RuntimeException('Persisted penalty series contaminated playing statistics.');
        $penaltyRevision = $encounter->revision($match);
        $extra = $payload; $extra['encounter_revision'] = $penaltyRevision;
        $extra['segments'][0]['children'][] = ['level_code' => 'penalty_round', 'sequence_number' => 3, 'status_code' => 'completed', 'values' => $values(8, 0), 'children' => []];
        try { $repo->replace($match, $extra, 0); throw new RuntimeException('Extra round after winner accepted.'); } catch (MatchResultValidationException $error) { if ($error->getLanguageKey() !== 'COM_JOOMLEAGUE_CAROM_PENALTY_ERROR_ORDER') throw $error; }
        if ($repo->get($match) !== $penaltyStored || $encounter->revision($match) !== $penaltyRevision) throw new RuntimeException('Rejected penalty round changed result or audit revision.');
        $payload['encounter_revision'] = $penaltyRevision;
        $payload['segments'][0]['children'][5]['values'] = $values(5, 4);
        foreach ([4, 5] as $index) foreach ($payload['segments'][0]['children'][$index]['values'] as &$value) $value['metadata']['lineup_member_id'] = $members[$value['participant_id']][$index - 4];
        unset($value);
        $repo->replace($match, $payload, 0);
        if (array_column($repo->get($match)['segments'][0]['values'], 'numeric_value') !== ['4.000000000', '4.000000000'] || array_column($repo->get($match)['segments'][0]['values'], 'status_code') !== ['winner', 'loser']) throw new RuntimeException('Penalty correction failed to update winner independently of regular score.');
        echo ($pg ? 'PostgreSQL' : 'MariaDB') . " penalties OK: retained 4:4, winner correction, four nominations, statistical exclusion and rollback.\n";
        $nominated = $repo->get($match);
        $badNomination = $payload; $badNomination['encounter_revision'] = $encounter->revision($match);
        $badNomination['segments'][0]['children'][4]['values'][0]['metadata']['lineup_member_id'] = $members[$participants[1]][0];
        try { $repo->replace($match, $badNomination, 0); throw new RuntimeException('Foreign-team penalty player accepted.'); } catch (MatchResultValidationException $error) { if ($error->getLanguageKey() !== 'COM_JOOMLEAGUE_CAROM_PENALTY_ERROR_NOMINATION') throw $error; }
        if ($repo->get($match) !== $nominated || $encounter->revision($match) !== $badNomination['encounter_revision']) throw new RuntimeException('Rejected nomination changed result or revision.');
        if ((int) $nominated['segments'][0]['children'][5]['values'][0]['metadata']['lineup_member_id'] !== $members[$participants[0]][1]) throw new RuntimeException('Penalty nomination did not survive storage.');
        echo ($pg ? 'PostgreSQL' : 'MariaDB') . " penalty nominations OK: persisted player references and foreign-team rollback.\n";
        $setPolicy = static function (string $policy) use ($db, $stage): void {
            $metadata = CanonicalJson::encodeObject(['encounter_decision_policy' => $policy, 'other' => 'retained']);
            $query = $db->getQuery(true)->update($db->quoteName('#__joomleague_project_stage'))
                ->set('metadata_json = :metadata')->where('id = :stage')->bind(':metadata', $metadata)->bind(':stage', $stage);
            $db->setQuery($query)->execute();
        };
        $withoutPenalties = $payload;
        $withoutPenalties['segments'][0]['children'] = array_slice($withoutPenalties['segments'][0]['children'], 0, 4);
        $withoutPenalties['encounter_revision'] = $encounter->revision($match);
        $setPolicy('allow_draw');
        if ((new MatchResultEditorContext($db, $repo))->get($match)['profile_payload']['match']['encounter']['penalty_series']['required'] !== false) throw new RuntimeException('Editor ignored stage draw override.');
        $repo->replace($match, $withoutPenalties, 0);
        if (array_column($repo->get($match)['segments'][0]['values'], 'status_code') !== [null, null]) throw new RuntimeException('Stage draw retained a stale penalty winner.');
        $setPolicy('require_penalties');
        $withoutPenalties['encounter_revision'] = $encounter->revision($match);
        $before = $repo->get($match);
        try { $repo->replace($match, $withoutPenalties, 0); throw new RuntimeException('Stage-required penalties bypassed.'); } catch (MatchResultValidationException $error) { if ($error->getLanguageKey() !== 'COM_JOOMLEAGUE_CAROM_PENALTY_ERROR_REQUIRED') throw $error; }
        if ($repo->get($match) !== $before || $encounter->revision($match) !== $withoutPenalties['encounter_revision']) throw new RuntimeException('Stage rejection changed stored history.');
        if ((new MatchResultEditorContext($db, $repo))->get($match)['profile_payload']['match']['encounter']['penalty_series']['required'] !== true) throw new RuntimeException('Editor ignored stage-required penalties.');
        $payload['encounter_revision'] = $encounter->revision($match);
        $repo->replace($match, $payload, 0);
        $setPolicy('inherit');
        if ((new MatchResultEditorContext($db, $repo))->get($match)['profile_payload']['match']['encounter']['penalty_series']['required'] !== true) throw new RuntimeException('Stage inheritance lost project policy.');
        echo ($pg ? 'PostgreSQL' : 'MariaDB') . " stage decisions OK: inheritance, draw override, required penalties, editor agreement and unchanged rejected history.\n";
    }
    if ($cycles) {
        (new Joomleague\Component\Joomleague\Administrator\Service\ProjectRuleConfigRepository($db))->save($project, ['match' => ['encounter' => ['penalty_series' => ['required' => true, 'resolution' => 'team_cycle', 'cycle_length' => 4, 'player_order' => 'descending']]]], 0);
        $payload['encounter_revision'] = $encounter->revision($match);
        $payload['segments'][0]['children'] = array_slice($payload['segments'][0]['children'], 0, 4);
        foreach ($payload['duels'] as $index => &$duel) foreach ($duel['sides'] as &$side) $side['lineup_member_ids'] = [$members[$side['participant_id']][$index % 2]];
        unset($duel, $side);
        foreach ([[5, 1], [0, 5], [0, 0], [0, 0]] as $index => [$a, $b]) $payload['segments'][0]['children'][] = ['level_code' => 'penalty_round', 'sequence_number' => $index + 1, 'status_code' => 'completed', 'values' => $values($a, $b), 'children' => []];
        $repo->replace($match, $payload, 0);
        $stored = $repo->get($match);
        if (array_column($stored['segments'][0]['values'], 'status_code') !== ['loser', 'winner']) throw new RuntimeException('Team cycle ended on first unequal player score.');
        foreach ([4 => 1, 5 => 0, 6 => 1, 7 => 0] as $index => $slot) if ((int) $stored['segments'][0]['children'][$index]['values'][0]['metadata']['lineup_member_id'] !== $members[$participants[0]][$slot]) throw new RuntimeException('Reverse board nomination not persisted.');
        $bad = $payload; $bad['encounter_revision'] = $encounter->revision($match);
        $bad['segments'][0]['children'][4]['values'][0]['metadata']['lineup_member_id'] = $members[$participants[0]][0];
        try { $repo->replace($match, $bad, 0); throw new RuntimeException('Wrong automatic board order accepted.'); } catch (MatchResultValidationException $error) { if ($error->getLanguageKey() !== 'COM_JOOMLEAGUE_CAROM_PENALTY_ERROR_NOMINATION') throw $error; }
        if ($repo->get($match) !== $stored || $encounter->revision($match) !== $bad['encounter_revision']) throw new RuntimeException('Rejected board order changed history.');
        echo ($pg ? 'PostgreSQL' : 'MariaDB') . " team cycles OK: full-cycle winner, automatic reverse boards and wrong-order rollback.\n";
        if (!$keep) {
            (new Joomleague\Component\Joomleague\Administrator\Service\ProjectRuleConfigRepository($db))->save($project, ['match' => ['encounter' => ['penalty_series' => ['required' => true, 'resolution' => 'sudden_death', 'cycle_length' => 4, 'player_order' => 'descending_last']]]], 0);
            $payload['encounter_revision'] = $encounter->revision($match);
            $payload['segments'][0]['children'] = array_slice($payload['segments'][0]['children'], 0, 4);
            foreach ([[1, 1], [2, 2], [3, 3], [4, 4], [5, 5], [6, 6], [2, 1]] as $index => [$a, $b]) $payload['segments'][0]['children'][] = ['level_code' => 'penalty_round', 'sequence_number' => $index + 1, 'status_code' => 'completed', 'values' => $values($a, $b), 'children' => []];
            $repo->replace($match, $payload, 0);
            $stored = $repo->get($match);
            if (array_column($stored['segments'][0]['values'], 'numeric_value') !== ['4.000000000', '4.000000000'] || array_column($stored['segments'][0]['values'], 'status_code') !== ['winner', 'loser']) throw new RuntimeException('Portuguese continuation lost regulation draw or winner.');
            foreach ([1, 0, 1, 0, 0, 0, 0] as $index => $slot) foreach ($participants as $side => $participant) if ((int) $stored['segments'][0]['children'][$index + 4]['values'][$side]['metadata']['lineup_member_id'] !== $members[$participant][$slot]) throw new RuntimeException('Portuguese last-board continuation was not persisted.');
            echo ($pg ? 'PostgreSQL' : 'MariaDB') . " Portuguese penalties OK: reverse first pass, repeated board one, unchanged 4:4 and decided winner.\n";
        }
    }
    if ($carom && $keep && !$penalties && in_array('--browser-complete', $argv, true)) {
        // Missing-data behavior was checked above; publish a complete synthetic example for UI verification.
        $payload['encounter_revision'] = $encounter->revision($match);
        foreach ($payload['segments'][0]['children'][0]['values'] as &$value) $value['metadata'] = ['innings' => 43, 'high_run' => 1];
        unset($value);
        $repo->replace($match, $payload, 0);
        $standingRecalculator->recalculate($project, null, 'total', 0);
        $standingRecalculator->recalculate($project, $stage, 'total', 0);
    }
    if ($keep) echo json_encode(['match' => $match, 'project' => $project, 'round' => $round], JSON_THROW_ON_ERROR) . "\n";
} finally {
    if (!$keep) {
        $delete('#__joomleague_project', $project);
        foreach ($teams as $id) $delete('#__joomleague_team', $id);
        foreach ($people as $id) $delete('#__joomleague_person', $id);
        $delete('#__joomleague_sport_type', $sport); $delete('#__joomleague_competition', $competition); $delete('#__joomleague_season', $season); $delete('#__joomleague_sport_profile', $profileId);
    }
}
