<?php
declare(strict_types=1);
define('_JEXEC', 1);
require dirname(__DIR__, 2) . '/administrator/components/com_joomleague/src/Service/TeamEncounterRosterValidator.php';
use Joomleague\Component\Joomleague\Administrator\Service\TeamEncounterRosterValidator;
$validator = new TeamEncounterRosterValidator();
$roster = [];
foreach ([101 => 10, 102 => 10, 201 => 20, 202 => 20] as $id => $team) $roster[] = ['id' => (string) $id, 'match_id' => '7', 'match_participant_id' => (string) $team, 'person_id' => (string) ($id + 1000), 'published' => '1'];
$formats = ['singles' => 1, 'doubles' => 2];
$single = ['sequence_number' => 1, 'format_code' => 'singles', 'sides' => [['participant_id' => 10, 'lineup_member_ids' => [101]], ['participant_id' => 20, 'lineup_member_ids' => [201]]]];
$double = ['sequence_number' => 2, 'format_code' => 'doubles', 'sides' => [['participant_id' => 20, 'lineup_member_ids' => [201, 202]], ['participant_id' => 10, 'lineup_member_ids' => [101, 102]]]];
$result = $validator->validate(7, [10, 20], $roster, $formats, [$double, $single]);
if (array_column($result, 'sequence_number') !== [1, 2] || $result[1]['sides'][0]['participant_id'] !== 10) throw new RuntimeException('Canonical order failed.');
if ($validator->validate(7, [10, 20], $roster, $formats, []) !== []) throw new RuntimeException('Empty draft should be allowed.');
$invalid = [];
$wrongTeam = $single; $wrongTeam['sides'][0]['lineup_member_ids'] = [201]; $invalid[] = [$roster, [$wrongTeam]];
$unknown = $single; $unknown['sides'][0]['lineup_member_ids'] = [999]; $invalid[] = [$roster, [$unknown]];
$sameTeam = $single; $sameTeam['sides'][1]['participant_id'] = 10; $invalid[] = [$roster, [$sameTeam]];
$duplicated = $double; $duplicated['sides'][0]['lineup_member_ids'] = [201, 201]; $invalid[] = [$roster, [$duplicated]];
$wrongSize = $single; $wrongSize['format_code'] = 'doubles'; $invalid[] = [$roster, [$wrongSize]];
$otherMatch = $roster; $otherMatch[0]['match_id'] = 8; $invalid[] = [$otherMatch, [$single]];
$hidden = $roster; $hidden[0]['published'] = 0; $invalid[] = [$hidden, [$single]];
$samePerson = $roster; $samePerson[2]['person_id'] = $samePerson[0]['person_id']; $invalid[] = [$samePerson, [$single]];
$invalid[] = [$roster, [$single, $single]];
foreach ([true, 1.5, '1e2', '-1', '999999999999999999999999', '01'] as $bad) { $duel = $single; $duel['sequence_number'] = $bad; $invalid[] = [$roster, [$duel]]; }
foreach ($invalid as [$rows, $duels]) {
	try { $validator->validate(7, [10, 20], $rows, $formats, $duels); } catch (InvalidArgumentException) { continue; }
	throw new RuntimeException('Invalid encounter nomination accepted.');
}
echo "Team encounter roster validator OK: singles/doubles, reuse across duels, scope, duplicates and strict IDs.\n";
