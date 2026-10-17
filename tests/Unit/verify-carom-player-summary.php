<?php
declare(strict_types=1);
define('_JEXEC', 1);
require_once dirname(__DIR__, 2) . '/administrator/components/com_joomleague/src/Service/CaromPlayerSummary.php';
use Joomleague\Component\Joomleague\Administrator\Service\CaromPlayerSummary;
$game = static fn (int $id, int $project = 1): object => (object) ['id' => $id, 'match_id' => $id, 'sequence_number' => 1, 'project_id' => $project, 'project_name' => 'Project ' . $project, 'season_id' => 1, 'season_name' => '2026', 'competition_name' => 'League', 'scheduled_start' => null, 'timezone' => 'UTC'];
$row = static fn (int $side, int $person, string $score, ?int $innings, ?int $run): object => (object) [
    'participant_id' => $side, 'member_id' => $person, 'nominated_person_id' => $person, 'person_id' => $person,
    'first_name' => 'Player', 'last_name' => (string) $person, 'numeric_value' => $score,
    'metadata_json' => json_encode(['innings' => $innings, 'high_run' => $run]),
];
$games = [1 => $game(1), 2 => $game(2), 3 => $game(3), 4 => $game(4, 2)];
$rows = [
    1 => [$row(10, 7, '40.000000000', 20, 9), $row(20, 8, '30', 20, 8)],
    2 => [$row(10, 7, '20', 40, 8), $row(20, 8, '40', 40, 10)],
    3 => [$row(10, 7, '30', 50, 7), $row(20, 8, '30', 50, 7)],
    4 => [$row(10, 7, '40', 10, 15), $row(20, 8, '30', 10, 8)],
];
$calculator = new CaromPlayerSummary(); $result = $calculator->build(7, $games, $rows);
$summary = $result['projects'][0];
if ($summary['games'] !== 3 || $summary['wins'] !== 1 || $summary['losses'] !== 1 || $summary['draws'] !== 1 || $summary['caroms'] !== 90 || $summary['innings'] !== 110 || abs($summary['average'] - 90 / 110) > 0.000001 || $summary['best_average'] != 2.0 || $summary['high_run'] !== 9 || count($result['projects']) !== 2) throw new RuntimeException('Incorrect project summaries / weighted averages.');
$rows[1][1]->person_id = null;
if ($calculator->build(7, $games, $rows)['history'][0]['opponent_name'] !== '') throw new RuntimeException('Private opponent identity leaked.');
$rows[2][0]->metadata_json = '{}';
if ($calculator->build(7, $games, $rows)['projects'][0]['average'] !== null) throw new RuntimeException('Incomplete innings produced average.');
$rows[3][] = $row(10, 9, '30', 50, 7);
if (count($calculator->build(7, $games, $rows)['history']) !== 3) throw new RuntimeException('Ambiguous singles attributed to player.');
$rows[4][1]->nominated_person_id = 7;
if (count($calculator->build(7, $games, $rows)['history']) !== 2) throw new RuntimeException('Self-opponent accepted.');
$rows[1][0]->numeric_value = '1.5';
if (count($calculator->build(7, $games, $rows)['history']) !== 1) throw new RuntimeException('Non-integer caroms accepted.');
echo "Carom player summary OK: W/D/L, weighted average, separate projects, missing data, privacy and ambiguous nominations.\n";
