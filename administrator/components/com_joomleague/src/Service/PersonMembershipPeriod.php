<?php
declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Domain\Service;

defined('_JEXEC') or die;

/** Read-only classification; never infer dates from competition or season names. */
final class PersonMembershipPeriod
{
	public static function classify(object $membership, string $today): string
	{
		if (self::date($today) === null) throw new \InvalidArgumentException('Invalid reference date');
		if ((string) ($membership->lifecycle_state ?? '') === 'departed'
			|| in_array((string) ($membership->project_lifecycle_state ?? ''), ['completed', 'archived'], true)) return 'history';

		$memberStart = self::date($membership->valid_from ?? null);
		$memberEnd = self::date($membership->valid_until ?? null);
		$projectStart = self::date($membership->project_start_date ?? null) ?? self::date($membership->season_start_date ?? null);
		$projectEnd = self::date($membership->project_end_date ?? null) ?? self::date($membership->season_end_date ?? null);
		$starts = array_values(array_filter([$memberStart, $projectStart], static fn ($value): bool => $value !== null));
		$ends = array_values(array_filter([$memberEnd, $projectEnd], static fn ($value): bool => $value !== null));
		$start = $starts === [] ? null : max($starts);
		$end = $ends === [] ? null : min($ends);
		if ($start !== null && $end !== null && $start > $end) return 'undated';
		if ($end !== null && $end < $today) return 'history';
		if ($start !== null && $start > $today) return 'upcoming';

		// A legacy default "active" plus an open draft project proves no current term.
		if ($memberStart !== null || $end !== null
			|| (string) ($membership->project_lifecycle_state ?? '') === 'active') return 'current';
		return 'undated';
	}

	private static function date(mixed $value): ?string
	{
		if (!is_string($value) || !preg_match('/^\d{4}-\d{2}-\d{2}$/D', $value)) return null;
		$date = \DateTimeImmutable::createFromFormat('!Y-m-d', $value);
		return $date !== false && $date->format('Y-m-d') === $value ? $value : null;
	}
}
