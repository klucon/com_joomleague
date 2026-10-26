<?php
declare(strict_types=1);
define('_JEXEC', 1);
require __DIR__ . '/../../administrator/components/com_joomleague/src/Service/PlayerParticipation.php';
use Joomleague\Component\Joomleague\Domain\Service\PlayerParticipation;
$make = static fn(int $id, string $status): object => (object) ['id' => $id, 'lineup_status' => $status, 'metadata_json' => null];
$change = static fn(int $out, int $in, mixed $time, string $unit = 'minute'): object => (object) ['incoming_lineup_member_id' => $in, 'outgoing_lineup_member_id' => $out, 'clock_value' => $time, 'clock_unit' => $unit, 'phase_sequence' => null];
$legacy = static fn(array $row): object => (object) ['metadata_json' => json_encode(['legacy_source_table' => 'match_player', 'legacy' => $row])];
$cases = [
    [$make(1, 'starter'), [], [], 90, true, 90.0],
    [$make(1, 'available'), [], [], 90, false, null],
    [$make(1, 'substitute'), [], [], 90, false, null],
    [$make(1, 'starter'), [], [$change(1, 2, 61)], 90, true, 61.0],
    [$make(1, 'substitute'), [], [$change(2, 1, 76)], 90, true, 14.0],
    [$make(1, 'substitute'), [], [$change(2, 1, 10), $change(1, 2, 20), $change(2, 1, 30), $change(1, 2, 40)], 90, true, 20.0],
    [$make(1, 'starter'), [], [$change(1, 2, 120)], 90, true, null],
    [$make(1, 'starter'), [], [$change(1, 2, null)], 90, true, null],
    [$make(1, 'starter'), [], [$change(1, 2, 600, 'second')], 90, true, 10.0],
    [$make(1, 'starter'), [], [], null, true, null],
    [$legacy(['teamplayer_id' => 7, 'came_in' => 0]), [], [], 90, true, 90.0],
    [$legacy(['teamplayer_id' => 7, 'came_in' => 1, 'in_for' => 8, 'in_out_time' => '76']), [], [], 90, true, 14.0],
    [$legacy(['teamplayer_id' => 7, 'came_in' => 0]), [$legacy(['came_in' => 1, 'in_for' => 7, 'in_out_time' => '61'])], [], 90, true, 61.0],
    [$legacy(['teamplayer_id' => 7, 'came_in' => 1, 'in_for' => null]), [], [], 90, true, 0.0],
    [$legacy(['teamplayer_id' => 7, 'came_in' => 1, 'in_for' => 8, 'in_out_time' => '93']), [], [], 90, true, -3.0],
];
foreach ($cases as [$member, $lineups, $changes, $duration, $played, $minutes]) {
    $before = serialize([$member, $lineups, $changes]);
    $result = PlayerParticipation::calculate($member, $lineups, $changes, $duration === null ? null : (float) $duration);
    if ($result['played'] !== $played || $result['minutes'] !== $minutes) throw new RuntimeException('Participation regression: ' . json_encode($result));
    if ($before !== serialize([$member, $lineups, $changes])) throw new RuntimeException('Participation mutated input');
}
$nativeDismissal = PlayerParticipation::calculate($make(1, 'starter'), [], [], 90.0, 55.0);
$legacyDismissal = PlayerParticipation::calculate($legacy(['teamplayer_id' => 7, 'came_in' => 0]), [], [], 90.0, 70.0);
if ($nativeDismissal['minutes'] !== 55.0 || $legacyDismissal['minutes'] !== 70.0) throw new RuntimeException('Dismissal timing regression');
echo json_encode(['passed' => count($cases) + 2, 'coverage' => 'native, unused bench, re-entry, unknown time, seconds, legacy bridge, dismissal, non-mutation']), PHP_EOL;
