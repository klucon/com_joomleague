<?php

declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Domain\Service;

defined('_JEXEC') or die;

use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;

/** Resolves a public programme scope to project-entry IDs. */
final class ProgrammeScopeResolver
{
	public function __construct(private readonly DatabaseInterface $database) {}

	/** @param list<int> $viewLevels @return list<int>|null Null means the complete project. */
	public function resolve(int $projectId, string $scope, int $scopeId, array $viewLevels): ?array
	{
		if ($projectId < 1) {
			throw new \InvalidArgumentException('Programme project is invalid.');
		}

		if ($scope === 'project') {
			return null;
		}

		$viewLevels = array_values(array_unique(array_filter(array_map('intval', $viewLevels), static fn (int $id): bool => $id > 0)));
		$viewLevels = $viewLevels === [] ? [1] : $viewLevels;
		if ($scopeId < 1) {
			return [];
		}

		$db = $this->database;
		if ($scope === 'entry') {
			$query = $db->getQuery(true)
				->select('entry.id')
				->from($db->quoteName('#__joomleague_project_entry', 'entry'))
				->leftJoin($db->quoteName('#__joomleague_team', 'team') . ' ON team.id = entry.team_id')
				->leftJoin($db->quoteName('#__joomleague_person', 'person') . ' ON person.id = entry.person_id')
				->where('entry.id = :entryId')
				->where('entry.project_id = :projectId')
				->where('entry.published = 1')
				->where("((entry.entry_kind = 'team' AND team.published = 1 AND team.access IN (" . implode(',', $viewLevels) . ")) OR (entry.entry_kind = 'person' AND person.published = 1 AND person.access IN (" . implode(',', $viewLevels) . ")) OR entry.entry_kind = 'group')")
				->bind(':entryId', $scopeId, ParameterType::INTEGER)
				->bind(':projectId', $projectId, ParameterType::INTEGER);

			return $db->setQuery($query)->loadResult() ? [$scopeId] : [];
		}

		if ($scope !== 'club') {
			return [];
		}

		$query = $db->getQuery(true)
			->select('entry.id')
			->from($db->quoteName('#__joomleague_project_entry', 'entry'))
			->innerJoin($db->quoteName('#__joomleague_team', 'team') . ' ON team.id = entry.team_id AND team.published = 1 AND team.access IN (' . implode(',', $viewLevels) . ')')
			->innerJoin($db->quoteName('#__joomleague_club', 'club') . ' ON club.id = team.club_id AND club.published = 1 AND club.access IN (' . implode(',', $viewLevels) . ')')
			->where('entry.project_id = :projectId')
			->where('entry.published = 1')
			->where('club.id = :clubId')
			->bind(':projectId', $projectId, ParameterType::INTEGER)
			->bind(':clubId', $scopeId, ParameterType::INTEGER)
			->order('entry.id ASC');

		return array_map('intval', $db->setQuery($query)->loadColumn());
	}
}
