<?php
declare(strict_types=1);
define('_JEXEC', 1);
$root = dirname(__DIR__, 2) . '/administrator/components/com_joomleague/';
foreach (['StandingsContractValidator', 'RoleDomain', 'SportProfileSchemaValidator', 'MatchResultValidationException', 'MatchResultDecimal', 'MatchResultAggregationValidator', 'EncounterPenaltySeries', 'TeamEncounterScoreNormalizer', 'MatchResultPayloadValidator', 'CaromEncounterSummary'] as $service) require_once $root . 'src/Service/' . $service . '.php';
use Joomleague\Component\Joomleague\Administrator\Service\MatchResultPayloadValidator;
use Joomleague\Component\Joomleague\Administrator\Service\MatchResultValidationException;
$profile = json_decode(file_get_contents($root . 'resources/sport-profiles/carom.json'), true, 512, JSON_THROW_ON_ERROR);
$segment = static fn (string $code, int $sequence, int $a, int $b): array => ['level_code' => $code, 'sequence_number' => $sequence, 'status_code' => 'completed', 'values' => [['participant_id' => 10, 'numeric_value' => (string) $a, 'metadata' => ['innings' => 20]], ['participant_id' => 20, 'numeric_value' => (string) $b, 'metadata' => ['innings' => 20]]], 'children' => []];
$regular = [$segment('duel', 1, 40, 17), $segment('duel', 2, 20, 40), $segment('duel', 3, 30, 30), $segment('duel', 4, 35, 35)];
$payload = static fn (array $children, string $status = 'final'): array => ['result_type' => 'nested_score', 'status_code' => $status, 'outcome_code' => 'completed', 'segments' => [['level_code' => 'result', 'sequence_number' => 1, 'values' => [], 'children' => $children]]];
$validator = new MatchResultPayloadValidator();
$result = $validator->validate($profile, [10, 20], $payload([...$regular, $segment('penalty_round', 1, 2, 2), $segment('penalty_round', 2, 3, 5)]));
$values = $result['segments'][0]['values'];
if (array_column($values, 'numeric_value') !== ['4', '4'] || array_column($values, 'status_code') !== ['loser', 'winner'] || array_column($values, 'result_rank') !== [2, 1]) throw new RuntimeException('Penalty series changed the official score or failed to derive the winner.');
$segments = []; $bySegment = [];
foreach ($result['segments'][0]['children'] as $index => $child) {
	$segments[] = (object) ['id' => $index + 1, 'level_code' => $child['level_code'], 'status_code' => $child['status_code']];
	foreach ($child['values'] as $value) $bySegment[$index + 1][$value['participant_id']] = (object) $value;
}
$summary = (new \Joomleague\Component\Joomleague\Administrator\Service\CaromEncounterSummary())->summarize($segments, $bySegment, [10, 20]);
if ($summary[10]['caroms'] !== 125 || $summary[10]['innings'] !== 80 || $summary[20]['caroms'] !== 122 || $summary[20]['games'] !== 4) throw new RuntimeException('Penalty caroms or innings entered playing statistics.');
$required = $profile; $required['match']['encounter']['penalty_series']['required'] = true;
$expectRejection = static function (array $p, array $children, string $key) use ($validator, $payload): void {
	try { $validator->validate($p, [10, 20], $payload($children)); }
	catch (MatchResultValidationException $error) { if ($error->getLanguageKey() !== 'COM_JOOMLEAGUE_CAROM_PENALTY_ERROR_' . $key) throw $error; return; }
	throw new RuntimeException('Invalid penalty series accepted: ' . $key);
};
$expectRejection($required, $regular, 'REQUIRED');
$expectRejection($profile, [...$regular, $segment('penalty_round', 1, 3, 3)], 'UNRESOLVED');
$expectRejection($profile, [...$regular, $segment('penalty_round', 2, 3, 1)], 'ORDER');
$expectRejection($profile, [...$regular, $segment('penalty_round', 1, 3, 1), $segment('penalty_round', 2, 1, 3)], 'ORDER');
$expectRejection($profile, [...$regular, $segment('penalty_round', 1, 100001, 1)], 'SCORE');
$won = $regular; $won[1] = $segment('duel', 2, 40, 20);
$expectRejection($profile, [...$won, $segment('penalty_round', 1, 3, 1)], 'NOT_ALLOWED');
$disabled = $profile; $disabled['match']['encounter']['penalty_series']['enabled'] = false;
$expectRejection($disabled, [...$regular, $segment('penalty_round', 1, 3, 1)], 'NOT_ALLOWED');
$validator->validate($required, [10, 20], $payload([...$regular, $segment('penalty_round', 1, 3, 3)], 'in_progress'));
$validator->validate($required, [10, 20], $payload($won));
$forged = $payload([...$regular, $segment('penalty_round', 1, 1, 3)]);
$forged['segments'][0]['values'] = [['participant_id' => 10, 'numeric_value' => '999', 'status_code' => 'winner'], ['participant_id' => 20, 'numeric_value' => '0', 'status_code' => 'loser']];
$safe = $validator->validate($profile, [10, 20], $forged);
if (array_column($safe['segments'][0]['values'], 'numeric_value') !== ['4', '4'] || $safe['segments'][0]['values'][1]['status_code'] !== 'winner') throw new RuntimeException('Forged root winner or scores survived normalisation.');
echo "Carom penalties OK: separate winner, unchanged 4:4, statistical exclusion, drafts, limits, ordering and forged-result rejection\n";
