<?php

declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Domain\Service;

defined('_JEXEC') or die;

use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;

/** Builds a club's historical head-to-head record from final two-team events. */
final class ClubRivalHistoryReader
{
	public function __construct(private readonly DatabaseInterface $database) {}

	/**
	 * @param list<int> $viewLevels
	 * @return array<string,mixed>
	 */
	public function read(int $clubId, array $filters, array $viewLevels): array
	{
		if ($clubId < 1) return ['error' => 'club_required'];
		$db = $this->database;
		$levels = array_values(array_unique(array_filter(array_map('intval', $viewLevels), static fn (int $id): bool => $id > 0))) ?: [1];
		$access = implode(',', $levels);
		$club = $db->setQuery($db->getQuery(true)->select(['id', 'name', 'short_name', 'logo'])->from($db->quoteName('#__joomleague_club'))->where('id = :club')->where('published = 1')->where('access IN (' . $access . ')')->bind(':club', $clubId, ParameterType::INTEGER))->loadObject();
		if (!$club) return ['error' => 'club_unavailable'];
		$clubId = (int) $club->id;

		$query = $db->getQuery(true)
			->select([
				'match.id AS match_id', 'match.scheduled_start', 'match.timezone', 'match.match_number',
				'project.id AS project_id', 'project.name AS project_name', 'project.project_type',
				'season.id AS season_id', 'season.name AS season_name', 'competition.name AS competition_name',
				'round.name AS round_name',
				'participant.slot_number', 'entry.id AS entry_id', 'team.id AS team_id', 'team.name AS team_name',
				'club.id AS club_id', 'club.name AS club_name', 'value.numeric_value', 'value.result_rank',
			])
			->from($db->quoteName('#__joomleague_project_match', 'match'))
			->innerJoin($db->quoteName('#__joomleague_match_result', 'result') . " ON result.match_id = match.id AND result.status_code = 'final' AND match.status_code <> 'cancelled'")
			->innerJoin($db->quoteName('#__joomleague_match_score_segment', 'segment') . ' ON segment.match_id = match.id AND segment.parent_id IS NULL')
			->innerJoin($db->quoteName('#__joomleague_match_score_value', 'value') . ' ON value.segment_id = segment.id AND value.match_id = match.id')
			->innerJoin($db->quoteName('#__joomleague_match_participant', 'participant') . ' ON participant.id = value.participant_id AND participant.match_id = match.id AND participant.published = 1')
			->innerJoin($db->quoteName('#__joomleague_project_entry', 'entry') . " ON entry.id = participant.project_entry_id AND entry.entry_kind = 'team' AND entry.published = 1")
			->innerJoin($db->quoteName('#__joomleague_team', 'team') . ' ON team.id = entry.team_id AND team.published = 1 AND team.access IN (' . $access . ')')
			->innerJoin($db->quoteName('#__joomleague_club', 'club') . ' ON club.id = team.club_id AND club.published = 1 AND club.access IN (' . $access . ')')
			->innerJoin($db->quoteName('#__joomleague_project', 'project') . ' ON project.id = match.project_id AND project.published = 1 AND project.access IN (' . $access . ')')
			->innerJoin($db->quoteName('#__joomleague_competition', 'competition') . ' ON competition.id = project.competition_id AND competition.published = 1 AND competition.access IN (' . $access . ')')
			->innerJoin($db->quoteName('#__joomleague_season', 'season') . ' ON season.id = project.season_id AND season.published = 1 AND season.access IN (' . $access . ')')
			->leftJoin($db->quoteName('#__joomleague_project_round', 'round') . ' ON round.id = match.round_id')
			->where('match.published = 1')->where('value.numeric_value IS NOT NULL')
			->order('match.scheduled_start DESC, match.id DESC, participant.slot_number ASC, participant.id ASC');
		$scopeClubId = (int) $clubId;
		$scope = $db->getQuery(true)->select('1')->from($db->quoteName('#__joomleague_match_participant', 'scope_participant'))
			->innerJoin($db->quoteName('#__joomleague_project_entry', 'scope_entry') . ' ON scope_entry.id = scope_participant.project_entry_id AND scope_entry.published = 1')
			->innerJoin($db->quoteName('#__joomleague_team', 'scope_team') . ' ON scope_team.id = scope_entry.team_id AND scope_team.published = 1')
			->where('scope_participant.match_id = match.id')->where('scope_participant.published = 1')->where('scope_team.club_id = :scope_club');
		$query->where('EXISTS (' . $scope . ')')->bind(':scope_club', $scopeClubId, ParameterType::INTEGER);

		foreach (['project_id' => 'project.id', 'season_id' => 'season.id'] as $filter => $column) {
			$value = (int) ($filters[$filter] ?? 0);
			if ($value > 0) $query->where($column . ' = :' . $filter)->bind(':' . $filter, $value, ParameterType::INTEGER);
		}
		$projectType = trim((string) ($filters['project_type'] ?? ''));
		if ($projectType !== '') $query->where('project.project_type = :project_type')->bind(':project_type', $projectType);
		$opponentClubId = (int) ($filters['opponent_club_id'] ?? 0);

		$rows = $db->setQuery($query)->loadObjectList();
		$higherIsBetter = $this->loadScoringDirections(array_values(array_unique(array_map(static fn (object $row): int => (int) $row->project_id, $rows))));
		$byMatch = [];
		foreach ($rows as $row) $byMatch[(int) $row->match_id][] = $row;
		$opponents = [];
		$matches = [];
		$options = $this->loadOptions((int) $clubId, $access);
		foreach ($byMatch as $participants) {
			if (count($participants) !== 2) continue;
			$ownIndexes = array_keys(array_filter($participants, static fn (object $row): bool => (int) $row->club_id === $clubId));
			if (count($ownIndexes) !== 1) continue;
			$own = $participants[$ownIndexes[0]];
			$opponent = $participants[1 - $ownIndexes[0]];
			if ($opponentClubId > 0 && (int) $opponent->club_id !== $opponentClubId) continue;
			$comparison = $this->compare($own, $opponent, $higherIsBetter[(int) $own->project_id] ?? true);
			if ($comparison === null) continue;
			$opponentId = (int) $opponent->club_id;
			$stats =& $opponents[$opponentId];
			$stats ??= ['club_id' => $opponentId, 'club_name' => (string) $opponent->club_name, 'played' => 0, 'wins' => 0, 'draws' => 0, 'losses' => 0, 'scored' => '0', 'conceded' => '0', 'first_match' => null, 'last_match' => null];
			$stats['played']++;
			$stats[$comparison > 0 ? 'wins' : ($comparison < 0 ? 'losses' : 'draws')]++;
			$stats['scored'] = StandingsDecimal::add($stats['scored'], (string) $own->numeric_value);
			$stats['conceded'] = StandingsDecimal::add($stats['conceded'], (string) $opponent->numeric_value);
			$date = $own->scheduled_start !== null ? (string) $own->scheduled_start : null;
			if ($date !== null && ($stats['first_match'] === null || $date < $stats['first_match'])) $stats['first_match'] = $date;
			if ($date !== null && ($stats['last_match'] === null || $date > $stats['last_match'])) $stats['last_match'] = $date;
			$matches[] = [
				'match_id' => (int) $own->match_id, 'scheduled_start' => $date, 'timezone' => (string) ($own->timezone ?: 'UTC'),
				'match_label' => (string) ($own->match_number ?: $own->round_name), 'project_id' => (int) $own->project_id,
				'project_name' => (string) $own->project_name, 'competition_name' => (string) $own->competition_name,
				'season_name' => (string) $own->season_name, 'project_type' => (string) $own->project_type,
				'own_team_name' => (string) $own->team_name, 'opponent_team_name' => (string) $opponent->team_name,
				'opponent_club_id' => $opponentId, 'opponent_club_name' => (string) $opponent->club_name,
				'scored' => StandingsDecimal::normalize((string) $own->numeric_value), 'conceded' => StandingsDecimal::normalize((string) $opponent->numeric_value),
				'outcome' => $comparison > 0 ? 'win' : ($comparison < 0 ? 'loss' : 'draw'),
			];
			unset($stats);
		}
		$opponents = array_values($opponents);
		usort($opponents, static fn (array $a, array $b): int => strnatcasecmp($a['club_name'], $b['club_name']));
		return compact('club', 'opponents', 'matches', 'options') + ['filters' => ['project_id' => (int) ($filters['project_id'] ?? 0), 'season_id' => (int) ($filters['season_id'] ?? 0), 'project_type' => $projectType, 'opponent_club_id' => $opponentClubId]];
	}

