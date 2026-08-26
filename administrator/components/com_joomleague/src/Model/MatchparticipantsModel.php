<?php

declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Administrator\Model;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\Database\ParameterType;
use Joomleague\Component\Joomleague\Administrator\Service\MatchCompetitionDataGuard;
use Joomleague\Component\Joomleague\Administrator\Service\ProjectContextRepository;
use Joomleague\Component\Joomleague\Administrator\Service\StageEntryOptionsProvider;
use Joomleague\Component\Joomleague\Domain\Service\UuidFactory;

/**
 * Manages the participant list of a non-head-to-head match (currently: any "race" contest).
 * Head-to-head matches keep their two fixed home/away slots on the Matches screen itself; this
 * model exists for everything else, where the number of participants is not fixed to two.
 */
final class MatchparticipantsModel extends BaseDatabaseModel
{
	/** @return array{match:object,contestType:string,locked:bool,assigned:list<array{id:int,entry_id:int,name:string}>,available:list<object>} */
	public function getContext(int $matchId): array
	{
		$match = $this->matchRow($matchId);
		$contestType = $this->assertVariableParticipants($match);
		$assigned = $this->assignedParticipants($matchId);
		$assignedEntryIds = array_column($assigned, 'entry_id');
		$available = array_values(array_filter(
			(new StageEntryOptionsProvider($this->getDatabase()))->load((int) $match->project_id, (int) $match->stage_id),
			static fn (object $entry): bool => !in_array((int) $entry->value, $assignedEntryIds, true)
		));

		return [
			'match' => $match,
			'contestType' => $contestType,
			'locked' => (new MatchCompetitionDataGuard($this->getDatabase()))->hasCompetitionData($matchId),
			'assigned' => $assigned,
			'available' => $available,
		];
	}

	/** @param list<int> $entryIds */
	public function add(int $matchId, array $entryIds, int $userId): void
	{
		$match = $this->matchRow($matchId);
		$this->assertVariableParticipants($match);
		if ((new MatchCompetitionDataGuard($this->getDatabase()))->hasCompetitionData($matchId)) {
			throw new \RuntimeException(Text::_('COM_JOOMLEAGUE_ERROR_MATCH_PARTICIPANTS_LOCKED'));
		}

		$entryIds = array_values(array_unique(array_filter(array_map('intval', $entryIds), static fn (int $id): bool => $id > 0)));
		if ($entryIds === []) {
			return;
		}

		if (!(new StageEntryOptionsProvider($this->getDatabase()))->contains((int) $match->project_id, (int) $match->stage_id, $entryIds)) {
			throw new \UnexpectedValueException(Text::_('COM_JOOMLEAGUE_ERROR_MATCH_PARTICIPANTS_INVALID'));
		}

		$existingEntryIds = array_column($this->assignedParticipants($matchId), 'entry_id');
		$slot = $this->nextSlotNumber($matchId);
		$db = $this->getDatabase();
		$db->transactionStart();
		try {
			foreach ($entryIds as $entryId) {
				if (in_array($entryId, $existingEntryIds, true)) {
					continue;
				}

				$row = (object) [
					'uuid' => UuidFactory::v4(), 'match_id' => $matchId, 'project_id' => (int) $match->project_id,
					'project_entry_id' => $entryId, 'role_code' => 'participant', 'slot_number' => $slot,
					'result_status' => 'scheduled', 'published' => 1, 'ordering' => $slot,
					'created' => Factory::getDate()->toSql(), 'created_by' => $userId,
				];
				$db->insertObject('#__joomleague_match_participant', $row);
				$slot++;
			}
			$db->transactionCommit();
		} catch (\Throwable $error) {
			$db->transactionRollback();
			throw $error;
		}
	}

