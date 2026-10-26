<?php
declare(strict_types=1);
define('_JEXEC', 1);
require __DIR__ . '/../../administrator/components/com_joomleague/src/Service/ClubRivalHistoryReader.php';
require __DIR__ . '/../../administrator/components/com_joomleague/src/Service/StandingsDecimal.php';

$class = new ReflectionClass(Joomleague\Component\Joomleague\Domain\Service\ClubRivalHistoryReader::class);
$reader = $class->newInstanceWithoutConstructor();
$compare = $class->getMethod('compare');
foreach ([
    ['3.125', '3.124999999', null, null, true, 1],
    ['3.125', '3.124999999', null, null, false, -1],
    ['0.000000000', '0', null, null, true, 0],
    ['1', '9', 1, 2, true, 1],
    ['9', '1', 2, 1, true, -1],
    ['9', '1', 1, 1, true, 0],
] as [$ownValue, $otherValue, $ownRank, $otherRank, $higher, $expected]) {
    $own = (object) ['numeric_value' => $ownValue, 'result_rank' => $ownRank];
    $other = (object) ['numeric_value' => $otherValue, 'result_rank' => $otherRank];
    if ($compare->invoke($reader, $own, $other, $higher) !== $expected) throw new RuntimeException('Rival result policy regression');
}
$pairs = $class->getMethod('participantPairs');
$rows = (static function (): Generator { foreach ([10, 10, 20, 20, 20, 30, 30] as $id) yield (object) ['match_id' => $id]; })();
$groups = iterator_to_array($pairs->invoke($reader, $rows));
if (array_map('count', $groups) !== [2, 3, 2]) throw new RuntimeException('Streaming event grouping changed');
$root = dirname(__DIR__, 2);
$source = file_get_contents($root . '/administrator/components/com_joomleague/src/Service/ClubRivalHistoryReader.php');
foreach (['active_participant.published = 1) = 2', 'stage.published = 1', 'round.published = 1', "\$filterValues[\$filter]", '$detailLimit = 25', 'getIterator()'] as $required) {
    if (!str_contains($source, $required)) throw new RuntimeException('Missing rival safety/bounds contract: ' . $required);
}
if (str_contains($source, '$rows = $db->setQuery($query)->loadObjectList()')) throw new RuntimeException('Unbounded participant hydration returned');
echo "PASS: rival decimal/rank policy, streamed event groups, independent bindings, visibility and bounded details\n";
