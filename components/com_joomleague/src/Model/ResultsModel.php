<?php

declare(strict_types=1);

/**
 * @package     Joomleague.Site
 * @subpackage  com_joomleague
 *
 * @copyright   (C) 2026 Ondřej Klučka (https://klucon.cz). All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomleague\Component\Joomleague\Site\Model;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;

/** Published programme items and finalized results for one project. */
final class ResultsModel extends BaseDatabaseModel
{
	protected function populateState($ordering = null, $direction = null): void
	{
		$input = Factory::getApplication()->getInput();
		$this->setState('project_id', $input->getInt('project_id', 0));
		$stageId = $input->getInt('stage_id', 0);
		$this->setState('stage_id', $stageId > 0 ? $stageId : null);
	}

	/** @return array<string,mixed> */
	public function getResults(): array
	{
		$projectId = (int) $this->getState('project_id');
		if ($projectId < 1) {
			return ['error' => 'COM_JOOMLEAGUE_RESULTS_NO_PROJECT'];
		}

		$db = Factory::getContainer()->get(DatabaseInterface::class);
		$project = $db->setQuery(
			$db->getQuery(true)
				->select(['project.id', 'project.name', 'project.timezone', 'sporttype.name AS sport_name'])
				->from($db->quoteName('#__joomleague_project', 'project'))
				->innerJoin($db->quoteName('#__joomleague_competition', 'competition') . ' ON competition.id = project.competition_id AND competition.published = 1')
				->innerJoin($db->quoteName('#__joomleague_season', 'season') . ' ON season.id = project.season_id AND season.published = 1')
				->innerJoin($db->quoteName('#__joomleague_sport_type', 'sporttype') . ' ON sporttype.id = project.sport_type_id AND sporttype.published = 1')
				->where('project.id = :projectId')->where('project.published = 1')
				->bind(':projectId', $projectId, ParameterType::INTEGER)
		)->loadObject();

		if (!$project) {
			return ['error' => 'COM_JOOMLEAGUE_RESULTS_UNAVAILABLE'];
		}

		$query = $db->getQuery(true)
			->select([
				'item.id', 'item.round_id', 'item.match_number', 'item.scheduled_start', 'item.timezone',
				'item.attendance', 'item.status_code', 'venue.name AS venue_name',
				'round.name AS round_name', 'stage.id AS stage_id', 'stage.name AS stage_name',
				'result.status_code AS result_status', 'result.notes AS result_notes',
			])
			->from($db->quoteName('#__joomleague_project_match', 'item'))
			->innerJoin($db->quoteName('#__joomleague_project_stage', 'stage') . ' ON stage.id = item.stage_id AND stage.published = 1')
			->innerJoin($db->quoteName('#__joomleague_project_round', 'round') . ' ON round.id = item.round_id AND round.published = 1')
			->leftJoin($db->quoteName('#__joomleague_venue', 'venue') . ' ON venue.id = item.venue_id AND venue.published = 1')
			->leftJoin($db->quoteName('#__joomleague_match_result', 'result') . " ON result.match_id = item.id AND result.status_code = 'final'")
			->where('item.project_id = :projectId')->where('item.published = 1')
			->bind(':projectId', $projectId, ParameterType::INTEGER)
			->order('stage.sequence_number ASC, stage.id ASC, round.sequence_number ASC, round.id ASC, item.scheduled_start ASC, item.id ASC');

		$stageId = $this->getState('stage_id');
		if ($stageId !== null) {
			$query->where('stage.id = :stageId')->bind(':stageId', $stageId, ParameterType::INTEGER);
		}

		$items = $db->setQuery($query)->loadObjectList();
		if ($items === []) {
			return ['error' => 'COM_JOOMLEAGUE_RESULTS_VIEW_EMPTY', 'project' => $project];
		}

		$itemIds = array_map(static fn (object $item): int => (int) $item->id, $items);
		$participants = $db->setQuery(
			$db->getQuery(true)
				->select([
					'participant.id', 'participant.match_id', 'participant.slot_number', 'participant.role_code',
					'participant.result_status', 'participant.result_rank', 'entry.id AS entry_id',
					'entry.display_name', 'team.name AS team_name', 'person.first_name', 'person.last_name',
				])
				->from($db->quoteName('#__joomleague_match_participant', 'participant'))
				->innerJoin($db->quoteName('#__joomleague_project_entry', 'entry') . ' ON entry.id = participant.project_entry_id AND entry.published = 1')
				->leftJoin($db->quoteName('#__joomleague_team', 'team') . ' ON team.id = entry.team_id AND team.published = 1')
				->leftJoin($db->quoteName('#__joomleague_person', 'person') . ' ON person.id = entry.person_id AND person.published = 1')
				->whereIn('participant.match_id', $itemIds, ParameterType::INTEGER)->where('participant.published = 1')
				->where("((entry.entry_kind = 'team' AND team.id IS NOT NULL) OR (entry.entry_kind = 'person' AND person.id IS NOT NULL) OR entry.entry_kind = 'group')")
				->order('participant.match_id ASC, participant.slot_number ASC, participant.id ASC')
		)->loadObjectList();

		$participantsByItem = [];
		$participantById = [];
		foreach ($participants as $participant) {
			$personName = trim((string) $participant->first_name . ' ' . (string) $participant->last_name);
			$participant->name = (string) ($participant->display_name ?: $participant->team_name ?: $personName ?: ('ID ' . $participant->entry_id));
			$participant->result_value = null;
			$participantsByItem[(int) $participant->match_id][] = $participant;
			$participantById[(int) $participant->id] = $participant;
		}

		$finalItemIds = array_values(array_map(
			static fn (object $item): int => (int) $item->id,
			array_filter($items, static fn (object $item): bool => $item->result_status === 'final')
		));
		if ($finalItemIds !== []) {
			$values = $db->setQuery(
				$db->getQuery(true)
					->select(['value.participant_id', 'value.numeric_value', 'value.text_value', 'value.status_code', 'value.result_rank'])
					->from($db->quoteName('#__joomleague_match_score_value', 'value'))
					->innerJoin($db->quoteName('#__joomleague_match_score_segment', 'segment') . ' ON segment.id = value.segment_id AND segment.parent_id IS NULL')
					->whereIn('value.match_id', $finalItemIds, ParameterType::INTEGER)
			)->loadObjectList();
			foreach ($values as $value) {
				if (isset($participantById[(int) $value->participant_id])) {
					$participantById[(int) $value->participant_id]->result_value = $value;
				}
			}
		}

		$params = Factory::getApplication()->getParams();
		$showEvents = (int) $params->get('show_events', 1) === 1;
		$showVenue = (int) $params->get('show_venue', 1) === 1;
		$eventsByItem = [];
		if ($showEvents) {
			$events = $db->setQuery(
				$db->getQuery(true)
					->select(['event.match_id', 'event.event_name_key', 'event.primary_name_snapshot', 'event.clock_value', 'event.clock_unit'])
					->from($db->quoteName('#__joomleague_match_event', 'event'))
					->whereIn('event.match_id', $itemIds, ParameterType::INTEGER)->where('event.published = 1')
					->order('event.match_id ASC, event.sequence_number ASC, event.id ASC')
			)->loadObjectList();
			foreach ($events as $event) {
				$eventsByItem[(int) $event->match_id][] = $event;
			}
		}

		$rounds = [];
		foreach ($items as $item) {
			$item->participants = $participantsByItem[(int) $item->id] ?? [];
			$item->events = $eventsByItem[(int) $item->id] ?? [];
			if (!$showVenue) {
				$item->venue_name = null;
				$item->attendance = null;
			}
			$key = (int) $item->round_id;
			$rounds[$key] ??= ['stage_name' => (string) $item->stage_name, 'name' => (string) $item->round_name, 'items' => []];
			$rounds[$key]['items'][] = $item;
		}

		return ['project' => $project, 'rounds' => array_values($rounds), 'show_events' => $showEvents, 'show_venue' => $showVenue];
	}
}
