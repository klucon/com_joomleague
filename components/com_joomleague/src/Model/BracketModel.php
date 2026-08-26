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

/** Builds a sport-neutral progression bracket from published programme data. */
final class BracketModel extends BaseDatabaseModel
{
	private const PARTICIPANT_HEIGHT = 22;
	private const CARD_PADDING = 12;
	private const ROW_GAP = 12;

	protected function populateState($ordering = null, $direction = null): void
	{
		$input = Factory::getApplication()->getInput();
		$this->setState('project_id', $input->getInt('project_id', 0));
		$this->setState('stage_id', $input->getInt('stage_id', 0));
	}

	/** @return array<string,mixed> */
	public function getBracket(): array
	{
		$projectId = (int) $this->getState('project_id');
		$stageId = (int) $this->getState('stage_id');
		if ($projectId < 1 || $stageId < 1) {
			return ['error' => 'COM_JOOMLEAGUE_BRACKET_NO_STAGE'];
		}

		$db = Factory::getContainer()->get(DatabaseInterface::class);
		$project = $db->setQuery(
			$db->getQuery(true)
				->select(['project.id', 'project.name'])
				->from($db->quoteName('#__joomleague_project', 'project'))
				->innerJoin($db->quoteName('#__joomleague_competition', 'competition') . ' ON competition.id = project.competition_id AND competition.published = 1')
				->innerJoin($db->quoteName('#__joomleague_season', 'season') . ' ON season.id = project.season_id AND season.published = 1')
				->innerJoin($db->quoteName('#__joomleague_sport_type', 'sporttype') . ' ON sporttype.id = project.sport_type_id AND sporttype.published = 1')
				->where('project.id = :projectId')->where('project.published = 1')
				->where(\Joomleague\Component\Joomleague\Site\Service\PublicAccess::condition($db, 'project'))
				->where(\Joomleague\Component\Joomleague\Site\Service\PublicAccess::condition($db, 'competition'))
				->where(\Joomleague\Component\Joomleague\Site\Service\PublicAccess::condition($db, 'season'))
				->bind(':projectId', $projectId, ParameterType::INTEGER)
		)->loadObject();

		$targetStage = $db->setQuery(
			$db->getQuery(true)->select(['id', 'name', 'sequence_number'])
				->from($db->quoteName('#__joomleague_project_stage'))
				->where('id = :stageId')->where('project_id = :projectId')->where('published = 1')
				->bind(':stageId', $stageId, ParameterType::INTEGER)
				->bind(':projectId', $projectId, ParameterType::INTEGER)
		)->loadObject();

		if (!$project || !$targetStage) {
			return ['error' => 'COM_JOOMLEAGUE_BRACKET_UNAVAILABLE'];
		}

		$rounds = $db->setQuery(
			$db->getQuery(true)->select(['round.id', 'round.name', 'round.stage_id'])
				->from($db->quoteName('#__joomleague_project_round', 'round'))
				->innerJoin($db->quoteName('#__joomleague_project_stage', 'stage') . ' ON stage.id = round.stage_id AND stage.published = 1')
				->where('round.stage_id = :stageId')->where('round.published = 1')
				->bind(':stageId', $stageId, ParameterType::INTEGER)
				->order('stage.sequence_number ASC, stage.id ASC, round.sequence_number ASC, round.id ASC')
		)->loadObjectList();
		if (count($rounds) < 2) {
			return ['error' => 'COM_JOOMLEAGUE_BRACKET_VIEW_EMPTY', 'project' => $project];
		}

		$roundIds = array_map(static fn (object $round): int => (int) $round->id, $rounds);
		$items = $db->setQuery(
			$db->getQuery(true)->select(['item.id', 'item.round_id', 'result.status_code AS result_status'])
				->from($db->quoteName('#__joomleague_project_match', 'item'))
				->leftJoin($db->quoteName('#__joomleague_match_result', 'result') . " ON result.match_id = item.id AND result.status_code = 'final'")
				->whereIn('item.round_id', $roundIds, ParameterType::INTEGER)->where('item.published = 1')
				->order('item.round_id ASC, item.scheduled_start ASC, item.id ASC')
		)->loadObjectList();
		$itemIds = array_map(static fn (object $item): int => (int) $item->id, $items);
		if ($itemIds === []) {
			return ['error' => 'COM_JOOMLEAGUE_BRACKET_VIEW_EMPTY', 'project' => $project];
		}

		$participants = $db->setQuery(
			$db->getQuery(true)
				->select([
					'participant.id', 'participant.match_id', 'participant.project_entry_id', 'participant.slot_number',
					'participant.result_rank', 'entry.id AS entry_id', 'entry.display_name',
					'team.name AS team_name', 'person.first_name', 'person.last_name',
				])
				->from($db->quoteName('#__joomleague_match_participant', 'participant'))
				->innerJoin($db->quoteName('#__joomleague_project_entry', 'entry') . ' ON entry.id = participant.project_entry_id AND entry.published = 1')
				->leftJoin($db->quoteName('#__joomleague_team', 'team') . ' ON team.id = entry.team_id AND team.published = 1 AND ' . \Joomleague\Component\Joomleague\Site\Service\PublicAccess::condition($db, 'team'))
				->leftJoin($db->quoteName('#__joomleague_person', 'person') . ' ON person.id = entry.person_id AND person.published = 1 AND ' . \Joomleague\Component\Joomleague\Site\Service\PublicAccess::condition($db, 'person'))
				->whereIn('participant.match_id', $itemIds, ParameterType::INTEGER)->where('participant.published = 1')
				->where("((entry.entry_kind = 'team' AND team.id IS NOT NULL) OR (entry.entry_kind = 'person' AND person.id IS NOT NULL) OR entry.entry_kind = 'group')")
				->order('participant.match_id ASC, participant.slot_number ASC, participant.id ASC')
		)->loadObjectList();

		$participantsByItem = [];
		$participantById = [];
		foreach ($participants as $participant) {
			$personName = trim((string) $participant->first_name . ' ' . (string) $participant->last_name);
			$participant->name = (string) ($participant->display_name ?: $participant->team_name ?: $personName ?: ('ID ' . $participant->entry_id));
			$participant->value = null;
			$participant->winner = false;
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
				$participant = $participantById[(int) $value->participant_id] ?? null;
				if (!$participant) {
					continue;
				}
				$participant->value = $value;
				$participant->winner = $value->status_code === 'winner' || (int) $value->result_rank === 1;
			}
		}

