<?php
declare(strict_types=1);
namespace Joomleague\Component\Joomleague\Administrator\Service;
defined('_JEXEC') or die;

/** Playing statistics exclude awards-only results and never average averages. */
final class CaromEncounterSummary
{
	/** Attribute singles statistics only to an unambiguous public nomination. */
	public function players(array $segments, array $valuesBySegment, array $nominations): array
	{
		$players = [];
		foreach ($segments as $segment) {
			if ($segment->level_code !== 'duel' || ($segment->status_code ?? null) !== 'completed') continue;
			$seen = [];
			$sides = $nominations[(int) $segment->sequence_number] ?? [];
			foreach ($sides as $members) if (count($members) === 1 && (int) ($members[0]->person_id ?? 0) > 0) $seen[(int) $members[0]->person_id] = ($seen[(int) $members[0]->person_id] ?? 0) + 1;
			foreach ($sides as $participantId => $members) {
				if (count($members) !== 1) continue;
				$person = $members[0]; $id = (int) ($person->person_id ?? 0);
				if ($id < 1 || $seen[$id] !== 1) continue;
				$players[$id]['person'] = $person;
				$players[$id]['segments'][] = $segment;
				$value = $valuesBySegment[(int) $segment->id][(int) $participantId] ?? null;
				if ($value) $players[$id]['values'][(int) $segment->id][$id] = $value;
			}
		}
		foreach ($players as $id => &$player) {
			$player['summary'] = $this->summarize($player['segments'], $player['values'] ?? [], [$id])[$id];
			unset($player['segments'], $player['values']);
		}
		unset($player);
		return $players;
	}

	public function summarize(array $segments, array $valuesBySegment, array $participantIds): array
	{
		$summary = [];
		foreach ($participantIds as $id) $summary[$id] = ['games' => 0, 'caroms' => 0, 'innings' => 0, 'high_run' => null, 'average' => null, 'complete' => true];
		foreach ($segments as $segment) {
			if ($segment->level_code !== 'duel' || ($segment->status_code ?? null) !== 'completed') continue;
			foreach ($summary as $id => &$row) {
				$value = $valuesBySegment[(int) $segment->id][$id] ?? null;
				if (!$value || !preg_match('/^([0-9]{1,9})(?:\.0{1,9})?$/D', (string) ($value->numeric_value ?? ''), $match)) { $row['complete'] = false; continue; }
				$row['games']++;
				$caroms = (int) $match[1]; $row['caroms'] += $caroms;
				$metadata = is_array($value->metadata ?? null) ? $value->metadata : [];
				$innings = $metadata['innings'] ?? null;
				if (!is_int($innings) || $innings < 1 || $innings > 100000) $row['complete'] = false;
				else $row['innings'] += $innings;
				$highRun = $metadata['high_run'] ?? null;
				if (is_int($highRun) && $highRun >= 0 && $highRun <= $caroms) $row['high_run'] = max($row['high_run'] ?? 0, $highRun);
			}
			unset($row);
		}
		foreach ($summary as &$row) {
			if ($row['complete'] && $row['innings'] > 0) $row['average'] = $row['caroms'] / $row['innings'];
		}
		unset($row);
		return $summary;
	}
}
