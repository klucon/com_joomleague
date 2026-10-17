<?php
declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Administrator\Service;

defined('_JEXEC') or die;

/** Career history is grouped by project, keeping different rule snapshots separate. */
final class CaromPlayerSummary
{
	public function build(int $personId, array $games, array $rowsByGame): array
	{
		$history = []; $projects = [];
		foreach ($games as $gameId => $game) {
			$sides = [];
			foreach ($rowsByGame[$gameId] ?? [] as $row) $sides[(int) $row->participant_id][] = $row;
			if (count($sides) !== 2 || array_filter($sides, static fn (array $members): bool => count($members) !== 1) !== []) continue;
			[$left, $right] = array_map(static fn (array $side): object => $side[0], array_values($sides));
			if ((int) $left->nominated_person_id === (int) $right->nominated_person_id) continue;
			if ((int) ($left->person_id ?? 0) === $personId) [$own, $other] = [$left, $right];
			elseif ((int) ($right->person_id ?? 0) === $personId) [$own, $other] = [$right, $left];
			else continue;
			$score = $this->score($own->numeric_value); $opponentScore = $this->score($other->numeric_value);
			if ($score === null || $opponentScore === null) continue;
			$metadata = json_decode((string) ($own->metadata_json ?? ''), true);
			$innings = $metadata['innings'] ?? null;
			if (!is_int($innings) || $innings < 1 || $innings > 100000) $innings = null;
			$highRun = $metadata['high_run'] ?? null;
			if (!is_int($highRun) || $highRun < 0 || $highRun > $score) $highRun = null;
			$outcome = $score > $opponentScore ? 'win' : ($score < $opponentScore ? 'loss' : 'draw');
			$record = [
				'match_id' => (int) $game->match_id, 'sequence_number' => (int) $game->sequence_number,
				'project_id' => (int) $game->project_id, 'project_name' => (string) $game->project_name,
				'season_id' => (int) $game->season_id, 'season_name' => (string) $game->season_name,
				'competition_name' => (string) $game->competition_name,
				'scheduled_start' => $game->scheduled_start, 'timezone' => (string) ($game->timezone ?: 'UTC'),
				'opponent_id' => (int) ($other->person_id ?? 0),
				'opponent_name' => (int) ($other->person_id ?? 0) > 0 ? trim((string) $other->first_name . ' ' . (string) $other->last_name) : '',
				'score' => $score, 'opponent_score' => $opponentScore, 'innings' => $innings,
				'high_run' => $highRun, 'average' => $innings !== null ? $score / $innings : null, 'outcome' => $outcome,
			];
			$history[] = $record;
			$id = $record['project_id'];
			$projects[$id] ??= [
				'project_id' => $id, 'project_name' => $record['project_name'], 'season_id' => $record['season_id'],
				'season_name' => $record['season_name'], 'competition_name' => $record['competition_name'],
				'games' => 0, 'wins' => 0, 'draws' => 0, 'losses' => 0, 'caroms' => 0, 'innings' => 0,
				'average' => null, 'best_average' => null, 'high_run' => null, 'complete' => true,
			];
			$summary = &$projects[$id];
			$summary['games']++;
			$summary[['win' => 'wins', 'draw' => 'draws', 'loss' => 'losses'][$outcome]]++;
			$summary['caroms'] += $score;
			if ($innings === null) $summary['complete'] = false;
			else { $summary['innings'] += $innings; $summary['best_average'] = max($summary['best_average'] ?? 0, $record['average']); }
			if ($highRun !== null) $summary['high_run'] = max($summary['high_run'] ?? 0, $highRun);
			unset($summary);
		}
		foreach ($projects as &$summary) if ($summary['complete'] && $summary['innings'] > 0) $summary['average'] = $summary['caroms'] / $summary['innings'];
		unset($summary);
		return ['history' => $history, 'projects' => array_values($projects)];
	}

	private function score(mixed $value): ?int
	{
		return preg_match('/^([0-9]{1,9})(?:\.0{1,9})?$/D', (string) $value, $match) ? (int) $match[1] : null;
	}
}
