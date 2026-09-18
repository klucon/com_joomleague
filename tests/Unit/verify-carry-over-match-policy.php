<?php

declare(strict_types=1);

define('_JEXEC', 1);
require_once dirname(__DIR__, 2) . '/administrator/components/com_joomleague/src/Service/CarryOverMatchPolicy.php';

use Joomleague\Component\Joomleague\Domain\Service\CarryOverMatchPolicy;

$qualifiedA = [10 => true];
$qualifiedBoth = [10 => true, 20 => true];
$qualifiedNeither = [30 => true];
$participants = [10, 20];

if (!CarryOverMatchPolicy::includes('all_results', $participants, $qualifiedA)) {
	throw new RuntimeException('All-results did not carry a result against an eliminated participant.');
}
if (!CarryOverMatchPolicy::includes('all_results', $participants, $qualifiedBoth)) {
	throw new RuntimeException('All-results did not carry a result between two qualified participants.');
}
if (CarryOverMatchPolicy::includes('all_results', $participants, $qualifiedNeither)) {
	throw new RuntimeException('All-results carried a result without a qualified participant.');
}
if (CarryOverMatchPolicy::includes('mutual_results', $participants, $qualifiedA)) {
	throw new RuntimeException('Mutual-results carried a result against an eliminated participant.');
}
if (!CarryOverMatchPolicy::includes('mutual_results', $participants, $qualifiedBoth)) {
	throw new RuntimeException('Mutual-results did not carry a result between qualified participants.');
}
if (CarryOverMatchPolicy::includes('none', $participants, $qualifiedBoth)) {
	throw new RuntimeException('Disabled carry-over accepted a source result.');
}
if (CarryOverMatchPolicy::includes('all_results', [], $qualifiedBoth)) {
	throw new RuntimeException('Carry-over accepted a result without participants.');
}

try {
	CarryOverMatchPolicy::includes('unknown', $participants, $qualifiedBoth);
	throw new RuntimeException('Unknown carry-over mode was accepted.');
} catch (InvalidArgumentException) {
}

echo "Stage carry-over match policy OK\n";
