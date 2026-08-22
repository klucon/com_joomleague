<?php

declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Administrator\Service;

defined('_JEXEC') or die;

final class StageTransitionValidator
{
	private const SELECTORS = ['all_entries', 'standing_rank_range', 'match_outcome', 'manual'];
	private const CARRY_MODES = ['none', 'all_results', 'mutual_results'];

	/** @return array<string,mixed> */
	public function validate(string $selectorType, ?string $configJson, string $carryOverMode): array
	{
		if (!in_array($selectorType, self::SELECTORS, true) || !in_array($carryOverMode, self::CARRY_MODES, true)) throw new \InvalidArgumentException('Stage transition type is invalid.');
		$config = $configJson === null || trim($configJson) === '' ? [] : json_decode($configJson, true, 32, JSON_THROW_ON_ERROR);
		if (!is_array($config) || array_is_list($config) && $config !== []) throw new \InvalidArgumentException('Stage transition configuration must be a JSON object.');

		if (in_array($selectorType, ['all_entries', 'manual'], true) && $config !== []) throw new \InvalidArgumentException('This stage transition selector does not accept configuration.');
		if ($selectorType === 'standing_rank_range') {
			$from = filter_var($config['from'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
			$to = filter_var($config['to'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
			$scope = $config['scope'] ?? null;
			if ($from === false || $to === false || $from > $to || !is_string($scope) || preg_match('/^[a-z][a-z0-9_]*$/', $scope) !== 1 || array_diff(array_keys($config), ['from', 'to', 'scope']) !== []) throw new \InvalidArgumentException('Standing rank transition configuration is invalid.');
			$config = ['from' => $from, 'to' => $to, 'scope' => $scope];
		}
		if ($selectorType === 'match_outcome') {
			$outcome = $config['outcome'] ?? null;
			$roundId = $config['round_id'] ?? null;
			if (!in_array($outcome, ['winner', 'loser'], true) || ($roundId !== null && filter_var($roundId, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) === false) || array_diff(array_keys($config), ['outcome', 'round_id']) !== []) throw new \InvalidArgumentException('Match outcome transition configuration is invalid.');
			$config = ['outcome' => $outcome] + ($roundId === null ? [] : ['round_id' => (int) $roundId]);
		}

		return $config;
	}

	/** @param list<array{source:int,target:int}> $edges */
	public function assertAcyclic(array $edges): void
	{
		$graph = [];
		foreach ($edges as $edge) {
			$source = (int) ($edge['source'] ?? 0); $target = (int) ($edge['target'] ?? 0);
			if ($source < 1 || $target < 1 || $source === $target) throw new \InvalidArgumentException('Stage transition edge is invalid.');
			$graph[$source][] = $target;
		}
		$visiting = []; $visited = [];
		$visit = function (int $node) use (&$visit, &$graph, &$visiting, &$visited): void {
			if (isset($visiting[$node])) throw new \DomainException('Stage transitions must not contain a cycle.');
			if (isset($visited[$node])) return;
			$visiting[$node] = true;
			foreach ($graph[$node] ?? [] as $target) $visit($target);
			unset($visiting[$node]); $visited[$node] = true;
		};
		foreach (array_keys($graph) as $node) $visit((int) $node);
	}
}
