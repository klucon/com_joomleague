<?php

declare(strict_types=1);

define('_JEXEC', 1);
require_once dirname(__DIR__, 2) . '/administrator/components/com_joomleague/src/Service/MatchResultValidationException.php';
require_once dirname(__DIR__, 2) . '/administrator/components/com_joomleague/src/Service/MatchResultEditorSchemaBuilder.php';
require_once dirname(__DIR__, 2) . '/administrator/components/com_joomleague/src/Service/MatchResultDuration.php';
require_once dirname(__DIR__, 2) . '/administrator/components/com_joomleague/src/Service/MatchResultFormStateBuilder.php';
require_once dirname(__DIR__, 2) . '/administrator/components/com_joomleague/src/Service/MatchResultFormPayloadBuilder.php';

use Joomleague\Component\Joomleague\Administrator\Service\MatchResultEditorSchemaBuilder;
use Joomleague\Component\Joomleague\Administrator\Service\MatchResultFormPayloadBuilder;
use Joomleague\Component\Joomleague\Administrator\Service\MatchResultFormStateBuilder;

$profile = json_decode((string) file_get_contents(dirname(__DIR__, 2) . '/administrator/components/com_joomleague/resources/sport-profiles/football.json'), true, 512, JSON_THROW_ON_ERROR);
$schema = (new MatchResultEditorSchemaBuilder())->build($profile);
$participants = [['id' => 10, 'name' => 'Home'], ['id' => 20, 'name' => 'Away']];
$builder = new MatchResultFormStateBuilder();
$state = $builder->build($schema, $participants, null);
$root = $state['segments'][0];

if (array_column($root['children'], 'level_code') !== ['period', 'period', 'extra_time', 'extra_time', 'shootout']) throw new RuntimeException('Football form-state segments are incomplete or incorrectly ordered.');
if (!$root['children'][0]['required'] || $root['children'][2]['required'] || $root['children'][2]['included']) throw new RuntimeException('Required and optional segment state is invalid.');

$state['status_code'] = 'final';
$state['outcome_code'] = 'completed';
$state['segments'][0]['values'] = [10 => ['numeric_value' => '2'], 20 => ['numeric_value' => '1']];
foreach ([0 => ['1', '0'], 1 => ['1', '1']] as $index => $scores) {
	$state['segments'][0]['children'][$index]['values'] = [10 => ['numeric_value' => $scores[0]], 20 => ['numeric_value' => $scores[1]]];
}
$payloadBuilder = new MatchResultFormPayloadBuilder();
$payload = $payloadBuilder->build($state, $schema);

if (count($payload['segments'][0]['children']) !== 2 || array_column($payload['segments'][0]['children'], 'level_code') !== ['period', 'period']) throw new RuntimeException('Unchecked optional segments were persisted.');

$state['conditions']['tied_after_regulation'] = 1;
$extraTimePayload = $payloadBuilder->build($state, $schema);

if (array_column($extraTimePayload['segments'][0]['children'], 'level_code') !== ['period', 'period', 'extra_time', 'extra_time']) {
	throw new RuntimeException('A conditional expected-count segment group was not included atomically.');
}

$extraTimeReloaded = $builder->build($schema, $participants, $extraTimePayload);

if (($extraTimeReloaded['conditions']['tied_after_regulation'] ?? false) !== true || ($extraTimeReloaded['conditions']['tied_after_extra_time'] ?? true) !== false) {
	throw new RuntimeException('Conditional segment state was not derived from the stored result.');
}

$stored = $payload;
foreach ($stored['segments'][0]['children'] as &$segment) {
	$segment['segment_type_ordinal'] = 10;
	$segment['status_code'] = 'completed';
}
unset($segment);
$stored['segments'][0]['segment_type_ordinal'] = 0;
$stored['segments'][0]['status_code'] = 'completed';
$reloaded = $builder->build($schema, $participants, $stored);

if (array_column($reloaded['segments'][0]['children'], 'level_code') !== ['period', 'period', 'extra_time', 'extra_time', 'shootout']) throw new RuntimeException('Profile-defined optional segments disappeared after reload.');

$profileDirectory = dirname(__DIR__, 2) . '/administrator/components/com_joomleague/resources/sport-profiles';
$countSegments = static function (array $segment) use (&$countSegments): int {
	$count = 1;
	foreach ($segment['children'] ?? [] as $child) $count += $countSegments($child);
	return $count;
};

foreach (glob($profileDirectory . '/*.json') ?: [] as $profileFile) {
	$candidate = json_decode((string) file_get_contents($profileFile), true, 512, JSON_THROW_ON_ERROR);
	$candidateSchema = (new MatchResultEditorSchemaBuilder())->build($candidate);
	$candidateState = $builder->build($candidateSchema, $participants, null);
	$candidateRoot = $candidateState['segments'][0] ?? null;

	if (!is_array($candidateRoot) || $candidateRoot['level_code'] !== 'result' || $countSegments($candidateRoot) < 2) {
		throw new RuntimeException(basename($profileFile) . ': form-state tree is invalid.');
	}

	foreach ($candidateRoot['values'] as $index => $candidateValue) {
		if (($candidateValue['participant_id'] ?? null) !== $participants[$index]['id']) {
			throw new RuntimeException(basename($profileFile) . ': participant identity was not preserved.');
		}
	}

	if (($candidate['code'] ?? null) === 'mma_boxing') {
		$judge = $candidateRoot['children'][0] ?? null;
		if ($candidateRoot['editor_control'] !== 'status_rank' || !is_array($judge) || $judge['editor_control'] !== 'none' || $judge['values'] !== [] || ($judge['children'][0]['editor_control'] ?? null) !== 'number') {
			throw new RuntimeException('MMA structured decision controls are not driven by the profile contract.');
		}
	}
}

echo "Match result form state and payload round trip OK\n";
