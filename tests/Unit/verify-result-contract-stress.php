<?php

declare(strict_types=1);

define('_JEXEC', 1);
require_once dirname(__DIR__, 2) . '/administrator/components/com_joomleague/src/Service/MatchResultValidationException.php';
require_once dirname(__DIR__, 2) . '/administrator/components/com_joomleague/src/Service/MatchResultDecimal.php';
require_once dirname(__DIR__, 2) . '/administrator/components/com_joomleague/src/Service/MatchResultAggregationValidator.php';
require_once dirname(__DIR__, 2) . '/administrator/components/com_joomleague/src/Service/MatchResultPayloadValidator.php';

use Joomleague\Component\Joomleague\Administrator\Service\MatchResultPayloadValidator;

$directory = dirname(__DIR__, 2) . '/administrator/components/com_joomleague/resources/sport-profiles';
$validator = new MatchResultPayloadValidator();
$participants = [10, 20];

$load = static function (string $file) use ($directory): array {
	return json_decode((string) file_get_contents($directory . '/' . $file), true, 512, JSON_THROW_ON_ERROR);
};
$values = static fn (string $home, string $away): array => [
	['participant_id' => 10, 'numeric_value' => $home],
	['participant_id' => 20, 'numeric_value' => $away],
];
$segment = static fn (string $code, int $sequence, array $values, array $children = []): array => [
	'level_code' => $code,
	'sequence_number' => $sequence,
	'values' => $values,
	'children' => $children,
];
$payload = static fn (string $type, array $children, array $rootValues = [], ?string $outcome = 'completed'): array => [
	'result_type' => $type,
	'status_code' => 'final',
	'outcome_code' => $outcome,
	'segments' => [['level_code' => 'result', 'values' => $rootValues, 'children' => $children]],
];

$football = $payload('numeric_score', [
	$segment('period', 1, $values('1', '0')),
	$segment('period', 2, $values('1', '1')),
], $values('2', '1'));
$validator->validate($load('football.json'), $participants, $football);
$derivedFootballProfile = $load('football.json');
$derivedFootballProfile['match']['score']['aggregation']['mode'] = 'derive';
$derivedFootball = $football;
$derivedFootball['segments'][0]['values'] = [];
$derivedFootballResult = $validator->validate($derivedFootballProfile, $participants, $derivedFootball);
if (array_column($derivedFootballResult['segments'][0]['values'], 'numeric_value') !== ['2', '1']) throw new RuntimeException('Profile-derived football root score is invalid.');

$chess = $payload('numeric_score', [
	$segment('board', 1, $values('0.5', '0.5')),
	$segment('board', 2, $values('1', '0')),
], $values('1.5', '0.5'));
$validator->validate($load('chess.json'), $participants, $chess);

$tennis = $payload('nested_score', [
	$segment('set', 1, $values('1', '0'), [
		$segment('game', 1, $values('6', '4'), [$segment('point', 1, $values('40', '30'))]),
	]),
], $values('1', '0'));
$validator->validate($load('tennis.json'), $participants, $tennis);

$raceValues = [
	['participant_id' => 10, 'numeric_value' => '3600', 'result_rank' => 1, 'status_code' => 'finished'],
	['participant_id' => 20, 'numeric_value' => '3660', 'result_rank' => 2, 'status_code' => 'finished'],
];
$race = $payload('time_result', [
	$segment('gun_time', 1, $raceValues),
	$segment('chip_time', 1, $raceValues),
	$segment('split', 1, $values('1800', '1830')),
], $raceValues, 'classified');
$validator->validate($load('running-race.json'), $participants, $race);

$mma = $payload('decision_result', [
	$segment('judge', 1, [], [
		$segment('round', 1, $values('10', '9')),
		$segment('round', 2, $values('9', '10')),
		$segment('round', 3, $values('10', '9')),
	]),
], [
	['participant_id' => 10, 'result_rank' => 1, 'status_code' => 'winner'],
	['participant_id' => 20, 'result_rank' => 2, 'status_code' => 'loser'],
], 'decision');
$validator->validate($load('mma-boxing.json'), $participants, $mma);

$invalidFootball = $football;
array_pop($invalidFootball['segments'][0]['children']);
try {
	$validator->validate($load('football.json'), $participants, $invalidFootball);
	throw new RuntimeException('Incomplete final football periods were accepted.');
} catch (InvalidArgumentException) {
}

$invalidAggregation = $football;
$invalidAggregation['segments'][0]['values'][0]['numeric_value'] = '3';
try {
	$validator->validate($load('football.json'), $participants, $invalidAggregation);
	throw new RuntimeException('A mismatched final score aggregation was accepted.');
} catch (InvalidArgumentException) {
}

echo "Result contract stress test OK: football, chess, tennis, running race and MMA\n";
