<?php

declare(strict_types=1);

define('_JEXEC', 1);
define('JPATH_BASE', '/var/www/html');
$_SERVER['HTTP_HOST'] ??= 'localhost';
$_SERVER['REQUEST_URI'] ??= '/';
$_SERVER['SCRIPT_NAME'] = '/index.php';
require_once JPATH_BASE . '/includes/defines.php';
require_once JPATH_BASE . '/includes/framework.php';

foreach (['UuidFactory.php', 'CanonicalJson.php', 'StageTransitionValidator.php', 'StageProgressionService.php', 'MatchResultValidationException.php', 'MatchResultDecimal.php', 'MatchResultAggregationValidator.php', 'MatchResultPayloadValidator.php', 'MatchResultRepository.php', 'StandingsContractValidator.php', 'StandingsDecimal.php', 'StandingsCalculator.php', 'StandingsReader.php', 'StandingsRecalculator.php', 'StandingsSnapshotSynchronizer.php', 'StandingProgressionReader.php'] as $service) {
	require_once JPATH_ADMINISTRATOR . '/components/com_joomleague/src/Service/' . $service;
}
require_once JPATH_ADMINISTRATOR . '/components/com_joomleague/src/Table/StagetransitionTable.php';
require_once JPATH_ADMINISTRATOR . '/components/com_joomleague/src/Table/StandingadjustmentTable.php';
require_once JPATH_ADMINISTRATOR . '/components/com_joomleague/src/Table/MatchTable.php';
require_once JPATH_ADMINISTRATOR . '/components/com_joomleague/src/Table/RoundTable.php';
require_once JPATH_ADMINISTRATOR . '/components/com_joomleague/src/Table/StageTable.php';
require_once JPATH_ADMINISTRATOR . '/components/com_joomleague/src/Service/StandingsCascadeTrigger.php';
require_once JPATH_ADMINISTRATOR . '/components/com_joomleague/src/Extension/JoomleagueComponent.php';
require_once JPATH_ADMINISTRATOR . '/components/com_joomleague/src/Model/StageentriesModel.php';
require_once JPATH_ADMINISTRATOR . '/components/com_joomleague/src/Model/MatchModel.php';
require_once JPATH_ADMINISTRATOR . '/components/com_joomleague/src/Model/RoundModel.php';
require_once JPATH_ADMINISTRATOR . '/components/com_joomleague/src/Model/StageModel.php';

