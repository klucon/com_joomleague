<?php
declare(strict_types=1);
define('_JEXEC', 1);
$root = dirname(__DIR__, 2) . '/administrator/components/com_joomleague/';
foreach (['StandingsContractValidator', 'RoleDomain', 'SportProfileSchemaValidator', 'MatchResultValidationException', 'MatchResultDecimal', 'MatchResultAggregationValidator', 'EncounterPenaltySeries', 'TeamEncounterScoreNormalizer', 'MatchResultPayloadValidator'] as $service) {
    require_once $root . 'src/Service/' . $service . '.php';
}
use Joomleague\Component\Joomleague\Administrator\Service\SportProfileSchemaValidator;
use Joomleague\Component\Joomleague\Administrator\Service\MatchResultPayloadValidator;
use Joomleague\Component\Joomleague\Administrator\Service\MatchResultValidationException;
$profile = json_decode(file_get_contents($root . 'resources/sport-profiles/team-encounter.json'), true, 512, JSON_THROW_ON_ERROR);
(new SportProfileSchemaValidator())->validate($profile);
$validator = new MatchResultPayloadValidator();
$segment = static fn(string $code, int $sequence, ?int $a, ?int $b, array $children = [], string $status = 'completed'): array => [
    'level_code' => $code, 'sequence_number' => $sequence, 'status_code' => $status,
    'values' => $a === null ? [] : [['participant_id' => 10, 'numeric_value' => (string) $a], ['participant_id' => 20, 'numeric_value' => (string) $b]],
    'children' => $children,
];
$payload = static fn(array $duels, string $status = 'final'): array => [
    'result_type' => 'nested_score', 'status_code' => $status, 'outcome_code' => 'completed',
    'segments' => [$segment('result', 1, null, null, $duels)],
];
$scores = static fn(array $result): array => array_column($result['segments'][0]['values'], 'numeric_value');
$duels = [$segment('duel', 1, 21, 17), $segment('duel', 2, 21, 19), $segment('duel', 3, 11, 21), $segment('duel', 4, null, null, [$segment('set', 1, 6, 4), $segment('set', 2, 3, 6), $segment('set', 3, 7, 5)])];
$result = $validator->validate($profile, [10, 20], $payload($duels));
if ($scores($result) !== ['3', '1']) throw new RuntimeException('Team score was not derived from four duels.');
if (array_column($result['segments'][0]['children'][3]['values'], 'numeric_value') !== ['2', '1']) throw new RuntimeException('Duel score was not derived from sets.');
$cancelled = $segment('duel', 5, null, null, [], 'cancelled');
if ($scores($validator->validate($profile, [10, 20], $payload([...$duels, $cancelled]))) !== ['3', '1']) throw new RuntimeException('Cancelled duel affected the team score.');
$walkover = $validator->validate($profile, [10, 20], $payload([$segment('duel', 1, 99, 0, [], 'walkover')]));
if ($scores($walkover) !== ['1', '0'] || array_column($walkover['segments'][0]['children'][0]['values'], 'numeric_value') !== ['1', '0']) throw new RuntimeException('Walkover was not normalized.');
if ($scores($validator->validate($profile, [10, 20], $payload([$duels[0], $duels[2]]))) !== ['1', '1']) throw new RuntimeException('Team draw should be permitted.');
$validator->validate($profile, [10, 20], $payload([], 'draft'));
$validator->validate($profile, [10, 20], $payload([$segment('duel', 1, null, null)], 'in_progress'));
foreach ([[$segment('duel', 1, null, null, [], 'cancelled')], [$segment('duel', 1, null, null, [$segment('set', 1, 1, 0, [], 'cancelled')])]] as $invalid) {
    try { $validator->validate($profile, [10, 20], $payload($invalid)); }
    catch (MatchResultValidationException) { continue; }
    throw new RuntimeException('Cancelled-only encounter or cancelled set was accepted.');
}
foreach (['-1', '1.5', '1000000000', '1e2'] as $invalidScore) {
    $bad = $segment('duel', 1, 1, 0);
    $bad['values'][0]['numeric_value'] = $invalidScore;
    try { $validator->validate($profile, [10, 20], $payload([$bad])); }
    catch (MatchResultValidationException) { continue; }
    throw new RuntimeException('Invalid score representation was accepted.');
}
foreach ([['entry_model', 'allowed_kinds', ['person']], ['match', 'encounter', ['aggregation' => 'sum', 'formats' => ['singles' => 1], 'member_person_types' => ['player']]]] as [$section, $field, $value]) {
    $bad = $profile;
    $bad[$section][$field] = $value;
    try { (new SportProfileSchemaValidator())->validate($bad); }
    catch (UnexpectedValueException) { continue; }
    throw new RuntimeException('Invalid encounter profile was accepted.');
}
foreach ([[], [$segment('duel', 1, 1, 1)], [$segment('duel', 1, null, null)], [$segment('duel', 1, 1, 0, [], 'cancelled')], [$segment('duel', 1, 1, 0, [$segment('set', 1, 1, 0)], 'walkover')], [$segment('duel', 1, null, null, [$segment('set', 1, 6, 6)])]] as $invalid) {
    try { $validator->validate($profile, [10, 20], $payload($invalid)); }
    catch (MatchResultValidationException) { continue; }
    throw new RuntimeException('Invalid final encounter result was accepted.');
}
echo "Team encounter profile and scores OK: 3:1, sets, draw, cancellation, walkover and incomplete results.\n";
$carom = json_decode(file_get_contents($root . 'resources/sport-profiles/carom.json'), true, 512, JSON_THROW_ON_ERROR);
(new SportProfileSchemaValidator())->validate($carom);
$fourWins = array_map(static fn(int $n): array => $segment('duel', $n, 40, 17), range(1, 4));
if ($scores($validator->validate($carom, [10, 20], $payload($fourWins))) !== ['8', '0']) throw new RuntimeException('Four carom wins must award 8:0.');
$mixed = [$segment('duel', 1, 40, 17), $segment('duel', 2, 20, 40), $segment('duel', 3, 30, 30), $segment('duel', 4, 35, 35)];
if ($scores($validator->validate($carom, [10, 20], $payload($mixed))) !== ['4', '4']) throw new RuntimeException('Carom wins, losses and draws must award 4:4.');
foreach ([['win'=>-1,'draw'=>1,'loss'=>0], ['win'=>2,'draw'=>1.5,'loss'=>0], ['win'=>2,'draw'=>1], ['win'=>1,'draw'=>1,'loss'=>2]] as $awards) {
    $invalid=$carom; $invalid['match']['encounter']['duel_points']=$awards;
    try { (new SportProfileSchemaValidator())->validate($invalid); }
    catch (UnexpectedValueException) { continue; }
    throw new RuntimeException('Invalid game awards were accepted.');
}
echo "Carom awards OK: 8:0, 4:4, individual draws and rejected invalid awards.\n";
require_once $root . 'src/Service/MatchResultFormPayloadBuilder.php';
$carom['match']['encounter']['game_rules']['games_per_encounter'] = 1;
$game = $segment('duel', 1, 40, 32);
$game['values'][0]['metadata'] = ['innings' => '43', 'high_run' => '9'];
$game['values'][1]['metadata'] = ['innings' => '43', 'high_run' => '7'];
$form = $payload([$game]);
$form['segments'][0]['children'][0]['included'] = 1;
$schema = $carom['match']['score']; $schema['encounter'] = $carom['match']['encounter'];
$built = (new \Joomleague\Component\Joomleague\Administrator\Service\MatchResultFormPayloadBuilder())->build($form, $schema);
$validated = $validator->validate($carom, [10, 20], $built);
if ($validated['segments'][0]['children'][0]['values'][0]['metadata'] !== ['innings' => 43, 'high_run' => 9]) throw new RuntimeException('Carom form statistics did not survive validation.');
foreach ([['innings', 0], ['innings', '1.5'], ['innings', []], ['high_run', 41], ['high_run', -1]] as [$field, $invalidValue]) {
    $invalid = $game; $invalid['values'][0]['metadata'][$field] = $invalidValue;
    try { $validator->validate($carom, [10, 20], $payload([$invalid])); }
    catch (InvalidArgumentException) { continue; }
    throw new RuntimeException('Invalid carom statistic was accepted.');
}
echo "Carom metadata OK: form round-trip, typed innings, high run bounds.\n";
foreach (['target', 'innings', 'equal', 'count'] as $case) {
    $invalid = $game; $rulesProfile = $carom;
    if ($case === 'target') $invalid['values'][0]['numeric_value'] = '41';
    if ($case === 'innings') $rulesProfile['match']['encounter']['game_rules']['inning_limit'] = 42;
    if ($case === 'equal') $invalid['values'][1]['metadata']['innings'] = '42';
    if ($case === 'count') $rulesProfile['match']['encounter']['game_rules']['games_per_encounter'] = 4;
    try { $validator->validate($rulesProfile, [10, 20], $payload([$invalid])); }
    catch (MatchResultValidationException) { continue; }
    throw new RuntimeException('Invalid carom game rule was accepted: ' . $case);
}
echo "Carom rules OK: game count, target score, inning limit and equal innings.\n";
