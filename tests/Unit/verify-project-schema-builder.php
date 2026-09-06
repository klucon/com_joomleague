<?php

declare(strict_types=1);

define('_JEXEC', 1);
require_once __DIR__ . '/../../components/com_joomleague/src/Service/ProjectSchemaBuilder.php';

use Joomleague\Component\Joomleague\Site\Service\ProjectSchemaBuilder;

$project = (object) ['name' => 'Demo league', 'description' => '<p>Season overview</p>', 'competition_name' => 'Demo competition', 'start_date' => '2026-08-01', 'end_date' => '2027-05-31', 'picture' => 'images/project.jpg'];
$schema = (new ProjectSchemaBuilder())->build(['project' => $project], 'https://example.test/competition');
if (($schema['@type'] ?? '') !== 'EventSeries' || ($schema['description'] ?? '') !== 'Season overview' || ($schema['organizer']['name'] ?? '') !== 'Demo competition' || ($schema['image'] ?? '') !== 'https://example.test/images/project.jpg') {
	throw new RuntimeException('Project schema metadata is invalid.');
}
if ((new ProjectSchemaBuilder())->build([], 'https://example.test') !== []) {
	throw new RuntimeException('Invalid project data must not produce schema metadata.');
}
echo "Project EventSeries schema builder OK\n";
