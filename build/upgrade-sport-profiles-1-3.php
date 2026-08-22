<?php

declare(strict_types=1);

$directory = dirname(__DIR__) . '/administrator/components/com_joomleague/resources/sport-profiles';
$regular = static fn (string|int $win, string|int $draw, string|int $loss): array => [rule(always(), ['winner' => $win, 'draw' => $draw, 'loser' => $loss])];
$none = ['mode' => 'none', 'rule_strategy' => 'first_match', 'rules' => [], 'bonus_rules' => []];
$definitions = [
	'basketball' => head($regular(2, 0, 1), [], ['wins', 'points', 'score_difference', 'score_for']),
	'bowling' => head([], [], ['score_difference', 'score_for', 'wins'], $none),
	'chess' => head($regular(1, '0.5', 0), [], ['points', 'score_for', 'wins']),
	'darts' => head([], segmentMetrics('set', 'sets', true, 'leg', 'legs'), ['wins', 'sets_difference', 'legs_difference'], $none),
	'esports' => head([], segmentMetrics('map', 'maps', true, 'round', 'rounds'), ['maps_difference', 'rounds_difference', 'wins'], $none),
	'floorball' => head([
		rule(presentSegment('shootout'), ['winner' => 2, 'draw' => 0, 'loser' => 1]),
		rule(presentSegment('extra_time'), ['winner' => 2, 'draw' => 0, 'loser' => 1]),
		...$regular(3, 1, 0),
	], [], ['points', 'score_difference', 'score_for', 'wins']),
	'football' => head($regular(3, 1, 0), [], ['points', 'score_difference', 'score_for', 'wins']),
	'futsal' => head($regular(3, 1, 0), [], ['points', 'score_difference', 'score_for', 'wins']),
	'ice_hockey' => head([
		rule(presentSegment('shootout'), ['winner' => 2, 'draw' => 0, 'loser' => 1]),
		rule(presentSegment('extra_time'), ['winner' => 2, 'draw' => 0, 'loser' => 1]),
		...$regular(3, 0, 0),
	], [
		metric('wins_regular', 'count_outcome', ['outcome' => 'win', 'when' => notAnySegment(['extra_time', 'shootout'])]),
		metric('wins_overtime', 'count_outcome', ['outcome' => 'win', 'when' => decisionOnly('extra_time', ['shootout'])]),
		metric('wins_shootout', 'count_outcome', ['outcome' => 'win', 'when' => presentSegment('shootout')]),
	], ['points', 'wins_regular', 'wins_overtime', 'wins_shootout', 'score_difference', 'score_for']),
	'mma_boxing' => head([], [], ['wins'], $none, 'participant_status'),
	'motorsport' => classification(['finished', 'dnf', 'dns', 'dsq']),
	'rugby' => head($regular(4, 2, 0), [], ['points', 'wins', 'score_difference', 'score_for'], [
		'mode' => 'outcome_rules', 'rule_strategy' => 'first_match', 'rules' => $regular(4, 2, 0),
		'bonus_rules' => [
			['when' => ['type' => 'statistic', 'operator' => 'gte', 'code' => 'tries', 'value' => 4], 'value' => 1],
			['when' => ['type' => 'all', 'conditions' => [['type' => 'outcome', 'operator' => 'eq', 'value' => 'loss'], ['type' => 'score_difference', 'operator' => 'gte', 'value' => -7]]], 'value' => 1],
		],
	]),
	'running_race' => classification(['finished', 'dnf', 'dns', 'dsq']),
	'tennis' => head([], segmentMetrics('set', 'sets', true, 'game', 'games'), ['wins', 'sets_difference', 'games_difference'], $none),
	'volleyball' => head([
		rule(['type' => 'score_pair', 'operator' => 'eq', 'value' => '3:2'], ['winner' => 2, 'draw' => 0, 'loser' => 1]),
		...$regular(3, 0, 0),
	], segmentMetrics('set', 'sets', true, 'point', 'score_points'), ['points', 'wins', 'sets_ratio', 'score_points_ratio', 'sets_difference', 'score_points_difference']),
];

foreach (glob($directory . '/*.json') ?: [] as $file) {
	$profile = json_decode((string) file_get_contents($file), true, 512, JSON_THROW_ON_ERROR); $code = (string) ($profile['code'] ?? '');
	if (!isset($definitions[$code])) throw new RuntimeException('Missing standings 1.3 definition for ' . basename($file));
	$profile['schema_version'] = '1.3.0'; $profile['version'] = '1.4.0'; $profile['standings']['calculation'] = $definitions[$code];
	file_put_contents($file, json_encode($profile, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR) . "\n");
}

