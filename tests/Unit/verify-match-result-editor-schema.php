<?php

declare(strict_types=1);

define('_JEXEC', 1);

require_once dirname(__DIR__, 2) . '/administrator/components/com_joomleague/src/Service/MatchResultEditorSchemaBuilder.php';

use Joomleague\Component\Joomleague\Administrator\Service\MatchResultEditorSchemaBuilder;

$builder = new MatchResultEditorSchemaBuilder();
$directory = dirname(__DIR__, 2) . '/administrator/components/com_joomleague/resources/sport-profiles';
$expectedTypes = ['numeric_score' => 0, 'nested_score' => 0, 'time_result' => 0, 'decision_result' => 0];

foreach (glob($directory . '/*.json') ?: [] as $file) {
	$profile = json_decode((string) file_get_contents($file), true, 512, JSON_THROW_ON_ERROR);
	$schema = $builder->build($profile);
	$type = $schema['result_type'];

	if (!array_key_exists($type, $expectedTypes)) throw new RuntimeException(basename($file) . ': unsupported editor type.');
	if ($schema['levels'][0]['code'] !== 'result') throw new RuntimeException(basename($file) . ': missing result root.');
	if (count($schema['levels']) !== count($profile['match']['score']['segment_types']) + 1) throw new RuntimeException(basename($file) . ': segment types were not preserved.');
	if ($type === 'time_result' && $schema['higher_is_better'] !== false) throw new RuntimeException(basename($file) . ': time result direction is invalid.');
	if (($profile['code'] ?? null) === 'mma_boxing' && $schema['editor_control'] !== 'status_rank') throw new RuntimeException('MMA decision root does not use the profile-defined status and rank editor.');

	$expectedTypes[$type]++;
}

if ($expectedTypes !== ['numeric_score' => 8, 'nested_score' => 4, 'time_result' => 2, 'decision_result' => 1]) throw new RuntimeException('Editor schema coverage does not match all profiles.');

echo 'Match result editor schemas OK: ' . json_encode($expectedTypes, JSON_THROW_ON_ERROR) . "\n";