	/** @return array{projects:array<int,string>,seasons:array<int,string>,project_types:array<string,string>} */
	private function loadOptions(int $clubId, string $access): array
	{
		$db = $this->database;
		$rows = $db->setQuery($db->getQuery(true)
			->select(['project.id AS project_id', 'project.name AS project_name', 'project.project_type', 'season.id AS season_id', 'season.name AS season_name'])
			->from($db->quoteName('#__joomleague_project_entry', 'entry'))
			->innerJoin($db->quoteName('#__joomleague_team', 'team') . ' ON team.id = entry.team_id AND team.club_id = :club AND team.published = 1 AND team.access IN (' . $access . ')')
			->innerJoin($db->quoteName('#__joomleague_project', 'project') . ' ON project.id = entry.project_id AND project.published = 1 AND project.access IN (' . $access . ')')
			->innerJoin($db->quoteName('#__joomleague_competition', 'competition') . ' ON competition.id = project.competition_id AND competition.published = 1 AND competition.access IN (' . $access . ')')
			->innerJoin($db->quoteName('#__joomleague_season', 'season') . ' ON season.id = project.season_id AND season.published = 1 AND season.access IN (' . $access . ')')
			->where("entry.entry_kind = 'team'")->where('entry.published = 1')->bind(':club', $clubId, ParameterType::INTEGER)
			->group(['project.id', 'project.name', 'project.project_type', 'season.id', 'season.name']))->loadObjectList();
		$options = ['projects' => [], 'seasons' => [], 'project_types' => []];
		foreach ($rows as $row) {
			$options['projects'][(int) $row->project_id] = (string) $row->project_name;
			$options['seasons'][(int) $row->season_id] = (string) $row->season_name;
			if ((string) $row->project_type !== '') $options['project_types'][(string) $row->project_type] = (string) $row->project_type;
		}
		foreach ($options as &$values) asort($values, SORT_NATURAL | SORT_FLAG_CASE);
		return $options;
	}