function metric(string $code, string $operation, array $extra = []): array { return array_merge(['code' => $code, 'operation' => $operation, 'value_type' => 'decimal'], $extra); }
function rule(array $when, array $values): array { return compact('when', 'values'); }
function always(): array { return ['type' => 'always']; }
function presentSegment(string $code): array { return ['type' => 'decision_segment', 'operator' => 'present', 'code' => $code]; }
function notAnySegment(array $codes): array { return ['type' => 'all', 'conditions' => array_map(static fn (string $code): array => ['type' => 'decision_segment', 'operator' => 'neq', 'value' => $code], $codes)]; }
function decisionOnly(string $code, array $excludedCodes): array { return ['type' => 'all', 'conditions' => [presentSegment($code), notAnySegment($excludedCodes)]]; }
function order(array $metrics): array { return array_map(static fn (string $metric): array => ['metric' => $metric, 'direction' => 'desc', 'nulls' => 'last'], $metrics); }

function baseMetrics(): array
{
	return [metric('played', 'count_matches'), metric('wins', 'count_outcome', ['outcome' => 'win']), metric('draws', 'count_outcome', ['outcome' => 'draw']), metric('losses', 'count_outcome', ['outcome' => 'loss']), metric('score_for', 'sum_root', ['perspective' => 'own']), metric('score_against', 'sum_root', ['perspective' => 'opponent']), metric('score_difference', 'difference', ['left_metric' => 'score_for', 'right_metric' => 'score_against']), metric('points', 'sum_awards')];
}

function head(array $rules, array $extraMetrics, array $ordering, ?array $awards = null, string $outcomeSource = 'root_numeric'): array
{
	return ['mode' => 'head_to_head', 'outcome_source' => $outcomeSource, 'included_result_statuses' => ['final'], 'metrics' => [...baseMetrics(), ...$extraMetrics], 'awards' => $awards ?? ['mode' => 'outcome_rules', 'rule_strategy' => 'first_match', 'rules' => $rules, 'bonus_rules' => []], 'ordering' => order($ordering)];
}

function segmentMetrics(string $firstCode, string $firstPrefix, bool $firstUsesRoot, ?string $secondCode = null, ?string $secondPrefix = null): array
{
	$result = $firstUsesRoot
		? [metric($firstPrefix . '_for', 'sum_root', ['perspective' => 'own']), metric($firstPrefix . '_against', 'sum_root', ['perspective' => 'opponent'])]
		: [metric($firstPrefix . '_for', 'sum_segment_wins', ['segment_code' => $firstCode, 'perspective' => 'own']), metric($firstPrefix . '_against', 'sum_segment_wins', ['segment_code' => $firstCode, 'perspective' => 'opponent'])];
	$result[] = metric($firstPrefix . '_difference', 'difference', ['left_metric' => $firstPrefix . '_for', 'right_metric' => $firstPrefix . '_against']);
	$result[] = metric($firstPrefix . '_ratio', 'ratio', ['left_metric' => $firstPrefix . '_for', 'right_metric' => $firstPrefix . '_against']);
	if ($secondCode !== null && $secondPrefix !== null) {
		$result[] = metric($secondPrefix . '_for', 'sum_segment_wins', ['segment_code' => $secondCode, 'perspective' => 'own']);
		$result[] = metric($secondPrefix . '_against', 'sum_segment_wins', ['segment_code' => $secondCode, 'perspective' => 'opponent']);
		$result[] = metric($secondPrefix . '_difference', 'difference', ['left_metric' => $secondPrefix . '_for', 'right_metric' => $secondPrefix . '_against']);
		$result[] = metric($secondPrefix . '_ratio', 'ratio', ['left_metric' => $secondPrefix . '_for', 'right_metric' => $secondPrefix . '_against']);
	}
	return $result;
}

function classification(array $statuses): array
{
	return ['mode' => 'classification', 'included_result_statuses' => ['final'], 'metrics' => [metric('races', 'count_matches'), metric('status_order', 'status_order', ['status_precedence' => $statuses]), metric('result_rank', 'best_rank'), metric('elapsed', 'sum_root'), metric('points', 'sum_awards')], 'awards' => ['mode' => 'none', 'rule_strategy' => 'first_match', 'rules' => [], 'bonus_rules' => []], 'ordering' => [['metric' => 'status_order', 'direction' => 'asc', 'nulls' => 'last'], ['metric' => 'result_rank', 'direction' => 'asc', 'nulls' => 'last'], ['metric' => 'elapsed', 'direction' => 'asc', 'nulls' => 'last']], 'classification' => ['primary' => 'result_rank', 'direction' => 'asc', 'status_precedence' => $statuses]];
}
