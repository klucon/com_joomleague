<?php
declare(strict_types=1);
define('_JEXEC', 1);
$root = dirname(__DIR__, 2) . '/administrator/components/com_joomleague/src/Service/';
foreach (['MatchResultValidationException', 'TeamEncounterRosterValidator', 'EncounterPenaltyNominations'] as $service) require $root . $service . '.php';
use Joomleague\Component\Joomleague\Administrator\Service\EncounterPenaltyNominations;
use Joomleague\Component\Joomleague\Administrator\Service\MatchResultValidationException;
$roster = [['id' => 1, 'match_id' => 7, 'match_participant_id' => 10, 'person_id' => 100, 'published' => 1], ['id' => 2, 'match_id' => 7, 'match_participant_id' => 20, 'person_id' => 200, 'published' => 1]];
$contract = ['penalty_series' => ['nominations' => 'optional_single']];
$round = ['level_code' => 'penalty_round', 'sequence_number' => 1, 'values' => [['participant_id' => 10, 'metadata' => ['lineup_member_id' => 1]], ['participant_id' => 20, 'metadata' => ['lineup_member_id' => 2]]]];
$validator = new EncounterPenaltyNominations();
$root = ['children' => [$round]];
$validator->validate(7, [10, 20], $roster, $contract, $root);
$empty = $round; foreach ($empty['values'] as &$value) $value['metadata'] = []; unset($value);
$root = ['children' => [$empty]];
$validator->validate(7, [10, 20], $roster, $contract, $root);
foreach (['foreign_team', 'missing', 'partial', 'unpublished', 'foreign_match', 'duplicate_person', 'unsupported'] as $case) {
    $r = $round; $rows = $roster; $c = $contract;
    if ($case === 'foreign_team') $r['values'][0]['metadata']['lineup_member_id'] = 2;
    if ($case === 'missing') $r['values'][0]['metadata']['lineup_member_id'] = 999;
    if ($case === 'partial') $r['values'][0]['metadata'] = [];
    if ($case === 'unpublished') $rows[0]['published'] = 0;
    if ($case === 'foreign_match') $rows[0]['match_id'] = 8;
    if ($case === 'duplicate_person') $rows[1]['person_id'] = 100;
    if ($case === 'unsupported') $c = [];
    $root = ['children' => [$r]];
    try { $validator->validate(7, [10, 20], $rows, $c, $root); throw new RuntimeException('Accepted ' . $case); }
    catch (MatchResultValidationException $error) { if ($error->getLanguageKey() !== 'COM_JOOMLEAGUE_CAROM_PENALTY_ERROR_NOMINATION') throw $error; }
}
$roster[] = ['id' => 3, 'match_id' => 7, 'match_participant_id' => 10, 'person_id' => 300, 'published' => 1];
$roster[] = ['id' => 4, 'match_id' => 7, 'match_participant_id' => 20, 'person_id' => 400, 'published' => 1];
$duels = [];
foreach ([[1, 2], [3, 4]] as $i => [$a, $b]) $duels[] = ['sequence_number' => $i + 1, 'sides' => [['participant_id' => 10, 'lineup_member_ids' => [$a]], ['participant_id' => 20, 'lineup_member_ids' => [$b]]]];
foreach (['ascending' => [1, 3, 1], 'descending' => [3, 1, 3], 'descending_last' => [3, 1, 1, 1, 1, 1]] as $order => $expected) {
    $c = $contract; $c['penalty_series']['player_order'] = $order; $c['penalty_series']['cycle_length'] = 2;
    $root = ['children' => []];
    for ($i = 1; $i <= count($expected); $i++) { $r = $empty; $r['sequence_number'] = $i; $root['children'][] = $r; }
    $validator->validate(7, [10, 20], $roster, $c, $root, array_reverse($duels));
    foreach ($expected as $index => $id) if ($root['children'][$index]['values'][0]['metadata']['lineup_member_id'] !== $id) throw new RuntimeException('Automatic cyclic board order failed.');
    $root['children'][0]['values'][0]['metadata']['lineup_member_id'] = $expected[1];
    try { $validator->validate(7, [10, 20], $roster, $c, $root, $duels); throw new RuntimeException('Forged board order accepted.'); }
    catch (MatchResultValidationException) {}
}
$fourBoards = [];
foreach ([[1, 2], [3, 4], [5, 6], [7, 8]] as $index => [$a, $b]) {
    if ($a > 4) {
        $roster[] = ['id' => $a, 'match_id' => 7, 'match_participant_id' => 10, 'person_id' => $a * 100, 'published' => 1];
        $roster[] = ['id' => $b, 'match_id' => 7, 'match_participant_id' => 20, 'person_id' => $b * 100, 'published' => 1];
    }
    $fourBoards[] = ['sequence_number' => $index + 1, 'sides' => [['participant_id' => 10, 'lineup_member_ids' => [$a]], ['participant_id' => 20, 'lineup_member_ids' => [$b]]]];
}
$c = $contract;
$c['penalty_series'] += ['player_order' => 'descending_last', 'cycle_length' => 4];
$root = ['children' => []];
foreach (range(1, 7) as $sequence) { $r = $empty; $r['sequence_number'] = $sequence; $root['children'][] = $r; }
$validator->validate(7, [10, 20], $roster, $c, $root, array_reverse($fourBoards));
foreach ([7, 5, 3, 1, 1, 1, 1] as $index => $id) {
    $values = $root['children'][$index]['values'];
    if ($values[0]['metadata']['lineup_member_id'] !== $id || $values[1]['metadata']['lineup_member_id'] !== $id + 1) throw new RuntimeException('Portuguese four-board continuation failed.');
}
echo "Penalty nominations OK: optional pairs, ownership, publication, duplicate players, cyclic orders, Portuguese 4-3-2-1-1 continuation and forged-order rejection.\n";
