<?php
declare(strict_types=1);
define('_JEXEC', 1);
$root = dirname(__DIR__, 2);
foreach (['MatchResultValidationException', 'MatchResultDecimal', 'MatchResultAggregationValidator', 'MatchResultPayloadValidator', 'MatchResultEditorSchemaBuilder', 'MatchResultFormStateBuilder', 'MatchResultFormPayloadBuilder'] as $class) require $root . '/administrator/components/com_joomleague/src/Service/' . $class . '.php';
use Joomleague\Component\Joomleague\Administrator\Service\MatchResultPayloadValidator;
use Joomleague\Component\Joomleague\Administrator\Service\MatchResultEditorSchemaBuilder;
use Joomleague\Component\Joomleague\Administrator\Service\MatchResultFormStateBuilder;
use Joomleague\Component\Joomleague\Administrator\Service\MatchResultFormPayloadBuilder;
$profile = json_decode(file_get_contents($root . '/administrator/components/com_joomleague/resources/sport-profiles/football.json'), true, 512, JSON_THROW_ON_ERROR);
$schema = (new MatchResultEditorSchemaBuilder())->build($profile);
$builder = new MatchResultFormStateBuilder();
$participants = [['id' => 10], ['id' => 20]];
$state = $builder->build($schema, $participants, null);
$state['status_code'] = 'final';
$state['outcome_code'] = 'completed';
$state['segments'][0]['values'] = [['participant_id' => 10, 'numeric_value' => '2'], ['participant_id' => 20, 'numeric_value' => '1']];
$payload = (new MatchResultFormPayloadBuilder())->build($state, $schema);
$validator = new MatchResultPayloadValidator();
$result = $validator->validate($profile, [10, 20], $payload);
if ($result['segments'][0]['children'] !== [] || ($result['metadata']['aggregate_only'] ?? false) !== true) throw new RuntimeException('Root-only result not preserved');
$reopened = $builder->build($schema, $participants, $result);
if ($reopened['segments'][0]['children'][0]['included'] || $reopened['segments'][0]['values'][0]['numeric_value'] !== '2') throw new RuntimeException('Reopen forced empty periods');
$part = static fn (int $n, string $a, string $b): array => ['level_code' => 'period', 'sequence_number' => $n, 'values' => [['participant_id' => 10, 'numeric_value' => $a], ['participant_id' => 20, 'numeric_value' => $b]]];
$detailed = $payload;
$detailed['segments'][0]['children'] = [$part(1, '1', '0'), $part(2, '1', '1')];
$valid = $validator->validate($profile, [10, 20], $detailed);
if (isset($valid['metadata']['aggregate_only'])) throw new RuntimeException('Detailed result mislabelled aggregate-only');
$detailedReloaded = $builder->build($schema, $participants, $valid);
$detailedRoundTrip = (new MatchResultFormPayloadBuilder())->build($detailedReloaded, $schema);
if (count($validator->validate($profile, [10, 20], $detailedRoundTrip)['segments'][0]['children']) !== 2) throw new RuntimeException('Existing breakdown was lost on save');
$partial = $detailed;
array_pop($partial['segments'][0]['children']);
$wrong = $detailed;
$wrong['segments'][0]['children'][1] = $part(2, '5', '1');
$nonNumeric = $payload;
$nonNumeric['segments'][0]['values'][0] = ['participant_id' => 10, 'status_code' => $profile['match']['participant_status_codes'][0]];
foreach ([$partial, $wrong, $nonNumeric] as $bad) {
    $rejected = false;
    try { $validator->validate($profile, [10, 20], $bad); } catch (InvalidArgumentException) { $rejected = true; }
    if (!$rejected) throw new RuntimeException('Invalid breakdown/root accepted');
}
foreach (['tennis', 'volleyball', 'team-encounter', 'carom'] as $code) {
    $other = json_decode(file_get_contents($root . '/administrator/components/com_joomleague/resources/sport-profiles/' . $code . '.json'), true, 512, JSON_THROW_ON_ERROR);
    if ((new MatchResultEditorSchemaBuilder())->build($other)['allow_aggregate_only']) throw new RuntimeException('Unsupported family enabled: ' . $code);
    if (in_array($code, ['tennis', 'volleyball'], true)) {
        $forged = $payload;
        $forged['result_type'] = $other['match']['score']['type'];
        $forged['metadata']['aggregate_only'] = true;
        $rejected = false;
        try { $validator->validate($other, [10, 20], $forged); } catch (InvalidArgumentException) { $rejected = true; }
        if (!$rejected) throw new RuntimeException('Metadata bypassed required sets: ' . $code);
    }
}
$derived = $profile;
$derived['match']['score']['aggregation']['mode'] = 'derive';
try { $validator->validate($derived, [10, 20], $payload); throw new RuntimeException('Derived result bypassed'); } catch (InvalidArgumentException) {}
echo "PASS: final-score-only save/reopen, detailed sums, partial rejection and unsupported-family guards.\n";