		$maxParticipants = max(1, ...array_map('count', $participantsByItem));
		$cardHeight = max(52, $maxParticipants * self::PARTICIPANT_HEIGHT + self::CARD_PADDING);
		$rowHeight = $cardHeight + self::ROW_GAP;
		$itemsByRound = [];
		foreach ($items as $item) {
			$item->participants = $participantsByItem[(int) $item->id] ?? [];
			$itemsByRound[(int) $item->round_id][(int) $item->id] = $item;
		}

		$y = [];
		$feedersOf = [];
		$entryItem = [];
		foreach ($rounds as $roundIndex => $round) {
			$roundItems = $itemsByRound[(int) $round->id] ?? [];
			$nextEntryItem = [];
			$desired = [];
			$feeders = [];
			$unpositioned = [];

			foreach ($roundItems as $itemId => $item) {
				$sourcePositions = [];
				$sourceIds = [];
				foreach ($item->participants as $participant) {
					$entryId = (int) $participant->project_entry_id;
					if ($roundIndex > 0 && isset($entryItem[$entryId])) {
						$sourceId = $entryItem[$entryId];
						$sourcePositions[] = $y[$sourceId];
						$sourceIds[] = $sourceId;
					}
					$nextEntryItem[$entryId] = $itemId;
				}
				$feeders[$itemId] = array_values(array_unique($sourceIds));
				if ($roundIndex === 0) {
					$desired[$itemId] = count($desired) * $rowHeight;
				} elseif ($sourcePositions !== []) {
					$desired[$itemId] = array_sum($sourcePositions) / count($sourcePositions);
				} else {
					$unpositioned[] = $itemId;
				}
			}

			$orderedIds = array_keys($desired);
			usort($orderedIds, static fn (int $a, int $b): int => $desired[$a] <=> $desired[$b]);
			$cursor = null;
			foreach ([...$orderedIds, ...$unpositioned] as $itemId) {
				$wanted = $desired[$itemId] ?? ($cursor === null ? 0.0 : $cursor + $rowHeight);
				$y[$itemId] = $cursor === null ? $wanted : max($wanted, $cursor + $rowHeight);
				$feedersOf[$itemId] = $feeders[$itemId] ?? [];
				$cursor = $y[$itemId];
			}
			$entryItem = $nextEntryItem;
		}

		$roundsOut = [];
		$focusRoundIndex = null;
		foreach ($rounds as $roundIndex => $round) {
			$itemsOut = [];
			foreach ($itemsByRound[(int) $round->id] ?? [] as $itemId => $item) {
				$item->y = $y[$itemId] ?? 0.0;
				$item->feeder_ids = $feedersOf[$itemId] ?? [];
				$item->feeder_ys = array_map(static fn (int $sourceId): float => $y[$sourceId], $item->feeder_ids);
				$itemsOut[] = $item;
			}
			$inTargetStage = (int) $round->stage_id === (int) $targetStage->id;
			if ($inTargetStage && $focusRoundIndex === null) {
				$focusRoundIndex = $roundIndex;
			}
			$roundsOut[] = ['name' => (string) $round->name, 'in_target_stage' => $inTargetStage, 'items' => $itemsOut];
		}

		return [
			'project' => $project,
			'stage' => $targetStage,
			'rounds' => $roundsOut,
			'card_height' => $cardHeight,
			'canvas_height' => ($y === [] ? 0 : max($y)) + $cardHeight,
			'focus_round_index' => $focusRoundIndex ?? 0,
		];
	}
}
