<?php

declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Domain\Service;

defined('_JEXEC') or die;

use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;

/** Builds a public cross-table for two-participant head-to-head contests. */
final class ResultMatrixReader
{
	public function __construct(private readonly DatabaseInterface $database) {}

	/**
	 * @param list<int> $viewLevels
	 * @return array<string,mixed>
	 */
	public function forProject(int $projectId, ?int $stageId, array $viewLevels): array
	{
		if ($projectId < 1 || ($stageId !== null && $stageId < 1)) {
			throw new \InvalidArgumentException('Result matrix context is invalid.');
		}

		$db = $this->database;
		$levels = array_values(array_unique(array_filter(array_map('intval', $viewLevels), static fn (int $id): bool => $id > 0))) ?: [1];
		$access = implode(',', $levels);
		$query = $db->getQuery(true)
			->select(['project.id', 'project.name', 'version.payload_json'])
			->from($db->quoteName('#__joomleague_project', 'project'))
			->innerJoin($db->quoteName('#__joomleague_competition', 'competition') . ' ON competition.id = project.competition_id AND competition.published = 1 AND competition.access IN (' . $access . ')')
			->innerJoin($db->quoteName('#__joomleague_season', 'season') . ' ON season.id = project.season_id AND season.published = 1 AND season.access IN (' . $access . ')')
			->innerJoin($db->quoteName('#__joomleague_sport_type', 'sport_type') . ' ON sport_type.id = project.sport_type_id AND sport_type.published = 1')
			->innerJoin($db->quoteName('#__joomleague_sport_profile_version', 'version') . ' ON version.id = project.profile_version_id')
			->where('project.id = :project')
			->where('project.published = 1')
			->where('project.access IN (' . $access . ')')
			->bind(':project', $projectId, ParameterType::INTEGER);
		$project = $db->setQuery($query)->loadObject();

		if (!$project) {
			throw new \RuntimeException('Result matrix project is unavailable.');
		}

		$profile = json_decode((string) $project->payload_json, true);
		if (!is_array($profile) || (string) ($profile['contest']['type'] ?? '') !== 'head_to_head') {
			return ['project' => $project, 'supported' => false, 'stage' => null, 'entries' => [], 'cells' => []];
		}

		$stageQuery = $db->getQuery(true)
			->select(['stage.id', 'stage.name'])
			->from($db->quoteName('#__joomleague_project_stage', 'stage'))
			->where('stage.project_id = :project')
			->where('stage.published = 1')
			->bind(':project', $projectId, ParameterType::INTEGER)
			->order('stage.sequence_number ASC, stage.id ASC');
		if ($stageId !== null) {
			$stageQuery->where('stage.id = :stage')->bind(':stage', $stageId, ParameterType::INTEGER);
		}
		$stages = $db->setQuery($stageQuery)->loadObjectList();
		$stage = $stageId !== null ? ($stages[0] ?? null) : null;
		if ($stageId !== null && !$stage) {
			throw new \RuntimeException('Result matrix stage is unavailable.');
		}

		$entryQuery = $db->getQuery(true)
			->select([
				'entry.id', 'entry.entry_kind', 'entry.team_id', 'entry.person_id',
				"COALESCE(NULLIF(entry.display_name, ''), team.name, NULLIF(TRIM(CONCAT(person.first_name, ' ', person.last_name)), ''), CONCAT('ID ', entry.id)) AS display_name",
				"COALESCE(NULLIF(team.middle_name, ''), NULLIF(team.short_name, ''), NULLIF(entry.display_name, ''), team.name, NULLIF(TRIM(CONCAT(person.first_name, ' ', person.last_name)), ''), CONCAT('ID ', entry.id)) AS short_name",
			])
			->from($db->quoteName('#__joomleague_project_entry', 'entry'))
			->leftJoin($db->quoteName('#__joomleague_team', 'team') . ' ON team.id = entry.team_id AND team.published = 1 AND team.access IN (' . $access . ')')
			->leftJoin($db->quoteName('#__joomleague_person', 'person') . ' ON person.id = entry.person_id AND person.published = 1 AND person.access IN (' . $access . ')')
			->where('entry.project_id = :project')
			->where('entry.published = 1')
			->where("((entry.entry_kind = 'team' AND team.id IS NOT NULL) OR (entry.entry_kind = 'person' AND person.id IS NOT NULL) OR entry.entry_kind = 'group')")
			->bind(':project', $projectId, ParameterType::INTEGER)
			->order('entry.ordering ASC, display_name ASC, entry.id ASC');
		$entries = $db->setQuery($entryQuery)->loadObjectList('id');

		if ($entries === []) {
			return ['project' => $project, 'supported' => true, 'stage' => $stage, 'stages' => $stages, 'entries' => [], 'cells' => []];
		}

		$matchQuery = $db->getQuery(true)
			->select(['match.id', 'match.stage_id', 'match.round_id', 'match.scheduled_start', 'round.name AS round_name'])
			->from($db->quoteName('#__joomleague_project_match', 'match'))
			->innerJoin($db->quoteName('#__joomleague_project_stage', 'stage') . ' ON stage.id = match.stage_id AND stage.published = 1')
			->innerJoin($db->quoteName('#__joomleague_project_round', 'round') . ' ON round.id = match.round_id AND round.published = 1')
			->innerJoin($db->quoteName('#__joomleague_match_result', 'result') . " ON result.match_id = match.id AND result.status_code = 'final'")
			->where('match.project_id = :project')
			->where('match.published = 1')
			->bind(':project', $projectId, ParameterType::INTEGER)
			->order('stage.sequence_number ASC, round.sequence_number ASC, match.scheduled_start ASC, match.id ASC');
		if ($stageId !== null) {
			$matchQuery->where('match.stage_id = :stage')->bind(':stage', $stageId, ParameterType::INTEGER);
		}
		$matches = $db->setQuery($matchQuery)->loadObjectList('id');

		if ($matches === []) {
			return ['project' => $project, 'supported' => true, 'stage' => $stage, 'stages' => $stages, 'entries' => array_values($entries), 'cells' => []];
		}

		$matchIds = array_map('intval', array_keys($matches));
		$participants = $db->setQuery(
			$db->getQuery(true)
				->select(['participant.id', 'participant.match_id', 'participant.project_entry_id', 'participant.slot_number'])
				->from($db->quoteName('#__joomleague_match_participant', 'participant'))
				->whereIn('participant.match_id', $matchIds, ParameterType::INTEGER)
				->where('participant.published = 1')
				->order('participant.match_id ASC, participant.slot_number ASC, participant.id ASC')
		)->loadObjectList();
		$byMatch = [];
		$participantById = [];
		foreach ($participants as $participant) {
			if (isset($entries[(int) $participant->project_entry_id])) {
				$byMatch[(int) $participant->match_id][] = $participant;
				$participantById[(int) $participant->id] = $participant;
			}
		}

		$values = $db->setQuery(
			$db->getQuery(true)
				->select(['value.match_id', 'value.participant_id', 'value.numeric_value', 'value.text_value', 'value.status_code', 'value.result_rank'])
				->from($db->quoteName('#__joomleague_match_score_value', 'value'))
				->innerJoin($db->quoteName('#__joomleague_match_score_segment', 'segment') . ' ON segment.id = value.segment_id AND segment.parent_id IS NULL')
				->whereIn('value.match_id', $matchIds, ParameterType::INTEGER)
		)->loadObjectList();
		$valueByParticipant = [];
		foreach ($values as $value) {
			$valueByParticipant[(int) $value->participant_id] = $value;
		}

		$cells = [];
		foreach ($matches as $matchId => $match) {
			$pair = $byMatch[(int) $matchId] ?? [];
			if (count($pair) !== 2 || (int) $pair[0]->slot_number === (int) $pair[1]->slot_number) {
				continue;
			}
			usort($pair, static fn (object $a, object $b): int => (int) $a->slot_number <=> (int) $b->slot_number);
			$first = (int) $pair[0]->project_entry_id;
			$second = (int) $pair[1]->project_entry_id;
			$cells[$first][$second][] = [
				'match_id' => (int) $matchId,
				'round_name' => (string) $match->round_name,
				'scheduled_start' => $match->scheduled_start,
				'values' => [$this->value($valueByParticipant[(int) $pair[0]->id] ?? null), $this->value($valueByParticipant[(int) $pair[1]->id] ?? null)],
			];
		}

		return ['project' => $project, 'supported' => true, 'stage' => $stage, 'stages' => $stages, 'entries' => array_values($entries), 'cells' => $cells];
	}

	/** @return array{type:string,value:string}|null */
	private function value(?object $value): ?array
	{
		if ($value === null) return null;
		if ($value->numeric_value !== null) return ['type' => 'numeric', 'value' => rtrim(rtrim((string) $value->numeric_value, '0'), '.')];
		if ($value->text_value !== null && $value->text_value !== '') return ['type' => 'text', 'value' => (string) $value->text_value];
		if ($value->status_code !== null && $value->status_code !== '') return ['type' => 'status', 'value' => (string) $value->status_code];
		if ($value->result_rank !== null) return ['type' => 'rank', 'value' => '#' . (int) $value->result_rank];
		return null;
	}
}
