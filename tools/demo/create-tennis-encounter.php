<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { exit(1); }
define('_JEXEC', 1);
define('JPATH_BASE', '/var/www/html');
require JPATH_BASE . '/includes/defines.php';
require JPATH_BASE . '/includes/framework.php';
use Joomla\CMS\Factory;
use Joomla\Database\DatabaseInterface;
use Joomleague\Component\Joomleague\Domain\Service\UuidFactory;
use Joomleague\Component\Joomleague\Administrator\Service\MatchResultRepository;
$container = Factory::getContainer();
$container->alias('session', 'session.cli')->alias(Joomla\Session\SessionInterface::class, 'session.cli');
Factory::$application = $container->get(Joomla\Console\Application::class);
foreach (['UuidFactory', 'CanonicalJson', 'EntryModelValidator', 'MatchResultValidationException', 'MatchResultDecimal', 'MatchResultAggregationValidator', 'TeamEncounterScoreNormalizer', 'TeamEncounterRosterValidator', 'TeamEncounterRepository', 'MatchResultPayloadValidator', 'MatchResultRepository'] as $service) {
    require_once JPATH_ADMINISTRATOR . '/components/com_joomleague/src/Service/' . $service . '.php';
}
$db = $container->get(DatabaseInterface::class);
$name = 'Tennis team encounter demo 2026';
$existing = (int) $db->setQuery($db->getQuery(true)->select('id')->from('#__joomleague_project')->where('name = ' . $db->quote($name)))->loadResult();
if ($existing) { throw new RuntimeException('Demo already exists: project ' . $existing); }
$version = (int) $db->setQuery("SELECT v.id FROM #__joomleague_sport_profile_version v INNER JOIN #__joomleague_sport_profile p ON p.id=v.profile_id WHERE p.code='team_encounter' ORDER BY v.id DESC")->loadResult();
if (!$version) { throw new RuntimeException('Install the team encounter profile first.'); }
$insert = static function (string $table, array $data) use ($db): int {
    $q = $db->getQuery(true)->insert($db->quoteName($table))->columns($db->quoteName(array_keys($data)));
    $holders = [];
    foreach ($data as $key => &$value) { $holders[] = ':' . $key; $q->bind(':' . $key, $value); }
    unset($value);
    $db->setQuery($q->values(implode(',', $holders)))->execute();
    return (int) $db->insertid();
};
$db->transactionStart();
$teams = []; $people = []; $fixtureCommitted = false;
try {
    $competition = $insert('#__joomleague_competition', ['uuid'=>UuidFactory::v4(), 'name'=>'Demo Tennis Team Cup', 'published'=>1, 'access'=>1]);
    $season = $insert('#__joomleague_season', ['uuid'=>UuidFactory::v4(), 'name'=>'Tennis demo 2026', 'published'=>1, 'access'=>1]);
    $sport = $insert('#__joomleague_sport_type', ['code'=>'tennis_encounter_demo', 'name'=>'Tennis team encounters', 'profile_version_id'=>$version, 'published'=>1]);
    $project = $insert('#__joomleague_project', ['uuid'=>UuidFactory::v4(), 'name'=>$name, 'competition_id'=>$competition, 'season_id'=>$season, 'sport_type_id'=>$sport, 'profile_version_id'=>$version, 'project_type'=>'tournament', 'published'=>1, 'access'=>1]);
    $stage = $insert('#__joomleague_project_stage', ['uuid'=>UuidFactory::v4(), 'project_id'=>$project, 'name'=>'Team final', 'code'=>'team_final', 'stage_type'=>'league', 'published'=>1]);
    $round = $insert('#__joomleague_project_round', ['uuid'=>UuidFactory::v4(), 'project_id'=>$project, 'stage_id'=>$stage, 'name'=>'Tennis team final - singles and doubles', 'code'=>'final', 'sequence_number'=>1, 'published'=>1]);
    $match = $insert('#__joomleague_project_match', ['uuid'=>UuidFactory::v4(), 'project_id'=>$project, 'stage_id'=>$stage, 'round_id'=>$round, 'contest_type'=>'head_to_head', 'status_code'=>'completed', 'scheduled_start'=>'2026-10-03 10:00:00', 'timezone'=>'Europe/Prague', 'description'=>'Fictional tennis team encounter: four singles and one doubles rubber. Demonstration data only.', 'published'=>1]);
    $participants = []; $members = [];
    foreach (['Demo Tennis Club Riverside', 'Demo Tennis Club Hilltop'] as $i=>$teamName) {
        $team = $insert('#__joomleague_team', ['uuid'=>UuidFactory::v4(), 'name'=>$teamName, 'published'=>1, 'access'=>1]);
        $teams[] = $team;
        $entry = $insert('#__joomleague_project_entry', ['uuid'=>UuidFactory::v4(), 'project_id'=>$project, 'entry_kind'=>'team', 'team_id'=>$team, 'published'=>1]);
        $participant = $insert('#__joomleague_match_participant', ['uuid'=>UuidFactory::v4(), 'match_id'=>$match, 'project_id'=>$project, 'project_entry_id'=>$entry, 'slot_number'=>$i+1, 'result_status'=>'completed', 'published'=>1]);
        $participants[] = $participant;
        foreach (($i===0 ? [['Alex','River'],['Daniel','Brook']] : [['Martin','Hill'],['David','Stone']]) as $n=>$player) {
            $person = $insert('#__joomleague_person', ['uuid'=>UuidFactory::v4(), 'first_name'=>$player[0], 'last_name'=>$player[1], 'published'=>1, 'access'=>1]);
            $people[] = $person;
            $members[$participant][] = $insert('#__joomleague_match_lineup_member', ['uuid'=>UuidFactory::v4(), 'match_id'=>$match, 'match_participant_id'=>$participant, 'person_id'=>$person, 'member_person_type'=>'player', 'published'=>1]);
        }
    }
    $values = static fn(int $a,int $b):array => [['participant_id'=>$participants[0], 'numeric_value'=>(string)$a],['participant_id'=>$participants[1], 'numeric_value'=>(string)$b]];
    $scores = [[[6,4],[6,3]], [[3,6],[6,4],[4,6]], [[5,7],[2,6]], [[4,6],[6,3],[6,4]], [[6,7],[6,4],[7,5]]];
    $duels = []; $nominations = [];
    foreach ($scores as $i=>$sets) {
        $children = [];
        foreach ($sets as $j=>[$a,$b]) { $children[] = ['level_code'=>'set', 'sequence_number'=>$j+1, 'values'=>$values($a,$b)]; }
        $duels[] = ['level_code'=>'duel', 'sequence_number'=>$i+1, 'status_code'=>'completed', 'values'=>[], 'children'=>$children];
        $sides = [];
        foreach ($participants as $sideIndex=>$p) {
            $playerIndex = $i % 2;
            if ($sideIndex === 1 && $i >= 2) { $playerIndex = 1 - $playerIndex; }
            $sides[] = ['participant_id'=>$p, 'lineup_member_ids'=>$i===4 ? $members[$p] : [$members[$p][$playerIndex]]];
        }
        $nominations[] = ['sequence_number'=>$i+1, 'format_code'=>$i===4 ? 'doubles':'singles', 'sides'=>$sides];
    }
    $repo = new MatchResultRepository($db);
    // The result repository owns its transaction; do not nest Joomla transactions.
    $db->transactionCommit();
    $fixtureCommitted = true;
    $repo->replace($match, ['result_type'=>'nested_score','status_code'=>'final','outcome_code'=>'completed','encounter_revision'=>'new','segments'=>[['level_code'=>'result','values'=>[],'children'=>$duels]],'duels'=>$nominations], 0);
    if (array_column($repo->get($match)['segments'][0]['values'],'numeric_value') !== ['3.000000000','2.000000000']) { throw new RuntimeException('Unexpected aggregate score.'); }
    echo json_encode(['project'=>$project,'match'=>$match,'result'=>'3:2','singles'=>4,'doubles'=>1], JSON_THROW_ON_ERROR) . "\n";
} catch (Throwable $e) {
    if (!$fixtureCommitted) { $db->transactionRollback(); }
    else {
        $db->transactionStart();
        foreach (['project'=>[$project], 'team'=>$teams, 'person'=>$people, 'sport_type'=>[$sport], 'competition'=>[$competition], 'season'=>[$season]] as $table=>$ids) {
            foreach ($ids as $id) { $db->setQuery($db->getQuery(true)->delete($db->quoteName('#__joomleague_' . $table))->where('id = ' . (int)$id))->execute(); }
        }
        $db->transactionCommit();
    }
    throw $e;
}
