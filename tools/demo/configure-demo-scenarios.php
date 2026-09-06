<?php

declare(strict_types=1);

define('_JEXEC', 1);
define('JPATH_BASE', '/var/www/html');
require_once JPATH_BASE . '/includes/defines.php';
require_once JPATH_BASE . '/includes/framework.php';

$services = [
	'UuidFactory.php', 'CanonicalJson.php', 'TemplateDefinitionRegistry.php', 'ProjectTemplateConfigRepository.php',
	'ProjectRuleValidator.php', 'ProjectRuleConfigRepository.php', 'ScheduleTemplateService.php', 'SchedulePlannerService.php',
	'StandingsContractValidator.php', 'StandingsDecimal.php', 'StandingsCalculator.php', 'StandingsReader.php',
	'StandingsSnapshotSynchronizer.php', 'StandingsRecalculator.php',
];
foreach ($services as $service) require_once JPATH_ADMINISTRATOR . '/components/com_joomleague/src/Service/' . $service;

use Joomla\CMS\Factory;
use Joomla\Database\DatabaseInterface;
use Joomleague\Component\Joomleague\Administrator\Service\ProjectRuleConfigRepository;
use Joomleague\Component\Joomleague\Administrator\Service\ProjectTemplateConfigRepository;
use Joomleague\Component\Joomleague\Administrator\Service\SchedulePlannerService;
use Joomleague\Component\Joomleague\Administrator\Service\TemplateDefinitionRegistry;
use Joomleague\Component\Joomleague\Domain\Service\CanonicalJson;
use Joomleague\Component\Joomleague\Domain\Service\StandingsReader;
use Joomleague\Component\Joomleague\Domain\Service\StandingsRecalculator;
use Joomleague\Component\Joomleague\Domain\Service\UuidFactory;

$container = Factory::getContainer();
$container->alias('session', 'session.cli')->alias('JSession', 'session.cli')
	->alias(Joomla\CMS\Session\Session::class, 'session.cli')->alias(Joomla\Session\Session::class, 'session.cli')
	->alias(Joomla\Session\SessionInterface::class, 'session.cli');
Factory::$application = $container->get(Joomla\Console\Application::class);
$db = $container->get(DatabaseInterface::class);
$actorId = 0;
$now = gmdate('Y-m-d H:i:s');

$insert = static function (string $table, array $values) use ($db): int {
	$query = $db->getQuery(true)->insert($db->quoteName($table))->columns($db->quoteName(array_keys($values)));
	$holders = [];
	foreach ($values as $key => &$value) { $holders[] = ':' . $key; $query->bind(':' . $key, $value); }
	$query->values(implode(',', $holders));
	$db->setQuery($query)->execute();
	return (int) $db->insertid();
};

$registry = new TemplateDefinitionRegistry();
$projectTemplates = new ProjectTemplateConfigRepository($db, $registry);
$projects = $db->setQuery($db->getQuery(true)
	->select(['project.id', 'project.profile_version_id', 'version.payload_json'])
	->from($db->quoteName('#__joomleague_project', 'project'))
	->innerJoin($db->quoteName('#__joomleague_sport_profile_version', 'version') . ' ON version.id=project.profile_version_id')
	->order('project.id ASC'))->loadObjectList();

foreach ($projects as $project) {
	$profile = json_decode((string) $project->payload_json, true, 512, JSON_THROW_ON_ERROR);
	$configs = [];
	foreach ($profile['template_defaults'] ?? [] as $code => $defaults) {
		$override = [];
		foreach ($defaults as $field => $value) {
			if (is_bool($value)) { $override[$field] = !$value; break; }
		}
		if ($override !== []) $configs[(string) $code] = $override;

		// A profile-level sparse override demonstrates global template settings.
		if ($override !== []) {
			$json = CanonicalJson::encodeObject($override);
			$existing = $db->setQuery($db->getQuery(true)->select('id')->from($db->quoteName('#__joomleague_profile_template_config'))
				->where('profile_version_id=' . (int) $project->profile_version_id)->where('template_code=' . $db->quote((string) $code)))->loadResult();
			$record = (object) ['profile_version_id' => (int) $project->profile_version_id, 'template_code' => (string) $code,
				'schema_version' => TemplateDefinitionRegistry::SCHEMA_VERSION, 'params_json' => $json,
				'params_checksum' => hash('sha256', $json), 'published' => 1, 'created_by' => $actorId];
			if ($existing === null) $db->insertObject('#__joomleague_profile_template_config', $record, 'id');
		}
	}
	$projectTemplates->saveAll((int) $project->id, $configs, $actorId);
}

// A safe project-specific football rule: use an evening default start time.
(new ProjectRuleConfigRepository($db))->save(1, ['match_structure' => ['default_start_time' => '18:30']], $actorId);

$mainStageId = (int) $db->setQuery($db->getQuery(true)->select('id')->from($db->quoteName('#__joomleague_project_stage'))
	->where('project_id=1')->where('code=' . $db->quote('main')))->loadResult();
