<?php
declare(strict_types=1);
namespace Joomleague\Component\Joomleague\Administrator\Service;
defined('_JEXEC') or die;

/** Optional single-player sides reuse the regular, match-scoped roster checks. */
final class EncounterPenaltyNominations
{
	public function validate(int $matchId, array $participants, array $roster, array $contract, array &$root, array $duels = []): void
	{
		$order = $contract['penalty_series']['player_order'] ?? 'manual';
		if ($order !== 'manual') {
			if (!in_array($order, ['ascending', 'descending', 'descending_last'], true) || count($duels) !== ($contract['penalty_series']['cycle_length'] ?? 0)) throw new MatchResultValidationException('COM_JOOMLEAGUE_CAROM_PENALTY_ERROR_NOMINATION');
			usort($duels, static fn(array $a, array $b): int => $a['sequence_number'] <=> $b['sequence_number']);
			if ($order !== 'ascending') $duels = array_reverse($duels);
			foreach ($root['children'] as &$round) {
				if ($round['level_code'] !== 'penalty_round') continue;
				$index = $round['sequence_number'] - 1;
				// The Portuguese protocol repeats board one after the first tied pass.
				$index = $order === 'descending_last' ? min($index, count($duels) - 1) : $index % count($duels);
				if ($index < 0) throw new MatchResultValidationException('COM_JOOMLEAGUE_CAROM_PENALTY_ERROR_NOMINATION');
				$duel = $duels[$index];
				$members = [];
				foreach ($duel['sides'] as $side) {
					if (count($side['lineup_member_ids']) !== 1) throw new MatchResultValidationException('COM_JOOMLEAGUE_CAROM_PENALTY_ERROR_NOMINATION');
					$members[$side['participant_id']] = $side['lineup_member_ids'][0];
				}
				foreach ($round['values'] as &$value) {
					$expected = $members[$value['participant_id']];
					if (isset($value['metadata']['lineup_member_id']) && (int) $value['metadata']['lineup_member_id'] !== $expected) throw new MatchResultValidationException('COM_JOOMLEAGUE_CAROM_PENALTY_ERROR_NOMINATION');
					$value['metadata']['lineup_member_id'] = $expected;
				}
				unset($value);
			}
			unset($round);
		}
		$rounds = [];
		foreach ($root['children'] as $round) {
			if ($round['level_code'] !== 'penalty_round') continue;
			$sides = [];
			foreach ($round['values'] as $value) {
				$id = $value['metadata']['lineup_member_id'] ?? null;
				if ($id !== null) $sides[] = ['participant_id' => $value['participant_id'], 'lineup_member_ids' => [$id]];
			}
			if ($sides === []) continue;
			if (($contract['penalty_series']['nominations'] ?? null) !== 'optional_single') {
				throw new MatchResultValidationException('COM_JOOMLEAGUE_CAROM_PENALTY_ERROR_NOMINATION');
			}
			$rounds[] = ['sequence_number' => $round['sequence_number'], 'format_code' => 'single', 'sides' => $sides];
		}
		if ($rounds === []) return;
		try {
			(new TeamEncounterRosterValidator())->validate($matchId, $participants, $roster, ['single' => 1], $rounds);
		} catch (\InvalidArgumentException) {
			throw new MatchResultValidationException('COM_JOOMLEAGUE_CAROM_PENALTY_ERROR_NOMINATION');
		}
	}
}
