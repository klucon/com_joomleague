<?php

declare(strict_types=1);

define('_JEXEC', 1);

$root = dirname(__DIR__, 2);
require_once $root . '/administrator/components/com_joomleague/src/Service/CanonicalJson.php';
require_once $root . '/administrator/components/com_joomleague/src/Service/ProjectRuleValidator.php';

use Joomleague\Component\Joomleague\Domain\Service\CanonicalJson;
use Joomleague\Component\Joomleague\Administrator\Service\ProjectRuleValidator;

$validator = new ProjectRuleValidator();
$profiles = glob($root . '/administrator/components/com_joomleague/resources/sport-profiles/*.json') ?: [];
$fieldCount = 0;

foreach ($profiles as $file) {
	$profile = json_decode((string) file_get_contents($file), true, 512, JSON_THROW_ON_ERROR);
	$validator->validateProfileSchema($profile);
	$validator->validateOverrides($profile, []);
	$fieldCount += count($profile['project_rule_schema']['fields']);
}

$football = json_decode(
	(string) file_get_contents($root . '/administrator/components/com_joomleague/resources/sport-profiles/football.json'),
	true,
	512,
	JSON_THROW_ON_ERROR
);
$overrides = [
	'match_structure' => [
		'period_length_minutes' => 40,
		'extra_time' => ['enabled' => false],
	],
	'standings' => ['points_regular' => [3, 2, 0]],
];
$effective = $validator->resolve($football, $overrides);

if ($effective['match_structure']['period_length_minutes'] !== 40
	|| $effective['match_structure']['extra_time']['enabled'] !== false
	|| $effective['match_structure']['extra_time']['period_count'] !== 2
	|| $football['match_structure']['period_length_minutes'] !== 45
) {
	throw new RuntimeException('Project-rule overrides were not resolved as sparse immutable layers.');
}

$expectedJson = '{"match_structure":{"extra_time":{"enabled":false},"period_length_minutes":40},"standings":{"points_regular":[3,2,0]}}';

if (CanonicalJson::encodeObject($overrides) !== $expectedJson
	|| $validator->checksum($overrides) !== hash('sha256', $expectedJson)
	|| $validator->checksum(array_reverse($overrides, true)) !== $validator->checksum($overrides)
) {
	throw new RuntimeException('Canonical project-rule JSON or checksum is unstable.');
}

$invalidOverrides = [
	['unknown' => ['field' => true]],
	['match_structure' => ['period_count' => '2']],
	['match_structure' => ['period_count' => 0]],
	['match_structure' => ['default_start_time' => '25:90']],
	['standings' => ['points_regular' => [3, 0]]],
	['standings' => ['points_regular' => [3, '1', 0]]],
	['lineup' => ['minimum_players_to_start' => 12]],
];

foreach ($invalidOverrides as $invalid) {
	try {
		$validator->validateOverrides($football, $invalid);
		throw new RuntimeException('Invalid project-rule override was accepted.');
	} catch (InvalidArgumentException) {
	}
}

$constraintCases = [
	'ice-hockey.json' => ['lineup' => ['skaters_on_ice' => 4]],
	'mma-boxing.json' => ['match_structure' => ['period_count' => 6]],
	'tennis.json' => ['match_structure' => ['sets_to_win' => 3]],
	'volleyball.json' => ['lineup' => ['minimum_players_to_start' => 7]],
];

foreach ($constraintCases as $profileFile => $invalid) {
	$profile = json_decode(
		(string) file_get_contents($root . '/administrator/components/com_joomleague/resources/sport-profiles/' . $profileFile),
		true,
		512,
		JSON_THROW_ON_ERROR
	);

	try {
		$validator->validateOverrides($profile, $invalid);
		throw new RuntimeException(sprintf('Relational constraint in %s was not enforced.', $profileFile));
	} catch (InvalidArgumentException) {
	}
}

$tennis = json_decode(
	(string) file_get_contents($root . '/administrator/components/com_joomleague/resources/sport-profiles/tennis.json'),
	true,
	512,
	JSON_THROW_ON_ERROR
);
$validator->validateOverrides($tennis, ['match_structure' => ['sets_to_win' => 3, 'maximum_sets' => 5]]);

printf(
	"Project-rule validator OK: %d profiles, %d explicitly overridable fields, relational constraints and canonical checksum validated\n",
	count($profiles),
	$fieldCount
);
