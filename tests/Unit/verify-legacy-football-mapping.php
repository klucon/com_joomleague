<?php
declare(strict_types=1);
define('_JEXEC', 1);
$services = __DIR__ . '/../../administrator/components/com_joomleague/src/Service/';
require_once $services . 'StandingsDecimal.php';
require_once $services . 'LegacyFootballResultMapping.php';
use Joomleague\Component\Joomleague\Administrator\Service\LegacyFootballResultMapping as Mapping;

$cases = json_decode(file_get_contents(__DIR__ . '/../Fixtures/legacy-football-reviewed.json'), true, 512, JSON_THROW_ON_ERROR);
foreach ($cases as $case) {
    $actual = Mapping::map($case['source'], $case['profile'], $case['project']);
    if ($actual !== $case['expected']) throw new RuntimeException('PHP/Python mapping differs for reviewed match ' . $case['source']['id']);
}
$base = ['id'=>1, 'team1_result'=>2, 'team2_result'=>1, 'team1_result_ot'=>4, 'team2_result_ot'=>1, 'match_result_type'=>1];
if (Mapping::map($base, 'football')['final'] !== ['4','1']) throw new RuntimeException('Return-leg result rejected.');
$ordinary = ['match_result_type'=>'0','alt_decision'=>'0','cancel'=>'0','team1_result'=>2,'team2_result'=>1,'team1_result_split'=>'1;2','team2_result_split'=>'0;1'];
if (Mapping::splitMode([$ordinary]) !== 'cumulative') throw new RuntimeException('Cumulative evidence missing.');
$incremental = array_replace($ordinary, ['team1_result_split'=>'1;1']);
if (Mapping::splitMode([$incremental]) !== 'incremental' || Mapping::splitMode([$ordinary, $incremental]) !== null) throw new RuntimeException('Mixed split evidence was guessed.');
$invalid = [
    array_replace($base, ['team1_result_ot'=>'<script>']),
    array_replace($base, ['team2_result_ot'=>null]),
    array_replace($base, ['alt_decision'=>1]),
    array_replace($base, ['team1_result'=>true]),
];
foreach ($invalid as $source) {
    try { Mapping::map($source, 'football'); } catch (InvalidArgumentException) { continue; }
    throw new RuntimeException('Malformed result was accepted.');
}
try { Mapping::map($base, 'basketball'); throw new RuntimeException('Cross-sport mapping accepted.'); } catch (InvalidArgumentException) {}
echo 'PASS: ' . count($cases) . " reviewed archive cases, PHP/Python parity, split evidence and invalid input guards.\n";
