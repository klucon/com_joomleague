<?php
/**
 * @package     JoomLeague
 * @copyright   Copyright (C) 2026 Ondřej Klučka (https://klucon.cz). All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */


declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Administrator\Model;

\defined('_JEXEC') or die;

use Joomla\CMS\Date\Date;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\Database\ParameterType;
use Throwable;

final class ProjectsetupModel extends BaseDatabaseModel
{
	public function getProject(int $id): ?object
	{
		$db = $this->getDatabase();
		$query = $db->createQuery()
			->select('*')
			->from($db->quoteName('#__joomleague_project'))
			->where($db->quoteName('id') . ' = :id')
			->bind(':id', $id, ParameterType::INTEGER);

		return $db->setQuery($query)->loadObject();
	}

	public function getPositions(int $projectId): array
	{
		$db = $this->getDatabase();
		$query = $db->createQuery()
			->select([
				'p.id',
				'p.name',
				'p.persontype',
				'parent.name AS parent_name',
				'COUNT(DISTINCT pe.eventtype_id) AS event_count',
				'COUNT(DISTINCT ps.statistic_id) AS statistic_count',
				'pp.id AS assignment_id',
				'CASE WHEN pp.id IS NULL THEN 0 ELSE 1 END AS selected',
			])
			->from('#__joomleague_position p')
			->join('LEFT', '#__joomleague_position parent ON parent.id = p.parent_id')
			->join('LEFT', '#__joomleague_position_eventtype pe ON pe.position_id = p.id')
			->join('LEFT', '#__joomleague_position_statistic ps ON ps.position_id = p.id')
			->join('LEFT', '#__joomleague_project_position pp ON pp.position_id = p.id AND pp.project_id = :project_id')
			->where('p.sports_type_id = (SELECT sports_type_id FROM #__joomleague_project WHERE id = :project_id_lookup)')
			->group('p.id, p.name, p.persontype, parent.name, pp.id')
			->order('p.ordering ASC, p.name ASC')
			->bind(':project_id', $projectId, ParameterType::INTEGER)
			->bind(':project_id_lookup', $projectId, ParameterType::INTEGER);

		return $db->setQuery($query)->loadObjectList();
	}

	public function getTeams(int $projectId): array
	{
		$db = $this->getDatabase();
		$query = $db->createQuery()
			->select([
				't.id',
				'CONCAT(t.name, " — ", COALESCE(c.name, "")) AS name',
				'0 AS persontype',
				'pt.ordering',
				'pt.id AS assignment_id',
				'CASE WHEN pt.id IS NULL THEN 0 ELSE 1 END AS selected',
			])
			->from('#__joomleague_team t')
			->join('LEFT', '#__joomleague_club c ON c.id = t.club_id')
			->join('LEFT', '#__joomleague_project_team pt ON pt.team_id = t.id AND pt.project_id = :project_id')
			->order('CASE WHEN pt.id IS NULL THEN 1 ELSE 0 END ASC, pt.ordering ASC, t.name ASC')
			->bind(':project_id', $projectId, ParameterType::INTEGER);

		return $db->setQuery($query)->loadObjectList();
	}

	public function getReferees(int $projectId): array
	{
		$db = $this->getDatabase();
		$query = $db->createQuery()
			->select([
				'p.id',
				'TRIM(CONCAT_WS(" ", p.firstname, p.lastname)) AS name',
				'3 AS persontype',
				'pr.id AS assignment_id',
				'CASE WHEN pr.id IS NULL THEN 0 ELSE 1 END AS selected',
			])
			->from('#__joomleague_person p')
			->join('LEFT', '#__joomleague_project_referee pr ON pr.person_id = p.id AND pr.project_id = :project_id')
			->where('p.published = 1')
			->order('p.lastname ASC, p.firstname ASC')
			->bind(':project_id', $projectId, ParameterType::INTEGER);

		return $db->setQuery($query)->loadObjectList();
	}

