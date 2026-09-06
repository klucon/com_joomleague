<?php

declare(strict_types=1);

define('_JEXEC', 1);
define('JPATH_BASE', '/var/www/html');
require_once JPATH_BASE . '/includes/defines.php';
require_once JPATH_BASE . '/includes/framework.php';

foreach ([
	'UuidFactory.php', 'CanonicalJson.php', 'MatchResultValidationException.php', 'MatchResultDuration.php',
	'MatchResultDecimal.php', 'MatchResultAggregationValidator.php', 'MatchResultPayloadValidator.php',
	'MatchResultRepository.php',
] as $service) {
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

/** @return array{match_id:int,participant_ids:list<int>} */
$context = static function (string $profileCode) use ($db): array {
	$query = $db->getQuery(true)
		->select($db->quoteName('match.id'))
		->from($db->quoteName('#__joomleague_project_match', 'match'))
		->innerJoin($db->quoteName('#__joomleague_project', 'project') . ' ON project.id = match.project_id')
		->innerJoin($db->quoteName('#__joomleague_sport_profile_version', 'version') . ' ON version.id = project.profile_version_id')
		->innerJoin($db->quoteName('#__joomleague_sport_profile', 'profile') . ' ON profile.id = version.profile_id')
		->innerJoin($db->quoteName('#__joomleague_match_result', 'result') . ' ON result.match_id = match.id')
		->where('profile.code = :code')->where('result.status_code = ' . $db->quote('final'))
		->bind(':code', $profileCode)->order('match.id ASC');
	$matchId = (int) $db->setQuery($query, 0, 1)->loadResult();
	if ($matchId < 1) throw new RuntimeException('No completed demo item exists for profile ' . $profileCode . '.');

	$query = $db->getQuery(true)->select($db->quoteName('id'))
		->from($db->quoteName('#__joomleague_match_participant'))
		->where('match_id = :matchId')->where('published = 1')->bind(':matchId', $matchId)
		->order('slot_number ASC');
	$participantIds = array_map('intval', $db->setQuery($query)->loadColumn());
	if ($participantIds === []) throw new RuntimeException('The selected demo item has no participants.');

	return ['match_id' => $matchId, 'participant_ids' => $participantIds];
};

$duration = static function (int $milliseconds): string {
	$hours = intdiv($milliseconds, 3_600_000); $milliseconds %= 3_600_000;
	$minutes = intdiv($milliseconds, 60_000); $milliseconds %= 60_000;
	$seconds = intdiv($milliseconds, 1_000); $milliseconds %= 1_000;
	return sprintf('%02d:%02d:%02d.%03d', $hours, $minutes, $seconds, $milliseconds);
};

$value = static fn(int $participantId, ?string $time, ?string $status, ?int $rank, array $metadata = []): array => [
	'participant_id' => $participantId, 'numeric_value' => null, 'text_value' => $time,
	'status_code' => $status, 'result_rank' => $rank, 'metadata' => $metadata,
];

$segment = static fn(string $code, int $ordinal, int $sequence, array $values, array $metadata = [], array $children = []): array => [
	'level_code' => $code, 'segment_type_ordinal' => $ordinal, 'sequence_number' => $sequence,
	'status_code' => 'completed', 'metadata' => $metadata, 'values' => $values, 'children' => $children,
];

// Motorsport: practice, qualifying and race sessions with five timed laps and classified non-finishers.
$motor = $context('motorsport');
$motorValues = []; $sessions = [];
foreach ($motor['participant_ids'] as $index => $participantId) {
	$status = $index === count($motor['participant_ids']) - 1 ? 'dsq' : ($index === count($motor['participant_ids']) - 2 ? 'dnf' : 'finished');
	$rank = in_array($status, ['dnf', 'dsq'], true) ? null : $index + 1;
	$motorValues[] = $value($participantId, $duration(5_421_000 + ($index * 17_350)), $status, $rank, [
		'grid_position' => (($index + 3) % count($motor['participant_ids'])) + 1,
		'laps_completed' => $status === 'finished' ? 5 : ($status === 'dnf' ? 3 : 5),
	]);
}
foreach (['practice', 'qualifying', 'race'] as $sessionIndex => $sessionName) {
	$sessionValues = []; $laps = [];
	foreach ($motor['participant_ids'] as $index => $participantId) {
		$sessionValues[] = $value($participantId, $duration(88_000 + ($sessionIndex * 1_700) + ($index * 430)), 'active', null);
	}
	if ($sessionName === 'race') {
		for ($lap = 1; $lap <= 5; $lap++) {
			$lapValues = [];
			foreach ($motor['participant_ids'] as $index => $participantId) {
				$lapValues[] = $value($participantId, $duration(86_500 + ($lap * 260) + ($index * 390)), 'active', null,
					$lap === 4 && $index === 0 ? ['fastest_lap' => true] : []);
			}
			$laps[] = $segment('lap', 10, $lap, $lapValues, ['lap_number' => $lap]);
		}
	}
	$sessions[] = $segment('session', 10, $sessionIndex + 1, $sessionValues, ['session_code' => $sessionName], $laps);
}
$repository->replace($motor['match_id'], [
	'result_type' => 'time_result', 'status_code' => 'final', 'outcome_code' => 'classified',
	'finalized_at' => '2035-08-04 15:45:00', 'notes' => 'Fictional classification with practice, qualifying, laps, DNF and DSQ examples.',
	'metadata' => ['demo_scenario' => 'motorsport_classification', 'track_length_km' => 4.2],
	'segments' => [$segment('result', 0, 1, $motorValues, ['classification' => 'official'], $sessions)],
], 0);

// Running race: category/bib metadata, gun and chip times, three splits and two laps.
$run = $context('running_race');
$runValues = []; $gunValues = []; $chipValues = []; $runChildren = [];
foreach ($run['participant_ids'] as $index => $participantId) {
	$isDnf = $index === count($run['participant_ids']) - 1;
	$chipMs = 2_145_000 + ($index * 21_730); $gunMs = $chipMs + 2_400 + (($index % 5) * 850);
	$metadata = ['bib_number' => 101 + $index, 'category' => $index % 2 === 0 ? 'open' : 'masters'];
	$runValues[] = $value($participantId, $isDnf ? $duration(1_420_000) : $duration($chipMs), $isDnf ? 'dnf' : 'finished', $isDnf ? null : $index + 1, $metadata);
	$gunValues[] = $value($participantId, $duration($gunMs), $isDnf ? 'dnf' : 'finished', null);
	$chipValues[] = $value($participantId, $duration($chipMs), $isDnf ? 'dnf' : 'finished', null);
}
$runChildren[] = $segment('gun_time', 10, 1, $gunValues, ['timing_point' => 'finish']);
$runChildren[] = $segment('chip_time', 20, 1, $chipValues, ['timing_point' => 'finish']);
for ($split = 1; $split <= 3; $split++) {
	$values = [];
	foreach ($run['participant_ids'] as $index => $participantId) $values[] = $value($participantId,
		$duration((690_000 * $split) + ($index * 6_900 * $split)), $index === count($run['participant_ids']) - 1 && $split === 3 ? 'dnf' : 'started', null);
	$runChildren[] = $segment('split', 30, $split, $values, ['distance_km' => $split * 2.5]);
}
for ($lap = 1; $lap <= 2; $lap++) {
	$values = [];
	foreach ($run['participant_ids'] as $index => $participantId) $values[] = $value($participantId,
		$duration(1_060_000 + ($index * 10_600) + ($lap * 3_200)), 'started', null);
	$runChildren[] = $segment('lap', 40, $lap, $values, ['lap_number' => $lap]);
}
$repository->replace($run['match_id'], [
	'result_type' => 'time_result', 'status_code' => 'final', 'outcome_code' => 'classified',
	'finalized_at' => '2035-08-05 10:55:00', 'notes' => 'Fictional race classification with categories, timing mats, laps and one DNF.',
	'metadata' => ['demo_scenario' => 'running_race_classification', 'distance_km' => 7.5, 'timing' => 'chip'],
	'segments' => [$segment('result', 0, 1, $runValues, ['classification' => 'overall'], $runChildren)],
], 0);

// Combat sports: three judges, each with a complete three-round 10-point scorecard.
$combat = $context('mma_boxing');
if (count($combat['participant_ids']) !== 2) throw new RuntimeException('Combat demo requires exactly two participants.');
[$fighterA, $fighterB] = $combat['participant_ids'];
$judges = [];
$cards = [
	[[10, 9], [9, 10], [10, 9]],
	[[10, 9], [10, 9], [9, 10]],
	[[10, 9], [9, 10], [10, 9]],
];
foreach ($cards as $judgeIndex => $roundScores) {
	$rounds = [];
	foreach ($roundScores as $roundIndex => [$scoreA, $scoreB]) {
		$rounds[] = $segment('round', 10, $roundIndex + 1, [
			['participant_id' => $fighterA, 'numeric_value' => (string) $scoreA, 'text_value' => null, 'status_code' => 'active', 'result_rank' => null, 'metadata' => []],
			['participant_id' => $fighterB, 'numeric_value' => (string) $scoreB, 'text_value' => null, 'status_code' => 'active', 'result_rank' => null, 'metadata' => []],
		], ['round_number' => $roundIndex + 1]);
	}
	$judges[] = $segment('judge', 10, $judgeIndex + 1, [], ['judge' => 'Demo Judge ' . ($judgeIndex + 1)], $rounds);
}
$repository->replace($combat['match_id'], [
	'result_type' => 'decision_result', 'status_code' => 'final', 'outcome_code' => 'decision',
	'finalized_at' => '2035-08-06 20:25:00', 'notes' => 'Fictional split-decision example with three complete judge scorecards.',
	'metadata' => ['demo_scenario' => 'combat_scorecards', 'decision_type' => 'split_decision', 'weight_class' => 'demo_middleweight'],
	'segments' => [$segment('result', 0, 1, [
		['participant_id' => $fighterA, 'numeric_value' => '1', 'text_value' => null, 'status_code' => 'winner', 'result_rank' => 1, 'metadata' => []],
		['participant_id' => $fighterB, 'numeric_value' => '0', 'text_value' => null, 'status_code' => 'loser', 'result_rank' => 2, 'metadata' => []],
	], ['method' => 'split_decision'], $judges)],
], 0);

echo "Individual result enrichment: motorsport classification, running race timing and combat scorecards saved.\n";
