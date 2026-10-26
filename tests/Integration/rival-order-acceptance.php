<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli' || !str_starts_with((string) getenv('JL_R1_DB_HOST'), 'jl-r1-')) throw new RuntimeException('Disposable target required');
define('_JEXEC', 1);
define('JPATH_BASE', '/var/www/html');
$_SERVER['HTTP_HOST'] = 'localhost';
$_SERVER['SCRIPT_NAME'] = '/index.php';
$_SERVER['REQUEST_URI'] = '/';
require JPATH_BASE . '/includes/defines.php';
require JPATH_BASE . '/includes/framework.php';
$container = Joomla\CMS\Factory::getContainer();
$container->alias('session', 'session.cli')->alias(Joomla\Session\SessionInterface::class, 'session.cli');
$app = $container->get(Joomla\CMS\Application\SiteApplication::class);
Joomla\CMS\Factory::$application = $app;
if ($app->get('host') !== getenv('JL_R1_DB_HOST') || $app->get('db') !== 'r1_test' || $app->get('dbprefix') !== 'r1_') throw new RuntimeException('Unsafe target');
$app->createExtensionNamespaceMap();
$app->bootComponent('com_joomleague');
$db = $container->get(Joomla\Database\DatabaseInterface::class);
$fixture = json_decode(file_get_contents('/tmp/jl-r1-fixture.json'), true, 512, JSON_THROW_ON_ERROR);
$a = $fixture['projects']['a'];
$insert = static function (string $table, array $data, bool $published = true) use ($db): int {
    $row = (object) (['uuid' => Joomleague\Component\Joomleague\Domain\Service\UuidFactory::v4()] + $data + ($published ? ['published' => 1] : []));
    $db->insertObject('#__joomleague_' . $table, $row, 'id');
    return (int) $row->id;
};
$clubs = [];
$teams = [];
foreach (['own', 'opponent'] as $index => $kind) {
    $clubs[] = $insert('club', ['name' => 'R104 ' . $kind . ' club']);
    $team = (int) $db->setQuery('SELECT team_id FROM #__joomleague_project_entry WHERE id=' . (int) $a['entries'][$index])->loadResult();
    $teamUpdate = (object) ['id' => $team, 'club_id' => $clubs[$index], 'name' => 'R104 ' . $kind . ' team'];
    $db->updateObject('#__joomleague_team', $teamUpdate, 'id');
    $teams[] = $team;
}
$matches = [];
foreach ([false, true] as $away) {
    $match = $insert('project_match', ['project_id' => $a['id'], 'stage_id' => $a['stage'], 'round_id' => $a['round'], 'scheduled_start' => $away ? '2026-10-02 12:00:00' : '2026-10-01 12:00:00']);
    $insert('match_result', ['match_id' => $match, 'result_type' => 'numeric_score', 'status_code' => 'final'], false);
    $root = $insert('match_score_segment', ['match_id' => $match, 'level_code' => 'result', 'sequence_number' => 1], false);
    foreach ($a['entries'] as $index => $entry) {
        $participant = $insert('match_participant', ['project_id' => $a['id'], 'match_id' => $match, 'project_entry_id' => $entry, 'slot_number' => $away ? 2 - $index : $index + 1]);
        $insert('match_score_value', ['match_id' => $match, 'segment_id' => $root, 'participant_id' => $participant, 'numeric_value' => $index === 0 ? ($away ? 4 : 2) : 1], false);
    }
    $matches[$away ? 'away' : 'home'] = $match;
}
$reader = new Joomleague\Component\Joomleague\Domain\Service\ClubRivalHistoryReader($db);
$count = 0;
$assert = static function (bool $ok, string $message) use (&$count): void { if (!$ok) throw new RuntimeException($message); ++$count; };
$data = $reader->read($clubs[0], ['opponent_club_id' => $clubs[1]], [1]);
$assert(count($data['matches']) === 2, 'Both fixtures included');
$detail = array_column($data['matches'], null, 'match_id');
foreach (['home' => ['R104 own team', 'R104 opponent team', '2', '1'], 'away' => ['R104 opponent team', 'R104 own team', '1', '4']] as $kind => $expected) {
    $row = $detail[$matches[$kind]];
    $assert([$row['first_team_name'], $row['second_team_name'], $row['first_score'], $row['second_score']] === $expected, 'Participant slot order and score: ' . $kind);
    $assert($row['outcome'] === 'win', 'Outcome remains club-relative');
}
$summary = $data['opponents'][0];
$assert([$summary['played'], $summary['wins'], $summary['scored'], $summary['conceded']] === [2, 2, '6', '2'], 'Club-relative totals preserved');
$teamData = $reader->read($clubs[0], ['team_id' => $teams[0]], [1]);
if (count($teamData['matches']) !== 2 || (int) $teamData['opponents'][0]['opponent_team_id'] !== $teams[1]
    || !isset($teamData['options']['teams'][$teams[0]]) || isset($teamData['options']['teams'][$teams[1]])) {
    throw new RuntimeException('Team scope or accessible own-club team options failed');
}
if ($reader->read($clubs[0], ['team_id' => $teams[1]], [1])['matches'] !== []
    || $reader->read($clubs[0], ['team_id' => $teams[0], 'opponent_team_id' => $teams[0]], [1])['matches'] !== []) {
    throw new RuntimeException('Foreign or mismatched team filter crossed scope');
}
$reverse = $reader->read($clubs[1], ['opponent_club_id' => $clubs[0]], [1]);
$assert($reverse['matches'][0]['first_score'] === '1' && $reverse['matches'][0]['outcome'] === 'loss', 'Opposite club sees same event ordering');
$teamUpdate = (object) ['id' => $teams[1], 'access' => 2];
$db->updateObject('#__joomleague_team', $teamUpdate, 'id');
$assert($reader->read($clubs[0], [], [1])['matches'] === [], 'Private opponent excluded');
$teamUpdate->access = 1;
$db->updateObject('#__joomleague_team', $teamUpdate, 'id');
file_put_contents('/tmp/jl-rival-order.json', json_encode(['club' => $clubs[0], 'opponent' => $clubs[1], 'teams' => $teams, 'matches' => $matches], JSON_THROW_ON_ERROR));
require __DIR__ . '/rival-scope-acceptance.php';
echo 'PASS: rival order; ' . $count . " mandatory assertions.\n";
