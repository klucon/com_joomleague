<?php

declare(strict_types=1);

define('_JEXEC', 1);

$root = dirname(__DIR__, 2);
require_once $root . '/administrator/components/com_joomleague/src/Service/MatchResultValidationException.php';
require_once $root . '/administrator/components/com_joomleague/src/Service/MatchResultDuration.php';
require_once $root . '/administrator/components/com_joomleague/src/Service/MatchResultEditorSchemaBuilder.php';
require_once $root . '/administrator/components/com_joomleague/src/Service/MatchResultFormStateBuilder.php';
require_once $root . '/administrator/components/com_joomleague/src/Service/MatchResultFormPayloadBuilder.php';

use Joomleague\Component\Joomleague\Administrator\Service\MatchResultDuration;
use Joomleague\Component\Joomleague\Administrator\Service\MatchResultEditorSchemaBuilder;
use Joomleague\Component\Joomleague\Administrator\Service\MatchResultFormPayloadBuilder;
use Joomleague\Component\Joomleague\Administrator\Service\MatchResultFormStateBuilder;

$cases = [
	'9.007' => '9007',
	'12:34.056' => '754056',
	'1:02:03.456' => '3723456',
];

foreach ($cases as $display => $milliseconds) {
	if (MatchResultDuration::parse($display) !== $milliseconds) throw new RuntimeException('Duration parsing failed for ' . $display);
	if (MatchResultDuration::parse(MatchResultDuration::format($milliseconds)) !== $milliseconds) throw new RuntimeException('Duration format round trip failed for ' . $display);
}

foreach (['', '1:60.000', '-1.000', '1:2:03.000', 'not-a-time'] as $invalid) {
	if ($invalid === '' && MatchResultDuration::parse($invalid) === null) continue;
	try {
		MatchResultDuration::parse($invalid);
		throw new RuntimeException('Invalid duration was accepted: ' . $invalid);
	} catch (InvalidArgumentException) {
	}
}

$profile = json_decode((string) file_get_contents($root . '/administrator/components/com_joomleague/resources/sport-profiles/running-race.json'), true, 512, JSON_THROW_ON_ERROR);
$schema = (new MatchResultEditorSchemaBuilder())->build($profile);
$participants = [['id' => 10, 'name' => 'Runner A'], ['id' => 20, 'name' => 'Runner B']];
$stateBuilder = new MatchResultFormStateBuilder();
$state = $stateBuilder->build($schema, $participants, null);
$state['segments'][0]['values'][0]['duration_value'] = '1:02:03.456';
$state['segments'][0]['values'][1]['duration_value'] = '1:03:00.000';
$state['segments'][0]['children'][0]['values'][0]['duration_value'] = '1:02:03.500';
$state['segments'][0]['children'][0]['values'][1]['duration_value'] = '1:03:00.100';
$payload = (new MatchResultFormPayloadBuilder())->build($state, $schema);

if ($payload['segments'][0]['values'][0]['numeric_value'] !== '3723456'
	|| $payload['segments'][0]['children'][0]['values'][1]['numeric_value'] !== '3780100') {
	throw new RuntimeException('Duration form values were not converted to canonical milliseconds.');
}

$reloaded = $stateBuilder->build($schema, $participants, $payload);

if ($reloaded['segments'][0]['values'][0]['duration_value'] !== '1:02:03.456'
	|| $reloaded['segments'][0]['children'][0]['values'][1]['duration_value'] !== '1:03:00.100') {
	throw new RuntimeException('Canonical duration values were not formatted for editing.');
}

echo "Match result duration adapter OK\n";
