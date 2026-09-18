<?php

declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Domain\Service;

defined('_JEXEC') or die;

use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;

/** Maintains a cheap invalidation marker for published standings scopes. */
final class StandingsFreshnessState
{
	public function __construct(private readonly DatabaseInterface $database)
	{
	}

	public function isFresh(int $projectId, ?int $stageId, string $scope): bool
	{
		$stageKey = $stageId ?? 0;
		$query = $this->database->getQuery(true)
			->select(['state.is_dirty', 'state.input_checksum AS state_checksum', 'snapshot.input_checksum AS snapshot_checksum'])
			->from($this->database->quoteName('#__joomleague_standing_freshness', 'state'))
			->innerJoin($this->database->quoteName('#__joomleague_standing_current', 'current')
				. ' ON current.project_id = state.project_id AND current.stage_key = state.stage_key AND current.scope_code = state.scope_code')
			->innerJoin($this->database->quoteName('#__joomleague_standing_snapshot', 'snapshot') . ' ON snapshot.id = current.snapshot_id')
			->where('state.project_id = :project')
			->where('state.stage_key = :stage')
			->where('state.scope_code = :scope')
			->bind(':project', $projectId, ParameterType::INTEGER)
			->bind(':stage', $stageKey, ParameterType::INTEGER)
			->bind(':scope', $scope);
		$row = $this->database->setQuery($query)->loadObject();

		return $row !== null
			&& (int) $row->is_dirty === 0
			&& (string) $row->state_checksum !== ''
			&& hash_equals((string) $row->state_checksum, (string) $row->snapshot_checksum);
	}

	public function markDirty(int $projectId, ?int $stageId, string $scope): void
	{
		$this->database->transactionStart();

		try {
			$this->lockProject($projectId);
			$this->write($projectId, $stageId, $scope, true, null, 0);
			$this->database->transactionCommit();
		} catch (\Throwable $error) {
			$this->database->transactionRollback();

			throw $error;
		}
	}

	public function markFresh(int $projectId, ?int $stageId, string $scope, string $checksum, int $actorId): void
	{
		$this->write($projectId, $stageId, $scope, false, $checksum, $actorId);
	}

	/** Serializes standings publication and invalidation for one project. */
	public function lockProject(int $projectId): void
	{
		if ($projectId < 1) {
			throw new \InvalidArgumentException('Standings project is invalid.');
		}

		$sql = 'SELECT ' . $this->database->quoteName('id')
			. ' FROM ' . $this->database->quoteName('#__joomleague_project')
			. ' WHERE ' . $this->database->quoteName('id') . ' = ' . $projectId
			. ' FOR UPDATE';

		if ((int) $this->database->setQuery($sql)->loadResult() !== $projectId) {
			throw new \UnexpectedValueException('Standings project does not exist.');
		}
	}

	private function write(int $projectId, ?int $stageId, string $scope, bool $dirty, ?string $checksum, int $actorId): void
	{
		$stageKey = $stageId ?? 0;
		$now = gmdate('Y-m-d H:i:s');
		$dirtyValue = $dirty ? 1 : 0;
		$dirtyAt = $dirty ? $now : null;
		$refreshedAt = $dirty ? null : $now;
		$refreshedBy = $dirty ? 0 : $actorId;
		$existsQuery = $this->database->getQuery(true)
			->select('COUNT(*)')
			->from($this->database->quoteName('#__joomleague_standing_freshness'))
			->where($this->database->quoteName('project_id') . ' = :project')
			->where($this->database->quoteName('stage_key') . ' = :stage')
			->where($this->database->quoteName('scope_code') . ' = :scope')
			->bind(':project', $projectId, ParameterType::INTEGER)
			->bind(':stage', $stageKey, ParameterType::INTEGER)
			->bind(':scope', $scope);
		$exists = (int) $this->database->setQuery($existsQuery)->loadResult() > 0;

		if (!$exists) {
			$query = $this->database->getQuery(true)
				->insert($this->database->quoteName('#__joomleague_standing_freshness'))
				->columns($this->database->quoteName(['project_id', 'stage_key', 'scope_code', 'is_dirty', 'input_checksum', 'dirty_at', 'refreshed_at', 'refreshed_by']))
				->values(':project, :stage, :scope, :dirty, :checksum, :dirtyAt, :refreshedAt, :actor')
				->bind(':project', $projectId, ParameterType::INTEGER)
				->bind(':stage', $stageKey, ParameterType::INTEGER)
				->bind(':scope', $scope)
				->bind(':dirty', $dirtyValue, ParameterType::INTEGER)
				->bind(':checksum', $checksum)
				->bind(':dirtyAt', $dirtyAt)
				->bind(':refreshedAt', $refreshedAt)
				->bind(':actor', $refreshedBy, ParameterType::INTEGER);
			$this->database->setQuery($query)->execute();

			return;
		}

		$query = $this->database->getQuery(true)
			->update($this->database->quoteName('#__joomleague_standing_freshness'))
			->set($this->database->quoteName('is_dirty') . ' = :dirty')
			->set($this->database->quoteName('input_checksum') . ' = :checksum')
			->set($this->database->quoteName('dirty_at') . ' = :dirtyAt')
			->set($this->database->quoteName('refreshed_at') . ' = :refreshedAt')
			->set($this->database->quoteName('refreshed_by') . ' = :actor')
			->where($this->database->quoteName('project_id') . ' = :project')
			->where($this->database->quoteName('stage_key') . ' = :stage')
			->where($this->database->quoteName('scope_code') . ' = :scope')
			->bind(':dirty', $dirtyValue, ParameterType::INTEGER)
			->bind(':checksum', $checksum)
			->bind(':dirtyAt', $dirtyAt)
			->bind(':refreshedAt', $refreshedAt)
			->bind(':actor', $refreshedBy, ParameterType::INTEGER)
			->bind(':project', $projectId, ParameterType::INTEGER)
			->bind(':stage', $stageKey, ParameterType::INTEGER)
			->bind(':scope', $scope);
		$this->database->setQuery($query)->execute();
	}
}
