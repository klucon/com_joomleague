<?php
/**
 * @package     JoomLeague
 * @copyright   Copyright (C) 2026 Ondřej Klučka (https://klucon.cz). All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */


declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Site\Service;

\defined('_JEXEC') or die;

final class IcalFeedHelper
{
	public static function render(array $matches, string $calendarName, string $host): string
	{
		$lines = [
			'BEGIN:VCALENDAR',
			'VERSION:2.0',
			'PRODID:-//JoomLeague//Joomla 6//EN',
			'CALSCALE:GREGORIAN',
			'METHOD:PUBLISH',
			'X-WR-CALNAME:' . self::escape($calendarName),
		];

		foreach ($matches as $match) {
			if (empty($match->match_date)) {
				continue;
			}

			$start = strtotime((string) $match->match_date);

			if ($start === false) {
				continue;
			}

			$end = $start + 7200;
			$summary = trim((string) ($match->home_name ?? '') . ' - ' . (string) ($match->away_name ?? ''));
			$description = trim((string) ($match->project_name ?? '') . ' ' . (string) ($match->round_name ?? ''));
			$uid = 'joomleague-match-' . (int) $match->id . '@' . ($host !== '' ? $host : 'localhost');

			$lines[] = 'BEGIN:VEVENT';
			$lines[] = 'UID:' . self::escape($uid);
			$lines[] = 'DTSTAMP:' . gmdate('Ymd\THis\Z');
			$lines[] = 'DTSTART:' . gmdate('Ymd\THis\Z', $start);
			$lines[] = 'DTEND:' . gmdate('Ymd\THis\Z', $end);
			$lines[] = 'SUMMARY:' . self::escape($summary !== '' ? $summary : (string) ($match->project_name ?? 'JoomLeague'));
			$lines[] = 'DESCRIPTION:' . self::escape($description);
			$lines[] = 'LOCATION:' . self::escape((string) ($match->playground_name ?? ''));
			$lines[] = 'END:VEVENT';
		}

		$lines[] = 'END:VCALENDAR';

		return implode("\r\n", $lines) . "\r\n";
	}

	private static function escape(string $value): string
	{
		$value = str_replace('\\', '\\\\', $value);
		$value = str_replace(["\r\n", "\r", "\n"], '\\n', $value);
		$value = str_replace([',', ';'], ['\,', '\;'], $value);

		return $value;
	}

}
