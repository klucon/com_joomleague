<?php

declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Domain\Service;

defined('_JEXEC') or die;

use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;

/** Read-only sport-neutral programme projection shared by site views and modules. */
final class ProgrammeReader
{
	public function __construct(private readonly DatabaseInterface $database) {}

	/**
	 * @param list<int>|null $entryIds Null means all project entries.
	 * @param list<int> $viewLevels Joomla access levels authorised for the current visitor.
	 * @return list<array<string,mixed>>
	 */
	public function forProject(int $projectId, ?array $entryIds, array $viewLevels, ?int $venueId = null): array
	{
		if ($projectId < 1) {
			throw new \InvalidArgumentException('Programme project is invalid.');
		}

		$viewLevels = array_values(array_unique(array_filter(array_map('intval', $viewLevels), static fn (int $id): bool => $id > 0)));
		$viewLevels = $viewLevels === [] ? [1] : $viewLevels;
		if ($entryIds !== null) {
			$entryIds = array_values(array_unique(array_filter(array_map('intval', $entryIds), static fn (int $id): bool => $id > 0)));
			if ($entryIds === []) {
				return [];
			}
		}

		$db = $this->database;
		$query = $db->getQuery(true)
			->select([
				'match.id', 'match.project_id', 'match.scheduled_start', 'match.timezone', 'match.duration_minutes', 'match.status_code',
				'match.contest_type', 'project.name AS project_name', 'round.name AS round_name', 'venue.id AS venue_id', 'venue.name AS venue_name',
				'result.status_code AS result_status',
			])
			->from($db->quoteName('#__joomleague_project_match', 'match'))
			->innerJoin($db->quoteName('#__joomleague_project', 'project') . ' ON project.id = match.project_id AND project.published = 1 AND project.access IN (' . implode(',', $viewLevels) . ')')
			->innerJoin($db->quoteName('#__joomleague_project_round', 'round') . ' ON round.id = match.round_id AND round.published = 1')
			->leftJoin($db->quoteName('#__joomleague_venue', 'venue') . ' ON venue.id = match.venue_id AND venue.published = 1 AND venue.access IN (' . implode(',', $viewLevels) . ')')
			->leftJoin($db->quoteName('#__joomleague_match_result', 'result') . " ON result.match_id = match.id AND result.status_code = 'final'")
			->where('match.project_id = :projectId')
			->where('match.published = 1')
			->bind(':projectId', $projectId, ParameterType::INTEGER)
			->order('match.scheduled_start ASC, match.id ASC');

		if ($venueId !== null) {
			$query->where('match.venue_id = :venueId')->bind(':venueId', $venueId, ParameterType::INTEGER);
		}

		if ($entryIds !== null) {
			$query->innerJoin($db->quoteName('#__joomleague_match_participant', 'scope_participant') . ' ON scope_participant.match_id = match.id AND scope_participant.published = 1')
				->whereIn('scope_participant.project_entry_id', $entryIds, ParameterType::INTEGER)
				->group('match.id, match.project_id, match.scheduled_start, match.timezone, match.duration_minutes, match.status_code, match.contest_type, project.name, round.name, venue.id, venue.name, result.status_code');
		}

		$items = $db->setQuery($query)->loadObjectList();
		if ($items === []) {
			return [];
		}

		$eventIds = array_map(static fn (object $item): int => (int) $item->id, $items);
		$participants = $db->setQuery(
			$db->getQuery(true)
				->select([
					'participant.id AS participant_id', 'participant.match_id', 'participant.slot_number', 'participant.project_entry_id',
					'COALESCE(NULLIF(entry.display_name, \'\'), team.name, NULLIF(TRIM(CONCAT(person.first_name, \' \', person.last_name)), \'\'), CONCAT(\'ID \', entry.id)) AS display_name',
				])
				->from($db->quoteName('#__joomleague_match_participant', 'participant'))
				->innerJoin($db->quoteName('#__joomleague_project_entry', 'entry') . ' ON entry.id = participant.project_entry_id AND entry.published = 1')
				->leftJoin($db->quoteName('#__joomleague_team', 'team') . ' ON team.id = entry.team_id AND team.published = 1 AND team.access IN (' . implode(',', $viewLevels) . ')')
				->leftJoin($db->quoteName('#__joomleague_person', 'person') . ' ON person.id = entry.person_id AND person.published = 1 AND person.access IN (' . implode(',', $viewLevels) . ')')
				->whereIn('participant.match_id', $eventIds, ParameterType::INTEGER)
				->where('participant.published = 1')
				->where("((entry.entry_kind = 'team' AND team.id IS NOT NULL) OR (entry.entry_kind = 'person' AND person.id IS NOT NULL) OR entry.entry_kind = 'group')")
				->order('participant.match_id ASC, participant.slot_number ASC, participant.id ASC')
		)->loadObjectList();

		$participantIds = array_map(static fn (object $participant): int => (int) $participant->participant_id, $participants);
		$scores = [];
		if ($participantIds !== []) {
			$scoreRows = $db->setQuery(
				$db->getQuery(true)
					->select(['segment.match_id', 'value.participant_id', 'value.numeric_value', 'value.text_value'])
					->from($db->quoteName('#__joomleague_match_score_segment', 'segment'))
					->innerJoin($db->quoteName('#__joomleague_match_score_value', 'value') . ' ON value.segment_id = segment.id')
					->innerJoin($db->quoteName('#__joomleague_match_result', 'result') . " ON result.match_id = segment.match_id AND result.status_code = 'final'")
					->whereIn('segment.match_id', $eventIds, ParameterType::INTEGER)
					->where('segment.level_code = ' . $db->quote('result'))
			)->loadObjectList();
			foreach ($scoreRows as $score) {
				$scores[(int) $score->match_id][(int) $score->participant_id] = $score->numeric_value ?? $score->text_value;
			}
		}

		$participantsByEvent = [];
		foreach ($participants as $participant) {
			$participantsByEvent[(int) $participant->match_id][] = [
				'entry_id' => (int) $participant->project_entry_id,
				'slot' => (int) $participant->slot_number,
				'name' => (string) $participant->display_name,
				'score' => $scores[(int) $participant->match_id][(int) $participant->participant_id] ?? null,
			];
		}

		return array_map(static fn (object $item): array => [
			'id' => (int) $item->id,
			'project_id' => (int) $item->project_id,
			'scheduled_start' => $item->scheduled_start,
			'timezone' => $item->timezone,
			'duration_minutes' => $item->duration_minutes === null ? null : (int) $item->duration_minutes,
			'status_code' => (string) $item->status_code,
			'contest_type' => (string) $item->contest_type,
			'project_name' => (string) $item->project_name,
			'round_name' => (string) $item->round_name,
			'venue_name' => $item->venue_name,
			'venue_id' => $item->venue_id === null ? null : (int) $item->venue_id,
			'played' => $item->result_status === 'final',
			'participants' => $participantsByEvent[(int) $item->id] ?? [],
		], $items);
	}

