<?php

declare(strict_types=1);

define('_JEXEC', 1);
define('JPATH_BASE', '/var/www/html');
require_once JPATH_BASE . '/includes/defines.php';
require_once JPATH_BASE . '/includes/framework.php';
foreach (['UuidFactory.php', 'CanonicalJson.php', 'MatchResultValidationException.php', 'MatchResultDecimal.php', 'MatchResultAggregationValidator.php', 'MatchResultPayloadValidator.php', 'MatchResultRepository.php'] as $service) {
	require_once JPATH_ADMINISTRATOR . '/components/com_joomleague/src/Service/' . $service;
}

use Joomla\CMS\Factory;
use Joomla\Database\DatabaseInterface;
use Joomleague\Component\Joomleague\Administrator\Service\MatchResultRepository;

$container = Factory::getContainer();
$container->alias('session', 'session.cli')->alias('JSession', 'session.cli')
	->alias(Joomla\CMS\Session\Session::class, 'session.cli')->alias(Joomla\Session\Session::class, 'session.cli')
	->alias(Joomla\Session\SessionInterface::class, 'session.cli');
Factory::$application = $container->get(Joomla\Console\Application::class);
$db = $container->get(DatabaseInterface::class);
$repository = new MatchResultRepository($db);

/** @return array{match_id:int,participants:list<int>} */
$context = static function (string $code) use ($db): array {
	$query = $db->getQuery(true)->select('match.id')
		->from($db->quoteName('#__joomleague_project_match', 'match'))
		->innerJoin($db->quoteName('#__joomleague_project', 'project') . ' ON project.id=match.project_id')
		->innerJoin($db->quoteName('#__joomleague_sport_profile_version', 'version') . ' ON version.id=project.profile_version_id')
		->innerJoin($db->quoteName('#__joomleague_sport_profile', 'profile') . ' ON profile.id=version.profile_id')
		->innerJoin($db->quoteName('#__joomleague_match_result', 'result') . ' ON result.match_id=match.id')
		->where('profile.code=:code')->where('result.status_code=' . $db->quote('final'))->bind(':code', $code)
		->order('match.id ASC');
	$matchId = (int) $db->setQuery($query, 0, 1)->loadResult();
	if ($matchId < 1) throw new RuntimeException('No completed demo item for ' . $code . '.');
	$query = $db->getQuery(true)->select('id')->from($db->quoteName('#__joomleague_match_participant'))
		->where('match_id=:match')->where('published=1')->bind(':match', $matchId)->order('slot_number ASC');
	$participants = array_map('intval', $db->setQuery($query)->loadColumn());
	if (count($participants) !== 2) throw new RuntimeException($code . ' structured scenario requires two participants.');
	return ['match_id' => $matchId, 'participants' => $participants];
};

$numericValue = static fn(int $participant, int|float|string $number, ?int $rank = null, array $metadata = []): array => [
	'participant_id' => $participant, 'numeric_value' => (string) $number, 'text_value' => null,
	'status_code' => null, 'result_rank' => $rank, 'metadata' => $metadata,
];
$segment = static fn(string $code, int $ordinal, int $sequence, array $values, array $metadata = [], array $children = []): array => [
	'level_code' => $code, 'segment_type_ordinal' => $ordinal, 'sequence_number' => $sequence,
	'status_code' => 'completed', 'metadata' => $metadata, 'values' => $values, 'children' => $children,
];
$payload = static fn(string $type, string $scenario, array $root, string $notes): array => [
	'result_type' => $type, 'status_code' => 'final', 'outcome_code' => 'completed',
	'finalized_at' => '2035-08-10 20:00:00', 'notes' => $notes,
	'metadata' => ['demo_scenario' => $scenario], 'segments' => [$root],
];