	/** @param list<int> $projectIds @return array<int,bool> */
	private function loadScoringDirections(array $projectIds): array
	{
		if ($projectIds === []) return [];
		$db = $this->database;
		$rows = $db->setQuery($db->getQuery(true)->select(['project.id', 'version.payload_json'])->from($db->quoteName('#__joomleague_project', 'project'))->innerJoin($db->quoteName('#__joomleague_sport_profile_version', 'version') . ' ON version.id = project.profile_version_id')->whereIn('project.id', $projectIds, ParameterType::INTEGER))->loadObjectList();
		$result = [];
		foreach ($rows as $row) {
			$profile = json_decode((string) $row->payload_json, true);
			$result[(int) $row->id] = (bool) ($profile['match']['score']['higher_is_better'] ?? true);
		}
		return $result;
	}

	private function compare(object $own, object $opponent, bool $higherIsBetter): ?int
	{
		if ($own->result_rank !== null && $opponent->result_rank !== null) return (int) $own->result_rank === (int) $opponent->result_rank ? 0 : ((int) $own->result_rank < (int) $opponent->result_rank ? 1 : -1);
		$comparison = StandingsDecimal::compare((string) $own->numeric_value, (string) $opponent->numeric_value);
		return $higherIsBetter ? $comparison : -$comparison;
	}
}
