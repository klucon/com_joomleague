<?php

declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Administrator\Service;

defined('_JEXEC') or die;

use Joomla\CMS\Log\Log;
use Joomla\Database\DatabaseInterface;
use Joomleague\Component\Joomleague\Domain\Service\StandingsReader;
use Joomleague\Component\Joomleague\Domain\Service\StandingsRecalculator;

/**
 * Republishes standings snapshots after a match result changes, so the
 * published table (and any read-only frontend module/view built on
 * StandingsReader) reflects the latest result without an admin separately
 * opening the Standings screen or clicking Recalculate. Never throws —
 * a standings refresh failure must not fail the result save it followed.
 */
final class StandingsCascadeTrigger
{
	public function __construct(private readonly DatabaseInterface $database)
	{
	}

	public function trigger(int $projectId, ?int $stageId, int $actorId): void
	{
		if ($projectId < 1) {
			return;
		}

		// Refresh both the project-wide table (stage_id null) and, when the
		// match belongs to a stage, that stage's own table — different sport
		// profiles publish standings at either level.
		$stageIds = $stageId === null ? [null] : [null, $stageId];
		$this->triggerContexts($projectId, $stageIds, $actorId);
	}

	/** Republishes project-wide standings and every stage after a project-entry change. */
	public function triggerProject(int $projectId, int $actorId): void
	{
		if ($projectId < 1) {
			return;
		}

		$boundProjectId = $projectId;
		$query = $this->database->getQuery(true)
			->select($this->database->quoteName('id'))
			->from($this->database->quoteName('#__joomleague_project_stage'))
			->where($this->database->quoteName('project_id') . ' = :projectId')
			->bind(':projectId', $boundProjectId, \Joomla\Database\ParameterType::INTEGER)
			->order($this->database->quoteName('id') . ' ASC');
		$stageIds = [null, ...array_map('intval', $this->database->setQuery($query)->loadColumn())];
		$this->triggerContexts($projectId, $stageIds, $actorId);
	}

	/** Republishes only the affected stage after its participant selection changes. */
	public function triggerStage(int $projectId, int $stageId, int $actorId): void
	{
		if ($projectId < 1 || $stageId < 1) {
			return;
		}

		$this->triggerContexts($projectId, [$stageId], $actorId);
	}

	/** @param list<?int> $stageIds */
	private function triggerContexts(int $projectId, array $stageIds, int $actorId): void
	{
		$reader = new StandingsReader($this->database);
		$recalculator = new StandingsRecalculator($this->database, $reader);

		foreach ($stageIds as $targetStageId) {
			try {
				$context = $reader->describe($projectId, $targetStageId);
			} catch (\Throwable $exception) {
				continue;
			}

			foreach ($context['available_scopes'] as $scope) {
				try {
					$recalculator->recalculate($projectId, $targetStageId, $scope, $actorId);
				} catch (\Throwable $exception) {
					Log::add($exception->getMessage(), Log::ERROR, 'com_joomleague.standings');
				}
			}
		}
	}
}