	public function syncPositions(int $projectId, array $ids): void
	{
		$this->sync('#__joomleague_project_position', 'position_id', $projectId, $ids);
	}

	public function syncTeams(int $projectId, array $ids): void
	{
		$this->sync(
			'#__joomleague_project_team',
			'team_id',
			$projectId,
			$ids,
			[
				'notes' => '',
				'reason' => '',
				'info' => '',
				'alias' => '',
				'is_in_score' => 1,
				'use_finally' => 0,
				'start_points' => 0,
			]
		);
	}

	public function saveTeamOrdering(int $projectId, array $ordering): void
	{
		$db = $this->getDatabase();
		$ordering = array_filter($ordering, static fn ($value, $key): bool => (int) $key > 0, ARRAY_FILTER_USE_BOTH);

		$db->transactionStart();

		try {
			foreach ($ordering as $assignmentId => $value) {
				$assignmentId = (int) $assignmentId;
				$value = max(1, (int) $value);

				$query = $db->createQuery()
					->update($db->quoteName('#__joomleague_project_team'))
					->set($db->quoteName('ordering') . ' = :ordering')
					->where($db->quoteName('id') . ' = :id')
					->where($db->quoteName('project_id') . ' = :project_id')
					->bind(':ordering', $value, ParameterType::INTEGER)
					->bind(':id', $assignmentId, ParameterType::INTEGER)
					->bind(':project_id', $projectId, ParameterType::INTEGER);

				$db->setQuery($query)->execute();
			}

			$db->transactionCommit();
		} catch (Throwable $exception) {
			$db->transactionRollback();
			throw $exception;
		}
	}

	public function syncReferees(int $projectId, array $ids): void
	{
		$this->sync(
			'#__joomleague_project_referee',
			'person_id',
			$projectId,
			$ids,
			[
				'notes' => '',
				'picture' => '',
				'alias' => '',
				'published' => 1,
			]
		);
	}

	private function sync(string $table, string $column, int $projectId, array $ids, array $defaults = []): void
	{
		$ids = array_values(array_unique(array_filter(array_map('intval', $ids))));
		$db = $this->getDatabase();

		$db->transactionStart();

		try {
			$delete = $db->createQuery()
				->delete($db->quoteName($table))
				->where($db->quoteName('project_id') . ' = :project_id')
				->bind(':project_id', $projectId, ParameterType::INTEGER);

			if ($ids !== []) {
				$delete->where($db->quoteName($column) . ' NOT IN (' . implode(',', $ids) . ')');
			}

			$db->setQuery($delete)->execute();

			$select = $db->createQuery()
				->select($db->quoteName($column))
				->from($db->quoteName($table))
				->where($db->quoteName('project_id') . ' = :project_id')
				->bind(':project_id', $projectId, ParameterType::INTEGER);

			$existing = array_map('intval', $db->setQuery($select)->loadColumn());
			$nextOrdering = $table === '#__joomleague_project_team' ? $this->getNextOrdering($table, $projectId) : 0;

			foreach (array_diff($ids, $existing) as $value) {
				$data = [
					'project_id' => $projectId,
					$column => $value,
					'modified' => (new Date())->toSql(),
				];

				if ($table === '#__joomleague_project_team') {
					$data['ordering'] = $nextOrdering++;
				}

				$row = (object) array_merge($data, $defaults);

				$db->insertObject($table, $row);
			}

			$db->transactionCommit();
		} catch (Throwable $exception) {
			$db->transactionRollback();
			throw $exception;
		}
	}

	private function getNextOrdering(string $table, int $projectId): int
	{
		$db = $this->getDatabase();
		$query = $db->createQuery()
			->select('COALESCE(MAX(' . $db->quoteName('ordering') . '), 0) + 1')
			->from($db->quoteName($table))
			->where($db->quoteName('project_id') . ' = :project_id')
			->bind(':project_id', $projectId, ParameterType::INTEGER);

		return max(1, (int) $db->setQuery($query)->loadResult());
	}
}