$championshipStageId = (int) $db->setQuery($db->getQuery(true)->select('id')->from($db->quoteName('#__joomleague_project_stage'))
	->where('project_id=1')->where('code=' . $db->quote('championship')))->loadResult();

if ($championshipStageId === 0) {
	$championshipStageId = $insert('#__joomleague_project_stage', [
		'uuid' => UuidFactory::v4(), 'project_id' => 1, 'name' => 'Championship stage', 'alias' => 'championship-stage',
		'code' => 'championship', 'stage_type' => 'league', 'entry_selection_mode' => 'explicit', 'sequence_number' => 2,
		'start_date' => '2036-03-01', 'end_date' => '2036-05-31', 'description' => 'Fictional top-four stage populated by a standings transition.',
		'published' => 1, 'ordering' => 2, 'created' => $now, 'created_by' => $actorId,
	]);
}

$topEntries = array_map('intval', $db->setQuery($db->getQuery(true)->select('id')->from($db->quoteName('#__joomleague_project_entry'))
	->where('project_id=1')->order(['seed_number ASC', 'id ASC']), 0, 4)->loadColumn());
foreach ($topEntries as $index => $entryId) {
	$count = (int) $db->setQuery($db->getQuery(true)->select('COUNT(*)')->from($db->quoteName('#__joomleague_stage_entry'))
		->where('stage_id=' . $championshipStageId)->where('entry_id=' . $entryId))->loadResult();
	if ($count === 0) $insert('#__joomleague_stage_entry', ['stage_id' => $championshipStageId, 'entry_id' => $entryId, 'project_id' => 1,
		'ordering' => $index + 1, 'seed_number' => $index + 1, 'manual_assignment' => 0, 'created' => $now, 'created_by' => $actorId]);
}

$transitionId = (int) $db->setQuery($db->getQuery(true)->select('id')->from($db->quoteName('#__joomleague_stage_transition'))
	->where('project_id=1')->where('code=' . $db->quote('top-four')))->loadResult();
if ($transitionId === 0) {
	$transitionId = $insert('#__joomleague_stage_transition', [
		'uuid' => UuidFactory::v4(), 'project_id' => 1, 'source_stage_id' => $mainStageId, 'target_stage_id' => $championshipStageId,
		'code' => 'top-four', 'name' => 'Top four qualify', 'selector_type' => 'standing_rank_range',
		'selector_config_json' => CanonicalJson::encodeObject(['from' => 1, 'to' => 4, 'scope' => 'total']),
		'carry_over_mode' => 'mutual_results', 'target_seed_start' => 1, 'published' => 1, 'ordering' => 1,
		'created' => $now, 'created_by' => $actorId,
	]);
}

$runId = (int) $db->setQuery($db->getQuery(true)->select('id')->from($db->quoteName('#__joomleague_stage_transition_run'))
	->where('transition_id=' . $transitionId))->loadResult();
if ($runId === 0) {
	$snapshot = ['selector' => 'standing_rank_range', 'from' => 1, 'to' => 4, 'scope' => 'total'];
	$resolved = array_map(static fn(int $entryId, int $index): array => ['entry_id' => $entryId, 'target_seed' => $index + 1], $topEntries, array_keys($topEntries));
	$runId = $insert('#__joomleague_stage_transition_run', [
		'uuid' => UuidFactory::v4(), 'transition_id' => $transitionId, 'project_id' => 1,
		'input_checksum' => CanonicalJson::checksum(['snapshot' => $snapshot, 'entries' => $topEntries]),
		'selector_snapshot_json' => CanonicalJson::encodeObject($snapshot), 'resolved_entries_json' => json_encode($resolved, JSON_THROW_ON_ERROR),
		'resolved_count' => count($topEntries), 'status' => 'applied', 'created' => $now, 'created_by' => $actorId,
	]);
	foreach ($topEntries as $index => $entryId) $insert('#__joomleague_stage_transition_assignment', [
		'transition_id' => $transitionId, 'target_stage_id' => $championshipStageId, 'project_entry_id' => $entryId,
		'project_id' => 1, 'run_id' => $runId, 'target_seed' => $index + 1, 'created' => $now, 'created_by' => $actorId,
	]);
}

if ((int) $db->setQuery($db->getQuery(true)->select('COUNT(*)')->from($db->quoteName('#__joomleague_project_match'))->where('stage_id=' . $championshipStageId))->loadResult() === 0) {
	$planner = new SchedulePlannerService($db);
	$options = $planner->defaults($championshipStageId);
	$options += [];
	$options['start_date'] = '2036-03-01'; $options['start_time'] = '18:30'; $options['round_interval_days'] = 7;
	$options['published'] = 1; $options['assign_home_venues'] = 1;
	$planner->apply($championshipStageId, $options, $actorId);
}

