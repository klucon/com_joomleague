<?php

declare(strict_types=1);

define('_JEXEC', 1);
require_once dirname(__DIR__, 2) . '/administrator/components/com_joomleague/src/Service/StageTransitionValidator.php';

use Joomleague\Component\Joomleague\Administrator\Service\StageTransitionValidator;

$validator = new StageTransitionValidator();
$rank = $validator->validate('standing_rank_range', '{"from":1,"to":4,"scope":"total"}', 'mutual_results');
if ($rank !== ['from' => 1, 'to' => 4, 'scope' => 'total']) throw new RuntimeException('Rank selector was not normalized.');
$match = $validator->validate('match_outcome', '{"outcome":"winner","round_id":8}', 'none');
if ($match !== ['outcome' => 'winner', 'round_id' => 8]) throw new RuntimeException('Match selector was not normalized.');
$validator->validate('all_entries', null, 'all_results');
$validator->assertAcyclic([['source' => 1, 'target' => 2], ['source' => 2, 'target' => 3]]);

foreach ([
	fn () => $validator->validate('football_winner', null, 'none'),
	fn () => $validator->validate('standing_rank_range', '{"from":4,"to":1,"scope":"total"}', 'none'),
	fn () => $validator->validate('manual', '{"unexpected":true}', 'none'),
	fn () => $validator->assertAcyclic([['source' => 1, 'target' => 2], ['source' => 2, 'target' => 1]]),
] as $invalid) {
	try { $invalid(); throw new RuntimeException('Invalid stage transition was accepted.'); } catch (InvalidArgumentException|DomainException) {}
}

echo "Stage transition contract OK\n";