	/** @param list<int> $viewLevels @return list<array<string,mixed>> */
	public function forVenue(int $venueId, array $viewLevels): array
	{
		if ($venueId < 1) return [];
		$levels = array_values(array_unique(array_filter(array_map('intval', $viewLevels), static fn (int $id): bool => $id > 0)));
		$levels = $levels === [] ? [1] : $levels;
		$query = $this->database->getQuery(true)->select('DISTINCT match.project_id')->from($this->database->quoteName('#__joomleague_project_match', 'match'))
			->innerJoin($this->database->quoteName('#__joomleague_project', 'project') . ' ON project.id = match.project_id AND project.published = 1 AND project.access IN (' . implode(',', $levels) . ')')
			->where('match.venue_id = :venueId')->where('match.published = 1')->bind(':venueId', $venueId, ParameterType::INTEGER)->order('match.project_id ASC');
		$items = [];
		foreach (array_map('intval', $this->database->setQuery($query)->loadColumn()) as $projectId) {
			array_push($items, ...$this->forProject($projectId, null, $levels, $venueId));
		}
		usort($items, static fn (array $a, array $b): int => strcmp((string) $a['scheduled_start'], (string) $b['scheduled_start']) ?: $a['id'] <=> $b['id']);
		return $items;
	}
}
