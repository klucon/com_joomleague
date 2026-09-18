<?php

declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Domain\Service;

defined('_JEXEC') or die;

use Joomla\CMS\Log\Log;
use Joomla\Database\DatabaseInterface;

/**
 * Repairs missing or explicitly invalidated published standings scopes.
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

		$staleScopes = [];
		$freshness = new StandingsFreshnessState($this->database);

		foreach ($context['available_scopes'] as $scope) {
			try {
				if (!$freshness->isFresh($projectId, $stageId, (string) $scope)) {
					$staleScopes[] = (string) $scope;
				}
			} catch (\Throwable $exception) {
				Log::add($exception->getMessage(), Log::ERROR, 'com_joomleague.standings');
			}
		}

		if ($staleScopes === []) {
			return;
		}

		$recalculator = new StandingsRecalculator($this->database, $reader);

		foreach ($staleScopes as $scope) {
			try {
				$recalculator->recalculate($projectId, $stageId, $scope, $actorId);
			} catch (\Throwable $exception) {
				// Another request may have completed the same scope first.
				// Keep the public page available and let its final read decide whether
				// a published snapshot now exists.
				Log::add($exception->getMessage(), Log::ERROR, 'com_joomleague.standings');
			}
		}
	}
}
