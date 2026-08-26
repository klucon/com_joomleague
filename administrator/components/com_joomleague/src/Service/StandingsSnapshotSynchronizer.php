<?php

declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Domain\Service;

defined('_JEXEC') or die;

use Joomla\CMS\Log\Log;
use Joomla\Database\DatabaseInterface;

/**
 * Completes missing published standings scopes without recalculating snapshots
 * that are already available. This also repairs data loaded outside Joomla's
 * normal result-save workflow, for example by a legacy SQL migration.
 */
final class StandingsSnapshotSynchronizer
{
	public function __construct(private readonly DatabaseInterface $database)
	{
	}

	/** @param array<string,mixed>|null $context */
	public function synchronize(int $projectId, ?int $stageId, int $actorId = 0, ?array $context = null): void
	{
		if ($projectId < 1 || $actorId < 0) {
			return;
		}

		$reader = new StandingsReader($this->database);

		try {
			$context ??= $reader->describe($projectId, $stageId);
		} catch (\Throwable $exception) {
			Log::add($exception->getMessage(), Log::ERROR, 'com_joomleague.standings');

			return;
		}

		$missingScopes = [];

		foreach ($context['available_scopes'] as $scope) {
			try {
				if ($reader->current($projectId, $stageId, (string) $scope)['snapshot'] === null) {
					$missingScopes[] = (string) $scope;
				}
			} catch (\Throwable $exception) {
				Log::add($exception->getMessage(), Log::ERROR, 'com_joomleague.standings');
			}
		}

		if ($missingScopes === []) {
			return;
		}

		$recalculator = new StandingsRecalculator($this->database, $reader);

		foreach ($missingScopes as $scope) {
			try {
				$recalculator->recalculate($projectId, $stageId, $scope, $actorId);
			} catch (\Throwable $exception) {
				// Another request may have completed the same missing scope first.
				// Keep the public page available and let its final read decide whether
				// a published snapshot now exists.
				Log::add($exception->getMessage(), Log::ERROR, 'com_joomleague.standings');
			}
		}
	}
}
