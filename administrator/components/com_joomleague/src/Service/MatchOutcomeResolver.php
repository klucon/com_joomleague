<?php

declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Domain\Service;

defined('_JEXEC') or die;

final class MatchOutcomeResolver
{
	public static function isDecider(string $level, mixed $metadata): bool
	{
		return $level === 'shootout' || (is_array($metadata) && ($metadata['decider'] ?? false) === true);
	}

	/** @param list<object> $values @param list<object> $decider @return list<int> Participant IDs. */
	public static function resolve(array $values, array $decider = [], bool $higherIsBetter = true, string $outcome = 'winner'): array
	{
		$explicit = [];
		foreach ($values as $value) {
			$rank = $value->result_rank ?? null;
			if (($value->status_code ?? '') === $outcome || ($rank !== null && ($outcome === 'winner' ? (int) $rank === 1 : (int) $rank > 1))) {
				$explicit[] = (int) $value->participant_id;
			}
		}
		if ($explicit !== []) return $explicit;
		if (count($values) < 2) return [];
		foreach ($values as $value) if (($value->numeric_value ?? null) === null) return [];
		$compare = static fn(object $a, object $b): int => StandingsDecimal::compare((string) $a->numeric_value, (string) $b->numeric_value) * ($higherIsBetter ? -1 : 1);
		usort($values, $compare);
		$index = $outcome === 'winner' ? 0 : count($values) - 1;
		$neighbour = $outcome === 'winner' ? 1 : $index - 1;
		if ($compare($values[$index], $values[$neighbour]) !== 0) return [(int) $values[$index]->participant_id];
		// A separate decider may resolve only a fully tied two-sided result.
		if (count($values) !== 2 || count($decider) !== 2) return [];
		$ids = array_map(static fn(object $v): int => (int) $v->participant_id, $values);
		$deciderIds = array_map(static fn(object $v): int => (int) $v->participant_id, $decider);
		sort($ids); sort($deciderIds);
		if ($ids !== $deciderIds || count(array_unique($ids)) !== 2) return [];
		return self::resolve($decider, [], true, $outcome);
	}
}
