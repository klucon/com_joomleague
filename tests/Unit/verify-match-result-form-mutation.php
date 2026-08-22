<?php

declare(strict_types=1);

define('_JEXEC', 1);

$root = dirname(__DIR__, 2);
require_once $root . '/administrator/components/com_joomleague/src/Service/MatchResultEditorSchemaBuilder.php';
require_once $root . '/administrator/components/com_joomleague/src/Service/MatchResultFormStateMutator.php';

use Joomleague\Component\Joomleague\Administrator\Service\MatchResultEditorSchemaBuilder;
use Joomleague\Component\Joomleague\Administrator\Service\MatchResultFormStateMutator;

$profiles = $root . '/administrator/components/com_joomleague/resources/sport-profiles';
$loadSchema = static function (string $code) use ($profiles): array {
	$profile = json_decode((string) file_get_contents($profiles . '/' . $code . '.json'), true, 512, JSON_THROW_ON_ERROR);
	return (new MatchResultEditorSchemaBuilder())->build($profile);
};
$payload = static fn (string $type): array => [
	'result_type' => $type,
	'status_code' => 'draft',
	'outcome_code' => null,
	'finalized_at' => null,
	'notes' => null,
	'metadata' => [],
	'segments' => [[
		'level_code' => 'result',
		'sequence_number' => 1,
		'status_code' => 'completed',
		'metadata' => [],
		'values' => [],
		'children' => [],
	]],
];

$mutator = new MatchResultFormStateMutator();
$tennis = $loadSchema('tennis');
$result = $mutator->add($payload('nested_score'), $tennis, 'result:1', 'set');
$result = $mutator->add($result, $tennis, 'result:1/set:1', 'game');
$result = $mutator->add($result, $tennis, 'result:1/set:1/game:1', 'point');

if ($result['segments'][0]['children'][0]['children'][0]['children'][0]['level_code'] !== 'point') {
	throw new RuntimeException('Nested repeatable score segments were not added at the requested parent.');
}

$result = $mutator->remove($result, $tennis, 'result:1/set:1/game:1/point:1');

if ($result['segments'][0]['children'][0]['children'][0]['children'] !== []) {
	throw new RuntimeException('The selected nested score segment was not removed.');
}

try {
	$mutator->add($payload('numeric_score'), $loadSchema('football'), 'result:1', 'period');
	throw new RuntimeException('A fixed-count segment was added manually.');
} catch (InvalidArgumentException) {
}

$mma = $loadSchema('mma-boxing');
$result = $mutator->add($payload('decision_result'), $mma, 'result:1', 'judge');

for ($round = 1; $round <= 5; $round++) {
	$result = $mutator->add($result, $mma, 'result:1/judge:1', 'round');
}

try {
	$mutator->add($result, $mma, 'result:1/judge:1', 'round');
	throw new RuntimeException('A profile segment maximum was exceeded.');
} catch (LengthException) {
}

echo "Match result form mutation OK\n";
