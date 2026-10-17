<?php
declare(strict_types=1);
namespace Joomleague\Component\Joomleague\Administrator\Service;
defined('_JEXEC') or die;

/** Resolve separate team-series totals without adding them to regular scores. */
final class EncounterPenaltySeries
{
	public function resolve(array $series, array $participants, array $totals, bool $final, array $rules): ?int
	{
		$tied = $totals[$participants[0]] === $totals[$participants[1]];
		if ($series === []) {
			if ($final && $tied && ($rules['required'] ?? false)) $this->fail('REQUIRED');
			return null;
		}
		if (!($rules['enabled'] ?? false) || !$tied) $this->fail('NOT_ALLOWED');
		usort($series, static fn (array $a, array $b): int => $a['sequence_number'] <=> $b['sequence_number']);
		$winner = null;
		$cycleLength = ($rules['resolution'] ?? 'sudden_death') === 'team_cycle' ? ($rules['cycle_length'] ?? 1) : 1;
		if (!is_int($cycleLength) || $cycleLength < 1 || $cycleLength > 64) $this->fail('ORDER');
		$cycleScores = array_fill_keys($participants, 0);
		foreach ($series as $index => $round) {
			if ($winner !== null || (int) $round['sequence_number'] !== $index + 1 || ($round['status_code'] ?? '') !== 'completed' || ($round['children'] ?? []) !== []) $this->fail('ORDER');
			$scores = [];
			foreach ($round['values'] as $value) {
				$id = $value['participant_id'];
				if (!in_array($id, $participants, true) || isset($scores[$id]) || !is_string($value['numeric_value']) || preg_match('/^([0-9]{1,9})(?:\.0{1,9})?$/D', $value['numeric_value'], $number) !== 1 || (int) $number[1] > $rules['maximum_points']) $this->fail('SCORE');
				$scores[$id] = (int) $number[1];
			}
			if (count($scores) !== 2) {
				if ($final || $index !== count($series) - 1) $this->fail('SCORE');
				return null;
			}
			foreach ($participants as $id) $cycleScores[$id] += $scores[$id];
			if (($index + 1) % $cycleLength === 0) {
				if ($cycleScores[$participants[0]] !== $cycleScores[$participants[1]]) $winner = $cycleScores[$participants[0]] > $cycleScores[$participants[1]] ? $participants[0] : $participants[1];
				$cycleScores = array_fill_keys($participants, 0);
			}
		}
		if ($winner === null && $final) $this->fail('UNRESOLVED');
		return $winner;
	}

	private function fail(string $reason): never
	{
		throw new MatchResultValidationException('COM_JOOMLEAGUE_CAROM_PENALTY_ERROR_' . $reason);
	}
}
