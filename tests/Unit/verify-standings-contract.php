<?php

declare(strict_types=1);

define('_JEXEC', 1);
require_once __DIR__ . '/../../administrator/components/com_joomleague/src/Service/StandingsContractValidator.php';

use Joomleague\Component\Joomleague\Domain\Service\StandingsContractValidator;

$validator = new StandingsContractValidator();
$football = [
	'mode' => 'head_to_head',
	'included_result_statuses' => ['final'],
	'scopes' => [
		['code' => 'total', 'filter' => ['type' => 'always']],
		['code' => 'home', 'filter' => ['type' => 'participant_slot', 'value' => 1]],
		['code' => 'away', 'filter' => ['type' => 'participant_slot', 'value' => 2]],
	],
	'metrics' => [
		['code' => 'played', 'operation' => 'count_matches', 'value_type' => 'integer'],
		['code' => 'wins', 'operation' => 'count_outcome', 'outcome' => 'win', 'value_type' => 'integer'],
		['code' => 'score_for', 'operation' => 'sum_root', 'value_type' => 'decimal'],
		['code' => 'score_against', 'operation' => 'sum_root', 'value_type' => 'decimal'],
		['code' => 'score_difference', 'operation' => 'difference', 'left_metric' => 'score_for', 'right_metric' => 'score_against', 'value_type' => 'decimal'],
		['code' => 'points', 'operation' => 'sum_awards', 'value_type' => 'decimal'],
	],
	'awards' => [
		'mode' => 'outcome_rules',
		'rule_strategy' => 'first_match',
		'rules' => [['when' => ['type' => 'always'], 'values' => ['winner' => 3, 'draw' => 1, 'loser' => 0]]],
		'bonus_rules' => [],
	],
	'ordering' => [
		['metric' => 'points', 'direction' => 'desc', 'nulls' => 'last'],
		['metric' => 'score_difference', 'direction' => 'desc', 'nulls' => 'last'],
	],
];
$validator->validate($football);

$race = $football;
$race['mode'] = 'classification';
$race['awards'] = ['mode' => 'rank_table', 'rule_strategy' => 'first_match', 'rules' => [['when' => ['type' => 'result_rank', 'operator' => 'eq', 'value' => 1], 'values' => ['entry' => 25]]], 'bonus_rules' => []];
$race['classification'] = ['primary' => 'result_rank', 'direction' => 'asc', 'status_precedence' => ['finished', 'dnf', 'dns', 'dsq']];
$validator->validate($race);

$invalid = $football;
$invalid['scopes'][1]['filter']['value'] = 0;
try { $validator->validate($invalid); throw new RuntimeException('Invalid participant slot scope was accepted.'); } catch (UnexpectedValueException) {}
$invalid = $football;
$invalid['scopes'][] = $invalid['scopes'][0];
try { $validator->validate($invalid); throw new RuntimeException('Duplicate scope code was accepted.'); } catch (UnexpectedValueException) {}
$invalid = $football;
$invalid['ordering'][0]['metric'] = 'unknown';
try { $validator->validate($invalid); throw new RuntimeException('Unknown ordering metric was accepted.'); } catch (UnexpectedValueException) {}
$invalid = $football;
$invalid['awards']['rules'][0]['when'] = ['type' => 'php', 'operator' => 'eq', 'value' => 'system'];
try { $validator->validate($invalid); throw new RuntimeException('Executable condition type was accepted.'); } catch (UnexpectedValueException) {}
$invalid = $football;
$condition = ['type' => 'outcome', 'operator' => 'eq', 'value' => 'win'];
for ($depth = 0; $depth < 7; $depth++) $condition = ['type' => 'all', 'conditions' => [$condition]];
$invalid['awards']['rules'][0]['when'] = $condition;
try { $validator->validate($invalid); throw new RuntimeException('Excessive condition depth was accepted.'); } catch (UnexpectedValueException) {}

echo "Universal standings contract validator OK\n";
