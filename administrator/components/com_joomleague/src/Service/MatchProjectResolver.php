<?php

declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Administrator\Service;

defined('_JEXEC') or die;

use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;

/**
 * Resolves the owning project for a schedule item / stage / round / transition / entry,
 * so per-project ACL checks can be applied to controllers that only receive a child ID
 * from the request.
 */
final class MatchProjectResolver
{
	public function __construct(private readonly DatabaseInterface $database)
	{
	}

	public function resolveProjectId(int $matchId): int
	{
		return $this->resolveFromTable('#__joomleague_project_match', $matchId);
	}

	/** @return array{project_id:int,stage_id:?int} */
	public function resolveMatchContext(int $matchId): array
	{
		if ($matchId < 1) {
			return ['project_id' => 0, 'stage_id' => null];
		}

		$query = $this->database->getQuery(true)
			->select($this->database->quoteName(['project_id', 'stage_id']))
			->from($this->database->quoteName('#__joomleague_project_match'))
			->where($this->database->quoteName('id') . ' = :id')
			->bind(':id', $matchId, ParameterType::INTEGER);

		$row = $this->database->setQuery($query)->loadAssoc();

		if ($row === null) {
			return ['project_id' => 0, 'stage_id' => null];
		}

		return [
			'project_id' => (int) $row['project_id'],
			'stage_id' => $row['stage_id'] === null ? null : (int) $row['stage_id'],
		];
	}

	public function resolveProjectIdFromStage(int $stageId): int
	{
		return $this->resolveFromTable('#__joomleague_project_stage', $stageId);
	}

	public function resolveProjectIdFromRound(int $roundId): int
	{
		return $this->resolveFromTable('#__joomleague_project_round', $roundId);
	}

	public function resolveProjectIdFromTransition(int $transitionId): int
	{
		return $this->resolveFromTable('#__joomleague_stage_transition', $transitionId);
	}

	public function resolveProjectIdFromStandingAdjustment(int $adjustmentId): int
	{
		return $this->resolveFromTable('#__joomleague_standing_adjustment', $adjustmentId);
	}

	public function resolveProjectIdFromEntry(int $entryId): int
	{
		return $this->resolveFromTable('#__joomleague_project_entry', $entryId);
	}

	private function resolveFromTable(string $table, int $id): int
	{
		if ($id < 1) {
			return 0;
		}

		$query = $this->database->getQuery(true)
			->select($this->database->quoteName('project_id'))
			->from($this->database->quoteName($table))
			->where($this->database->quoteName('id') . ' = :id')
			->bind(':id', $id, ParameterType::INTEGER);

		return (int) $this->database->setQuery($query)->loadResult();
	}
}
