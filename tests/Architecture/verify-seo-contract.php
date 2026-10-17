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

foreach (glob($site . '/src/View/*/HtmlView.php') ?: [] as $file) {
	$view = strtolower(basename(dirname($file)));
	$source = (string) file_get_contents($file);
	if ($view === 'programitem' && str_contains($source, 'Eventreport\\HtmlView')) continue;
	if (!str_contains($source, 'SeoMetadata')) {
		throw new RuntimeException(sprintf('%s view is missing SeoMetadata.', $view));
	}
}

$guardedViews = [
	'bracket', 'club', 'clubpeople', 'clubplan', 'clubrivals', 'clubstaffhistory',
	'comparison', 'eventranking', 'eventreport', 'nextmatch', 'participant', 'participants',
	'participantstats', 'person', 'personnel', 'project', 'resultmatrix', 'results',
	'standingprogression', 'standings', 'statisticsoverview', 'statranking', 'team',
	'teamplan', 'venue',
];
foreach ($guardedViews as $view) {
	$source = (string) file_get_contents($site . '/src/View/' . ucfirst($view) . '/HtmlView.php');
	if (!str_contains($source, 'PublicViewGuard')) {
		throw new RuntimeException(sprintf('%s view is missing the public entity guard.', $view));
	}
}

printf("SEO contract OK: metadata on every public view and 404 guards on entity views\n");
