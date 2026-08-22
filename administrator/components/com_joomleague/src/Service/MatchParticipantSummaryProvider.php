<?php

declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Administrator\Service;

defined('_JEXEC') or die;

use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;

final class MatchParticipantSummaryProvider
{
	public function __construct(private readonly DatabaseInterface $database)
	{
	}

	/** @param list<int> $matchIds @return array<int,list<string>> */
	public function load(array $matchIds): array
	{
		$details = $this->loadDetails($matchIds);
		$result = [];

		foreach ($details as $matchId => $participants) {
			$result[$matchId] = array_column($participants, 'name');
		}

		return $result;
	}

	/** @param list<int> $matchIds @return array<int,list<array{entry_id:int,slot_number:int,name:string}>> */
	public function loadDetails(array $matchIds): array
	{
		$matchIds = array_values(array_unique(array_filter(array_map('intval', $matchIds), static fn (int $id): bool => $id > 0)));

		if ($matchIds === []) return [];

		$query = $this->database->getQuery(true)
			->select([
				$this->database->quoteName('participant.match_id'),
				$this->database->quoteName('participant.slot_number'),
				$this->database->quoteName('participant.project_entry_id'),
				$this->database->quoteName('entry.entry_kind'),
				$this->database->quoteName('entry.display_name'),
				$this->database->quoteName('team.name', 'team_name'),
				$this->database->quoteName('person.first_name'),
				$this->database->quoteName('person.last_name'),
			])
			->from($this->database->quoteName('#__joomleague_match_participant', 'participant'))
			->innerJoin($this->database->quoteName('#__joomleague_project_entry', 'entry') . ' ON entry.id = participant.project_entry_id AND entry.project_id = participant.project_id')
			->leftJoin($this->database->quoteName('#__joomleague_team', 'team') . ' ON team.id = entry.team_id')
			->leftJoin($this->database->quoteName('#__joomleague_person', 'person') . ' ON person.id = entry.person_id')
			->whereIn($this->database->quoteName('participant.match_id'), $matchIds, ParameterType::INTEGER)
			->order($this->database->quoteName('participant.match_id') . ' ASC')
			->order($this->database->quoteName('participant.slot_number') . ' ASC');
		$participants = [];

		foreach ($this->database->setQuery($query)->loadObjectList() as $row) {
			$name = match ((string) $row->entry_kind) {
				'team' => (string) $row->team_name,
				'person' => trim((string) $row->first_name . ' ' . (string) $row->last_name),
				default => (string) $row->display_name,
			};
			$participants[(int) $row->match_id][] = [
				'entry_id' => (int) $row->project_entry_id,
				'slot_number' => (int) $row->slot_number,
				'name' => $name,
			];
		}

		return $participants;
	}
}
