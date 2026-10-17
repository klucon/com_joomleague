<?php

declare(strict_types=1);

define('_JEXEC', 1);
require dirname(__DIR__, 2) . '/administrator/components/com_joomleague/src/Service/ProfileMigrationPlan.php';

use Joomleague\Component\Joomleague\Administrator\Service\ProfileMigrationPlan;

$plan = new ProfileMigrationPlan();
$base = ['version' => '1.0.0', 'rules' => ['period_count' => 2], 'roles' => [['code' => 'keeper', 'name_key' => 'KEEPER']]];
if ($plan->compare($base, $base) !== []) throw new RuntimeException('Identical contracts must have no changes.');
$next = $base;
$next['version'] = '1.1.0';
$next['roles'][0]['name_key'] = 'GOALKEEPER';
foreach ($plan->compare($base, $next) as $change) {
    if (!$change['compatible']) throw new RuntimeException('Display metadata should be compatible.');
}
foreach (['rule', 'role', 'deletion', 'addition'] as $case) {
    $next = $base;
    if ($case === 'rule') $next['rules']['period_count'] = 3;
    if ($case === 'role') $next['roles'][0]['code'] = 'defender';
    if ($case === 'deletion') unset($next['roles']);
    if ($case === 'addition') $next['new_contract'] = ['enabled' => true];
    $blocked = array_filter($plan->compare($base, $next), static fn (array $change): bool => !$change['compatible']);
    if ($blocked === []) throw new RuntimeException("Semantic change was not blocked: $case");
}
$changes = $plan->compare(['a/b~c' => 1], ['a/b~c' => 2]);
if ($changes[0]['path'] !== '/a~1b~0c') throw new RuntimeException('Invalid JSON pointer escaping.');
echo "Profile migration compatibility plan OK\n";
