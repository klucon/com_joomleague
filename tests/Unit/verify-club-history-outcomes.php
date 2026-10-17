<?php
declare(strict_types=1);
define('_JEXEC', 1);
require __DIR__ . '/../../administrator/components/com_joomleague/src/Service/ClubPersonHistoryReader.php';
require __DIR__ . '/../../administrator/components/com_joomleague/src/Service/StandingsDecimal.php';
$class = new ReflectionClass(Joomleague\Component\Joomleague\Domain\Service\ClubPersonHistoryReader::class);
$reader = $class->newInstanceWithoutConstructor();
$decimal = Joomleague\Component\Joomleague\Domain\Service\StandingsDecimal::class;
foreach (['1.000000000' => '1', '0.000000000' => '0', '3.125000000' => '3.125', '-0.500000000' => '-0.5', '999999999999999999999.123456789' => '999999999999999999999.123456789'] as $input => $expected) {
    if ($decimal::normalize($input) !== $expected) throw new RuntimeException('History decimal formatting regression.');
}
$method = $class->getMethod('outcome');
foreach ([
    [null, null, '3', '2', true, 'wins'],
    [null, null, '3', '2', false, 'losses'],
    [null, null, '2.00', '2', true, 'draws'],
    [1, 2, '3', '2', false, 'wins'],
    [2, 1, '3', '2', true, 'losses'],
    [1, 1, null, null, true, 'draws'],
    [null, null, null, '2', true, null],
] as [$ownRank, $opponentRank, $ownValue, $opponentValue, $higher, $expected]) {
    $row = (object) ['own_rank' => $ownRank, 'opponent_rank' => $opponentRank, 'own_value' => $ownValue, 'opponent_value' => $opponentValue];
    if ($method->invoke($reader, $row, $higher) !== $expected) throw new RuntimeException('History outcome regression.');
}
$source = file_get_contents(__DIR__ . '/../../administrator/components/com_joomleague/src/Service/ClubPersonHistoryReader.php');
if (str_contains($source, 'profile.payload_json AS profile_json')) throw new RuntimeException('Profile payload repeated per lineup.');
echo "PASS: history outcomes preserve rank precedence, score direction and missing results\n";
