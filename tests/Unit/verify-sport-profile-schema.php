<?php

declare(strict_types=1);

define('_JEXEC', 1);
require_once dirname(__DIR__, 2) . '/administrator/components/com_joomleague/src/Service/StandingsContractValidator.php';
require_once dirname(__DIR__, 2) . '/administrator/components/com_joomleague/src/Service/SportProfileSchemaValidator.php';

use Joomleague\Component\Joomleague\Administrator\Service\SportProfileSchemaValidator;

$calculation = [
	'mode' => 'head_to_head',
	'outcome_source' => 'root_numeric',
	'included_result_statuses' => ['final'],
	'scopes' => [['code' => 'total', 'filter' => ['type' => 'always']]],
	'metrics' => [['code' => 'points', 'operation' => 'sum_awards', 'value_type' => 'decimal']],
	'awards' => ['mode' => 'outcome_rules', 'rule_strategy' => 'first_match', 'rules' => [['when' => ['type' => 'always'], 'values' => ['winner' => 3, 'draw' => 1, 'loser' => 0]]], 'bonus_rules' => []],
	'ordering' => [['metric' => 'points', 'direction' => 'desc', 'nulls' => 'last']],
];
$base = ['schema_version' => '1.4.0', 'code' => 'test_sport', 'contest' => ['type' => 'head_to_head'], 'entry_model' => ['allowed_kinds' => ['team'], 'default_kind' => 'team'], 'match' => ['structure' => ['type' => 'timed_periods'], 'score' => ['type' => 'numeric_score', 'unit' => 'points', 'segment_types' => [['code' => 'period', 'name_key' => 'TEST_PERIOD', 'unit' => 'periods', 'value_type' => 'integer', 'parent_code' => null, 'ordinal' => 10, 'repeatable' => true, 'expected_count' => 2]], 'aggregation' => ['mode' => 'validate', 'from' => ['period'], 'final_only' => true]], 'result_status_codes' => ['draft', 'in_progress', 'final'], 'outcome_codes' => ['completed'], 'participant_status_codes' => ['started', 'finished']], 'standings' => ['type' => 'team_table', 'sort_order' => ['points'], 'columns' => ['rank', 'points'], 'calculation' => $calculation], 'positions' => [['code' => 'player']], 'event_types' => [['code' => 'point'], ['code' => 'bonus_point']], 'statistics' => [['code' => 'points', 'source' => 'event', 'event_codes' => ['point', 'bonus_point']]]];
$validator = new SportProfileSchemaValidator();
$validator->validate($base);

$nested = $base;
$nested['match']['structure']['type'] = 'set_based';
$nested['match']['score']['type'] = 'nested_score';
$nested['match']['score']['unit'] = 'match';
$nested['match']['score']['segment_types'] = [['code' => 'set', 'name_key' => 'TEST_SET', 'unit' => 'sets', 'value_type' => 'integer', 'parent_code' => null, 'ordinal' => 10, 'repeatable' => true], ['code' => 'game', 'name_key' => 'TEST_GAME', 'unit' => 'games', 'value_type' => 'integer', 'parent_code' => 'set', 'ordinal' => 10, 'repeatable' => true]];
$nested['match']['score']['aggregation']['from'] = ['set'];
$validator->validate($nested);

$race = $base;
$race['contest']['type'] = 'race';
$race['match']['structure']['type'] = 'race';
$race['match']['score']['type'] = 'time_result';
$race['match']['score']['unit'] = 'milliseconds';
$race['match']['score']['segment_types'] = [['code' => 'lap', 'name_key' => 'TEST_LAP', 'unit' => 'laps', 'value_type' => 'duration', 'parent_code' => null, 'ordinal' => 10, 'repeatable' => true]];
$race['match']['score']['aggregation'] = ['mode' => 'none', 'from' => [], 'final_only' => true];
$race['standings']['type'] = 'race_results';
$race['standings']['calculation'] = ['mode' => 'classification', 'included_result_statuses' => ['final'], 'scopes' => [['code' => 'overall', 'filter' => ['type' => 'always']]], 'metrics' => [['code' => 'elapsed', 'operation' => 'sum_root', 'value_type' => 'duration']], 'awards' => ['mode' => 'none', 'rule_strategy' => 'first_match', 'rules' => [], 'bonus_rules' => []], 'ordering' => [['metric' => 'elapsed', 'direction' => 'asc', 'nulls' => 'last']], 'classification' => ['primary' => 'root_value', 'direction' => 'asc', 'status_precedence' => ['finished', 'dnf']]];
$validator->validate($race);

$invalid = $base;
$invalid['statistics'][0]['event_codes'] = ['missing'];
try { $validator->validate($invalid); throw new RuntimeException('Unknown event reference was accepted.'); } catch (UnexpectedValueException) {}
$cyclic = $nested;
$cyclic['match']['score']['segment_types'][0]['parent_code'] = 'game';
try { $validator->validate($cyclic); throw new RuntimeException('Cyclic segment graph was accepted.'); } catch (UnexpectedValueException) {}
$invalidControl = $base;
$invalidControl['match']['score']['editor_control'] = 'duration';
try { $validator->validate($invalidControl); throw new RuntimeException('Incompatible score editor control was accepted.'); } catch (UnexpectedValueException) {}

echo "Sport profile schema 1.4 validator OK\n";
