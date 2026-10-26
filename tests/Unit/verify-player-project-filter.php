<?php
declare(strict_types=1);
define('_JEXEC', 1);
require __DIR__ . '/../../administrator/components/com_joomleague/src/Service/StandingsDecimal.php';
require __DIR__ . '/../../components/com_joomleague/src/Service/PlayerHistoryReader.php';
use Joomleague\Component\Joomleague\Site\Service\PlayerHistoryReader;
$row = static fn(int $project, string $value): array => ['project_id' => $project, 'totals' => ['played' => 1, 'started' => 1, 'in' => 0, 'out' => 0, 'minutes' => 90.0, 'unknown_minutes' => 0, 'values' => ['goals' => $value]]];
$profiles = ['football' => ['projects' => [$row(7, '0.1'), $row(7, '0.2'), $row(8, '100')], 'totals' => ['played' => 3]], 'other' => ['projects' => [$row(9, '50')], 'totals' => ['played' => 1]]];
$before = serialize($profiles);
if (PlayerHistoryReader::profilesForProject($profiles, 0) !== $profiles) throw new RuntimeException('Career changed');
$result = PlayerHistoryReader::profilesForProject($profiles, 7);
if (count($result) !== 1 || count($result['football']['projects']) !== 2 || $result['football']['totals']['played'] !== 2 || (float) $result['football']['totals']['values']['goals'] !== 0.3) throw new RuntimeException('Project totals incorrect');
if (PlayerHistoryReader::profilesForProject($profiles, 999) !== []) throw new RuntimeException('Invalid project leaked statistics');
if (serialize($profiles) !== $before) throw new RuntimeException('Input mutated');
echo "PASS: full career, multiple entries, exact decimal sum, profile isolation, invalid project, non-mutation.\n";