if ((int) $db->setQuery($db->getQuery(true)->select('COUNT(*)')->from($db->quoteName('#__joomleague_standing_adjustment'))->where('project_id=1'))->loadResult() === 0) {
	$insert('#__joomleague_standing_adjustment', [
		'uuid' => UuidFactory::v4(), 'project_id' => 1, 'stage_id' => null, 'stage_key' => 0,
		'project_entry_id' => $topEntries[3], 'scope_code' => 'total', 'metric_code' => 'points', 'adjustment_value' => '-2',
		'reason' => 'Fictional administrative penalty demonstrating manual standing adjustments.', 'effective_date' => '2035-09-01',
		'published' => 1, 'ordering' => 1, 'created' => $now, 'created_by' => $actorId,
	]);
}

// A separate eight-entry knockout stage powers the universal bracket view.
$knockoutStageId = (int) $db->setQuery($db->getQuery(true)->select('id')->from($db->quoteName('#__joomleague_project_stage'))
	->where('project_id=1')->where('code=' . $db->quote('demo-knockout')))->loadResult();
if ($knockoutStageId === 0) {
	$knockoutStageId = $insert('#__joomleague_project_stage', [
		'uuid' => UuidFactory::v4(), 'project_id' => 1, 'name' => 'Demo knockout cup', 'alias' => 'demo-knockout-cup',
		'code' => 'demo-knockout', 'stage_type' => 'knockout', 'entry_selection_mode' => 'explicit', 'sequence_number' => 3,
		'start_date' => '2036-04-01', 'end_date' => '2036-05-31', 'description' => 'Fictional eight-team elimination stage demonstrating the universal bracket.',
		'published' => 1, 'ordering' => 3, 'created' => $now, 'created_by' => $actorId,
	]);
	$allEntries = array_map('intval', $db->setQuery($db->getQuery(true)->select('id')->from($db->quoteName('#__joomleague_project_entry'))
		->where('project_id=1')->order(['seed_number ASC', 'id ASC']), 0, 8)->loadColumn());
	foreach ($allEntries as $index => $entryId) $insert('#__joomleague_stage_entry', [
		'stage_id' => $knockoutStageId, 'entry_id' => $entryId, 'project_id' => 1, 'ordering' => $index + 1,
		'seed_number' => $index + 1, 'manual_assignment' => 1, 'created' => $now, 'created_by' => $actorId,
	]);
	$roundDefinitions = [
		['Quarter-finals', 'quarter-finals', '2036-04-05', [[0,7],[3,4],[1,6],[2,5]]],
		['Semi-finals', 'semi-finals', '2036-04-19', [[0,3],[1,2]]],
		['Final', 'final', '2036-05-03', [[0,1]]],
	];
	$matchNumber = 100;
	foreach ($roundDefinitions as $roundIndex => [$roundName, $roundCode, $date, $pairs]) {
		$roundId = $insert('#__joomleague_project_round', [
			'uuid' => UuidFactory::v4(), 'project_id' => 1, 'stage_id' => $knockoutStageId, 'name' => $roundName,
			'alias' => $roundCode, 'code' => 'demo_' . str_replace('-', '_', $roundCode), 'round_type' => 'knockout',
			'sequence_number' => $roundIndex + 1, 'start_date' => $date, 'end_date' => $date,
			'lifecycle_state' => 'draft', 'published' => 1, 'ordering' => $roundIndex + 1, 'created' => $now, 'created_by' => $actorId,
		]);
		foreach ($pairs as $pairIndex => [$left, $right]) {
			$matchId = $insert('#__joomleague_project_match', [
				'uuid' => UuidFactory::v4(), 'project_id' => 1, 'stage_id' => $knockoutStageId, 'round_id' => $roundId,
				'code' => 'demo_knockout_' . ($roundIndex + 1) . '_' . ($pairIndex + 1), 'match_number' => (string) $matchNumber++,
				'contest_type' => 'head_to_head', 'scheduled_start' => $date . ' 17:00:00', 'duration_minutes' => 105,
				'status_code' => 'scheduled', 'description' => 'Fictional future knockout event.', 'published' => 1,
				'ordering' => $pairIndex + 1, 'created' => $now, 'created_by' => $actorId,
			]);
			foreach ([$left, $right] as $slot => $entryIndex) $insert('#__joomleague_match_participant', [
				'uuid' => UuidFactory::v4(), 'match_id' => $matchId, 'project_id' => 1, 'project_entry_id' => $allEntries[$entryIndex],
				'role_code' => 'participant', 'slot_number' => $slot + 1, 'result_status' => 'scheduled',
				'published' => 1, 'ordering' => $slot, 'created' => $now, 'created_by' => $actorId,
			]);
		}
	}
}

$reader = new StandingsReader($db); $recalculator = new StandingsRecalculator($db, $reader);
foreach ($reader->describe(1, null)['available_scopes'] as $scope) $recalculator->recalculate(1, null, (string) $scope, $actorId);

echo "Advanced demo scenarios configured.\n";