use Joomla\CMS\Factory;
use Joomla\Database\DatabaseInterface;
use Joomla\CMS\User\User;
use Joomleague\Component\Joomleague\Administrator\Service\MatchResultRepository;
use Joomleague\Component\Joomleague\Administrator\Service\StageProgressionService;
use Joomleague\Component\Joomleague\Administrator\Service\StandingsCascadeTrigger;
use Joomleague\Component\Joomleague\Administrator\Model\StageentriesModel;
use Joomleague\Component\Joomleague\Administrator\Model\MatchModel;
use Joomleague\Component\Joomleague\Administrator\Model\RoundModel;
use Joomleague\Component\Joomleague\Administrator\Model\StageModel;
use Joomleague\Component\Joomleague\Administrator\Table\StandingadjustmentTable;
use Joomleague\Component\Joomleague\Domain\Service\StandingsReader;
use Joomleague\Component\Joomleague\Domain\Service\StandingsRecalculator;
use Joomleague\Component\Joomleague\Domain\Service\StandingsSnapshotSynchronizer;
use Joomleague\Component\Joomleague\Domain\Service\StandingProgressionReader;
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
	$stageId = $insert('#__joomleague_project_stage', ['uuid' => UuidFactory::v4(), 'project_id' => $projectId, 'name' => 'League', 'code' => 'league', 'stage_type' => 'league', 'published' => 1]);
	$targetStageId = $insert('#__joomleague_project_stage', ['uuid' => UuidFactory::v4(), 'project_id' => $projectId, 'name' => 'Final', 'code' => 'final', 'stage_type' => 'knockout', 'published' => 1]);
	$transition = new StagetransitionTable($database); $transition->bind(['uuid' => UuidFactory::v4(), 'project_id' => $projectId, 'source_stage_id' => $stageId, 'target_stage_id' => $targetStageId, 'code' => 'league_to_final', 'name' => 'League to final', 'selector_type' => 'standing_rank_range', 'selector_config_json' => '{"from":1,"to":2,"scope":"total"}', 'carry_over_mode' => 'none']);
	if (!$transition->check() || !$transition->store()) throw new RuntimeException('Valid stage progression could not be stored: ' . $transition->getError());
	$cycle = new StagetransitionTable($database); $cycle->bind(['uuid' => UuidFactory::v4(), 'project_id' => $projectId, 'source_stage_id' => $targetStageId, 'target_stage_id' => $stageId, 'code' => 'invalid_cycle', 'name' => 'Invalid cycle', 'selector_type' => 'manual', 'carry_over_mode' => 'none']);
	if ($cycle->check() || $cycle->getError() === '') throw new RuntimeException('Cyclic stage progression was accepted.');
	$roundId = $insert('#__joomleague_project_round', ['uuid' => UuidFactory::v4(), 'project_id' => $projectId, 'stage_id' => $stageId, 'name' => 'Round 1', 'code' => 'round_1', 'round_type' => 'regular', 'sequence_number' => 1, 'published' => 1]);
	$resultRepository = new MatchResultRepository($database);
	$matches = [];

	foreach ([[[1, 0], [1, 0]], [[1, 0], [0, 1]]] as $periodScores) {
		$matchId = $insert('#__joomleague_project_match', ['uuid' => UuidFactory::v4(), 'project_id' => $projectId, 'stage_id' => $stageId, 'round_id' => $roundId, 'contest_type' => 'head_to_head', 'published' => 1]);
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
	$targetSnapshot = (int) $reader->current($projectId, $targetStageId, 'total')['snapshot']->id; $targetRows = $reader->current($projectId, $targetStageId, 'total')['rows'];
	if ($targetSnapshot < 1 || count($targetRows) !== 2 || $targetRows[0]->metrics['points'] !== '4') throw new RuntimeException('All-results carry-over was not applied to target standings.');
	$entryPoints = static function (string $scope, int $entryId) use ($reader, $projectId): string {
		foreach ($reader->current($projectId, null, $scope)['rows'] as $row) if ((int) $row->entry_id_snapshot === $entryId) return (string) $row->metrics['points'];
		throw new RuntimeException('Entry is missing from the ' . $scope . ' scope.');
	};

	[$changedMatchId, $changedParticipants] = $matches[1];
	$resultRepository->replace($changedMatchId, $resultPayload($changedParticipants, [[0, 1], [0, 2]]), 0);
	(new StandingsCascadeTrigger($database))->trigger($projectId, $stageId, 0);
	$current = $reader->current($projectId, null, 'total');
	$secondSnapshot = (int) $current['snapshot']->id;
	if ($secondSnapshot === $firstSnapshot) throw new RuntimeException('Changed standings input did not publish a new snapshot.');
	if ($current['rows'][0]->entry_name_snapshot !== 'Beta' || $current['rows'][0]->metrics['points'] !== '3' || $current['rows'][1]->metrics['points'] !== '3') throw new RuntimeException('Tie-break ordering after result replacement is incorrect.');
	if ($entryPoints('home', $entries[0]) !== '3' || $entryPoints('away', $entries[1]) !== '3') throw new RuntimeException('Changed final result did not refresh home and away scopes.');
	$draftPayload = $resultPayload($changedParticipants, [[0, 1], [0, 2]]); $draftPayload['status_code'] = 'draft'; $draftPayload['finalized_at'] = null;
	$resultRepository->replace($changedMatchId, $draftPayload, 0); (new StandingsCascadeTrigger($database))->trigger($projectId, $stageId, 0);
	if ($entryPoints('total', $entries[0]) !== '3' || $entryPoints('total', $entries[1]) !== '0' || $entryPoints('away', $entries[1]) !== '0') throw new RuntimeException('Draft result remained included in published standings.');
	$resultRepository->replace($changedMatchId, $resultPayload($changedParticipants, [[0, 1], [0, 2]]), 0); (new StandingsCascadeTrigger($database))->trigger($projectId, $stageId, 0);
	if ($entryPoints('total', $entries[0]) !== '3' || $entryPoints('total', $entries[1]) !== '3' || $entryPoints('away', $entries[1]) !== '3') throw new RuntimeException('Finalized result was not restored to every standings scope.');
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
	$futureAdjustment = new StandingadjustmentTable($database); $futureAdjustment->bind(array_replace($adjustmentData, ['adjustment_value' => '-10', 'reason' => 'Future adjustment test', 'effective_date' => gmdate('Y-m-d', strtotime('+1 day'))])); $futureAdjustment->uuid = UuidFactory::v4();
	if (!$futureAdjustment->check() || !$futureAdjustment->store()) throw new RuntimeException('Future adjustment could not be created: ' . $futureAdjustment->getError());
	(new StandingsCascadeTrigger($database))->trigger($projectId, null, 0);
	foreach ($context['available_scopes'] as $scope) if ((float) $points((string) $scope) !== (float) $baseline[$scope]) throw new RuntimeException('Future adjustment was applied early to the ' . $scope . ' scope.');
	$futureAdjustment->effective_date = gmdate('Y-m-d', strtotime('-1 day')); $futureAdjustment->published = 0;
	if (!$futureAdjustment->check() || !$futureAdjustment->store()) throw new RuntimeException('Future adjustment could not be rescheduled as unpublished.');
	(new StandingsCascadeTrigger($database))->trigger($projectId, null, 0);
	foreach ($context['available_scopes'] as $scope) if ((float) $points((string) $scope) !== (float) $baseline[$scope]) throw new RuntimeException('Unpublished adjustment affected the ' . $scope . ' scope.');
	$futureAdjustment->published = 1;
	if (!$futureAdjustment->check() || !$futureAdjustment->store()) throw new RuntimeException('Adjustment could not be published.');
	(new StandingsCascadeTrigger($database))->trigger($projectId, null, 0);
	foreach ($context['available_scopes'] as $scope) if ((float) $points((string) $scope) !== (float) $baseline[$scope] - 10.0) throw new RuntimeException('Published effective adjustment did not refresh the ' . $scope . ' scope.');
	$futureAdjustment->published = 0;
	if (!$futureAdjustment->check() || !$futureAdjustment->store()) throw new RuntimeException('Adjustment could not be unpublished.');
	(new StandingsCascadeTrigger($database))->trigger($projectId, null, 0);
	foreach ($context['available_scopes'] as $scope) if ((float) $points((string) $scope) !== (float) $baseline[$scope]) throw new RuntimeException('Unpublishing an adjustment did not restore the ' . $scope . ' scope.');
	if (!$futureAdjustment->delete((int) $futureAdjustment->id)) throw new RuntimeException('Future adjustment could not be deleted.');
	$decimalAdjustments = [];
	foreach ([['-0.5', 'First decimal adjustment'], ['-1.25', 'Second decimal adjustment']] as [$value, $reason]) {
		$table = new StandingadjustmentTable($database); $table->bind(array_replace($adjustmentData, ['adjustment_value' => $value, 'reason' => $reason])); $table->uuid = UuidFactory::v4();
		if (!$table->check() || !$table->store()) throw new RuntimeException('Decimal adjustment could not be created: ' . $table->getError());
		$decimalAdjustments[] = $table;
	}
	(new StandingsCascadeTrigger($database))->trigger($projectId, null, 0);
	foreach ($context['available_scopes'] as $scope) if ((float) $points((string) $scope) !== (float) $baseline[$scope] - 1.75) throw new RuntimeException('Multiple decimal adjustments were not accumulated in the ' . $scope . ' scope.');
	foreach ($decimalAdjustments as $table) if (!$table->delete((int) $table->id)) throw new RuntimeException('Decimal adjustment could not be deleted.');
	(new StandingsCascadeTrigger($database))->trigger($projectId, null, 0);
	foreach ($context['available_scopes'] as $scope) if ((float) $points((string) $scope) !== (float) $baseline[$scope]) throw new RuntimeException('Deleting decimal adjustments did not restore the ' . $scope . ' scope.');
	$recalculator->recalculate($projectId, $stageId, 'total', 0); $stageBaseline = null;
	foreach ($reader->current($projectId, $stageId, 'total')['rows'] as $row) if ((int) $row->entry_id_snapshot === $entries[0]) $stageBaseline = (string) $row->metrics['points'];
	if ($stageBaseline === null) throw new RuntimeException('Stage adjustment baseline is unavailable.');
	$stageAdjustment = new StandingadjustmentTable($database); $stageAdjustment->bind(array_replace($adjustmentData, ['stage_id' => $stageId, 'adjustment_value' => '-2', 'reason' => 'Stage isolation test'])); $stageAdjustment->uuid = UuidFactory::v4();
	if (!$stageAdjustment->check() || !$stageAdjustment->store()) throw new RuntimeException('Stage adjustment could not be created: ' . $stageAdjustment->getError());
	(new StandingsCascadeTrigger($database))->trigger($projectId, $stageId, 0); $stageAdjusted = null;
	foreach ($reader->current($projectId, $stageId, 'total')['rows'] as $row) if ((int) $row->entry_id_snapshot === $entries[0]) $stageAdjusted = (string) $row->metrics['points'];
	if ((float) $stageAdjusted !== (float) $stageBaseline - 2.0) throw new RuntimeException('Stage adjustment was not applied to its stage.');
	if ((float) $points('total') !== (float) $baseline['total']) throw new RuntimeException('Stage adjustment leaked into project-wide standings.');
	if (!$stageAdjustment->delete((int) $stageAdjustment->id)) throw new RuntimeException('Stage adjustment could not be deleted.');
	(new StandingsCascadeTrigger($database))->trigger($projectId, $stageId, 0); $stageRestored = null;
	foreach ($reader->current($projectId, $stageId, 'total')['rows'] as $row) if ((int) $row->entry_id_snapshot === $entries[0]) $stageRestored = (string) $row->metrics['points'];
	if ((float) $stageRestored !== (float) $stageBaseline) throw new RuntimeException('Deleting stage adjustment did not restore stage standings.');
	$database->setQuery($database->getQuery(true)->update($database->quoteName('#__joomleague_project_entry'))->set('included_in_standings = 0')->where('id = ' . $entries[0]))->execute();
	(new StandingsCascadeTrigger($database))->triggerProject($projectId, 0);
	if (count($reader->current($projectId, null, 'total')['rows']) !== 1 || count($reader->current($projectId, $stageId, 'total')['rows']) !== 1 || count($reader->current($projectId, $targetStageId, 'total')['rows']) !== 1) throw new RuntimeException('Excluding a project entry did not refresh project and stage standings.');
	$database->setQuery($database->getQuery(true)->update($database->quoteName('#__joomleague_project_entry'))->set('included_in_standings = 1')->where('id = ' . $entries[0]))->execute();
	(new StandingsCascadeTrigger($database))->triggerProject($projectId, 0);
	if (count($reader->current($projectId, null, 'total')['rows']) !== 2 || count($reader->current($projectId, $stageId, 'total')['rows']) !== 2 || count($reader->current($projectId, $targetStageId, 'total')['rows']) !== 2) throw new RuntimeException('Re-including a project entry did not refresh project and stage standings.');
	$database->setQuery($database->getQuery(true)->update($database->quoteName('#__joomleague_project_entry'))->set('published = 0')->where('id = ' . $entries[1]))->execute();
	(new StandingsCascadeTrigger($database))->triggerProject($projectId, 0);
	if (count($reader->current($projectId, null, 'total')['rows']) !== 1 || count($reader->current($projectId, $stageId, 'total')['rows']) !== 1) throw new RuntimeException('Unpublishing a project entry did not refresh standings.');
	$database->setQuery($database->getQuery(true)->update($database->quoteName('#__joomleague_project_entry'))->set('published = 1')->where('id = ' . $entries[1]))->execute();
	(new StandingsCascadeTrigger($database))->triggerProject($projectId, 0);
	if (count($reader->current($projectId, null, 'total')['rows']) !== 2 || count($reader->current($projectId, $stageId, 'total')['rows']) !== 2) throw new RuntimeException('Republishing a project entry did not restore standings.');
	$temporaryEntryId = $insert('#__joomleague_project_entry', ['uuid' => UuidFactory::v4(), 'project_id' => $projectId, 'entry_kind' => 'group', 'display_name' => 'Temporary entry']);
	(new StandingsCascadeTrigger($database))->triggerProject($projectId, 0);
	if (count($reader->current($projectId, null, 'total')['rows']) !== 3 || count($reader->current($projectId, $stageId, 'total')['rows']) !== 3 || count($reader->current($projectId, $targetStageId, 'total')['rows']) !== 2) throw new RuntimeException('Adding a project entry did not respect inherited and explicit stage entry selection.');
	$database->setQuery($database->getQuery(true)->delete($database->quoteName('#__joomleague_project_entry'))->where('id = ' . $temporaryEntryId))->execute();
	(new StandingsCascadeTrigger($database))->triggerProject($projectId, 0);
	if (count($reader->current($projectId, null, 'total')['rows']) !== 2 || count($reader->current($projectId, $stageId, 'total')['rows']) !== 2) throw new RuntimeException('Deleting a project entry did not restore standings.');
	$stageEntriesModel = new StageentriesModel(['dbo' => $database]);
	$stageEntriesModel->saveAssignments($targetStageId, 'explicit', [$entries[0]]);
	if (count($reader->current($projectId, $targetStageId, 'total')['rows']) !== 1) throw new RuntimeException('Manual stage assignment did not refresh target-stage standings.');
	if (count($reader->current($projectId, null, 'total')['rows']) !== 2 || count($reader->current($projectId, $stageId, 'total')['rows']) !== 2) throw new RuntimeException('Manual stage assignment leaked into another standings context.');
	$stageEntriesModel->saveAssignments($targetStageId, 'explicit', $entries);
	if (count($reader->current($projectId, $targetStageId, 'total')['rows']) !== 2) throw new RuntimeException('Restoring explicit stage assignments did not refresh target-stage standings.');
	$stageEntriesModel->saveAssignments($targetStageId, 'inherit_project', []);
	if (count($reader->current($projectId, $targetStageId, 'total')['rows']) !== 2) throw new RuntimeException('Switching a stage to inherited entries did not refresh target-stage standings.');

	$adminId = (int) $database->setQuery($database->getQuery(true)->select('user_id')->from($database->quoteName('#__user_usergroup_map'))->where('group_id = 8')->order('user_id ASC'), 0, 1)->loadResult();
	if ($adminId < 1) throw new RuntimeException('A Super Users account is required for model lifecycle integration tests.');
	$currentUser = new User($adminId);
	$modelConfig = ['dbo' => $database, 'events_map' => ['delete' => 'joomleague_test_none', 'save' => 'joomleague_test_none', 'change_state' => 'joomleague_test_none', 'validate' => 'joomleague_test_none', 'batch' => 'joomleague_test_none']];
	$matchModel = new MatchModel($modelConfig); $matchModel->setCurrentUser($currentUser);
	$roundModel = new RoundModel($modelConfig); $roundModel->setCurrentUser($currentUser);
	$stageModel = new StageModel($modelConfig); $stageModel->setCurrentUser($currentUser);
	$publicationIds = [$matches[1][0]];
	if (!$matchModel->publish($publicationIds, 0)) throw new RuntimeException('Match could not be unpublished.');
	if ($entryPoints('total', $entries[0]) !== '3' || $entryPoints('total', $entries[1]) !== '0') throw new RuntimeException('Unpublishing a match did not refresh standings.');
	if (!$matchModel->publish($publicationIds, 1)) throw new RuntimeException('Match could not be republished.');
	if ($entryPoints('total', $entries[0]) !== '3' || $entryPoints('total', $entries[1]) !== '3') throw new RuntimeException('Republishing a match did not restore standings.');
	$publicationIds = [$roundId];
	if (!$roundModel->publish($publicationIds, 0)) throw new RuntimeException('Round could not be unpublished.');
	if ($entryPoints('total', $entries[0]) !== '0' || $entryPoints('total', $entries[1]) !== '0') throw new RuntimeException('Unpublishing a round did not refresh standings.');
	if (!$roundModel->publish($publicationIds, 1)) throw new RuntimeException('Round could not be republished.');
	if ($entryPoints('total', $entries[0]) !== '3' || $entryPoints('total', $entries[1]) !== '3') throw new RuntimeException('Republishing a round did not restore standings.');
	$publicationIds = [$stageId];
	if (!$stageModel->publish($publicationIds, 0)) throw new RuntimeException('Stage could not be unpublished.');
	if ($entryPoints('total', $entries[0]) !== '0' || $entryPoints('total', $entries[1]) !== '0') throw new RuntimeException('Unpublishing a stage did not refresh standings.');
	if (!$stageModel->publish($publicationIds, 1)) throw new RuntimeException('Stage could not be republished.');
	if ($entryPoints('total', $entries[0]) !== '3' || $entryPoints('total', $entries[1]) !== '3') throw new RuntimeException('Republishing a stage did not restore standings.');

	$createFinalMatch = static function (int $fixtureStageId, int $fixtureRoundId) use ($insert, $database, $projectId, $entries, $resultPayload, $resultRepository): int {
		$matchId = $insert('#__joomleague_project_match', ['uuid' => UuidFactory::v4(), 'project_id' => $projectId, 'stage_id' => $fixtureStageId, 'round_id' => $fixtureRoundId, 'contest_type' => 'head_to_head', 'published' => 1]);
		$participants = [];
		foreach ($entries as $slot => $entryId) $participants[] = $insert('#__joomleague_match_participant', ['uuid' => UuidFactory::v4(), 'match_id' => $matchId, 'project_id' => $projectId, 'project_entry_id' => $entryId, 'slot_number' => $slot + 1]);
		$resultRepository->replace($matchId, $resultPayload($participants, [[1, 0], [1, 0]]), 0);
		(new StandingsCascadeTrigger($database))->trigger($projectId, $fixtureStageId, 0);
		return $matchId;
	};
	$temporaryMatchId = $createFinalMatch($stageId, $roundId);
	if ($entryPoints('total', $entries[0]) !== '6') throw new RuntimeException('Temporary match was not included before deletion.');
	$deleteIds = [$temporaryMatchId];
	if (!$matchModel->delete($deleteIds)) throw new RuntimeException('Match could not be deleted.');
	if ($entryPoints('total', $entries[0]) !== '3') throw new RuntimeException('Deleting a match did not refresh standings.');

	$temporaryRoundId = $insert('#__joomleague_project_round', ['uuid' => UuidFactory::v4(), 'project_id' => $projectId, 'stage_id' => $stageId, 'name' => 'Temporary round', 'code' => 'temporary_round', 'round_type' => 'regular', 'sequence_number' => 2, 'published' => 1]);
	$createFinalMatch($stageId, $temporaryRoundId);
	if ($entryPoints('total', $entries[0]) !== '6') throw new RuntimeException('Temporary round was not included before deletion.');
	$roundProgression = (new StandingProgressionReader($database))->forProject($projectId, $stageId, 'total');
	if (count($roundProgression['points']) !== 2 || $roundProgression['points'][0]['round_sequence'] !== 1 || $roundProgression['points'][1]['round_sequence'] !== 2 || !isset($roundProgression['points'][1]['ranks'][$entries[0]])) throw new RuntimeException('Round-by-round standings progression is invalid.');
	$deleteIds = [$temporaryRoundId];
	if (!$roundModel->delete($deleteIds)) throw new RuntimeException('Round could not be deleted.');
	if ($entryPoints('total', $entries[0]) !== '3') throw new RuntimeException('Deleting a round did not refresh standings.');

	$temporaryStageId = $insert('#__joomleague_project_stage', ['uuid' => UuidFactory::v4(), 'project_id' => $projectId, 'name' => 'Temporary stage', 'code' => 'temporary_stage', 'stage_type' => 'league', 'published' => 1]);
	$temporaryStageRoundId = $insert('#__joomleague_project_round', ['uuid' => UuidFactory::v4(), 'project_id' => $projectId, 'stage_id' => $temporaryStageId, 'name' => 'Temporary stage round', 'code' => 'temporary_stage_round', 'round_type' => 'regular', 'sequence_number' => 1, 'published' => 1]);
	$createFinalMatch($temporaryStageId, $temporaryStageRoundId);
	if ($entryPoints('total', $entries[0]) !== '6') throw new RuntimeException('Temporary stage was not included before deletion.');
	$deleteIds = [$temporaryStageId];
	if (!$stageModel->delete($deleteIds)) throw new RuntimeException('Stage could not be deleted.');
	if ($entryPoints('total', $entries[0]) !== '3') throw new RuntimeException('Deleting a stage did not refresh project standings.');
	$snapshotCount = (int) $database->setQuery($database->getQuery(true)->select('COUNT(*)')->from($database->quoteName('#__joomleague_standing_snapshot'))->where('project_id = ' . $projectId))->loadResult();
	if ($snapshotCount < 25) throw new RuntimeException('Immutable standings history was not retained across adjustment lifecycle changes.');

	printf("Standings repository OK on %s: result, adjustment, participant and competition-structure lifecycles verified\n", $database->getName());
} finally {
	$database->setQuery($database->getQuery(true)->delete($database->quoteName('#__joomleague_project'))->where('id = ' . $projectId))->execute();
	foreach ([['#__joomleague_sport_type', $sportTypeId], ['#__joomleague_competition', $competitionId], ['#__joomleague_season', $seasonId]] as [$table, $id]) $database->setQuery($database->getQuery(true)->delete($database->quoteName($table))->where('id = ' . $id))->execute();
}
