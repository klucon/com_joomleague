<?php

declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Domain\Service;

defined('_JEXEC') or die;

/** Determines whether a source-stage result belongs in target-stage standings. */
final class CarryOverMatchPolicy
{
	/** @param list<int> $participantEntryIds @param array<int,bool> $qualifiedEntryIds */
	public static function includes(string $mode, array $participantEntryIds, array $qualifiedEntryIds): bool
	{
		if ($participantEntryIds === []) {
			return false;
		}

		$qualifiedCount = count(array_filter(
			$participantEntryIds,
			static fn (int $entryId): bool => isset($qualifiedEntryIds[$entryId])
		));

		return match ($mode) {
			'all_results' => $qualifiedCount > 0,
			'mutual_results' => $qualifiedCount === count($participantEntryIds),
			'none' => false,
			default => throw new \InvalidArgumentException('Stage carry-over mode is invalid.'),
		};
	}
}
