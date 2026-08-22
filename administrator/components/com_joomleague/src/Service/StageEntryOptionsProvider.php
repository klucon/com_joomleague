<?php

declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Administrator\Service;

defined('_JEXEC') or die;

use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;

final class StageEntryOptionsProvider
{
	public function __construct(private readonly DatabaseInterface $database)
	{
	}

	/** @return list<object> */
	public function load(int $projectId, int $stageId): array
	{
		if ($projectId < 1 || $stageId < 1) return [];

		$modeQuery = $this->database->getQuery(true)
			->select($this->database->quoteName('entry_selection_mode'))
			->from($this->database->quoteName('#__joomleague_project_stage'))
			->where($this->database->quoteName('id') . ' = :stageId')
			->where($this->database->quoteName('project_id') . ' = :projectId')
			->bind(':stageId', $stageId, ParameterType::INTEGER)
			->bind(':projectId', $projectId, ParameterType::INTEGER);
		$mode = (string) $this->database->setQuery($modeQuery)->loadResult();

		if (!in_array($mode, ['inherit_project', 'explicit'], true)) return [];

		$query = $this->database->getQuery(true)
			->select([
				$this->database->quoteName('entry.id', 'value'),
				$this->database->quoteName('entry.entry_kind'),
				$this->database->quoteName('entry.display_name'),
				$this->database->quoteName('team.name', 'team_name'),
				$this->database->quoteName('person.first_name'),
				$this->database->quoteName('person.last_name'),
			])
			->from($this->database->quoteName('#__joomleague_project_entry', 'entry'))
			->leftJoin($this->database->quoteName('#__joomleague_team', 'team') . ' ON team.id = entry.team_id')
			->leftJoin($this->database->quoteName('#__joomleague_person', 'person') . ' ON person.id = entry.person_id')
			->where($this->database->quoteName('entry.project_id') . ' = :projectId')
			->where($this->database->quoteName('entry.published') . ' = 1')
			->bind(':projectId', $projectId, ParameterType::INTEGER)
			->order([$this->database->quoteName('entry.ordering') . ' ASC', $this->database->quoteName('entry.id') . ' ASC']);

		if ($mode === 'explicit') {
			$query->innerJoin($this->database->quoteName('#__joomleague_stage_entry', 'stage_entry') . ' ON stage_entry.entry_id = entry.id AND stage_entry.project_id = entry.project_id')
				->where($this->database->quoteName('stage_entry.stage_id') . ' = :selectedStageId')
				->bind(':selectedStageId', $stageId, ParameterType::INTEGER);
		}

		$entries = $this->database->setQuery($query)->loadObjectList();

		foreach ($entries as $entry) {
			$entry->text = match ((string) $entry->entry_kind) {
				'team' => (string) $entry->team_name,
				'person' => trim((string) $entry->first_name . ' ' . (string) $entry->last_name),
				default => (string) $entry->display_name,
			};
		}

		return $entries;
	}

	/** @param list<int> $entryIds */
	public function contains(int $projectId, int $stageId, array $entryIds): bool
	{
		$available = array_map(static fn (object $entry): int => (int) $entry->value, $this->load($projectId, $stageId));

		return count($entryIds) === count(array_intersect($entryIds, $available));
	}
}
