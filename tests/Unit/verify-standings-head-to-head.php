<?php
declare(strict_types=1);
define('_JEXEC', 1);
$root = __DIR__ . '/../../administrator/components/com_joomleague/src/Service/';
foreach (['StandingsDecimal', 'StandingsContractValidator', 'StandingsCalculator'] as $class) require_once $root . $class . '.php';
use Joomleague\Component\Joomleague\Domain\Service\StandingsCalculator;
$metric = static fn (string $code, string $operation, array $extra = []): array => ['code' => $code, 'operation' => $operation, 'value_type' => 'decimal', ...$extra];
$clause = static fn (string $code, string $scope = 'overall'): array => ['metric' => $code, 'direction' => 'desc', 'nulls' => 'last', 'scope' => $scope];
$contract = ['mode' => 'head_to_head', 'included_result_statuses' => ['final'], 'scopes' => [['code' => 'total', 'filter' => ['type' => 'always']]], 'metrics' => [
	$metric('points', 'sum_awards'), $metric('caroms', 'sum_statistic', ['statistic_code' => 'caroms']), $metric('innings', 'sum_statistic', ['statistic_code' => 'innings']), $metric('average', 'ratio', ['left_metric' => 'caroms', 'right_metric' => 'innings'])
], 'awards' => ['mode' => 'outcome_rules', 'rules' => [['when' => ['type' => 'always'], 'values' => ['winner' => 2, 'draw' => 1, 'loser' => 0]]]], 'ordering' => [$clause('points'), $clause('points', 'head_to_head'), $clause('average', 'head_to_head')]];
$entries = [['id' => 1, 'name' => 'Alpha'], ['id' => 2, 'name' => 'Beta'], ['id' => 3, 'name' => 'Gamma'], ['id' => 4, 'name' => 'Outside']];
$match = static fn (int $a, int $b, int $scoreA, int $scoreB, int $caromsA, int $caromsB, ?int $innings): array => ['status' => 'final', 'participants' => [['entry_id' => $a, 'root_value' => (string) $scoreA], ['entry_id' => $b, 'root_value' => (string) $scoreB]], 'statistics' => [$a => ['caroms' => (string) $caromsA, 'innings' => $innings === null ? null : (string) $innings], $b => ['caroms' => (string) $caromsB, 'innings' => $innings === null ? null : (string) $innings]]];
$matches = [$match(1, 2, 6, 2, 40, 10, 20), $match(2, 3, 6, 2, 40, 10, 20), $match(3, 1, 6, 2, 40, 20, 20)];
$calculator = new StandingsCalculator();
$rows = $calculator->calculate($contract, array_slice($entries, 0, 3), $matches);
if (array_column($rows, 'id') !== [1, 2, 3] || array_column($rows, 'rank') !== [1, 2, 2]) throw new RuntimeException('Three-way cycle was evaluated using pairwise comparisons.');
$rowsReversed = $calculator->calculate($contract, array_reverse(array_slice($entries, 0, 3)), array_reverse($matches));
if ($rowsReversed !== $rows) throw new RuntimeException('Head-to-head ranking depends on input order.');

// Outside results change the full average but must not enter the mini-table.
$withOutside = [...$matches, $match(1, 4, 6, 2, 1, 0, 100), $match(2, 4, 6, 2, 500, 0, 1), $match(3, 4, 6, 2, 400, 0, 1)];
$outsideRows = $calculator->calculate($contract, $entries, $withOutside);
if (array_column($outsideRows, 'id') !== [1, 2, 3, 4] || $outsideRows[0]['metrics']['caroms'] !== '61') throw new RuntimeException('Outside matches leaked into head-to-head or replaced overall metrics.');
$draft = $match(2, 1, 8, 0, 999, 0, 1); $draft['status'] = 'draft';
if ($calculator->calculate($contract, $entries, [...$withOutside, $draft]) !== $outsideRows) throw new RuntimeException('Draft affected head-to-head.');

// Mini-tables do not include global disciplinary adjustments.
$pair = [$entries[0], $entries[1]];
$pairMatch = [$match(2, 1, 6, 2, 40, 20, 20)];
$adjustments = [['entry_id' => 1, 'metric' => 'points', 'value' => '2']];
$pairRows = $calculator->calculate($contract, $pair, $pairMatch, null, $adjustments);
if (array_column($pairRows, 'id') !== [2, 1] || $pairRows[0]['metrics']['points'] !== '2' || $pairRows[1]['metrics']['points'] !== '2') throw new RuntimeException('Global corrections contaminated mini-table awards.');

// Consecutive mini-table criteria retain the original tied group, rather than restarting.
$fixed = $contract; $fixed['ordering'] = [$clause('points'), $clause('caroms', 'head_to_head'), $clause('average', 'head_to_head')];
$fixedMatches = [$match(1, 2, 1, 1, 30, 10, 10), $match(2, 3, 1, 1, 30, 10, 20), $match(3, 1, 1, 1, 10, 10, 30)];
$fixedRows = $calculator->calculate($fixed, array_slice($entries, 0, 3), $fixedMatches);
if (array_column($fixedRows, 'id') !== [2, 1, 3]) throw new RuntimeException('Head-to-head subgroup was incorrectly recalculated.');

// Missing innings stay unknown, including when the next criterion is overall.
$missing = $contract; $missing['ordering'] = [$clause('points'), $clause('average', 'head_to_head'), $clause('caroms')];
$unknown = $match(1, 2, 1, 1, 20, 30, null);
$unknownRows = $calculator->calculate($missing, $pair, [$unknown]);
if (array_column($unknownRows, 'id') !== [2, 1] || $unknownRows[0]['metrics']['average'] !== null) throw new RuntimeException('Missing mini-table averages did not fall through correctly.');
$noMatches = $calculator->calculate($contract, array_reverse($pair), []);
if (array_column($noMatches, 'id') !== [1, 2] || array_column($noMatches, 'rank') !== [1, 1]) throw new RuntimeException('Teams without mutual games must remain tied.');
$precise = $contract; $precise['ordering'] = [$clause('points'), $clause('average', 'head_to_head')];
$tiny = $match(1, 2, 1, 1, 1, 1, 1);
$tiny['statistics'][1]['innings'] = '4000000000'; $tiny['statistics'][2]['innings'] = '3000000000';
$tinyRows = $calculator->calculate($precise, $pair, [$tiny]);
if (array_column($tinyRows, 'id') !== [2, 1] || array_column($tinyRows, 'rank') !== [1, 2]) throw new RuntimeException('Rounded head-to-head ratios created a false tie.');

foreach (['invalid', 'Head_to_head'] as $scope) {
	$invalid = $contract; $invalid['ordering'][1]['scope'] = $scope;
	try { $calculator->calculate($invalid, $pair, $pairMatch); throw new RuntimeException('Invalid ordering scope accepted.'); } catch (UnexpectedValueException) {}
}
$classification = $contract; $classification['mode'] = 'classification';
try { $calculator->calculate($classification, $pair, []); throw new RuntimeException('Classification accepted mutual-match criteria.'); } catch (UnexpectedValueException) {}
echo "Head-to-head groups OK: cycles, fixed mini-table, input order, outsiders, draft exclusion, corrections, missing data and shared ranks\n";
