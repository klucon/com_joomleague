<?php

declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Domain\Service;

defined('_JEXEC') or die;

final class StandingsContractValidator
{
	private const OPERATIONS = ['count_matches', 'count_outcome', 'sum_root', 'sum_awards', 'difference', 'sum_segment_wins', 'sum_segment_values', 'sum_statistic', 'ratio', 'best_rank', 'status_order'];
	private const DIRECTIONS = ['asc', 'desc'];

	/** @param array<string,mixed> $contract */
	public function validate(array $contract): void
	{
		if (!in_array($contract['mode'] ?? null, ['head_to_head', 'classification'], true)) throw new \UnexpectedValueException('Standings calculation mode is invalid.');
		if (($contract['mode'] ?? null) === 'head_to_head' && !in_array($contract['outcome_source'] ?? 'root_numeric', ['root_numeric', 'participant_status'], true)) throw new \UnexpectedValueException('Standings outcome source is invalid.');
		$this->codes($contract['included_result_statuses'] ?? null, 'included result status');
		$this->scopes($contract['scopes'] ?? null);
		$metrics = $this->metrics($contract['metrics'] ?? null);
		$this->awards($contract['awards'] ?? null);
		$this->ordering($contract['ordering'] ?? null, $metrics);
		if (($contract['mode'] ?? null) === 'classification') $this->classification($contract['classification'] ?? null);
	}

