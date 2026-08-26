<?php

declare(strict_types=1);

define('_JEXEC', 1);
define('JPATH_BASE', '/var/www/html');
$_SERVER['HTTP_HOST'] ??= 'localhost';
$_SERVER['REQUEST_URI'] ??= '/';
$_SERVER['SCRIPT_NAME'] = '/index.php';
require_once JPATH_BASE . '/includes/defines.php';
require_once JPATH_BASE . '/includes/framework.php';

foreach (['UuidFactory.php', 'CanonicalJson.php', 'StageTransitionValidator.php', 'StageProgressionService.php', 'MatchResultValidationException.php', 'MatchResultDecimal.php', 'MatchResultAggregationValidator.php', 'MatchResultPayloadValidator.php', 'MatchResultRepository.php', 'StandingsContractValidator.php', 'StandingsDecimal.php', 'StandingsCalculator.php', 'StandingsReader.php', 'StandingsRecalculator.php', 'StandingsSnapshotSynchronizer.php'] as $service) {
	require_once JPATH_ADMINISTRATOR . '/components/com_joomleague/src/Service/' . $service;
}
require_once JPATH_ADMINISTRATOR . '/components/com_joomleague/src/Table/StagetransitionTable.php';
require_once JPATH_ADMINISTRATOR . '/components/com_joomleague/src/Table/StandingadjustmentTable.php';
require_once JPATH_ADMINISTRATOR . '/components/com_joomleague/src/Service/StandingsCascadeTrigger.php';

use Joomla\CMS\Factory;
use Joomla\Database\DatabaseInterface;
use Joomleague\Component\Joomleague\Administrator\Service\MatchResultRepository;
use Joomleague\Component\Joomleague\Administrator\Service\StageProgressionService;
use Joomleague\Component\Joomleague\Administrator\Service\StandingsCascadeTrigger;
use Joomleague\Component\Joomleague\Administrator\Table\StandingadjustmentTable;
use Joomleague\Component\Joomleague\Domain\Service\StandingsReader;
use Joomleague\Component\Joomleague\Domain\Service\StandingsRecalculator;
use Joomleague\Component\Joomleague\Domain\Service\StandingsSnapshotSynchronizer;
use Joomleague\Component\Joomleague\Domain\Service\UuidFactory;
use Joomleague\Component\Joomleague\Administrator\Table\StagetransitionTable;

$container = Factory::getContainer();
$container->alias('session', 'session.cli')->alias('JSession', 'session.cli')->alias(Joomla\CMS\Session\Session::class, 'session.cli')->alias(Joomla\Session\Session::class, 'session.cli')->alias(Joomla\Session\SessionInterface::class, 'session.cli');
Factory::$application = $container->get(Joomla\Console\Application::class);
$database = $container->get(DatabaseInterface::class);
$suffix = bin2hex(random_bytes(5));

$insert = static function (string $table, array $values) use ($database): int {
	$query = $database->getQuery(true)->insert($database->quoteName($table))->columns($database->quoteName(array_keys($values)));
	$placeholders = [];
	foreach ($values as $key => &$value) {
		$placeholders[] = ':' . $key;
		$query->bind(':' . $key, $value);
	}
	$query->values(implode(',', $placeholders));
	$database->setQuery($query)->execute();
	return (int) $database->insertid();
};

$resultPayload = static function (array $participantIds, array $periodScores): array {
	$rootScores = [
		$periodScores[0][0] + $periodScores[1][0],
		$periodScores[0][1] + $periodScores[1][1],
	];
	$values = static fn (array $scores): array => [
		['participant_id' => $participantIds[0], 'numeric_value' => (string) $scores[0]],
		['participant_id' => $participantIds[1], 'numeric_value' => (string) $scores[1]],
	];

	return [
		'result_type' => 'numeric_score',
		'status_code' => 'final',
		'outcome_code' => 'completed',
		'segments' => [[
			'level_code' => 'result',
			'values' => $values($rootScores),
			'children' => [
				['level_code' => 'period', 'sequence_number' => 1, 'values' => $values($periodScores[0])],
				['level_code' => 'period', 'sequence_number' => 2, 'values' => $values($periodScores[1])],
			],
		]],
	];
};

$profileQuery = $database->getQuery(true)->select('version.id')->from($database->quoteName('#__joomleague_sport_profile_version', 'version'))
	->innerJoin($database->quoteName('#__joomleague_sport_profile', 'profile') . ' ON profile.id = version.profile_id')
	->where('profile.code = ' . $database->quote('football'))->where('version.state = ' . $database->quote('active'))->order('version.id DESC');
$profileVersionId = (int) $database->setQuery($profileQuery, 0, 1)->loadResult();
if ($profileVersionId < 1) throw new RuntimeException('Active football profile is unavailable.');

