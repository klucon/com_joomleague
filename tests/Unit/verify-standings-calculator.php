<?php

declare(strict_types=1);

define('_JEXEC', 1);
$services = __DIR__ . '/../../administrator/components/com_joomleague/src/Service/';
foreach (['StandingsDecimal.php', 'StandingsContractValidator.php', 'StandingsCalculator.php'] as $service) require_once $services . $service;

use Joomleague\Component\Joomleague\Domain\Service\StandingsCalculator;
use Joomleague\Component\Joomleague\Domain\Service\StandingsDecimal;

$metric = static fn (string $code, string $operation, array $extra = []): array => array_merge(['code' => $code, 'operation' => $operation, 'value_type' => 'decimal'], $extra);
$headToHead = static function (array $rules, array $extraMetrics = [], array $ordering = [] ) use ($metric): array {
	$metrics = [
		$metric('played', 'count_matches'), $metric('wins', 'count_outcome', ['outcome' => 'win']), $metric('draws', 'count_outcome', ['outcome' => 'draw']), $metric('losses', 'count_outcome', ['outcome' => 'loss']),
		$metric('score_for', 'sum_root', ['perspective' => 'own']), $metric('score_against', 'sum_root', ['perspective' => 'opponent']),
		$metric('score_difference', 'difference', ['left_metric' => 'score_for', 'right_metric' => 'score_against']), $metric('points', 'sum_awards'),
		...$extraMetrics,
	];
	return ['mode' => 'head_to_head', 'included_result_statuses' => ['final'], 'scopes' => [['code' => 'total', 'filter' => ['type' => 'always']], ['code' => 'home', 'filter' => ['type' => 'participant_slot', 'value' => 1]], ['code' => 'away', 'filter' => ['type' => 'participant_slot', 'value' => 2]]], 'metrics' => $metrics, 'awards' => ['mode' => 'outcome_rules', 'rule_strategy' => 'first_match', 'rules' => $rules, 'bonus_rules' => []], 'ordering' => $ordering ?: [['metric' => 'points', 'direction' => 'desc', 'nulls' => 'last'], ['metric' => 'score_difference', 'direction' => 'desc', 'nulls' => 'last']]];
};
$entries = [['id' => 1, 'name' => 'Alpha'], ['id' => 2, 'name' => 'Beta']];
$match = static fn (string $left, string $right, array $segments = []): array => ['status' => 'final', 'participants' => [['entry_id' => 1, 'root_value' => $left, 'slot' => 1], ['entry_id' => 2, 'root_value' => $right, 'slot' => 2]], 'segments' => $segments, 'statistics' => []];
$calculator = new StandingsCalculator();

$regularRules = [['when' => ['type' => 'always'], 'values' => ['winner' => 3, 'draw' => 1, 'loser' => 0]]];
$football = $calculator->calculate($headToHead($regularRules), $entries, [$match('2', '1'), $match('0', '0')]);
if ($football[0]['id'] !== 1 || $football[0]['metrics']['points'] !== '4' || $football[1]['metrics']['points'] !== '1' || $football[0]['metrics']['score_difference'] !== '1') throw new RuntimeException('Football standings are invalid.');

$returnMatch = ['status' => 'final', 'participants' => [['entry_id' => 2, 'root_value' => '3', 'slot' => 1], ['entry_id' => 1, 'root_value' => '0', 'slot' => 2]], 'segments' => [], 'statistics' => []];
$home = $calculator->calculate($headToHead($regularRules), $entries, [$match('2', '1'), $returnMatch], 'home');
$homeById = array_column($home, null, 'id');
if ($home[0]['id'] !== 2 || $homeById[1]['metrics']['played'] !== '1' || $homeById[1]['metrics']['points'] !== '3' || $homeById[2]['metrics']['played'] !== '1' || $homeById[2]['metrics']['points'] !== '3') throw new RuntimeException('Home standings scope is invalid.');
$adjusted = $calculator->calculate($headToHead($regularRules), $entries, [$match('2', '1')], 'total', [['entry_id' => 1, 'metric' => 'points', 'value' => '-4']]);
if ($adjusted[0]['id'] !== 2 || $adjusted[1]['metrics']['points'] !== '-1') throw new RuntimeException('Standings adjustment is invalid.');
try { $calculator->calculate($headToHead($regularRules), $entries, [$match('2', '1')], 'total', [['entry_id' => 1, 'metric' => 'score_difference', 'value' => '1']]); throw new RuntimeException('Derived metric adjustment was accepted.'); } catch (InvalidArgumentException) {}