// Timed sports with profile-valid aggregate scores and conditional deciding segments.
$timedScenarios = [
	'basketball' => ['periods' => [[22, 20], [18, 22], [19, 17], [21, 21]], 'extra' => [[9, 7]], 'shootout' => null],
	'ice_hockey' => ['periods' => [[1, 0], [0, 2], [1, 0]], 'extra' => [[0, 0]], 'shootout' => [2, 1]],
	'floorball' => ['periods' => [[2, 1], [0, 1], [1, 1]], 'extra' => [[0, 0]], 'shootout' => [3, 2]],
	'futsal' => ['periods' => [[2, 1], [1, 2]], 'extra' => [[0, 0], [0, 0]], 'shootout' => [5, 4]],
	'rugby' => ['periods' => [[17, 12], [14, 10]], 'extra' => [], 'shootout' => null],
];
foreach ($timedScenarios as $code => $scenario) {
	$ctx = $context($code); [$a, $b] = $ctx['participants']; $children = []; $totals = [0, 0];
	foreach ($scenario['periods'] as $index => [$left, $right]) {
		$totals[0] += $left; $totals[1] += $right;
		$children[] = $segment('period', 10, $index + 1, [$numericValue($a, $left), $numericValue($b, $right)]);
	}
	foreach ($scenario['extra'] as $index => [$left, $right]) {
		$totals[0] += $left; $totals[1] += $right;
		$children[] = $segment('extra_time', 20, $index + 1, [$numericValue($a, $left), $numericValue($b, $right)]);
	}
	if (is_array($scenario['shootout'])) {
		$children[] = $segment('shootout', 30, 1, [$numericValue($a, $scenario['shootout'][0]), $numericValue($b, $scenario['shootout'][1])], ['decider' => true]);
	}
	$rankA = $totals[0] === $totals[1] && is_array($scenario['shootout']) ? 1 : ($totals[0] > $totals[1] ? 1 : 2);
	$rankB = $rankA === 1 ? 2 : 1;
	$root = $segment('result', 0, 1, [$numericValue($a, $totals[0], $rankA), $numericValue($b, $totals[1], $rankB)], [], $children);
	$repository->replace($ctx['match_id'], $payload('numeric_score', $code . '_structured_score', $root, 'Fictional profile-valid period and deciding-segment scenario.'), 0);
}

// Bowling game totals.
$ctx = $context('bowling'); [$a, $b] = $ctx['participants']; $games = []; $totals = [0, 0];
foreach ([[238, 221], [204, 226], [247, 219]] as $index => [$left, $right]) {
	$totals[0] += $left; $totals[1] += $right;
	$games[] = $segment('game', 10, $index + 1, [$numericValue($a, $left), $numericValue($b, $right)], ['lane_pair' => $index + 1]);
}
$repository->replace($ctx['match_id'], $payload('numeric_score', 'bowling_three_games', $segment('result', 0, 1,
	[$numericValue($a, $totals[0], 1), $numericValue($b, $totals[1], 2)], [], $games), 'Fictional three-game bowling series.'), 0);

// Chess board-by-board score, including two draws.
$ctx = $context('chess'); [$a, $b] = $ctx['participants']; $boards = [];
foreach ([['1', '0'], ['0.5', '0.5'], ['0', '1'], ['0.5', '0.5']] as $index => [$left, $right]) {
	$boards[] = $segment('board', 10, $index + 1, [$numericValue($a, $left), $numericValue($b, $right)], ['board_number' => $index + 1]);
}
$repository->replace($ctx['match_id'], $payload('numeric_score', 'chess_four_boards', $segment('result', 0, 1,
	[$numericValue($a, '2', 1), $numericValue($b, '2', 1)], [], $boards), 'Fictional four-board team match with decisive games and draws.'), 0);

// Football knockout: tied regulation and extra time, decided by a shootout without affecting the league stage.
$query = $db->getQuery(true)->select('match.id')->from($db->quoteName('#__joomleague_project_match', 'match'))
	->innerJoin($db->quoteName('#__joomleague_project_stage', 'stage') . ' ON stage.id=match.stage_id')
	->innerJoin($db->quoteName('#__joomleague_project', 'project') . ' ON project.id=match.project_id')
	->innerJoin($db->quoteName('#__joomleague_sport_profile_version', 'version') . ' ON version.id=project.profile_version_id')
	->innerJoin($db->quoteName('#__joomleague_sport_profile', 'profile') . ' ON profile.id=version.profile_id')
	->where('profile.code=' . $db->quote('football'))->where('stage.stage_type=' . $db->quote('knockout'))
	->order('match.id ASC');
$footballMatchId = (int) $db->setQuery($query, 0, 1)->loadResult();
if ($footballMatchId < 1) throw new RuntimeException('No football knockout item exists.');
$query = $db->getQuery(true)->select('id')->from($db->quoteName('#__joomleague_match_participant'))
	->where('match_id=:match')->where('published=1')->bind(':match', $footballMatchId)->order('slot_number ASC');