	private function scopes(mixed $scopes): void
	{
		if (!is_array($scopes) || $scopes === [] || count($scopes) > 20) throw new \UnexpectedValueException('Standings scopes are required.');
		$known = [];
		foreach ($scopes as $scope) {
			if (!is_array($scope)) throw new \UnexpectedValueException('Standings scope is invalid.');
			$code = $this->code($scope['code'] ?? null, 'scope code');
			if (isset($known[$code])) throw new \UnexpectedValueException('Duplicate standings scope code.');
			$filter = $scope['filter'] ?? null;
			if (!is_array($filter) || !in_array($filter['type'] ?? null, ['always', 'participant_slot'], true)) throw new \UnexpectedValueException('Standings scope filter is invalid.');
			if (($filter['type'] ?? null) === 'participant_slot' && filter_var($filter['value'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 1000]]) === false) throw new \UnexpectedValueException('Standings participant slot filter is invalid.');
			$known[$code] = true;
		}
	}

	/** @return array<string,true> */
	private function metrics(mixed $metrics): array
	{
		if (!is_array($metrics) || $metrics === [] || count($metrics) > 100) throw new \UnexpectedValueException('Standings metrics are required.');
		$known = [];
		foreach ($metrics as $metric) {
			if (!is_array($metric)) throw new \UnexpectedValueException('Standings metric is invalid.');
			$code = $this->code($metric['code'] ?? null, 'metric code');
			if (isset($known[$code])) throw new \UnexpectedValueException('Duplicate standings metric code.');
			if (!in_array($metric['operation'] ?? null, self::OPERATIONS, true)) throw new \UnexpectedValueException('Standings metric operation is invalid.');
			if (!in_array($metric['value_type'] ?? null, ['integer', 'decimal', 'duration'], true)) throw new \UnexpectedValueException('Standings metric value type is invalid.');
			foreach (['source_metric', 'left_metric', 'right_metric'] as $reference) if (isset($metric[$reference]) && (!is_string($metric[$reference]) || !isset($known[$metric[$reference]]))) throw new \UnexpectedValueException('Standings metric references an unknown earlier metric.');
			foreach (['segment_code', 'statistic_code', 'outcome'] as $field) if (isset($metric[$field])) $this->code($metric[$field], $field);
			if (isset($metric['perspective']) && !in_array($metric['perspective'], ['own', 'opponent'], true)) throw new \UnexpectedValueException('Standings metric perspective is invalid.');
			if (isset($metric['when'])) $this->condition($metric['when'], 0);
			if (($metric['operation'] ?? null) === 'status_order') $this->codes($metric['status_precedence'] ?? null, 'metric status precedence');
			$known[$code] = true;
		}
		return $known;
	}

	private function awards(mixed $awards): void
	{
		if (!is_array($awards) || !in_array($awards['mode'] ?? null, ['none', 'outcome_rules', 'rank_table'], true)) throw new \UnexpectedValueException('Standings awards mode is invalid.');
		if (!in_array($awards['rule_strategy'] ?? 'first_match', ['first_match', 'all_match'], true)) throw new \UnexpectedValueException('Standings award strategy is invalid.');
		$rules = $awards['rules'] ?? [];
		if (!is_array($rules) || count($rules) > 100) throw new \UnexpectedValueException('Standings award rules are invalid.');
		if (($awards['mode'] ?? null) !== 'none' && $rules === []) throw new \UnexpectedValueException('Standings awards require rules.');
		if (($awards['mode'] ?? null) === 'none' && $rules !== []) throw new \UnexpectedValueException('Standings awards mode none cannot contain rules.');
		foreach ($rules as $rule) {
			if (!is_array($rule)) throw new \UnexpectedValueException('Standings award rule is invalid.');
			$this->condition($rule['when'] ?? null, 0);
			$values = $rule['values'] ?? null;
			if (!is_array($values) || $values === []) throw new \UnexpectedValueException('Standings award values are required.');
			foreach ($values as $target => $value) { $this->code($target, 'award target'); $this->decimal($value); }
		}
		$bonuses = $awards['bonus_rules'] ?? [];
		if (!is_array($bonuses) || count($bonuses) > 100) throw new \UnexpectedValueException('Standings bonus rules are invalid.');
		foreach ($bonuses as $rule) {
			if (!is_array($rule)) throw new \UnexpectedValueException('Standings bonus rule is invalid.');
			$this->condition($rule['when'] ?? null, 0);
			$this->decimal($rule['value'] ?? null);
		}
	}

	/** @param array<string,true> $metrics */
	private function ordering(mixed $ordering, array $metrics): void
	{
		if (!is_array($ordering) || $ordering === [] || count($ordering) > 50) throw new \UnexpectedValueException('Standings ordering is required.');
		foreach ($ordering as $clause) {
			if (!is_array($clause) || !isset($metrics[$clause['metric'] ?? ''])) throw new \UnexpectedValueException('Standings ordering references an unknown metric.');
			if (!in_array($clause['direction'] ?? null, self::DIRECTIONS, true) || !in_array($clause['nulls'] ?? null, ['first', 'last'], true)) throw new \UnexpectedValueException('Standings ordering clause is invalid.');
		}
	}

	private function classification(mixed $classification): void
	{
		if (!is_array($classification) || !in_array($classification['primary'] ?? null, ['result_rank', 'root_value'], true)) throw new \UnexpectedValueException('Classification source is invalid.');
		if (!in_array($classification['direction'] ?? null, self::DIRECTIONS, true)) throw new \UnexpectedValueException('Classification direction is invalid.');
		$this->codes($classification['status_precedence'] ?? null, 'classification status');
	}

	private function condition(mixed $condition, int $depth): void
	{
		if ($depth > 5 || !is_array($condition)) throw new \UnexpectedValueException('Standings condition is invalid.');
		$type = $condition['type'] ?? null;
		if (in_array($type, ['all', 'any'], true)) {
			$items = $condition['conditions'] ?? null;
			if (!is_array($items) || $items === [] || count($items) > 20) throw new \UnexpectedValueException('Composite standings condition is invalid.');
			foreach ($items as $item) $this->condition($item, $depth + 1);
			return;
		}
		if ($type === 'always') return;
		if (!in_array($type, ['outcome', 'decision_segment', 'score_pair', 'score_difference', 'statistic', 'result_rank', 'participant_status'], true)) throw new \UnexpectedValueException('Standings condition type is invalid.');
		if (!in_array($condition['operator'] ?? null, ['eq', 'neq', 'lt', 'lte', 'gt', 'gte', 'present', 'in'], true)) throw new \UnexpectedValueException('Standings condition operator is invalid.');
		if (isset($condition['code'])) $this->code($condition['code'], 'condition code');
		if (!array_key_exists('value', $condition) && ($condition['operator'] ?? null) !== 'present') throw new \UnexpectedValueException('Standings condition value is required.');
	}

	private function decimal(mixed $value): void
	{
		if ((!is_string($value) && !is_int($value)) || preg_match('/^-?\d{1,21}(?:\.\d{1,9})?$/', (string) $value) !== 1) throw new \UnexpectedValueException('Standings decimal value is invalid.');
	}

	private function codes(mixed $values, string $label): void
	{
		if (!is_array($values) || $values === [] || count($values) > 100) throw new \UnexpectedValueException(ucfirst($label) . ' codes are required.');
		$seen = [];
		foreach ($values as $value) { $code = $this->code($value, $label); if (isset($seen[$code])) throw new \UnexpectedValueException('Duplicate ' . $label . ' code.'); $seen[$code] = true; }
	}

	private function code(mixed $value, string $label): string
	{
		if (!is_string($value) || preg_match('/^[a-z][a-z0-9_]*$/', $value) !== 1) throw new \UnexpectedValueException(ucfirst($label) . ' is invalid.');
		return $value;
	}
}
