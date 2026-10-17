<?php

declare(strict_types=1);

$root = dirname(__DIR__, 2);
$contracts = [
	'components/com_joomleague/tmpl/clubpeople/default.xml' => ['COM_JOOMLEAGUE_CLUBPEOPLE_ENABLE_SORTING_LABEL', 'enable_column_sorting'],
	'components/com_joomleague/tmpl/clubrivals/default.xml' => ['COM_JOOMLEAGUE_CLUBRIVALS_ENABLE_SORTING_LABEL', 'enable_column_sorting'],
];
foreach ($contracts as $relative => $needles) {
	$xml = file_get_contents($root . '/' . $relative);
	if ($xml === false) throw new RuntimeException('Unable to read ' . $relative);
	foreach ($needles as $needle) if (!str_contains($xml, $needle)) throw new RuntimeException($relative . ' is missing ' . $needle);
}
foreach (['ClubpeopleModel.php', 'ClubrivalsModel.php'] as $model) {
	$source = file_get_contents($root . '/components/com_joomleague/src/Model/' . $model);
	if ($source === false || !str_contains($source, "get('enable_column_sorting', 1)") || !str_contains($source, "'list.direction'")) throw new RuntimeException($model . ' does not enforce the sorting setting and direction.');
}
echo "Club people and rival optional sorting contract OK\n";