$footballParticipants = array_map('intval', $db->setQuery($query)->loadColumn());
if (count($footballParticipants) !== 2) throw new RuntimeException('Football knockout item requires two participants.');
[$a, $b] = $footballParticipants;
$footballSegments = [
	$segment('period', 10, 1, [$numericValue($a, 1), $numericValue($b, 0)]),
	$segment('period', 10, 2, [$numericValue($a, 0), $numericValue($b, 1)]),
	$segment('extra_time', 20, 1, [$numericValue($a, 0), $numericValue($b, 0)]),
	$segment('extra_time', 20, 2, [$numericValue($a, 0), $numericValue($b, 0)]),
	$segment('shootout', 30, 1, [$numericValue($a, 5), $numericValue($b, 4)], ['decider' => true]),
];
$repository->replace($footballMatchId, $payload('numeric_score', 'football_knockout_shootout', $segment('result', 0, 1,
	[$numericValue($a, 1, 1), $numericValue($b, 1, 2)], ['winner_decided_by' => 'shootout'], $footballSegments),
	'Fictional knockout tie decided by a penalty shootout.'), 0);
$matchUpdate = (object) ['id' => $footballMatchId, 'status_code' => 'finished'];
$db->updateObject('#__joomleague_project_match', $matchUpdate, 'id');

/** Build a nested two-participant result whose root is the number of won top-level segments. */
$nestedResult = static function (string $code, array $topScores, callable $childBuilder) use ($context, $repository, $numericValue, $segment, $payload): void {
	$ctx = $context($code); [$a, $b] = $ctx['participants']; $topSegments = []; $wins = [0, 0];
	foreach ($topScores as $index => [$left, $right]) {
		if ($left > $right) $wins[0]++; elseif ($right > $left) $wins[1]++;
		$topSegments[] = $segment($code === 'esports' ? 'map' : 'set', 10, $index + 1,
			[$numericValue($a, $left > $right ? 1 : 0), $numericValue($b, $right > $left ? 1 : 0)],
			['final_score' => $left . ':' . $right], $childBuilder($a, $b, $index, $left, $right));
	}
	$repository->replace($ctx['match_id'], $payload('nested_score', $code . '_full_hierarchy', $segment('result', 0, 1,
		[$numericValue($a, $wins[0], $wins[0] > $wins[1] ? 1 : 2), $numericValue($b, $wins[1], $wins[1] > $wins[0] ? 1 : 2)], [], $topSegments),
		'Fictional complete nested result hierarchy.'), 0);
};

$nestedResult('volleyball', [[25, 21], [23, 25], [25, 18], [22, 25], [15, 12]],
	static fn(int $a, int $b, int $set, int $left, int $right): array => [$segment('point', 10, 1,
		[$numericValue($a, $left), $numericValue($b, $right)], ['set_number' => $set + 1])]);

$nestedResult('esports', [[13, 9], [10, 13], [13, 11]],
	static function (int $a, int $b, int $map, int $left, int $right) use ($segment, $numericValue): array {
		$rounds = [];
		for ($round = 1; $round <= min(6, $left + $right); $round++) {
			$winnerA = (($round + $map) % 3) !== 0;
			$rounds[] = $segment('round', 10, $round, [$numericValue($a, $winnerA ? 1 : 0), $numericValue($b, $winnerA ? 0 : 1)]);
		}
		return $rounds;
	});

$nestedResult('tennis', [[6, 4], [3, 6], [7, 6]],
	static function (int $a, int $b, int $setIndex, int $left, int $right) use ($segment, $numericValue): array {
		$games = [];
		for ($game = 1; $game <= min(6, $left + $right); $game++) {
			$winnerA = (($game + $setIndex) % 3) !== 0;
			$points = [$segment('point', 10, 1, [$numericValue($a, $winnerA ? 4 : 2), $numericValue($b, $winnerA ? 2 : 4)], ['score' => $winnerA ? '40:30' : '30:40'])];
			$games[] = $segment('game', 10, $game, [$numericValue($a, $winnerA ? 1 : 0), $numericValue($b, $winnerA ? 0 : 1)], [], $points);
		}
		return $games;
	});

$nestedResult('darts', [[3, 1], [2, 3], [3, 2]],
	static function (int $a, int $b, int $setIndex, int $left, int $right) use ($segment, $numericValue): array {
		$legs = [];
		for ($leg = 1; $leg <= $left + $right; $leg++) {
			$winnerA = (($leg + $setIndex) % 3) !== 0;
			$points = [$segment('point', 10, 1, [$numericValue($a, $winnerA ? 0 : 32), $numericValue($b, $winnerA ? 40 : 0)], ['checkout' => $winnerA ? 64 : 72])];
			$legs[] = $segment('leg', 10, $leg, [$numericValue($a, $winnerA ? 1 : 0), $numericValue($b, $winnerA ? 0 : 1)], [], $points);
		}
		return $legs;
	});

echo "Structured result enrichment: football knockout, five timed sports, bowling, chess and four nested-score sports saved.\n";
