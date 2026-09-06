<?php

declare(strict_types=1);

$root = dirname(__DIR__, 2);
$site = $root . '/components/com_joomleague';
$services = [
	'SeoMetadata.php' => ['canonical', 'og:title', 'og:description', 'og:image', 'twitter:card'],
	'EntitySchemaBuilder.php' => ['SportsOrganization', 'SportsTeam', 'Person', 'Place'],
	'ProjectSchemaBuilder.php' => ['EventSeries'],
	'EventSchemaBuilder.php' => ['Event', 'EventScheduled', 'EventCompleted'],
];

foreach ($services as $file => $needles) {
	$source = (string) file_get_contents($site . '/src/Service/' . $file);
	foreach ($needles as $needle) {
		if (!str_contains($source, $needle)) throw new RuntimeException(sprintf('%s is missing SEO contract %s.', $file, $needle));
	}
	if (preg_match('/\b(?:football|soccer|basketball|hockey)\b/i', $source)) {
		throw new RuntimeException(sprintf('%s contains a sport-specific assumption.', $file));
	}
}

foreach (['project', 'club', 'team', 'person', 'venue', 'eventreport'] as $view) {
	$source = (string) file_get_contents($site . '/src/View/' . ucfirst($view) . '/HtmlView.php');
	foreach (['SeoMetadata', "Route::_('index.php?option=com_joomleague&view="] as $needle) {
		if (!str_contains($source, $needle)) throw new RuntimeException(sprintf('%s view is missing %s.', $view, $needle));
	}
}

printf("SEO contract OK: canonical/Open Graph metadata and universal structured data on 6 public details\n");
