<?php

declare(strict_types=1);

namespace Joomleague\Module\Calendar\Site\Helper;

defined('_JEXEC') or die;

final class CalendarOptions
{
	public static function month(string $value, \DateTimeImmutable $fallback): \DateTimeImmutable
	{
		if (preg_match('/^(19[7-9][0-9]|20[0-9]{2}|2100)-(0[1-9]|1[0-2])$/D', $value) !== 1) {
			$value = $fallback->format('Y-m');
		}
		return new \DateTimeImmutable($value . '-01 00:00:00', $fallback->getTimezone());
	}

	public static function monthWindow(\DateTimeImmutable $month): array
	{
		return [$month->getTimestamp(), $month->modify('+1 month')->modify('-1 second')->getTimestamp()];
	}

	/** @return list<list<array{date:string,day:int,current:bool}>> */
	public static function monthGrid(\DateTimeImmutable $month, int $firstWeekday): array
	{
		$firstWeekday = $firstWeekday === 0 ? 0 : 1;
		$padding = ((int) $month->format('w') - $firstWeekday + 7) % 7;
		$cursor = $month->modify('-' . $padding . ' days');
		$size = (int) (ceil(($padding + (int) $month->format('t')) / 7) * 7);
		$days = [];
		for ($i = 0; $i < $size; $i++) {
			$days[] = ['date' => $cursor->format('Y-m-d'), 'day' => (int) $cursor->format('j'), 'current' => $cursor->format('Y-m') === $month->format('Y-m')];
			$cursor = $cursor->modify('+1 day');
		}
		return array_chunk($days, 7);
	}
	public static function timezone(string $value, string $siteTimezone): \DateTimeZone
	{
		try {
			return new \DateTimeZone($value === '' ? $siteTimezone : $value);
		} catch (\Exception) {
			return new \DateTimeZone('UTC');
		}
	}

	/** Local calendar days are converted to UTC instants, including DST boundaries. */
	public static function window(\DateTimeImmutable $now, int $before, int $after): array
	{
		return [
			$now->modify('-' . min(365, max(0, $before)) . ' days')->setTime(0, 0)->getTimestamp(),
			$now->modify('+' . min(5000, max(1, $after)) . ' days')->setTime(23, 59, 59)->getTimestamp(),
		];
	}

	public static function select(array $items, string $mode, string $direction, int $offset, int $limit, int $now = 0): array
	{
		$items = array_values(array_filter($items, static function (array $item) use ($mode, $now): bool {
			$final = (bool) ($item['played'] ?? false);
			return match ($mode) {
				'completed' => $final,
				'upcoming' => !$final && $item['timestamp'] >= $now && !in_array($item['status_code'] ?? '', ['cancelled', 'completed'], true),
				default => true,
			};
		}));
		usort($items, static fn(array $a, array $b): int => (($a['timestamp'] <=> $b['timestamp']) ?: ($a['id'] <=> $b['id'])) * ($direction === 'desc' ? -1 : 1));
		return array_slice($items, min(10000, max(0, $offset)), min(200, max(1, $limit)));
	}
}
