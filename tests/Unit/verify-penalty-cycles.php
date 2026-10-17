<?php
declare(strict_types=1);
define('_JEXEC', 1);
$root = dirname(__DIR__, 2) . '/administrator/components/com_joomleague/src/Service/';
require $root . 'MatchResultValidationException.php';
require $root . 'EncounterPenaltySeries.php';
use Joomleague\Component\Joomleague\Administrator\Service\EncounterPenaltySeries;
use Joomleague\Component\Joomleague\Administrator\Service\MatchResultValidationException;
$rules = ['enabled' => true, 'required' => true, 'maximum_points' => 100, 'resolution' => 'team_cycle', 'cycle_length' => 4];
$rounds = static function (array $scores): array {
    $result = [];
    foreach ($scores as $index => [$a, $b]) $result[] = ['sequence_number' => $index + 1, 'status_code' => 'completed', 'children' => [], 'values' => [['participant_id' => 10, 'numeric_value' => (string) $a], ['participant_id' => 20, 'numeric_value' => (string) $b]]];
    return $result;
};
$service = new EncounterPenaltySeries();
$scores = [[5, 1], [0, 5], [0, 0], [0, 0]];
if ($service->resolve($rounds($scores), [10, 20], [10 => 4, 20 => 4], true, $rules) !== 20) throw new RuntimeException('Series ended after first player instead of full cycle.');
if ($service->resolve($rounds(array_slice($scores, 0, 2)), [10, 20], [10 => 4, 20 => 4], false, $rules) !== null) throw new RuntimeException('Incomplete draft declared winner.');
$ties = [[1, 1], [2, 2], [0, 0], [3, 3]];
if ($service->resolve($rounds([...$ties, ...$scores]), [10, 20], [10 => 4, 20 => 4], true, $rules) !== 20) throw new RuntimeException('Repeated tied cycle failed.');
foreach ([array_slice($scores, 0, 2), $ties, [...$scores, [0, 0]]] as $invalid) {
    try { $service->resolve($rounds($invalid), [10, 20], [10 => 4, 20 => 4], true, $rules); throw new RuntimeException('Invalid final accepted.'); }
    catch (MatchResultValidationException) {}
}
echo "Penalty cycles OK: full-team totals, incomplete drafts, repeated ties and final rejection.\n";
