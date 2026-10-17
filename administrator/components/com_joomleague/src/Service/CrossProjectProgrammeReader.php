<?php

declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Domain\Service;

defined('_JEXEC') or die;

use Joomla\Database\DatabaseInterface;

/** Aggregates the public sport-neutral programme across published projects. */
final class CrossProjectProgrammeReader
{
	public function __construct(private readonly DatabaseInterface $database) {}

	/**
	 * @param list<int> $viewLevels
	 * @return list<array<string,mixed>>
	 */
	public function between(array $viewLevels, int $fromUnix, int $toUnix, int $sportTypeId = 0, int $clubId = 0, int $projectId = 0, int $seasonId = 0, int $stageId = 0, int $roundId = 0, int $entryId = 0, int $venueId = 0): array
	{
		$viewLevels = array_values(array_unique(array_filter(array_map('intval', $viewLevels), static fn(int $id): bool => $id > 0)));
		$viewLevels = $viewLevels === [] ? [1] : $viewLevels;
		if ($fromUnix > $toUnix) {
			throw new \InvalidArgumentException('Programme date range is invalid.');
		}

		$db = $this->database;
		$query = $db->getQuery(true)
			->select('project.id')
			->from($db->quoteName('#__joomleague_project', 'project'))
			->where('project.published = 1')
			->whereIn('project.access', $viewLevels)
			->order('project.id ASC');
		if ($sportTypeId > 0) {
			$query->where('project.sport_type_id = ' . $sportTypeId);
		}
		if ($projectId > 0) {
			$query->where('project.id = ' . $projectId);
		}
		if ($seasonId > 0) {
			$query->where('project.season_id = ' . $seasonId);
		}

		$reader = new ProgrammeReader($db);
		$scopeResolver = new ProgrammeScopeResolver($db);
		$items = [];
		foreach (array_map('intval', $db->setQuery($query)->loadColumn()) as $projectId) {
			try {
				$entryIds = null;
				if ($clubId > 0) {
					$entryIds = $scopeResolver->resolve($projectId, 'club', $clubId, $viewLevels);
					if ($entryIds === []) {
						continue;
					}
				}
				if ($entryId > 0) {
					$selectedEntryIds = $scopeResolver->resolve($projectId, 'entry', $entryId, $viewLevels);
					if ($selectedEntryIds === []) continue;
					$entryIds = $entryIds === null ? $selectedEntryIds : array_values(array_intersect($entryIds, $selectedEntryIds));
					if ($entryIds === []) continue;
				}

				foreach ($reader->forProject($projectId, $entryIds, $viewLevels, $venueId > 0 ? $venueId : null, $stageId > 0 ? $stageId : null, $roundId > 0 ? $roundId : null) as $item) {
					if ($item['scheduled_start'] === null) {
						continue;
					}
					$timestamp = (new \DateTimeImmutable((string) $item['scheduled_start'], new \DateTimeZone('UTC')))->getTimestamp();
					if ($timestamp < $fromUnix || $timestamp > $toUnix) {
						continue;
					}
					$item['timestamp'] = $timestamp;
					$items[] = $item;
				}
			} catch (\Throwable) {
				continue;
			}
		}

		usort($items, static fn(array $left, array $right): int => $left['timestamp'] <=> $right['timestamp'] ?: $left['id'] <=> $right['id']);

		return $items;
	}
}
