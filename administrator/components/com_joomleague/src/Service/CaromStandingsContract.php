<?php
declare(strict_types=1);
namespace Joomleague\Component\Joomleague\Domain\Service;
defined('_JEXEC') or die;

/** Resolve declared criteria without changing a project's pinned profile. */
final class CaromStandingsContract
{
	public function resolve(array $profile): array
	{
		$contract = $profile['standings']['calculation'];
		if (($profile['code'] ?? '') !== 'carom' || !isset($profile['standings']['tie_breaks'])) return $contract;
		$allowed = ['score_for', 'score_difference', 'caroms', 'average', 'best_average', 'high_run', 'wins', 'head_to_head_points', 'head_to_head_average', 'head_to_head_caroms'];
		$ordering = [['metric' => 'points', 'direction' => 'desc', 'nulls' => 'last']];
		$seen = ['points' => true];
		foreach (['first', 'second', 'third', 'fourth', 'fifth'] as $position) {
			$code = $profile['standings']['tie_breaks'][$position] ?? 'none';
			if ($code === 'none') continue;
			if (!is_string($code) || !in_array($code, $allowed, true)) throw new \UnexpectedValueException('Invalid carom standings criterion.');
			if (isset($seen[$code])) continue;
			$seen[$code] = true;
			$isHeadToHead = str_starts_with($code, 'head_to_head_');
			$ordering[] = ['metric' => $isHeadToHead ? substr($code, strlen('head_to_head_')) : $code, 'direction' => 'desc', 'nulls' => 'last', 'scope' => $isHeadToHead ? 'head_to_head' : 'overall'];
		}
		$contract['ordering'] = $ordering;
		$points = $profile['standings']['team_points'] ?? [];
		foreach (['win', 'draw', 'loss'] as $outcome) if (!is_int($points[$outcome] ?? null) || $points[$outcome] < 0 || $points[$outcome] > 1000) throw new \UnexpectedValueException('Invalid carom team award.');
		$contract['awards']['rules'] = [['when' => ['type' => 'always'], 'values' => ['winner' => $points['win'], 'draw' => $points['draw'], 'loser' => $points['loss']]]];
		(new StandingsContractValidator())->validate($contract);
		return $contract;
	}
}
