<?php

declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Domain\Service;

defined('_JEXEC') or die;

/** Builds RFC 5545 calendar output from the public programme projection. */
final class IcalendarBuilder
{
	/** @param list<array<string,mixed>> $events */
	public function build(string $calendarName, array $events, string $eventUrlPrefix): string
	{
		$lines = [
			'BEGIN:VCALENDAR',
			'PRODID:-//JoomLeague//Programme//EN',
			'VERSION:2.0',
			'CALSCALE:GREGORIAN',
			'METHOD:PUBLISH',
			'X-WR-CALNAME:' . $this->escape($calendarName),
		];
		$stamp = gmdate('Ymd\\THis\\Z');

		foreach ($events as $event) {
			if (empty($event['scheduled_start'])) {
				continue;
			}

			$start = new \DateTimeImmutable((string) $event['scheduled_start'], new \DateTimeZone('UTC'));
			$duration = max(1, (int) ($event['duration_minutes'] ?? 60));
			$end = $start->modify('+' . $duration . ' minutes');
			$participantNames = array_map(static fn (array $participant): string => (string) $participant['name'], $event['participants'] ?? []);
			$summary = $participantNames === [] ? $calendarName : implode(' - ', $participantNames);
			$description = implode(' | ', array_filter([(string) ($event['project_name'] ?? ''), (string) ($event['round_name'] ?? '')]));

			$lines[] = 'BEGIN:VEVENT';
			$lines[] = 'UID:joomleague-event-' . (int) $event['id'] . '@joomleague.eu';
			$lines[] = 'DTSTAMP:' . $stamp;
			$lines[] = 'DTSTART:' . $start->format('Ymd\\THis\\Z');
			$lines[] = 'DTEND:' . $end->format('Ymd\\THis\\Z');
			$lines[] = 'SUMMARY:' . $this->escape($summary);
			if ($description !== '') {
				$lines[] = 'DESCRIPTION:' . $this->escape($description);
			}
			if (!empty($event['venue_name'])) {
				$lines[] = 'LOCATION:' . $this->escape((string) $event['venue_name']);
			}
			$lines[] = 'URL:' . $this->escape(rtrim($eventUrlPrefix, '=') . '=' . (int) $event['id']);
			$lines[] = 'STATUS:' . ($event['played'] ? 'CONFIRMED' : 'TENTATIVE');
			$lines[] = 'END:VEVENT';
		}

		$lines[] = 'END:VCALENDAR';

		return implode("\r\n", array_map($this->fold(...), $lines)) . "\r\n";
	}

	private function escape(string $value): string
	{
		return str_replace(["\\", ";", ",", "\r\n", "\r", "\n"], ["\\\\", "\\;", "\\,", "\\n", "\\n", "\\n"], $value);
	}

	private function fold(string $line): string
	{
		$folded = [];
		$first = true;
		while (strlen($line) > ($first ? 75 : 74)) {
			$length = $first ? 75 : 74;
			$chunk = substr($line, 0, $length);
			while ($chunk !== '' && preg_match('//u', $chunk) !== 1) {
				$chunk = substr($chunk, 0, -1);
			}
			if ($chunk === '') {
				$chunk = substr($line, 0, $length);
			}
			$folded[] = ($first ? '' : ' ') . $chunk;
			$line = (string) substr($line, strlen($chunk));
			$first = false;
		}
		$folded[] = ($first ? '' : ' ') . $line;

		return implode("\r\n", $folded);
	}
}
