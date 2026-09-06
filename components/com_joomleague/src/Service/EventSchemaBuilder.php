<?php

declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Site\Service;

defined('_JEXEC') or die;

/** Builds a sport-neutral Schema.org Event document from public report data. */
final class EventSchemaBuilder
{
	/** @param array<string,mixed> $report @return array<string,mixed> */
	public function build(array $report, string $url): array
	{
		if (!isset($report['item'], $report['participants']) || !is_object($report['item']) || !is_array($report['participants'])) {
			return [];
		}

		$item = $report['item'];
		$participants = $report['participants'];
		$names = array_values(array_filter(array_map(static fn (object $participant): string => trim((string) ($participant->name ?? '')), $participants)));
		$name = count($names) >= 2 && count($names) <= 4
			? implode(' – ', $names)
			: trim((string) ($item->round_name ?? $item->project_name ?? ''));
		if ($name === '') {
			return [];
		}

		$schema = [
			'@context' => 'https://schema.org',
			'@type' => 'Event',
			'name' => $name,
			'url' => $url,
		];
		if (!empty($item->description)) {
			$schema['description'] = trim(strip_tags((string) $item->description));
		}

		$start = $this->date((string) ($item->scheduled_start ?? ''), (string) ($item->timezone ?? 'UTC'));
		if ($start !== null) {
			$schema['startDate'] = $start->format(DATE_ATOM);
			if ((int) ($item->duration_minutes ?? 0) > 0) {
				$schema['endDate'] = $start->modify('+' . (int) $item->duration_minutes . ' minutes')->format(DATE_ATOM);
			}
		}

		$status = match ((string) ($item->status_code ?? '')) {
			'scheduled', 'in_progress' => 'https://schema.org/EventScheduled',
			'postponed' => 'https://schema.org/EventPostponed',
			'cancelled' => 'https://schema.org/EventCancelled',
			'finished', 'completed' => 'https://schema.org/EventCompleted',
			default => null,
		};
		if ($status !== null) {
			$schema['eventStatus'] = $status;
		}

		if (!empty($item->venue_name)) {
			$schema['location'] = ['@type' => 'Place', 'name' => (string) $item->venue_name];
			$schema['eventAttendanceMode'] = 'https://schema.org/OfflineEventAttendanceMode';
		}
		if (!empty($item->competition_name)) {
			$schema['organizer'] = ['@type' => 'Organization', 'name' => (string) $item->competition_name];
		}

		$performers = [];
		foreach ($participants as $participant) {
			$participantName = trim((string) ($participant->name ?? ''));
			if ($participantName === '') {
				continue;
			}
			$performers[] = [
				'@type' => !empty($participant->person_id) ? 'Person' : 'Organization',
				'name' => $participantName,
			];
		}
		if ($performers !== []) {
			$schema['performer'] = $performers;
		}

		return $schema;
	}

	private function date(string $value, string $timezone): ?\DateTimeImmutable
	{
		if ($value === '') {
			return null;
		}
		try {
			$zone = new \DateTimeZone($timezone !== '' ? $timezone : 'UTC');
			return (new \DateTimeImmutable($value, new \DateTimeZone('UTC')))->setTimezone($zone);
		} catch (\Throwable) {
			return null;
		}
	}
}
