<?php

declare(strict_types=1);

define('_JEXEC', 1);
require_once __DIR__ . '/../../components/com_joomleague/src/Service/EventSchemaBuilder.php';

use Joomleague\Component\Joomleague\Site\Service\EventSchemaBuilder;

$report = [
	'item' => (object) [
		'id' => 7,
		'project_name' => 'Universal competition',
		'round_name' => 'Round 3',
		'scheduled_start' => '2026-09-05 15:00:00',
		'timezone' => 'Europe/Prague',
		'duration_minutes' => 90,
		'status_code' => 'scheduled',
		'venue_name' => 'Universal venue',
		'competition_name' => 'Universal competition',
		'description' => '<p>Public description</p>',
	],
	'participants' => [
		(object) ['name' => 'Demo Team', 'team_id' => 4, 'person_id' => null],
		(object) ['name' => 'Alex Example', 'team_id' => null, 'person_id' => 8],
	],
];

$schema = (new EventSchemaBuilder())->build($report, 'https://example.test/event/7');
$assert = static function (bool $condition, string $message): void {
	if (!$condition) {
		throw new RuntimeException($message);
	}
};

$assert(($schema['@type'] ?? null) === 'Event', 'Schema type is not universal Event.');
$assert(($schema['name'] ?? null) === 'Demo Team – Alex Example', 'Participant event name is invalid.');
$assert(($schema['startDate'] ?? null) === '2026-09-05T17:00:00+02:00', 'Timezone-aware start date is invalid.');
$assert(($schema['endDate'] ?? null) === '2026-09-05T18:30:00+02:00', 'Duration-derived end date is invalid.');
$assert(($schema['eventStatus'] ?? null) === 'https://schema.org/EventScheduled', 'Event status is invalid.');
$assert(($schema['location']['@type'] ?? null) === 'Place', 'Venue schema is invalid.');
$assert(($schema['performer'][0]['@type'] ?? null) === 'Organization', 'Team performer type is invalid.');
$assert(($schema['performer'][1]['@type'] ?? null) === 'Person', 'Person performer type is invalid.');
$assert(($schema['description'] ?? null) === 'Public description', 'Description was not sanitized.');
$assert((new EventSchemaBuilder())->build([], 'https://example.test') === [], 'Invalid report must not generate schema.');

echo "Universal event Schema.org builder OK\n";
