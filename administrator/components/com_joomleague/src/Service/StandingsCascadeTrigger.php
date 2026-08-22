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

		$reader = new StandingsReader($this->database);
		$recalculator = new StandingsRecalculator($this->database, $reader);

		// Refresh both the project-wide table (stage_id null) and, when the
		// match belongs to a stage, that stage's own table — different sport
		// profiles publish standings at either level.
		$stageIds = $stageId === null ? [null] : [null, $stageId];

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
