<?php

declare(strict_types=1);

$directory = dirname(__DIR__) . '/administrator/components/com_joomleague/resources/sport-profiles';

$definitions = [
	'basketball' => [
		'segments' => [
			segment('period', 'periods', 'integer', null, 10, true, 4),
			segment('extra_time', 'extra_time_periods', 'integer', null, 20, true, 1, null, 'tied_after_regulation'),
		],
		'aggregation' => aggregation('validate', ['period', 'extra_time']),
	],
	'bowling' => [
		'segments' => [segment('game', 'games', 'integer', null, 10, true)],
		'aggregation' => aggregation('none'),
	],
	'chess' => [
		'segments' => [segment('board', 'boards', 'decimal', null, 10, true)],
		'aggregation' => aggregation('validate', ['board']),
	],
	'darts' => [
		'segments' => [
			segment('set', 'sets', 'integer', null, 10, true),
			segment('leg', 'legs', 'integer', 'set', 10, true),
			segment('point', 'points', 'integer', 'leg', 10, true),
		],
		'aggregation' => aggregation('validate', ['set']),
	],
	'esports' => [
		'segments' => [
			segment('map', 'maps', 'integer', null, 10, true),
			segment('round', 'rounds', 'integer', 'map', 10, true),
		],
		'aggregation' => aggregation('validate', ['map']),
	],
	'floorball' => timedSegments(3),
	'football' => timedSegments(2, 2),
	'futsal' => timedSegments(2, 2),
	'ice_hockey' => timedSegments(3),
	'mma_boxing' => [
		'segments' => [
			segment('judge', 'judges', 'structured', null, 10, true),
			segment('round', 'rounds', 'decimal', 'judge', 10, true, null, 5),
		],
		'aggregation' => aggregation('none'),
	],
	'motorsport' => [
		'segments' => [
			segment('session', 'sessions', 'duration', null, 10, true, 3),
			segment('lap', 'laps', 'duration', 'session', 10, true),
		],
		'aggregation' => aggregation('none'),
	],
	'rugby' => [
		'segments' => [segment('period', 'periods', 'integer', null, 10, true, 2)],
		'aggregation' => aggregation('validate', ['period']),
	],
	'running_race' => [
		'segments' => [
			segment('gun_time', 'gun_times', 'duration', null, 10, false, 1),
			segment('chip_time', 'chip_times', 'duration', null, 20, false, 1),
			segment('split', 'splits', 'duration', null, 30, true),
			segment('lap', 'laps', 'duration', null, 40, true),
		],
		'aggregation' => aggregation('none'),
	],
	'tennis' => [
		'segments' => [
			segment('set', 'sets', 'integer', null, 10, true),
			segment('game', 'games', 'integer', 'set', 10, true),
			segment('point', 'points', 'integer', 'game', 10, true),
		],
		'aggregation' => aggregation('validate', ['set']),
	],
	'volleyball' => [
		'segments' => [
			segment('set', 'sets', 'integer', null, 10, true),
			segment('point', 'points', 'integer', 'set', 10, true),
		],
		'aggregation' => aggregation('validate', ['set']),
	],
];

foreach (glob($directory . '/*.json') ?: [] as $file) {
	$profile = json_decode((string) file_get_contents($file), true, 512, JSON_THROW_ON_ERROR);
	$code = (string) ($profile['code'] ?? '');

	if (!isset($definitions[$code])) {
		throw new RuntimeException('Missing schema 1.2 definition for ' . basename($file));
	}

	$profile['schema_version'] = '1.2.0';
	$profile['version'] = '1.3.0';
	$profile['match']['score']['segment_types'] = $definitions[$code]['segments'];
	$profile['match']['score']['aggregation'] = $definitions[$code]['aggregation'];
	$profile['match']['result_status_codes'] = ['draft', 'in_progress', 'final'];
	$profile['match']['outcome_codes'] = outcomeCodes($profile);
	$profile['match']['participant_status_codes'] = participantStatusCodes($code);

	file_put_contents(
		$file,
		json_encode($profile, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR) . "\n"
	);
}

/** @return array<string,mixed> */
function segment(string $code, string $unit, string $valueType, ?string $parentCode, int $ordinal, bool $repeatable, ?int $expectedCount = null, ?int $maximumCount = null, ?string $conditionCode = null): array
{
	$segment = [
		'code' => $code,
		'name_key' => 'COM_JOOMLEAGUE_SCORE_SEGMENT_' . strtoupper($code),
		'unit' => $unit,
		'value_type' => $valueType,
		'parent_code' => $parentCode,
		'ordinal' => $ordinal,
		'repeatable' => $repeatable,
	];

	if ($expectedCount !== null) $segment['expected_count'] = $expectedCount;
	if ($maximumCount !== null) $segment['maximum_count'] = $maximumCount;
	if ($conditionCode !== null) $segment['condition_code'] = $conditionCode;

	return $segment;
}

/** @return array{mode:string,from:list<string>,final_only:bool} */
function aggregation(string $mode, array $from = []): array
{
	return ['mode' => $mode, 'from' => array_values($from), 'final_only' => true];
}

/** @return array{segments:list<array<string,mixed>>,aggregation:array<string,mixed>} */
function timedSegments(int $periodCount, int $extraTimeCount = 1): array
{
	return [
		'segments' => [
			segment('period', 'periods', 'integer', null, 10, true, $periodCount),
			segment('extra_time', 'extra_time_periods', 'integer', null, 20, true, $extraTimeCount, null, 'tied_after_regulation'),
			segment('shootout', 'shootouts', 'integer', null, 30, false, 1, null, 'tied_after_extra_time'),
		],
		'aggregation' => aggregation('validate', ['period', 'extra_time']),
	];
}

/** @param array<string,mixed> $profile @return list<string> */
function outcomeCodes(array $profile): array
{
	$codes = ['completed'];
	if (($profile['match']['result_rules']['supports_forfeit'] ?? false) === true) $codes[] = 'forfeit';
	if (($profile['code'] ?? '') === 'mma_boxing') $codes = array_merge($codes, ['ko_tko', 'submission', 'decision', 'doctor_stoppage']);
	if (in_array($profile['code'] ?? '', ['motorsport', 'running_race'], true)) $codes[] = 'classified';
	return array_values(array_unique($codes));
}

/** @return list<string> */
function participantStatusCodes(string $code): array
{
	if ($code === 'motorsport') return ['active', 'finished', 'dnf', 'dns', 'dsq'];
	if ($code === 'running_race') return ['registered', 'started', 'finished', 'dns', 'dnf', 'dsq'];
	if ($code === 'mma_boxing') return ['active', 'winner', 'loser', 'draw', 'no_contest'];
	return ['eligible', 'started', 'substitute', 'finished', 'disqualified'];
}
