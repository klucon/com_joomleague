<?php

declare(strict_types=1);

define('_JEXEC', 1);
require_once dirname(__DIR__, 2) . '/administrator/components/com_joomleague/src/Service/IcalendarBuilder.php';

use Joomleague\Component\Joomleague\Domain\Service\IcalendarBuilder;

$calendar = (new IcalendarBuilder())->build('Demo, calendar', [[
	'id' => 42,
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
	"UID:joomleague-event-42@joomleague.eu\r\n",
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

if (str_contains($calendar, "\r\nX-INJECTED:")) {
	throw new RuntimeException('iCalendar text allowed property injection.');
}

foreach (explode("\r\n", $calendar) as $line) {
	if (strlen($line) > 75) {
		throw new RuntimeException('iCalendar line exceeds the RFC 5545 limit.');
	}
}

echo "iCalendar builder OK\n";
