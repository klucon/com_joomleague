<?php

declare(strict_types=1);

define('_JEXEC', 1);

require_once dirname(__DIR__, 2) . '/components/com_joomleague/src/Service/RankingColumnFilter.php';

use Joomleague\Component\Joomleague\Site\Service\RankingColumnFilter;

$columns = [
	['type' => 'single', 'code' => 'played'],
	['type' => 'combined', 'for' => 'score_for', 'against' => 'score_against', 'prefix' => 'score'],
	['type' => 'single', 'code' => 'score_difference'],
	['type' => 'combined', 'for' => 'sets_for', 'against' => 'sets_against', 'prefix' => 'sets'],
	['type' => 'single', 'code' => 'points'],
];

$filter = new RankingColumnFilter();
$visible = $filter->apply($columns, [
	'show_score' => false,
	'show_goal_difference' => false,
	'show_sets' => false,
	'show_points' => false,
]);
if (count($visible) !== 1 || ($visible[0]['code'] ?? null) !== 'played') {
	throw new RuntimeException('Ranking presentation did not hide all disabled metric families.');
}

$setOnly = $filter->apply($columns, [
	'show_score' => false,
	'show_goal_difference' => true,
	'show_sets' => true,
	'show_points' => false,
]);
$codes = array_map(static fn (array $column): string => (string) ($column['code'] ?? $column['prefix'] ?? ''), $setOnly);
if ($codes !== ['played', 'sets']) {
	throw new RuntimeException('Ranking presentation does not preserve an enabled set metric family.');
}

printf("Ranking column filter OK: inherited and presentation-overridden metric families validated\n");