$name = 'Standings fixture ' . $suffix;
$competitionId = $insert('#__joomleague_competition', ['uuid' => UuidFactory::v4(), 'name' => $name]);
$seasonId = $insert('#__joomleague_season', ['uuid' => UuidFactory::v4(), 'name' => $name]);
$sportTypeId = $insert('#__joomleague_sport_type', ['profile_version_id' => $profileVersionId, 'code' => 'standings-' . $suffix, 'name' => $name]);
$projectId = $insert('#__joomleague_project', ['uuid' => UuidFactory::v4(), 'competition_id' => $competitionId, 'season_id' => $seasonId, 'sport_type_id' => $sportTypeId, 'profile_version_id' => $profileVersionId, 'name' => $name, 'project_type' => 'league']);

try {
	$entries = [];
	foreach (['Alpha', 'Beta'] as $entryName) $entries[] = $insert('#__joomleague_project_entry', ['uuid' => UuidFactory::v4(), 'project_id' => $projectId, 'entry_kind' => 'group', 'display_name' => $entryName]);
	$stageId = $insert('#__joomleague_project_stage', ['uuid' => UuidFactory::v4(), 'project_id' => $projectId, 'name' => 'League', 'code' => 'league', 'stage_type' => 'league']);
	$targetStageId = $insert('#__joomleague_project_stage', ['uuid' => UuidFactory::v4(), 'project_id' => $projectId, 'name' => 'Final', 'code' => 'final', 'stage_type' => 'knockout']);
	$transition = new StagetransitionTable($database); $transition->bind(['uuid' => UuidFactory::v4(), 'project_id' => $projectId, 'source_stage_id' => $stageId, 'target_stage_id' => $targetStageId, 'code' => 'league_to_final', 'name' => 'League to final', 'selector_type' => 'standing_rank_range', 'selector_config_json' => '{"from":1,"to":2,"scope":"total"}', 'carry_over_mode' => 'none']);
	if (!$transition->check() || !$transition->store()) throw new RuntimeException('Valid stage progression could not be stored: ' . $transition->getError());
	$cycle = new StagetransitionTable($database); $cycle->bind(['uuid' => UuidFactory::v4(), 'project_id' => $projectId, 'source_stage_id' => $targetStageId, 'target_stage_id' => $stageId, 'code' => 'invalid_cycle', 'name' => 'Invalid cycle', 'selector_type' => 'manual', 'carry_over_mode' => 'none']);
	if ($cycle->check() || $cycle->getError() === '') throw new RuntimeException('Cyclic stage progression was accepted.');
	$roundId = $insert('#__joomleague_project_round', ['uuid' => UuidFactory::v4(), 'project_id' => $projectId, 'stage_id' => $stageId, 'name' => 'Round 1', 'code' => 'round_1', 'round_type' => 'regular', 'sequence_number' => 1]);
	$resultRepository = new MatchResultRepository($database);
	$matches = [];

	foreach ([[[1, 0], [1, 0]], [[1, 0], [0, 1]]] as $periodScores) {
		$matchId = $insert('#__joomleague_project_match', ['uuid' => UuidFactory::v4(), 'project_id' => $projectId, 'stage_id' => $stageId, 'round_id' => $roundId, 'contest_type' => 'head_to_head']);
		$participants = [];
		foreach ($entries as $slot => $entryId) $participants[] = $insert('#__joomleague_match_participant', ['uuid' => UuidFactory::v4(), 'match_id' => $matchId, 'project_id' => $projectId, 'project_entry_id' => $entryId, 'slot_number' => $slot + 1]);
		$resultRepository->replace($matchId, $resultPayload($participants, $periodScores), 0);
		$matches[] = [$matchId, $participants];
	}

	$reader = new StandingsReader($database);
	$recalculator = new StandingsRecalculator($database, $reader);
	$context = $reader->describe($projectId, null);
	if (!in_array('home', $context['available_scopes'], true) || !in_array('away', $context['available_scopes'], true)) throw new RuntimeException('Profile standings scopes are unavailable.');
	$firstSnapshot = $recalculator->recalculate($projectId, null, 'total', 0);
	$current = $reader->current($projectId, null, 'total');
	if ($firstSnapshot < 1 || count($current['rows']) !== 2) throw new RuntimeException('Initial standings snapshot was not published.');
	if ($current['rows'][0]->entry_name_snapshot !== 'Alpha' || $current['rows'][0]->metrics['points'] !== '4' || $current['rows'][1]->metrics['points'] !== '1') throw new RuntimeException('Football points were calculated incorrectly.');
	if ($recalculator->recalculate($projectId, null, 'total', 0) !== $firstSnapshot) throw new RuntimeException('Identical standings input created a duplicate snapshot.');
	(new StandingsSnapshotSynchronizer($database))->synchronize($projectId, null, 0, $context);
	if (count($reader->current($projectId, null, 'home')['rows']) !== 2 || count($reader->current($projectId, null, 'away')['rows']) !== 2) throw new RuntimeException('Missing profile-defined scopes were not published automatically.');
	$automaticSnapshotCount = (int) $database->setQuery($database->getQuery(true)->select('COUNT(*)')->from($database->quoteName('#__joomleague_standing_snapshot'))->where('project_id = ' . $projectId))->loadResult();
	(new StandingsSnapshotSynchronizer($database))->synchronize($projectId, null, 0, $context);
	if ((int) $database->setQuery($database->getQuery(true)->select('COUNT(*)')->from($database->quoteName('#__joomleague_standing_snapshot'))->where('project_id = ' . $projectId))->loadResult() !== $automaticSnapshotCount) throw new RuntimeException('Complete standings scopes were recalculated during a read.');
	$stageSnapshot = $recalculator->recalculate($projectId, $stageId, 'total', 0);
	if ($stageSnapshot < 1 || count($reader->current($projectId, $stageId, 'total')['rows']) !== 2) throw new RuntimeException('A stage inheriting project participants did not publish a complete table.');
	$outcomeTransition = new StagetransitionTable($database); $outcomeTransition->bind(['uuid' => UuidFactory::v4(), 'project_id' => $projectId, 'source_stage_id' => $stageId, 'target_stage_id' => $targetStageId, 'code' => 'winners_to_final', 'name' => 'Winners to final', 'selector_type' => 'match_outcome', 'selector_config_json' => '{"outcome":"winner"}', 'carry_over_mode' => 'none']);
	if (!$outcomeTransition->check() || !$outcomeTransition->store()) throw new RuntimeException('Match-outcome transition could not be stored.');
	$outcomePreview = (new StageProgressionService($database))->preview((int) $outcomeTransition->id);
	if (count($outcomePreview['entries']) !== 1 || $outcomePreview['entries'][0]['id'] !== $entries[0]) throw new RuntimeException('Numeric match-outcome fallback did not resolve the winner.');
	$database->setQuery($database->getQuery(true)->update($database->quoteName('#__joomleague_stage_transition'))->set("carry_over_mode = 'all_results'")->where('id = ' . (int) $transition->id))->execute();
	$insert('#__joomleague_stage_entry', ['stage_id' => $targetStageId, 'entry_id' => $entries[1], 'project_id' => $projectId, 'manual_assignment' => 1]);
	$progression = new StageProgressionService($database); $progressionPreview = $progression->preview((int) $transition->id);
	if (count($progressionPreview['entries']) !== 2 || !$progressionPreview['executable']) throw new RuntimeException('Standing-rank progression preview is incorrect.');
	$progressionRun = $progression->apply((int) $transition->id, 0); $repeatedRun = $progression->apply((int) $transition->id, 0);
	if ($progressionRun['run_id'] !== $repeatedRun['run_id'] || !$repeatedRun['reused']) throw new RuntimeException('Stage progression execution is not idempotent.');
	$runCount = (int) $database->setQuery($database->getQuery(true)->select('COUNT(*)')->from($database->quoteName('#__joomleague_stage_transition_run'))->where('transition_id = ' . (int) $transition->id))->loadResult();
	if ($runCount !== 1) throw new RuntimeException('Identical stage progression input created duplicate audit runs.');
	$manualFlag = (int) $database->setQuery($database->getQuery(true)->select('manual_assignment')->from($database->quoteName('#__joomleague_stage_entry'))->where('stage_id = ' . $targetStageId)->where('entry_id = ' . $entries[1]))->loadResult();
	if ($manualFlag !== 1) throw new RuntimeException('Automatic progression overwrote a manual target-stage assignment.');
	$targetSnapshot = $recalculator->recalculate($projectId, $targetStageId, 'total', 0); $targetRows = $reader->current($projectId, $targetStageId, 'total')['rows'];
	if ($targetSnapshot < 1 || count($targetRows) !== 2 || $targetRows[0]->metrics['points'] !== '4') throw new RuntimeException('All-results carry-over was not applied to target standings.');

	[$changedMatchId, $changedParticipants] = $matches[1];
	$resultRepository->replace($changedMatchId, $resultPayload($changedParticipants, [[0, 1], [0, 2]]), 0);
	$secondSnapshot = $recalculator->recalculate($projectId, null, 'total', 0);
	$current = $reader->current($projectId, null, 'total');
	if ($secondSnapshot === $firstSnapshot || (int) $current['snapshot']->id !== $secondSnapshot) throw new RuntimeException('Changed standings input did not publish a new snapshot.');
	if ($current['rows'][0]->entry_name_snapshot !== 'Beta' || $current['rows'][0]->metrics['points'] !== '3' || $current['rows'][1]->metrics['points'] !== '3') throw new RuntimeException('Tie-break ordering after result replacement is incorrect.');
	foreach ($context['available_scopes'] as $scope) $recalculator->recalculate($projectId, null, (string) $scope, 0);
	$points = static function (string $scope) use ($reader, $projectId, $entries): string {
		foreach ($reader->current($projectId, null, $scope)['rows'] as $row) if ((int) $row->entry_id_snapshot === $entries[0]) return (string) $row->metrics['points'];
		throw new RuntimeException('Adjusted entry is missing from the ' . $scope . ' scope.');
	};
	$baseline = []; foreach ($context['available_scopes'] as $scope) $baseline[$scope] = $points((string) $scope);
	$adjustmentData = ['project_id' => $projectId, 'stage_id' => null, 'project_entry_id' => $entries[0], 'scope_code' => 'all', 'metric_code' => 'points', 'adjustment_value' => '-1', 'reason' => 'Integration lifecycle test', 'published' => 1];
	$adjustment = new StandingadjustmentTable($database); $adjustment->bind($adjustmentData); $adjustment->uuid = UuidFactory::v4();
	if (!$adjustment->check() || !$adjustment->store()) throw new RuntimeException('Standing adjustment could not be created: ' . $adjustment->getError());
	$adjustmentId = (int) $adjustment->id; (new StandingsCascadeTrigger($database))->trigger($projectId, null, 0);
	foreach ($context['available_scopes'] as $scope) if ((float) $points((string) $scope) !== (float) $baseline[$scope] - 1.0) throw new RuntimeException('Created adjustment did not refresh the ' . $scope . ' scope.');
	$adjustment->adjustment_value = '-2';
	if (!$adjustment->check() || !$adjustment->store()) throw new RuntimeException('Standing adjustment could not be edited: ' . $adjustment->getError());
	(new StandingsCascadeTrigger($database))->trigger($projectId, null, 0);
	foreach ($context['available_scopes'] as $scope) if ((float) $points((string) $scope) !== (float) $baseline[$scope] - 2.0) throw new RuntimeException('Edited adjustment did not refresh the ' . $scope . ' scope.');
	if (!$adjustment->delete($adjustmentId)) throw new RuntimeException('Standing adjustment could not be deleted: ' . $adjustment->getError());
	(new StandingsCascadeTrigger($database))->trigger($projectId, null, 0);
	foreach ($context['available_scopes'] as $scope) if ((float) $points((string) $scope) !== (float) $baseline[$scope]) throw new RuntimeException('Deleted adjustment did not restore the ' . $scope . ' scope.');
	$homeAdjustment = new StandingadjustmentTable($database); $homeAdjustment->bind(array_replace($adjustmentData, ['scope_code' => 'home', 'adjustment_value' => '-3', 'reason' => 'Home scope isolation test'])); $homeAdjustment->uuid = UuidFactory::v4();
	if (!$homeAdjustment->check() || !$homeAdjustment->store()) throw new RuntimeException('Home-only adjustment could not be created: ' . $homeAdjustment->getError());
	(new StandingsCascadeTrigger($database))->trigger($projectId, null, 0);
	if ((float) $points('home') !== (float) $baseline['home'] - 3.0) throw new RuntimeException('Home-only adjustment did not refresh the home scope.');
	foreach (['total', 'away'] as $scope) if ((float) $points($scope) !== (float) $baseline[$scope]) throw new RuntimeException('Home-only adjustment leaked into the ' . $scope . ' scope.');
	if (!$homeAdjustment->delete((int) $homeAdjustment->id)) throw new RuntimeException('Home-only adjustment could not be deleted: ' . $homeAdjustment->getError());
	(new StandingsCascadeTrigger($database))->trigger($projectId, null, 0);
	foreach ($context['available_scopes'] as $scope) if ((float) $points((string) $scope) !== (float) $baseline[$scope]) throw new RuntimeException('Deleting the home-only adjustment did not restore the ' . $scope . ' scope.');
	$snapshotCount = (int) $database->setQuery($database->getQuery(true)->select('COUNT(*)')->from($database->quoteName('#__joomleague_standing_snapshot'))->where('project_id = ' . $projectId))->loadResult();
	if ($snapshotCount < 15) throw new RuntimeException('Immutable standings history was not retained across adjustment lifecycle changes.');

	printf("Standings repository OK on %s: calculation, automatic scopes and adjustment lifecycle verified\n", $database->getName());
} finally {
	$database->setQuery($database->getQuery(true)->delete($database->quoteName('#__joomleague_project'))->where('id = ' . $projectId))->execute();
	foreach ([['#__joomleague_sport_type', $sportTypeId], ['#__joomleague_competition', $competitionId], ['#__joomleague_season', $seasonId]] as [$table, $id]) $database->setQuery($database->getQuery(true)->delete($database->quoteName($table))->where('id = ' . $id))->execute();
}