	/** @param list<int> $participantIds */
	public function remove(int $matchId, array $participantIds): void
	{
		$match = $this->matchRow($matchId);
		$this->assertVariableParticipants($match);

		if ((new MatchCompetitionDataGuard($this->getDatabase()))->hasCompetitionData($matchId)) {
			throw new \RuntimeException(Text::_('COM_JOOMLEAGUE_ERROR_MATCH_PARTICIPANTS_LOCKED'));
		}

		$participantIds = array_values(array_unique(array_filter(array_map('intval', $participantIds), static fn (int $id): bool => $id > 0)));
		if ($participantIds === []) {
			return;
		}

		$db = $this->getDatabase();
		$query = $db->getQuery(true)->delete($db->quoteName('#__joomleague_match_participant'))
			->where($db->quoteName('match_id') . ' = :matchId')->bind(':matchId', $matchId, ParameterType::INTEGER)
			->whereIn($db->quoteName('id'), $participantIds, ParameterType::INTEGER);
		$db->setQuery($query)->execute();
	}

	private function assertVariableParticipants(object $match): string
	{
		$project = (new ProjectContextRepository($this->getDatabase()))->get((int) $match->project_id);
		$contestType = (string) ($project->profile['contest']['type'] ?? 'head_to_head');

		if ($contestType === 'head_to_head') {
			throw new \UnexpectedValueException(Text::_('COM_JOOMLEAGUE_ERROR_MATCH_PARTICIPANTS_FIXED'));
		}

		return $contestType;
	}

	private function matchRow(int $matchId): object
	{
		$db = $this->getDatabase();
		$query = $db->getQuery(true)
			->select(['m.id', 'm.round_id', 'm.stage_id', 'm.project_id', 'm.match_number', 'm.scheduled_start', 'round.name AS round_name'])
			->from($db->quoteName('#__joomleague_project_match', 'm'))
			->innerJoin($db->quoteName('#__joomleague_project_round', 'round') . ' ON round.id = m.round_id')
			->where($db->quoteName('m.id') . ' = :matchId')->bind(':matchId', $matchId, ParameterType::INTEGER);
		$match = $db->setQuery($query)->loadObject();
		if (!$match) {
			throw new \RuntimeException(Text::_('COM_JOOMLEAGUE_ERROR_MATCH_INVALID'));
		}

		return $match;
	}

	/** @return list<array{id:int,entry_id:int,name:string}> */
	private function assignedParticipants(int $matchId): array
	{
		$db = $this->getDatabase();
		$query = $db->getQuery(true)
			->select([
				$db->quoteName('participant.id'),
				$db->quoteName('participant.project_entry_id', 'entry_id'),
				$db->quoteName('entry.entry_kind'),
				$db->quoteName('entry.display_name'),
				$db->quoteName('team.name', 'team_name'),
				$db->quoteName('person.first_name'),
				$db->quoteName('person.last_name'),
			])
			->from($db->quoteName('#__joomleague_match_participant', 'participant'))
			->innerJoin($db->quoteName('#__joomleague_project_entry', 'entry') . ' ON entry.id = participant.project_entry_id')
			->leftJoin($db->quoteName('#__joomleague_team', 'team') . ' ON team.id = entry.team_id')
			->leftJoin($db->quoteName('#__joomleague_person', 'person') . ' ON person.id = entry.person_id')
			->where($db->quoteName('participant.match_id') . ' = :matchId')
			->bind(':matchId', $matchId, ParameterType::INTEGER)
			->order($db->quoteName('participant.slot_number') . ' ASC');

		$result = [];
		foreach ($db->setQuery($query)->loadObjectList() as $row) {
			$name = match ((string) $row->entry_kind) {
				'team' => (string) $row->team_name,
				'person' => trim((string) $row->first_name . ' ' . (string) $row->last_name),
				default => (string) $row->display_name,
			};
			$result[] = ['id' => (int) $row->id, 'entry_id' => (int) $row->entry_id, 'name' => $name];
		}

		return $result;
	}

	private function nextSlotNumber(int $matchId): int
	{
		$db = $this->getDatabase();
		$query = $db->getQuery(true)->select('MAX(' . $db->quoteName('slot_number') . ')')->from($db->quoteName('#__joomleague_match_participant'))
			->where($db->quoteName('match_id') . ' = :matchId')->bind(':matchId', $matchId, ParameterType::INTEGER);

		return (int) $db->setQuery($query)->loadResult() + 1;
	}
}
