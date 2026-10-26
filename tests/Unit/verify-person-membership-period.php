<?php
declare(strict_types=1);
define('_JEXEC', 1);
require __DIR__ . '/../../administrator/components/com_joomleague/src/Service/PersonMembershipPeriod.php';
use Joomleague\Component\Joomleague\Domain\Service\PersonMembershipPeriod;
$cases = [
    [[], 'undated'],
    [['lifecycle_state' => 'active', 'project_lifecycle_state' => 'draft', 'project_start_date' => '2023-08-01'], 'undated'],
    [['valid_from' => '2026-10-24'], 'current'],
    [['valid_until' => '2026-10-24'], 'current'],
    [['valid_until' => '2026-10-23'], 'history'],
    [['valid_from' => '2026-10-25'], 'upcoming'],
    [['lifecycle_state' => 'departed'], 'history'],
    [['project_lifecycle_state' => 'archived'], 'history'],
    [['project_lifecycle_state' => 'completed'], 'history'],
    [['project_lifecycle_state' => 'active'], 'current'],
    [['project_end_date' => '2026-10-23'], 'history'],
    [['season_end_date' => '2026-06-30'], 'history'],
    [['season_start_date' => '2026-11-01'], 'upcoming'],
    [['season_end_date' => '2026-12-31'], 'current'],
    [['valid_from' => '2020-01-01', 'season_end_date' => '2026-06-30'], 'history'],
    [['valid_from' => '2026-11-01', 'project_end_date' => '2026-10-01'], 'undated'],
    [['project_end_date' => '2026-12-31', 'season_end_date' => '2025-12-31'], 'current'],
    [['project_start_date' => '2026-01-01', 'season_start_date' => '2027-01-01', 'project_end_date' => '2026-12-31'], 'current'],
    [['valid_from' => '2026-02-30'], 'undated'],
    [['valid_until' => '0000-00-00'], 'undated'],
    [['project_name' => 'League 1900', 'season_name' => '1900/1901'], 'undated'],
];
foreach ($cases as [$input, $expected]) {
    $row = (object) $input;
    $before = serialize($row);
    if (PersonMembershipPeriod::classify($row, '2026-10-24') !== $expected) throw new RuntimeException('Membership period regression: ' . json_encode($input));
    if (serialize($row) !== $before) throw new RuntimeException('Classification mutated membership');
}
try { PersonMembershipPeriod::classify((object) [], 'invalid'); throw new RuntimeException('Invalid date accepted'); }
catch (InvalidArgumentException) {}
echo json_encode(['passed' => count($cases) + 1, 'policy' => 'explicit dates, terminal project state, conservative undated fallback, no writes'], JSON_THROW_ON_ERROR), PHP_EOL;