$hockeyRules = [
	['when' => ['type' => 'decision_segment', 'operator' => 'present', 'code' => 'shootout'], 'values' => ['winner' => 2, 'loser' => 1, 'draw' => 0]],
	['when' => ['type' => 'decision_segment', 'operator' => 'present', 'code' => 'extra_time'], 'values' => ['winner' => 2, 'loser' => 1, 'draw' => 0]],
	['when' => ['type' => 'always'], 'values' => ['winner' => 3, 'loser' => 0, 'draw' => 0]],
];
$hockey = $calculator->calculate($headToHead($hockeyRules), $entries, [$match('4', '3', [['code' => 'shootout', 'values' => [1 => '1', 2 => '0']]])]);
if ($hockey[0]['metrics']['points'] !== '2' || $hockey[1]['metrics']['points'] !== '1') throw new RuntimeException('Shootout points are invalid.');

$setMetrics = [$metric('sets_for', 'sum_segment_wins', ['segment_code' => 'set', 'perspective' => 'own']), $metric('sets_against', 'sum_segment_wins', ['segment_code' => 'set', 'perspective' => 'opponent']), $metric('set_difference', 'difference', ['left_metric' => 'sets_for', 'right_metric' => 'sets_against'])];
$volleyballRules = [
	['when' => ['type' => 'score_pair', 'operator' => 'eq', 'value' => '3:2'], 'values' => ['winner' => 2, 'loser' => 1, 'draw' => 0]],
	['when' => ['type' => 'always'], 'values' => ['winner' => 3, 'loser' => 0, 'draw' => 0]],
];
$sets = [['code' => 'set', 'values' => [1 => '25', 2 => '20']], ['code' => 'set', 'values' => [1 => '20', 2 => '25']], ['code' => 'set', 'values' => [1 => '15', 2 => '12']]];
$volleyball = $calculator->calculate($headToHead($volleyballRules, $setMetrics, [['metric' => 'points', 'direction' => 'desc', 'nulls' => 'last'], ['metric' => 'set_difference', 'direction' => 'desc', 'nulls' => 'last']]), $entries, [$match('3', '2', $sets)]);
if ($volleyball[0]['metrics']['points'] !== '2' || $volleyball[1]['metrics']['points'] !== '1' || $volleyball[0]['metrics']['sets_for'] !== '2') throw new RuntimeException('Nested-score standings are invalid.');

$chessRules = [['when' => ['type' => 'always'], 'values' => ['winner' => 1, 'draw' => '0.5', 'loser' => 0]]];
$chess = $calculator->calculate($headToHead($chessRules), $entries, [$match('0.5', '0.5')]);
if ($chess[0]['metrics']['points'] !== '0.5' || $chess[1]['metrics']['points'] !== '0.5' || $chess[0]['rank'] !== 1 || $chess[1]['rank'] !== 1) throw new RuntimeException('Decimal points or tied ranks are invalid.');

$raceContract = [
	'mode' => 'classification', 'included_result_statuses' => ['final'],
	'scopes' => [['code' => 'overall', 'filter' => ['type' => 'always']]],
	'metrics' => [$metric('races', 'count_matches'), $metric('elapsed', 'sum_root')],
	'awards' => ['mode' => 'none', 'rule_strategy' => 'first_match', 'rules' => [], 'bonus_rules' => []],
	'ordering' => [['metric' => 'elapsed', 'direction' => 'asc', 'nulls' => 'last']],
	'classification' => ['primary' => 'root_value', 'direction' => 'asc', 'status_precedence' => ['finished', 'dnf', 'dns', 'dsq']],
];
$raceEntries = [['id' => 1, 'name' => 'Runner A'], ['id' => 2, 'name' => 'Runner B'], ['id' => 3, 'name' => 'Runner C']];
$raceMatch = ['status' => 'final', 'participants' => [['entry_id' => 1, 'root_value' => '3723456', 'status' => 'finished'], ['entry_id' => 2, 'root_value' => '3699123', 'status' => 'finished'], ['entry_id' => 3, 'root_value' => '3800000', 'status' => 'finished']]];
$race = $calculator->calculate($raceContract, $raceEntries, [$raceMatch]);
if (array_column($race, 'id') !== [2, 1, 3]) throw new RuntimeException('Lower-is-better classification is invalid.');

if (StandingsDecimal::add('999999999999999999999.999999999', '0.000000001') !== '1000000000000000000000' || StandingsDecimal::divide('1', '3') !== '0.333333333') throw new RuntimeException('Exact standings decimal arithmetic is invalid.');
echo "Universal football, hockey, volleyball, chess and race standings kernel OK\n";
