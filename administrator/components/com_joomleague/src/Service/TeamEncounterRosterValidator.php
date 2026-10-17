<?php
declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Administrator\Service;

defined('_JEXEC') or die;

/** Validates nominations, not sport-specific scoring or player reuse rules. */
final class TeamEncounterRosterValidator
{
	/**
	 * The caller must load eligible published lineup rows under the parent match ACL.
	 * Format sizes come from the pinned encounter contract, never request input.
	 *
	 * @param list<int> $participantIds
	 * @param list<array<string,mixed>> $eligibleLineup
	 * @param array<string,int> $formatSizes
	 * @param list<array<string,mixed>> $duels
	 * @return list<array<string,mixed>>
	 */
	public function validate(int $matchId, array $participantIds, array $eligibleLineup, array $formatSizes, array $duels): array
	{
		if ($matchId < 1 || count($participantIds) !== 2 || !array_is_list($participantIds)) throw new \InvalidArgumentException('An encounter requires two team participants.');
		foreach ($participantIds as $id) if (!is_int($id) || $id < 1) throw new \InvalidArgumentException('Invalid team participant.');
		if ($participantIds[0] === $participantIds[1]) throw new \InvalidArgumentException('Encounter teams must be distinct.');
		if (!$formatSizes || count($formatSizes) > 32) throw new \InvalidArgumentException('Encounter formats are missing or excessive.');
		foreach ($formatSizes as $code => $size) {
			if (!is_string($code) || !preg_match('/^[a-z][a-z0-9_]{0,63}$/D', $code) || !is_int($size) || $size < 1 || $size > 64) throw new \InvalidArgumentException('Invalid encounter format.');
		}
		if (!array_is_list($duels) || count($duels) > 128) throw new \InvalidArgumentException('Invalid or excessive duel list.');
		$members = [];
		foreach ($eligibleLineup as $row) {
			if (!is_array($row)) throw new \InvalidArgumentException('Invalid lineup row.');
			$id = $this->id($row['id'] ?? null);
			$team = $this->id($row['match_participant_id'] ?? null);
			if (isset($members[$id]) || $this->id($row['match_id'] ?? null) !== $matchId || !in_array($team, $participantIds, true) || !in_array($row['published'] ?? null, [1, '1'], true)) throw new \InvalidArgumentException('Lineup belongs to another match, team or is not published.');
			$members[$id] = ['participant_id' => $team, 'person_id' => $this->id($row['person_id'] ?? null)];
		}
		$result = [];
		$sequences = [];
		foreach ($duels as $duel) {
			if (!is_array($duel)) throw new \InvalidArgumentException('Invalid duel.');
			$sequence = $this->id($duel['sequence_number'] ?? null);
			$format = $duel['format_code'] ?? null;
			if ($sequence > 128 || isset($sequences[$sequence]) || !is_string($format) || !isset($formatSizes[$format])) throw new \InvalidArgumentException('Invalid duel sequence or format.');
			$sides = $duel['sides'] ?? null;
			if (!is_array($sides) || !array_is_list($sides) || count($sides) !== 2) throw new \InvalidArgumentException('A duel requires two sides.');
			$seenTeams = [];
			$seenPeople = [];
			$normalized = [];
			foreach ($sides as $side) {
				if (!is_array($side)) throw new \InvalidArgumentException('Invalid duel side.');
				$team = $this->id($side['participant_id'] ?? null);
				$ids = $side['lineup_member_ids'] ?? null;
				if (!in_array($team, $participantIds, true) || isset($seenTeams[$team]) || !is_array($ids) || !array_is_list($ids) || count($ids) !== $formatSizes[$format]) throw new \InvalidArgumentException('Invalid team or player count.');
				$seenTeams[$team] = true;
				$selection = [];
				foreach ($ids as $value) {
					$id = $this->id($value);
					$member = $members[$id] ?? null;
					if (!$member || $member['participant_id'] !== $team || isset($seenPeople[$member['person_id']])) throw new \InvalidArgumentException('Player is missing, duplicated or belongs to another team.');
					$seenPeople[$member['person_id']] = true;
					$selection[] = $id;
				}
				$normalized[$team] = ['participant_id' => $team, 'lineup_member_ids' => $selection];
			}
			$sequences[$sequence] = true;
			$result[] = ['sequence_number' => $sequence, 'format_code' => $format, 'sides' => array_map(static fn(int $id): array => $normalized[$id], $participantIds)];
		}
		usort($result, static fn(array $a, array $b): int => $a['sequence_number'] <=> $b['sequence_number']);
		return $result;
	}

	private function id(mixed $value): int
	{
		if ((!is_int($value) && (!is_string($value) || !preg_match('/^[1-9][0-9]*$/D', $value))) || is_bool($value)) throw new \InvalidArgumentException('Identifier must be a positive integer.');
		$id = filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
		if ($id === false) throw new \InvalidArgumentException('Identifier exceeds the supported range.');
		return $id;
	}
}
