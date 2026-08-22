<?php

declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Domain\Service;

defined('_JEXEC') or die;

use Joomleague\Component\Joomleague\Domain\Service\StandingsDecimal;

final class StandingsCalculator
{
	public function __construct(private readonly StandingsContractValidator $validator = new StandingsContractValidator()) {}

	/**
	 * @param array<string,mixed> $contract
	 * @param list<array{id:int,name:string,included?:bool}> $entries
	 * @param list<array<string,mixed>> $matches
	 * @return list<array{id:int,name:string,rank:int,metrics:array<string,?string>}>
	 */
	public function calculate(array $contract, array $entries, array $matches, ?string $scope = null, array $adjustments = []): array
	{
		$this->validator->validate($contract);
		$scopeDefinition = $this->scope($contract['scopes'], $scope);
		if (count($entries) > 10000 || count($matches) > 100000) throw new \LengthException('Standings input exceeds the calculation limit.');
		$rows = []; $metricDefinitions = [];
		foreach ($contract['metrics'] as $metric) $metricDefinitions[$metric['code']] = $metric;
		foreach ($entries as $entry) {
			$id = filter_var($entry['id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
			if ($id === false || isset($rows[$id]) || !is_string($entry['name'] ?? null)) throw new \InvalidArgumentException('Standings entry is invalid.');
			if (($entry['included'] ?? true) !== true) continue;
			$initial = []; foreach ($metricDefinitions as $code => $definition) $initial[$code] = in_array($definition['operation'], ['best_rank', 'status_order'], true) ? null : '0';
			$rows[$id] = ['id' => $id, 'name' => trim($entry['name']), 'rank' => 0, 'metrics' => $initial];
		}
		$eligibleStatuses = array_fill_keys($contract['included_result_statuses'], true);
		foreach ($matches as $match) {
			if (!isset($eligibleStatuses[$match['status'] ?? '']) || !is_array($match['participants'] ?? null)) continue;
			$allParticipants = [];
			foreach ($match['participants'] as $participant) {
				$id = (int) ($participant['entry_id'] ?? 0);
				if ($id > 0) $allParticipants[$id] = $this->participant($participant);
			}
			$participants = array_intersect_key($allParticipants, $rows);
			if ($participants === []) continue;
			if ($contract['mode'] === 'head_to_head' && count($allParticipants) !== 2) throw new \UnexpectedValueException('Head-to-head standings require two participants per match.');
			$outcomes = $contract['mode'] === 'head_to_head' ? $this->outcomes($allParticipants, $contract['outcome_source'] ?? 'root_numeric') : [];
			foreach ($participants as $entryId => $participant) {
				$opponent = $contract['mode'] === 'head_to_head' ? current(array_filter($allParticipants, static fn (int $id): bool => $id !== $entryId, ARRAY_FILTER_USE_KEY)) : null;
				$facts = $this->facts($entryId, $participant, is_array($opponent) ? $opponent : null, $outcomes[$entryId] ?? null, $match);
				if (!$this->scopeIncludes($scopeDefinition['filter'], $facts)) continue;
				$award = $this->award($contract['awards'], $facts);
				foreach ($metricDefinitions as $code => $definition) $this->accumulate($rows[$entryId]['metrics'], $code, $definition, $facts, $award);
			}
		}
		foreach ($adjustments as $adjustment) {
			$entryId = filter_var($adjustment['entry_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
			$metricCode = $adjustment['metric'] ?? null;
			if ($entryId === false || !isset($rows[$entryId]) || !is_string($metricCode) || !isset($metricDefinitions[$metricCode])) throw new \InvalidArgumentException('Standings adjustment target is invalid.');
			if (in_array($metricDefinitions[$metricCode]['operation'], ['difference', 'ratio'], true)) throw new \InvalidArgumentException('Derived standings metrics cannot be adjusted directly.');
			$value = StandingsDecimal::normalize((string) ($adjustment['value'] ?? ''));
			$rows[$entryId]['metrics'][$metricCode] = StandingsDecimal::add($rows[$entryId]['metrics'][$metricCode] ?? '0', $value);
		}
		foreach ($rows as &$row) foreach ($metricDefinitions as $code => $definition) {
			if ($definition['operation'] === 'difference') $row['metrics'][$code] = StandingsDecimal::subtract($row['metrics'][$definition['left_metric']] ?? '0', $row['metrics'][$definition['right_metric']] ?? '0');
			if ($definition['operation'] === 'ratio') $row['metrics'][$code] = StandingsDecimal::divide($row['metrics'][$definition['left_metric']] ?? '0', $row['metrics'][$definition['right_metric']] ?? '0');
		}
		unset($row);
		$rows = array_values($rows); $ordering = $contract['ordering'];
		usort($rows, fn (array $left, array $right): int => $this->compareRows($left, $right, $ordering, true));
		$previous = null;
		foreach ($rows as $index => &$row) { $row['rank'] = $previous !== null && $this->compareRows($previous, $row, $ordering, false) === 0 ? $previous['rank'] : $index + 1; $previous = $row; }
		return $rows;
	}

	/** @param list<array<string,mixed>> $scopes @return array<string,mixed> */
	private function scope(array $scopes, ?string $requested): array
	{
		$requested ??= (string) ($scopes[0]['code'] ?? '');
		foreach ($scopes as $scope) if (($scope['code'] ?? null) === $requested) return $scope;
		throw new \InvalidArgumentException('Standings scope is not defined by the profile.');
	}

	/** @param array<string,mixed> $filter @param array<string,mixed> $facts */
	private function scopeIncludes(array $filter, array $facts): bool
	{
		return match ($filter['type']) {
			'always' => true,
			'participant_slot' => $facts['participant']['slot'] === (int) $filter['value'],
			default => false,
		};
	}

	/** @param array<string,mixed> $participant @return array<string,mixed> */
	private function participant(array $participant): array
	{
		$value = $participant['root_value'] ?? null;
		if ($value !== null) $value = StandingsDecimal::normalize((string) $value);
		$rank = $participant['result_rank'] ?? null;
		if ($rank !== null && filter_var($rank, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) === false) throw new \InvalidArgumentException('Participant result rank is invalid.');
		return ['entry_id' => (int) $participant['entry_id'], 'root_value' => $value, 'result_rank' => $rank === null ? null : (int) $rank, 'status' => (string) ($participant['status'] ?? ''), 'slot' => (int) ($participant['slot'] ?? 0)];
	}

	/** @param array<int,array<string,mixed>> $participants @return array<int,string> */
	private function outcomes(array $participants, string $source): array
	{
		$ids = array_keys($participants); [$left, $right] = [$participants[$ids[0]], $participants[$ids[1]]];
		if ($source === 'participant_status') {
			$outcomes = [];
			foreach ($participants as $id => $participant) $outcomes[$id] = match ($participant['status']) { 'winner' => 'win', 'loser' => 'loss', 'draw', 'no_contest' => 'draw', default => throw new \UnexpectedValueException('Final participant status does not determine an outcome.') };
			return $outcomes;
		}
		if ($left['root_value'] === null || $right['root_value'] === null) throw new \UnexpectedValueException('Final head-to-head result requires numeric root values.');
		$comparison = StandingsDecimal::compare($left['root_value'], $right['root_value']);
		return $comparison === 0 ? [$ids[0] => 'draw', $ids[1] => 'draw'] : ($comparison > 0 ? [$ids[0] => 'win', $ids[1] => 'loss'] : [$ids[0] => 'loss', $ids[1] => 'win']);
	}

	/** @return array<string,mixed> */
	private function facts(int $entryId, array $participant, ?array $opponent, ?string $outcome, array $match): array
	{
		$segments = [];
		foreach (($match['segments'] ?? []) as $segment) if (is_array($segment) && is_string($segment['code'] ?? null) && is_array($segment['values'] ?? null)) $segments[] = ['code' => $segment['code'], 'values' => $segment['values']];
		$statistics = is_array($match['statistics'][$entryId] ?? null) ? $match['statistics'][$entryId] : [];
		$opponentId = $opponent['entry_id'] ?? null;
		return compact('entryId', 'participant', 'opponent', 'opponentId', 'outcome', 'segments', 'statistics');
	}

	/** @param array<string,mixed> $awards @param array<string,mixed> $facts */
	private function award(array $awards, array $facts): string
	{
		if ($awards['mode'] === 'none') return '0';
		$total = '0'; $matched = false;
		foreach ($awards['rules'] as $rule) if ($this->condition($rule['when'], $facts)) {
			$target = $awards['mode'] === 'rank_table' ? 'entry' : match ($facts['outcome']) { 'win' => 'winner', 'loss' => 'loser', default => 'draw' };
			$total = StandingsDecimal::add($total, (string) ($rule['values'][$target] ?? 0)); $matched = true;
			if (($awards['rule_strategy'] ?? 'first_match') === 'first_match') break;
		}
		foreach ($awards['bonus_rules'] ?? [] as $rule) if ($this->condition($rule['when'], $facts)) $total = StandingsDecimal::add($total, (string) $rule['value']);
		return $matched || ($awards['bonus_rules'] ?? []) !== [] ? $total : '0';
	}

	/** @param array<string,?string> $metrics @param array<string,mixed> $definition @param array<string,mixed> $facts */
	private function accumulate(array &$metrics, string $code, array $definition, array $facts, string $award): void
	{
		$operation = $definition['operation'];
		if (in_array($operation, ['difference', 'ratio'], true)) return;
		if (isset($definition['when']) && !$this->condition($definition['when'], $facts)) return;
		if ($operation === 'best_rank' || $operation === 'status_order') {
			$value = $operation === 'best_rank' ? $facts['participant']['result_rank'] : array_search($facts['participant']['status'], $definition['status_precedence'], true);
			if ($value === null || $value === false) return; $value = (string) $value;
			if ($metrics[$code] === null || StandingsDecimal::compare($value, $metrics[$code]) < 0) $metrics[$code] = $value;
			return;
		}
		$value = match ($operation) {
			'count_matches' => '1',
			'count_outcome' => $facts['outcome'] === ($definition['outcome'] ?? null) ? '1' : '0',
			'sum_root' => $this->perspectiveValue($facts, $definition['perspective'] ?? 'own'),
			'sum_awards' => $award,
			'sum_segment_values' => $this->segmentValue($facts, (string) ($definition['segment_code'] ?? ''), $definition['perspective'] ?? 'own', false),
			'sum_segment_wins' => $this->segmentValue($facts, (string) ($definition['segment_code'] ?? ''), $definition['perspective'] ?? 'own', true),
			'sum_statistic' => isset($facts['statistics'][$definition['statistic_code'] ?? '']) ? StandingsDecimal::normalize((string) $facts['statistics'][$definition['statistic_code']]) : '0',
			default => '0',
		};
		$metrics[$code] = StandingsDecimal::add($metrics[$code] ?? '0', $value ?? '0');
	}

	private function perspectiveValue(array $facts, string $perspective): string
	{
		$value = $perspective === 'opponent' ? ($facts['opponent']['root_value'] ?? null) : ($facts['participant']['root_value'] ?? null);
		return $value === null ? '0' : (string) $value;
	}

	private function segmentValue(array $facts, string $code, string $perspective, bool $winsOnly): string
	{
		$total = '0'; $ownId = $facts['entryId']; $opponentId = $facts['opponentId'];
		foreach ($facts['segments'] as $segment) {
			if ($segment['code'] !== $code) continue;
			$own = $segment['values'][$ownId] ?? null; $opponent = $opponentId === null ? null : ($segment['values'][$opponentId] ?? null);
			if ($winsOnly) {
				if ($own === null || $opponent === null) continue;
				$winner = StandingsDecimal::compare((string) $own, (string) $opponent);
				if (($perspective === 'own' && $winner > 0) || ($perspective === 'opponent' && $winner < 0)) $total = StandingsDecimal::add($total, '1');
			} else {
				$value = $perspective === 'opponent' ? $opponent : $own;
				if ($value !== null) $total = StandingsDecimal::add($total, (string) $value);
			}
		}
		return $total;
	}

	private function condition(array $condition, array $facts): bool
	{
		$type = $condition['type'];
		if ($type === 'always') return true;
		if ($type === 'all') return !in_array(false, array_map(fn (array $item): bool => $this->condition($item, $facts), $condition['conditions']), true);
		if ($type === 'any') return in_array(true, array_map(fn (array $item): bool => $this->condition($item, $facts), $condition['conditions']), true);
		$actual = match ($type) {
			'outcome' => $facts['outcome'],
			'decision_segment' => array_column(
				array_filter($facts['segments'], static fn (array $segment): bool => ($segment['values'] ?? []) !== []),
				'code'
			),
			'score_pair' => $facts['outcome'] === 'draw' ? ($facts['participant']['root_value'] . ':' . $facts['opponent']['root_value']) : (($facts['outcome'] === 'win' ? $facts['participant']['root_value'] : $facts['opponent']['root_value']) . ':' . ($facts['outcome'] === 'win' ? $facts['opponent']['root_value'] : $facts['participant']['root_value'])),
			'score_difference' => $facts['opponent'] === null ? null : StandingsDecimal::subtract((string) ($facts['participant']['root_value'] ?? 0), (string) ($facts['opponent']['root_value'] ?? 0)),
			'statistic' => $facts['statistics'][$condition['code'] ?? ''] ?? null,
			'result_rank' => $facts['participant']['result_rank'],
			'participant_status' => $facts['participant']['status'],
			default => null,
		};
		$operator = $condition['operator']; $expected = $condition['value'] ?? null;
		if ($operator === 'present') return is_array($actual) ? in_array($condition['code'] ?? $expected, $actual, true) : $actual !== null && $actual !== '';
		if ($operator === 'in') return is_array($expected) && in_array($actual, $expected, true);
		if (is_array($actual)) return in_array($expected, $actual, true) === ($operator === 'eq');
		if (is_numeric((string) $actual) && is_numeric((string) $expected)) $comparison = StandingsDecimal::compare((string) $actual, (string) $expected); else $comparison = strcmp((string) $actual, (string) $expected);
		return match ($operator) { 'eq' => $comparison === 0, 'neq' => $comparison !== 0, 'lt' => $comparison < 0, 'lte' => $comparison <= 0, 'gt' => $comparison > 0, 'gte' => $comparison >= 0, default => false };
	}

	/** @param list<array<string,string>> $ordering */
	private function compareRows(array $left, array $right, array $ordering, bool $fallback): int
	{
		foreach ($ordering as $clause) {
			$leftValue = $left['metrics'][$clause['metric']] ?? null; $rightValue = $right['metrics'][$clause['metric']] ?? null;
			if ($leftValue === null || $rightValue === null) { if ($leftValue === $rightValue) continue; $result = $leftValue === null ? ($clause['nulls'] === 'first' ? -1 : 1) : ($clause['nulls'] === 'first' ? 1 : -1); }
			else $result = StandingsDecimal::compare($leftValue, $rightValue);
			if ($result !== 0) return $clause['direction'] === 'desc' ? -$result : $result;
		}
		if (!$fallback) return 0;
		return strnatcasecmp($left['name'], $right['name']) ?: ($left['id'] <=> $right['id']);
	}
}
