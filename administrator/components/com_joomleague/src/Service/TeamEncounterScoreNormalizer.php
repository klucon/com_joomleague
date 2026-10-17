<?php
declare(strict_types=1);
namespace Joomleague\Component\Joomleague\Administrator\Service;
defined('_JEXEC') or die;

/** Counts won duels; individual scores may be entered directly or derived from sets. */
final class TeamEncounterScoreNormalizer
{
	public function normalize(array $root, array $participants, string $status, array $contract = []): array
	{
		$awards = $contract['duel_points'] ?? ['win' => 1, 'draw' => 0, 'loss' => 0];
		$drawSupported = $contract['draw_supported'] ?? false;
		if (count($participants) !== 2) throw new MatchResultValidationException('COM_JOOMLEAGUE_ENCOUNTER_ERROR_TEAMS');
		$totals = array_fill_keys($participants, 0);
		$active = 0;
		$rules = $contract['game_rules'] ?? [];
		$penalties = array_values(array_filter($root['children'], static fn (array $child): bool => $child['level_code'] === 'penalty_round'));
		$duelCount = count($root['children']) - count($penalties);
		if ($status === 'final' && isset($rules['games_per_encounter']) && $duelCount !== $rules['games_per_encounter']) throw new MatchResultValidationException('COM_JOOMLEAGUE_CAROM_ERROR_GAME_COUNT');
		if ($status === 'final' && $root['children'] === []) throw new MatchResultValidationException('COM_JOOMLEAGUE_ENCOUNTER_ERROR_EMPTY');
		foreach ($root['children'] as &$duel) {
			if ($duel['level_code'] === 'penalty_round') continue;
			if ($duel['level_code'] !== 'duel') throw new MatchResultValidationException('COM_JOOMLEAGUE_ENCOUNTER_ERROR_SCORE');
			if (!in_array($duel['status_code'], ['completed', 'cancelled', 'walkover'], true)) throw new MatchResultValidationException('COM_JOOMLEAGUE_ENCOUNTER_ERROR_SCORE');
			if ($duel['status_code'] === 'cancelled') {
				if ($duel['children'] !== [] || $duel['values'] !== []) throw new MatchResultValidationException('COM_JOOMLEAGUE_ENCOUNTER_ERROR_SCORE');
				continue;
			}
			if ($duel['status_code'] === 'walkover' && $duel['children'] !== []) throw new MatchResultValidationException('COM_JOOMLEAGUE_ENCOUNTER_ERROR_SCORE');
			$active++;
			if ($rules !== [] && $duel['status_code'] !== 'walkover') {
				$innings = [];
				foreach ($duel['values'] as $value) {
					if ($value['numeric_value'] !== null && (float) $value['numeric_value'] > $rules['target_score']) throw new MatchResultValidationException('COM_JOOMLEAGUE_CAROM_ERROR_TARGET_SCORE');
					$inning = $value['metadata']['innings'] ?? null;
					if ($rules['inning_limit'] > 0 && (($status === 'final' && $inning === null) || ($inning !== null && $inning > $rules['inning_limit']))) throw new MatchResultValidationException('COM_JOOMLEAGUE_CAROM_ERROR_INNING_LIMIT');
					if ($inning !== null) $innings[] = $inning;
				}
				if ($status === 'final' && $rules['equal_innings'] && count($innings) === 2 && $innings[0] !== $innings[1]) throw new MatchResultValidationException('COM_JOOMLEAGUE_CAROM_ERROR_EQUAL_INNINGS');
			}
			if ($duel['children'] !== []) {
				$sets = array_fill_keys($participants, 0);
				$complete = true;
				foreach ($duel['children'] as $set) {
					if ($set['level_code'] !== 'set' || $set['children'] !== [] || $set['status_code'] !== 'completed') throw new MatchResultValidationException('COM_JOOMLEAGUE_ENCOUNTER_ERROR_SCORE');
					$winner = $this->winner($set['values'], $participants, $status === 'final');
					if ($winner === null) $complete = false;
					else $sets[$winner]++;
				}
				$duel['values'] = $complete ? $this->values($sets) : [];
			}
			$winner = $this->winner($duel['values'], $participants, $status === 'final', $drawSupported && $duel['status_code'] !== 'walkover');
			if ($duel['status_code'] === 'walkover' && $winner !== null) {
				$duel['values'] = $this->values(array_map(static fn(int $id): int => $id === $winner ? 1 : 0, array_combine($participants, $participants)));
			}
			if ($winner !== null) {
				foreach ($participants as $id) $totals[$id] += $winner === 0 ? $awards['draw'] : ($id === $winner ? $awards['win'] : $awards['loss']);
			}
		}
		unset($duel);
		if ($status === 'final' && $active === 0) throw new MatchResultValidationException('COM_JOOMLEAGUE_ENCOUNTER_ERROR_EMPTY');
		$root['values'] = $this->values($totals);
		if ($penalties !== [] || isset($contract['penalty_series'])) {
			$root['metadata']['penalty_resolution'] = $contract['penalty_series']['resolution'] ?? 'sudden_death';
			$root['metadata']['penalty_cycle_length'] = $contract['penalty_series']['cycle_length'] ?? 1;
			$winner = (new EncounterPenaltySeries())->resolve($penalties, $participants, $totals, $status === 'final', $contract['penalty_series'] ?? []);
			if ($winner !== null) foreach ($root['values'] as &$value) {
				$value['status_code'] = $value['participant_id'] === $winner ? 'winner' : 'loser';
				$value['result_rank'] = $value['participant_id'] === $winner ? 1 : 2;
			}
			unset($value);
		}
		return $root;
	}

	private function winner(array $values, array $participants, bool $required, bool $drawSupported = false): ?int
	{
		$numbers = [];
		foreach ($values as $value) {
			$n = $value['numeric_value'];
			if (!is_string($n) || !preg_match('/^(\d{1,9})(?:\.0{1,9})?$/D', $n, $m)) throw new MatchResultValidationException('COM_JOOMLEAGUE_ENCOUNTER_ERROR_SCORE');
			$numbers[$value['participant_id']] = (int) $m[1];
		}
		if (count($numbers) !== 2 || !isset($numbers[$participants[0]], $numbers[$participants[1]])) {
			if ($required) throw new MatchResultValidationException('COM_JOOMLEAGUE_ENCOUNTER_ERROR_INCOMPLETE');
			return null;
		}
		if ($numbers[$participants[0]] === $numbers[$participants[1]]) {
			if ($drawSupported) return 0;
			if ($required) throw new MatchResultValidationException('COM_JOOMLEAGUE_ENCOUNTER_ERROR_TIED_DUEL');
			return null;
		}
		return $numbers[$participants[0]] > $numbers[$participants[1]] ? $participants[0] : $participants[1];
	}

	private function values(array $scores): array
	{
		$values = [];
		foreach ($scores as $id => $score) $values[] = ['participant_id' => (int) $id, 'numeric_value' => (string) $score, 'text_value' => null, 'status_code' => null, 'result_rank' => null, 'metadata' => []];
		return $values;
	}
}
