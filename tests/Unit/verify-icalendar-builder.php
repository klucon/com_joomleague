<?php

declare(strict_types=1);

define('_JEXEC', 1);
require_once dirname(__DIR__, 2) . '/administrator/components/com_joomleague/src/Service/IcalendarBuilder.php';

use Joomleague\Component\Joomleague\Domain\Service\IcalendarBuilder;

$calendar = (new IcalendarBuilder())->build('Demo, calendar', [[
	'id' => 42,
	'uuid' => 'f6e7b61f-15bf-4af0-80f6-c43d61f93ce2',
	'created' => '2026-08-01 10:00:00',
	'modified' => '2026-08-15 11:30:45',
	'scheduled_start' => '2026-09-01 12:00:00',
	'duration_minutes' => 90,
	'project_name' => 'Universal project',
	'round_name' => "Round 1\r\nX-INJECTED: blocked",
	'venue_name' => 'Main; venue',
	'played' => false,
	'participants' => [
		['name' => 'Participant A'],
		['name' => 'Participant B'],
		['name' => 'Participant C'],
	],
]], 'https://example.test/index.php?option=com_joomleague&view=eventreport&event_id');
$unfolded = str_replace("\r\n ", '', $calendar);

foreach ([
	"BEGIN:VCALENDAR\r\n",
	"UID:joomleague-event-f6e7b61f-15bf-4af0-80f6-c43d61f93ce2@joomleague.eu\r\n",
	"DTSTAMP:20260801T100000Z\r\n",
	"LAST-MODIFIED:20260815T113045Z\r\n",
	"SEQUENCE:1786793445\r\n",
	"DTSTART:20260901T120000Z\r\n",
	"DTEND:20260901T133000Z\r\n",
	'SUMMARY:Participant A - Participant B - Participant C',
	'LOCATION:Main\\; venue',
	'Round 1\\nX-INJECTED: blocked',
	'URL:https://example.test/index.php?option=com_joomleague&view=eventreport&event_id=42',
	"END:VCALENDAR\r\n",
] as $expected) {
	if (!str_contains($unfolded, $expected)) {
		throw new RuntimeException('iCalendar output is missing: ' . $expected);
	}
}

$secondCalendar = (new IcalendarBuilder())->build('Demo, calendar', [[
	'id' => 42,
	'uuid' => 'f6e7b61f-15bf-4af0-80f6-c43d61f93ce2',
	'created' => '2026-08-01 10:00:00',
	'modified' => '2026-08-15 11:30:45',
	'scheduled_start' => '2026-09-01 12:00:00',
	'played' => false,
]], 'https://example.test/event_id');

foreach (['DTSTAMP:20260801T100000Z', 'LAST-MODIFIED:20260815T113045Z', 'SEQUENCE:1786793445'] as $stableMetadata) {
	if (!str_contains($secondCalendar, $stableMetadata)) {
		throw new RuntimeException('iCalendar modification metadata is not stable across feed generation.');
	}
}

if (str_contains($calendar, "\r\nX-INJECTED:")) {
	throw new RuntimeException('iCalendar text allowed property injection.');
}

try {
	(new IcalendarBuilder())->build('Invalid identity', [[
		'id' => 42,
		'uuid' => 'not-a-uuid',
		'scheduled_start' => '2026-09-01 12:00:00',
	]], 'https://example.test/event_id');
	throw new RuntimeException('Invalid calendar UUID was accepted.');
} catch (InvalidArgumentException) {
}

foreach (explode("\r\n", $calendar) as $line) {
	if (strlen($line) > 75) {
		throw new RuntimeException('iCalendar line exceeds the RFC 5545 limit.');
	}
}

echo "iCalendar builder OK\n";
