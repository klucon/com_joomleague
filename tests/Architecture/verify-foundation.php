<?php

declare(strict_types=1);

define('_JEXEC', 1);

$root = dirname(__DIR__, 2);
$ruleValidatorFile = $root . '/administrator/components/com_joomleague/src/Service/ProjectRuleValidator.php';
require_once $ruleValidatorFile;
require_once $root . '/administrator/components/com_joomleague/src/Service/EntryModelValidator.php';
require_once $root . '/administrator/components/com_joomleague/src/Service/SourceSchemaClassifier.php';
require_once $root . '/administrator/components/com_joomleague/src/Service/LegacyVersionParser.php';
require_once $root . '/administrator/components/com_joomleague/src/Service/StandingsContractValidator.php';
require_once $root . '/administrator/components/com_joomleague/src/Service/SportProfileSchemaValidator.php';

use Joomleague\Component\Joomleague\Administrator\Service\EntryModelValidator;
use Joomleague\Component\Joomleague\Administrator\Service\LegacyVersionParser;
use Joomleague\Component\Joomleague\Administrator\Service\ProjectRuleValidator;
use Joomleague\Component\Joomleague\Administrator\Service\SourceSchemaClassifier;
use Joomleague\Component\Joomleague\Administrator\Service\SportProfileSchemaValidator;

$manifest = (string) file_get_contents($root . '/administrator/components/com_joomleague/joomleague.xml');
$admin = $root . '/administrator/components/com_joomleague';
$quickIcon = $root . '/plugins/quickicon/joomleague';
$consolePlugin = $root . '/plugins/console/joomleague';
$taskPlugin = $root . '/plugins/task/joomleague';
$packageManifest = (string) file_get_contents($root . '/build/pkg_joomleague.xml');

if (preg_match('/<uninstall>\s*<sql>/i', $manifest) === 1) {
	throw new RuntimeException('The component manifest must not run destructive SQL during uninstallation.');
}

foreach (['mysql', 'postgresql'] as $driver) {
	$uninstallSql = (string) file_get_contents($admin . '/sql/uninstall.' . ($driver === 'mysql' ? 'mysql.utf8' : 'postgresql') . '.sql');

	if (preg_match('/\bDROP\s+TABLE\b/i', $uninstallSql) === 1) {
		throw new RuntimeException(sprintf('%s uninstall SQL must preserve JoomLeague data tables.', $driver));
	}
}

$installerScript = (string) file_get_contents($admin . '/script.php');

foreach (['ProjectRuleValidator.php', 'EntryModelValidator.php', 'StandingsContractValidator.php', 'SportProfileSchemaValidator.php'] as $installerDependency) {
	if (!str_contains($installerScript, $installerDependency)) {
		throw new RuntimeException(sprintf('Installer profile synchronisation is missing the explicit %s bootstrap.', $installerDependency));
	}
}

$schemaClassifier = new SourceSchemaClassifier();
$schemaFixtures = [
	'canonical' => [
		'joomleague_project' => ['id', 'uuid', 'competition_id', 'profile_version_id'],
		'joomleague_sport_profile_version' => ['id', 'payload_checksum'],
		'joomleague_project_entry' => ['id', 'entry_kind'],
	],
	'legacy' => [
		'joomleague_project' => ['id', 'league_id', 'season_id', 'game_regular_time', 'points_after_regular_time', 'asset_id', 'is_utc_converted'],
		'joomleague_project_team' => ['id', 'team_id'],
		'joomleague_team_player' => ['id', 'projectteam_id'],
		'joomleague_league' => ['id', 'name'],
		'joomleague_playground' => ['id', 'name'],
		'joomleague_version' => ['id', 'version'],
	],
	'mixed' => [
		'joomleague_project' => ['id', 'uuid', 'competition_id', 'profile_version_id', 'league_id', 'game_regular_time', 'points_after_regular_time'],
		'joomleague_sport_profile_version' => ['id', 'payload_checksum'],
		'joomleague_project_entry' => ['id', 'entry_kind'],
		'joomleague_project_team' => ['id', 'team_id'],
		'joomleague_team_player' => ['id', 'projectteam_id'],
	],
	'unknown' => ['extensions' => ['extension_id', 'name']],
];

foreach ($schemaFixtures as $expectedClassification => $fixture) {
	$result = $schemaClassifier->classify($fixture);

	if ($result['classification'] !== $expectedClassification || $result['evidence'] !== array_values(array_unique($result['evidence']))) {
		throw new RuntimeException(sprintf('Source schema classifier failed the %s fixture.', $expectedClassification));
	}
}

if (!in_array('joomleague_2_or_newer', $schemaClassifier->classify($schemaFixtures['legacy'])['candidates'], true)) {
	throw new RuntimeException('Legacy schema classifier must preserve version uncertainty as candidates.');
}

$versionParser = new LegacyVersionParser();
$versionResult = $versionParser->parse([[
	'major' => '3',
	'minor' => '0',
	'build' => '23',
	'revision' => '1969985c7',
	'version' => 'b',
]]);

if ($versionResult['status'] !== 'detected'
	|| $versionResult['version'] !== '3.0.23.1969985c7-b'
	|| $versionResult['family'] !== 'joomleague_3_x') {
	throw new RuntimeException('Legacy version parser failed the JoomLeague 3 fixture.');
}

$fallbackVersion = $versionParser->parse([
	['major' => 'invalid', 'minor' => '0', 'build' => '1'],
	['major' => '1', 'minor' => '5', 'build' => '0', 'revision' => 'a'],
]);

if ($fallbackVersion['version'] !== '1.5.0.a' || $fallbackVersion['family'] !== 'joomleague_1_5_or_older') {
	throw new RuntimeException('Legacy version parser must skip malformed newer rows and inspect older rows.');
}

if ($versionParser->parse([])['status'] !== 'missing'
	|| $versionParser->parse([['major' => '-1', 'minor' => '0', 'build' => '0']])['status'] !== 'invalid') {
	throw new RuntimeException('Legacy version parser must distinguish missing and invalid version evidence.');
}

$unsafeVersion = $versionParser->parse([[
	'major' => '3',
	'minor' => '0',
	'build' => '23',
	'revision' => '<script>',
	'version' => 'stable<script>',
]]);

if ($unsafeVersion['version'] !== '3.0.23' || str_contains((string) $unsafeVersion['version'], '<')) {
	throw new RuntimeException('Legacy version parser must reject unsafe version suffixes.');
}

if ($manifest === '') {
	throw new RuntimeException('Manifest cannot be read.');
}

foreach (['joomleague.xml', 'services/provider.php', 'src/Extension/Joomleague.php', 'language/en-GB/plg_quickicon_joomleague.ini', 'language/en-GB/plg_quickicon_joomleague.sys.ini'] as $pluginFile) {
	if (!is_file($quickIcon . '/' . $pluginFile)) {
		throw new RuntimeException(sprintf('Quick Icon plugin is missing %s.', $pluginFile));
	}
}

foreach (['joomleague.xml', 'services/provider.php', 'src/Extension/Joomleague.php', 'src/Console/ResetDemoDataCommand.php', 'language/en-GB/plg_console_joomleague.ini', 'language/en-GB/plg_console_joomleague.sys.ini'] as $pluginFile) {
	if (!is_file($consolePlugin . '/' . $pluginFile)) {
		throw new RuntimeException(sprintf('Console plugin is missing %s.', $pluginFile));
	}
}

foreach (['joomleague.xml', 'services/provider.php', 'src/Extension/Joomleague.php', 'language/en-GB/plg_task_joomleague.ini', 'language/en-GB/plg_task_joomleague.sys.ini'] as $pluginFile) {
	if (!is_file($taskPlugin . '/' . $pluginFile)) {
		throw new RuntimeException(sprintf('Task plugin is missing %s.', $pluginFile));
	}
}

foreach (glob($root . '/modules/mod_*', GLOB_ONLYDIR) ?: [] as $moduleDirectory) {
	$module = basename($moduleDirectory);
	$moduleManifest = $moduleDirectory . '/' . $module . '.xml';

	if (!is_file($moduleManifest)) {
		throw new RuntimeException(sprintf('Module %s is missing its manifest.', $module));
	}

	if (!str_contains($packageManifest, 'id="' . $module . '"')) {
		throw new RuntimeException(sprintf('Module %s is not included in the JoomLeague package.', $module));
	}
}

$taskSource = (string) file_get_contents($taskPlugin . '/src/Extension/Joomleague.php');
$quickIconSource = (string) file_get_contents($quickIcon . '/src/Extension/Joomleague.php');

if (!str_contains($taskSource, 'joomleague.telemetry') || !str_contains($taskSource, 'TaskPluginTrait')) {
	throw new RuntimeException('Telemetry must be exposed through the native Joomla Task Scheduler.');
}

if (str_contains($quickIconSource, 'TelemetryService') || str_contains($quickIconSource, 'maybeSend')) {
	throw new RuntimeException('Quick Icon rendering must not perform telemetry network calls.');
}

$resetCommandSource = (string) file_get_contents($consolePlugin . '/src/Console/ResetDemoDataCommand.php');

foreach (['JOOMLEAGUE_ALLOW_DEMO_RESET', "addOption('force'", '#__joomleague_sport_type', 'RESTART IDENTITY CASCADE', 'FOREIGN_KEY_CHECKS'] as $resetGuard) {
	if (!str_contains($resetCommandSource, $resetGuard)) {
		throw new RuntimeException(sprintf('Demo reset command is missing required guard or driver behavior: %s.', $resetGuard));
	}
}

if (str_contains($resetCommandSource, '#__joomleague_sport_profile\'') || str_contains($resetCommandSource, '#__joomleague_sport_profile_version\'')) {
	throw new RuntimeException('Demo reset must preserve bundled sport profiles and their immutable versions.');
}

$telemetrySource = (string) file_get_contents($admin . '/src/Service/TelemetryService.php');
$schedulerStatusSource = (string) file_get_contents($admin . '/src/Service/SchedulerStatusService.php');

foreach (["bootComponent('com_scheduler')", "createModel('Tasks', 'Administrator'", "filter.type', self::TELEMETRY_TASK_TYPE", 'catch (Throwable)'] as $schedulerStatusRequirement) {
	if (!str_contains($schedulerStatusSource, $schedulerStatusRequirement)) {
		throw new RuntimeException(sprintf('Scheduler diagnostics are missing requirement: %s.', $schedulerStatusRequirement));
	}
}

if (str_contains($schedulerStatusSource, '#__scheduler_tasks')) {
	throw new RuntimeException('Scheduler diagnostics must use the com_scheduler MVC API instead of its database table.');
}

foreach (['https://stats.klucon.cz/collect', "telemetry_consent', 'never'", "'install_id'", "'jl_version'", "'joomla_version'", "'php_version'", "'language'"] as $telemetryRequirement) {
	if (!str_contains($telemetrySource, $telemetryRequirement)) {
		throw new RuntimeException(sprintf('Telemetry implementation is missing requirement: %s.', $telemetryRequirement));
	}
}

foreach (['domain', 'url', 'username', 'email', 'ip_address'] as $forbiddenPayloadKey) {
	if (preg_match('/[\'\"]' . preg_quote($forbiddenPayloadKey, '/') . '[\'\"]\s*=>/', $telemetrySource) === 1) {
		throw new RuntimeException(sprintf('Telemetry payload must not contain %s.', $forbiddenPayloadKey));
	}
}

if (!str_contains($quickIconSource, 'index.php?option=com_joomleague&view=dashboard') || !str_contains($quickIconSource, "authorise('core.manage', 'com_joomleague')")) {
	throw new RuntimeException('Quick Icon must link to the dashboard and enforce component management ACL.');
}

$dashboardModel = (string) file_get_contents($admin . '/src/Model/DashboardModel.php');
$dashboardTemplate = (string) file_get_contents($admin . '/tmpl/dashboard/default.php');
$componentConfig = (string) file_get_contents($admin . '/config.xml');

foreach (['getOverview', 'getSiteClub', 'getClubMatches'] as $dashboardRequirement) {
	if (!str_contains($dashboardModel, $dashboardRequirement)) throw new RuntimeException('Dashboard operational data is incomplete.');
}
if (!str_contains($componentConfig, 'name="site_club_id"')
	|| !str_contains($componentConfig, 'name="dashboard_match_limit"')
	|| !str_contains($componentConfig, 'name="dashboard_match_link"')
	|| str_contains($dashboardTemplate, 'COM_JOOMLEAGUE_DASHBOARD_FOUNDATION_TITLE')
	|| str_contains($dashboardTemplate, 'COM_JOOMLEAGUE_DASHBOARD_PREPARED_TITLE')
	|| preg_match('/<script\b/i', $dashboardTemplate) === 1) {
	throw new RuntimeException('Dashboard must remain operational and configurable, and must not introduce custom JavaScript.');
}

preg_match_all('/<file\s+driver="(mysql|postgresql)"[^>]*>sql\/install\.[^<]+<\/file>/', $manifest, $matches);
$drivers = array_values(array_unique($matches[1] ?? []));

sort($drivers);

if ($drivers !== ['mysql', 'postgresql']) {
	throw new RuntimeException('Both mysql and postgresql install drivers are required.');
}

if (str_contains($manifest, 'charset="utf8mb4"')) {
	throw new RuntimeException('Joomla SQL manifests require charset="utf8"; utf8mb4 would be skipped by the installer.');
}

$mysqlInstall = (string) file_get_contents($admin . '/sql/install.mysql.utf8.sql');
$postgresInstall = (string) file_get_contents($admin . '/sql/install.postgresql.sql');
preg_match_all('/CREATE TABLE IF NOT EXISTS [`"]#__([a-z0-9_]+)[`"]/', $mysqlInstall, $mysqlTables);
preg_match_all('/CREATE TABLE IF NOT EXISTS [`"]#__([a-z0-9_]+)[`"]/', $postgresInstall, $postgresTables);
$mysqlTables = array_values(array_unique($mysqlTables[1] ?? []));
$postgresTables = array_values(array_unique($postgresTables[1] ?? []));
sort($mysqlTables);
sort($postgresTables);
$expectedTables = [
	'joomleague_club',
	'joomleague_competition',
	'joomleague_migration_batch',
	'joomleague_migration_issue',
	'joomleague_migration_record',
	'joomleague_organization_media_history',
	'joomleague_organization_name_history',
	'joomleague_match_participant',
	'joomleague_match_lineup_member',
	'joomleague_match_lineup_change',
	'joomleague_match_actor_role',
	'joomleague_match_event',
	'joomleague_match_statistic_value',
	'joomleague_match_result',
	'joomleague_match_score_segment',
	'joomleague_match_score_value',
	'joomleague_standing_adjustment',
	'joomleague_standing_current',
	'joomleague_standing_snapshot',
	'joomleague_standing_snapshot_row',
	'joomleague_profile_template_config',
	'joomleague_position_event_type',
	'joomleague_position_statistic',
	'joomleague_project',
	'joomleague_project_actor_role',
	'joomleague_project_match',
	'joomleague_project_entry',
	'joomleague_project_entry_member',
	'joomleague_project_rule_config',
	'joomleague_project_round',
	'joomleague_schedule_generation',
	'joomleague_schedule_generation_match',
	'joomleague_project_stage',
	'joomleague_stage_entry',
	'joomleague_stage_transition',
	'joomleague_stage_transition_run',
	'joomleague_stage_transition_assignment',
	'joomleague_project_template_config',
	'joomleague_season',
	'joomleague_sport_profile',
	'joomleague_sport_profile_version',
	'joomleague_sport_position',
	'joomleague_sport_type',
	'joomleague_event_type',
	'joomleague_statistic',
	'joomleague_team',
	'joomleague_person',
	'joomleague_venue',
];
sort($expectedTables);

if ($mysqlTables !== $postgresTables || $mysqlTables !== $expectedTables) {
	throw new RuntimeException('MariaDB/MySQL and PostgreSQL must define the same canonical foundation tables.');
}

$resetCommand = (string) file_get_contents($consolePlugin . '/src/Console/ResetDemoDataCommand.php');
$resetTables = array_values(array_diff($expectedTables, ['joomleague_sport_profile', 'joomleague_sport_profile_version']));

foreach ($resetTables as $resetTable) {
	if (!str_contains($resetCommand, "'#__" . $resetTable . "'")) {
		throw new RuntimeException(sprintf('Demo-data reset is missing runtime table %s.', $resetTable));
	}
}

foreach (['joomleague_sport_profile', 'joomleague_sport_profile_version'] as $preservedTable) {
	if (str_contains($resetCommand, "'#__" . $preservedTable . "'")) {
		throw new RuntimeException(sprintf('Demo-data reset must preserve bundled table %s.', $preservedTable));
	}
}

foreach (['competition_id', 'season_id', 'sport_type_id', 'profile_version_id', 'project_type', 'timezone'] as $projectColumn) {
	foreach (['mysql' => $mysqlInstall, 'postgresql' => $postgresInstall] as $driver => $schema) {
		if (!str_contains($schema, '`' . $projectColumn . '`') && !str_contains($schema, '"' . $projectColumn . '"')) {
			throw new RuntimeException(sprintf('%s project schema is missing canonical column %s.', $driver, $projectColumn));
		}
	}
}

if (
	!str_contains($mysqlInstall, "`current_round_mode` VARCHAR(30) NOT NULL DEFAULT 'start'")
	|| !str_contains($mysqlInstall, '`auto_advance_seconds` INT UNSIGNED NULL DEFAULT 7200')
	|| !str_contains($postgresInstall, '"current_round_mode" VARCHAR(30) NOT NULL DEFAULT \'start\'')
	|| !str_contains($postgresInstall, '"auto_advance_seconds" INTEGER NULL DEFAULT 7200')
) {
	throw new RuntimeException('Both drivers must use start mode and a 7200-second automatic round offset for new projects.');
}

foreach ([
	'uq_jl_sport_type_profile_binding' => 'sport type/profile version binding key',
	'fk_jl_project_sport_binding' => 'project sport type/profile version binding',
	'chk_jl_season_dates' => 'season date chronology',
	'chk_jl_project_dates' => 'project date chronology',
	'chk_jl_project_auto_advance' => 'non-negative project auto-advance interval',
	'chk_jl_project_stage_dates' => 'project-stage date chronology',
	'fk_jl_stage_transition_source' => 'stage-transition source ownership',
	'fk_jl_stage_transition_target' => 'stage-transition target ownership',
	'chk_jl_stage_transition_distinct' => 'distinct stage-transition endpoints',
	'chk_jl_project_entry_target' => 'project entry discriminator integrity',
	'chk_jl_entry_member_dates' => 'project entry membership chronology',
] as $constraint => $description) {
	if (!str_contains($mysqlInstall, $constraint) || !str_contains($postgresInstall, $constraint)) {
		throw new RuntimeException(sprintf('Both database drivers must enforce %s.', $description));
	}
}

foreach (['mysql' => $mysqlTables, 'postgresql' => $postgresTables] as $driver => $installTables) {
	$updateTables = [];
	$updateSql = '';
	$updateFiles = glob($admin . '/sql/updates/' . $driver . '/*.sql') ?: [];

	foreach ($updateFiles as $updateFile) {
		$fileSql = (string) file_get_contents($updateFile);
		$updateSql .= "\n" . $fileSql;
		preg_match_all('/CREATE TABLE IF NOT EXISTS [`"]#__([a-z0-9_]+)[`"]/', $fileSql, $matches);
		$updateTables = array_merge($updateTables, $matches[1] ?? []);
	}

	$updateTables = array_values(array_unique($updateTables));
	sort($updateTables);

	if ($installTables !== $updateTables) {
		throw new RuntimeException(sprintf('%s update files must cumulatively create every fresh-install table.', $driver));
	}

	foreach (['uq_jl_sport_type_profile_binding', 'fk_jl_project_sport_binding', 'chk_jl_season_dates', 'chk_jl_project_dates', 'chk_jl_project_auto_advance', 'chk_jl_project_stage_dates', 'chk_jl_project_stage_entry_mode', 'fk_jl_stage_entry_stage', 'fk_jl_stage_entry_entry', 'fk_jl_stage_transition_source', 'fk_jl_stage_transition_target', 'chk_jl_stage_transition_distinct', 'chk_jl_project_round_sequence', 'chk_jl_project_round_dates', 'fk_jl_project_match_round', 'fk_jl_match_participant_match', 'uq_jl_match_participant_scope', 'fk_jl_match_lineup_participant', 'fk_jl_match_lineup_source', 'fk_jl_match_lineup_person', 'uq_jl_match_lineup_scope', 'fk_jl_lineup_change_outgoing', 'fk_jl_lineup_change_incoming', 'chk_jl_lineup_change_members', 'chk_jl_lineup_change_sequence', 'fk_jl_project_actor_role_project', 'chk_jl_project_actor_role_actor', 'chk_jl_project_actor_role_dates', 'fk_jl_match_actor_role_match', 'fk_jl_match_actor_role_source', 'chk_jl_match_actor_role_actor', 'fk_jl_match_event_match', 'fk_jl_match_event_participant', 'fk_jl_match_event_primary_person', 'fk_jl_match_event_secondary_person', 'chk_jl_match_event_people', 'chk_jl_match_event_clock_unit', 'fk_jl_match_stat_value_match', 'fk_jl_match_stat_value_participant', 'fk_jl_match_stat_value_person', 'chk_jl_match_stat_value_target', 'chk_jl_match_stat_value_payload', 'fk_jl_match_result_match', 'fk_jl_match_score_segment_parent', 'fk_jl_match_score_value_participant', 'chk_jl_match_score_value_payload', 'chk_jl_match_participant_slot', 'chk_jl_project_entry_target', 'chk_jl_entry_member_dates', 'chk_jl_org_name_history_owner', 'chk_jl_org_name_history_dates', 'chk_jl_org_media_history_owner', 'chk_jl_org_media_history_dates'] as $constraint) {
		if (!str_contains($updateSql, $constraint)) {
			throw new RuntimeException(sprintf('%s updates are missing canonical constraint %s.', $driver, $constraint));
		}
	}

	sort($updateFiles, SORT_STRING);
	$latestUpdate = basename((string) end($updateFiles));

	if ($latestUpdate !== '6.2.0-2026081601.sql') {
		throw new RuntimeException(sprintf('%s update ordering must end at the schema anchor.', $driver));
	}

	if (preg_match('/ADD\s+COLUMN\s+IF\s+NOT\s+EXISTS/i', $updateSql) === 1) {
		throw new RuntimeException(sprintf('%s updates use ADD COLUMN IF NOT EXISTS, which Joomla Database Checker parses incorrectly.', $driver));
	}

	if (preg_match('/CREATE\s+(?:UNIQUE\s+)?INDEX\s+IF\s+NOT\s+EXISTS/i', $updateSql) === 1) {
		throw new RuntimeException(sprintf('%s updates use CREATE INDEX IF NOT EXISTS, which Joomla Database Checker parses incorrectly.', $driver));
	}
}

$stageForm = file_get_contents($root . '/administrator/components/com_joomleague/forms/stage.xml');
$stageFilterForm = file_get_contents($root . '/administrator/components/com_joomleague/forms/filter_stages.xml');
$stageTable = file_get_contents($root . '/administrator/components/com_joomleague/src/Table/StageTable.php');
$stageListModel = file_get_contents($root . '/administrator/components/com_joomleague/src/Model/StagesModel.php');
$stageListTemplate = file_get_contents($root . '/administrator/components/com_joomleague/tmpl/stages/default.php');

foreach ([$stageForm, $stageFilterForm, $stageTable, $stageListModel, $stageListTemplate] as $stageSource) {
	if ($stageSource === false || str_contains($stageSource, 'lifecycle_state')) {
		throw new RuntimeException('Project stages must not expose or depend on a manually maintained lifecycle state.');
	}
}

foreach (['mysql' => '`lifecycle_state`', 'postgresql' => '"lifecycle_state"'] as $driver => $column) {
	$installSql = file_get_contents($root . '/administrator/components/com_joomleague/sql/install.' . ($driver === 'mysql' ? 'mysql.utf8' : 'postgresql') . '.sql');
	if ($installSql === false) {
		throw new RuntimeException(sprintf('Unable to read the %s install schema.', $driver));
	}
	$stageStart = strpos($installSql, $driver === 'mysql' ? 'CREATE TABLE IF NOT EXISTS `#__joomleague_project_stage`' : 'CREATE TABLE IF NOT EXISTS "#__joomleague_project_stage"');
	$stageEnd = strpos($installSql, $driver === 'mysql' ? 'CREATE TABLE IF NOT EXISTS `#__joomleague_project_round`' : 'CREATE TABLE IF NOT EXISTS "#__joomleague_project_round"', $stageStart ?: 0);
	$stageSql = $stageStart === false || $stageEnd === false ? '' : substr($installSql, $stageStart, $stageEnd - $stageStart);
	if ($stageSql === '' || str_contains($stageSql, $column)) {
		throw new RuntimeException(sprintf('%s fresh stage schema must not contain lifecycle_state.', $driver));
	}
}

$languageFiles = [
	$admin . '/language/en-GB/com_joomleague.ini',
	$admin . '/language/en-GB/com_joomleague.sys.ini',
];
$definedLanguageKeys = [];
$languageDirectories = array_map('basename', glob($admin . '/language/*', GLOB_ONLYDIR) ?: []);

sort($languageDirectories);

if ($languageDirectories !== ['cs-CZ', 'en-GB']) {
	throw new RuntimeException('The component package must contain exactly the canonical en-GB source and bundled cs-CZ translation.');
}

foreach (['com_joomleague.ini', 'com_joomleague.sys.ini'] as $languageFilename) {
	$keySets = [];

	foreach (['en-GB', 'cs-CZ'] as $languageTag) {
		$contents = (string) file_get_contents($admin . '/language/' . $languageTag . '/' . $languageFilename);
		preg_match_all('/^(COM_JOOMLEAGUE_[A-Z0-9_]+)=/m', $contents, $matches);
		$keySets[$languageTag] = $matches[1] ?? [];
	}

	if ($keySets['en-GB'] !== $keySets['cs-CZ']) {
		throw new RuntimeException(sprintf('The cs-CZ key order must match en-GB in %s.', $languageFilename));
	}
}

foreach ($languageFiles as $languageFile) {
	$lines = file($languageFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];
	$fileKeys = [];

	foreach ($lines as $line) {
		if (preg_match('/^(COM_JOOMLEAGUE_[A-Z0-9_]+)=/', $line, $match) === 1) {
			if (isset($fileKeys[$match[1]])) {
				throw new RuntimeException(sprintf('Duplicate language key %s in %s.', $match[1], basename($languageFile)));
			}

			$fileKeys[$match[1]] = true;

			if (isset($definedLanguageKeys[$match[1]]) && $definedLanguageKeys[$match[1]] !== $languageFile) {
				continue;
			}

			$definedLanguageKeys[$match[1]] = $languageFile;
		}
	}
}

$profiles = glob($admin . '/resources/sport-profiles/*.json') ?: [];
$profileCodes = [];
$requiredLanguageKeys = [];
$projectRuleFieldCount = 0;
$ruleValidator = new ProjectRuleValidator();
$entryModelValidator = new EntryModelValidator();
$profileSchemaValidator = new SportProfileSchemaValidator();
$templateData = json_decode((string) file_get_contents($admin . '/resources/template-definitions/templates.json'), true, 512, JSON_THROW_ON_ERROR);
$templateDefinitions = $templateData['definitions'] ?? [];

if (($templateData['schema_version'] ?? null) !== '1.0.0' || !is_array($templateDefinitions)) {
	throw new RuntimeException('Template definitions use an unsupported schema.');
}

$templateIterator = new RecursiveIteratorIterator(new RecursiveArrayIterator($templateDefinitions));

foreach ($templateIterator as $value) {
	if (is_string($value) && str_starts_with($value, 'COM_JOOMLEAGUE_')) {
		$requiredLanguageKeys[$value] = true;
	}
}

foreach ($profiles as $profile) {
	$data = json_decode((string) file_get_contents($profile), true, 512, JSON_THROW_ON_ERROR);

	foreach (['schema_version', 'code', 'version', 'name_key', 'description_key'] as $field) {
		if (!isset($data[$field]) || !is_string($data[$field]) || $data[$field] === '') {
			throw new RuntimeException(sprintf('%s is missing required field %s.', basename($profile), $field));
		}
	}

	if (!in_array($data['schema_version'], ['1.0-transitional', SportProfileSchemaValidator::CURRENT_VERSION], true)) {
		throw new RuntimeException(sprintf('%s uses an unsupported schema version.', basename($profile)));
	}
	if ($data['schema_version'] === SportProfileSchemaValidator::CURRENT_VERSION) {
		if (version_compare($data['version'], '1.5.0', '<')) throw new RuntimeException(sprintf('%s reuses an immutable development profile version.', basename($profile)));
		$profileSchemaValidator->validate($data);
	}

	$ruleValidator->validateProfileSchema($data);
	$entryModelValidator->validate($data);
	$projectRuleFieldCount += count($data['project_rule_schema']['fields']);

	foreach (['result_status_codes', 'outcome_codes', 'participant_status_codes'] as $codeList) {
		foreach ($data['match'][$codeList] ?? [] as $code) {
			$requiredLanguageKeys['COM_JOOMLEAGUE_RESULT_CODE_' . strtoupper((string) $code)] = true;
		}
	}

	foreach ($data['match']['score']['segment_types'] ?? [] as $segmentType) {
		if (is_array($segmentType) && is_string($segmentType['condition_code'] ?? null)) {
			$requiredLanguageKeys['COM_JOOMLEAGUE_RESULT_CONDITION_' . strtoupper($segmentType['condition_code'])] = true;
		}
	}

	if (isset($profileCodes[$data['code']])) {
		throw new RuntimeException(sprintf('Duplicate profile code: %s.', $data['code']));
	}

	$profileCodes[$data['code']] = true;
	$templateDefaults = $data['template_defaults'] ?? [];

	if (!is_array($templateDefaults)) {
		throw new RuntimeException(sprintf('%s has invalid template defaults.', basename($profile)));
	}

	foreach ($templateDefaults as $templateCode => $values) {
		if (!isset($templateDefinitions[$templateCode]) || !is_array($values)) {
			throw new RuntimeException(sprintf('%s references an unknown template definition: %s.', basename($profile), $templateCode));
		}

		$unknownFields = array_diff_key($values, $templateDefinitions[$templateCode]['fields'] ?? []);

		if ($unknownFields !== []) {
			throw new RuntimeException(sprintf('%s contains unknown fields for template %s: %s.', basename($profile), $templateCode, implode(', ', array_keys($unknownFields))));
		}
	}

	$displayData = $data;
	unset($displayData['migration']);
	$iterator = new RecursiveIteratorIterator(new RecursiveArrayIterator($displayData));

	foreach ($iterator as $value) {
		if (is_string($value) && str_starts_with($value, 'COM_JOOMLEAGUE_')) {
			$requiredLanguageKeys[$value] = true;
		}
	}
}

$sourceFiles = array_merge(
	glob($admin . '/*.xml') ?: [],
	glob($admin . '/forms/*.xml') ?: [],
	glob($admin . '/src/**/*.php', GLOB_BRACE) ?: [],
	glob($admin . '/tmpl/**/*.php', GLOB_BRACE) ?: [],
	[$admin . '/script.php']
);

foreach ($sourceFiles as $sourceFile) {
	$contents = (string) file_get_contents($sourceFile);
	preg_match_all('/COM_JOOMLEAGUE_[A-Z0-9_]+/', $contents, $matches);

	foreach ($matches[0] ?? [] as $key) {
		if (str_ends_with($key, '_')) {
			continue;
		}

		$requiredLanguageKeys[$key] = true;
	}
}

$missingLanguageKeys = array_diff_key($requiredLanguageKeys, $definedLanguageKeys);

if ($missingLanguageKeys !== []) {
	ksort($missingLanguageKeys);
	throw new RuntimeException('Missing en-GB language keys: ' . implode(', ', array_keys($missingLanguageKeys)));
}

preg_match_all('/<menu\s+link="[^"]*view=([a-z0-9_]+)"/', $manifest, $menuViews);
$missingViews = [];

foreach (array_unique($menuViews[1] ?? []) as $view) {
	$classFile = $admin . '/src/View/' . ucfirst($view) . '/HtmlView.php';

	if (!is_file($classFile)) {
		$missingViews[] = $view;
	}
}

if ($missingViews !== []) {
	throw new RuntimeException('Manifest menu views without HtmlView classes: ' . implode(', ', $missingViews));
}

$sqlExchange = (string) file_get_contents($admin . '/src/Service/SqlDataExchangeService.php');
$databaseToolsController = (string) file_get_contents($admin . '/src/Controller/DatabasetoolsController.php');
$dataImportController = (string) file_get_contents($admin . '/src/Controller/DataimportController.php');

if (!str_contains($sqlExchange, "'COM_JOOMLEAGUE_DATAIMPORT_ERROR_STATEMENT'")
	|| !str_contains($sqlExchange, "transactionStart()")
	|| !str_contains($sqlExchange, "#__joomleague_")
	|| !str_contains($sqlExchange, "'/^CREATE\\s")
	|| !str_contains($sqlExchange, "'/^INSERT\\s")
	|| !str_contains($databaseToolsController, "Session::checkToken()")
	|| !str_contains($databaseToolsController, "authorise('core.manage', 'com_joomleague')")
	|| !str_contains($databaseToolsController, "->sendHeaders()")
	|| !str_contains($dataImportController, "Session::checkToken()")
	|| !str_contains($dataImportController, "authorise('core.manage', 'com_joomleague')")
	|| !str_contains($dataImportController, "is_uploaded_file")
	|| !str_contains($dataImportController, 'Utility::getMaxUploadSize()')
	|| !str_contains($sqlExchange, 'Utility::getMaxUploadSize()')
	|| str_contains($dataImportController, '100 * 1024 * 1024')
	|| str_contains($sqlExchange, '100 * 1024 * 1024')) {
	throw new RuntimeException('SQL data exchange must retain restricted statements, transactions, upload validation, CSRF and ACL checks.');
}

$dataImportTemplate = (string) file_get_contents($admin . '/tmpl/dataimport/default.php');
if (!str_contains($dataImportTemplate, 'name="task" value=""')) {
	throw new RuntimeException('SQL import form must expose the Joomla toolbar task field.');
}

$editorTabs = [
	'club' => 6,
	'competition' => 3,
	'project' => 6,
	'season' => 3,
	'sporttype' => 3,
	'team' => 5,
	'person' => 5,
	'position' => 4,
	'event' => 2,
	'statistic' => 2,
	'venue' => 6,
	'projectentry' => 3,
	'entrymember' => 3,
];

foreach ($editorTabs as $editor => $expectedTabs) {
	$template = (string) file_get_contents($admin . '/tmpl/' . $editor . '/edit.php');

	if (!str_contains($template, "uitab.startTabSet") || substr_count($template, "uitab.addTab") !== $expectedTabs) {
		throw new RuntimeException(sprintf('%s editor must use %d Joomla tabs.', ucfirst($editor), $expectedTabs));
	}

	if (!in_array($editor, ['sporttype', 'position', 'event', 'statistic', 'projectentry', 'entrymember'], true) && (!str_contains($template, "'description', Text::_('JGLOBAL_DESCRIPTION')") || !str_contains($template, '$description->label') || !str_contains($template, '$description->input'))) {
		throw new RuntimeException(sprintf('%s editor must render Description as a dedicated full-width tab.', ucfirst($editor)));
	}

	if (str_contains($template, 'col-xl-8 col-lg-10') || str_contains($template, 'col-xl-5 col-lg-7')) {
		throw new RuntimeException(sprintf('%s editor fieldsets must use the full Joomla grid width.', ucfirst($editor)));
	}
}

$clubForm = (string) file_get_contents($admin . '/forms/club.xml');
$clubModel = (string) file_get_contents($admin . '/src/Model/ClubModel.php');
if (!str_contains($clubForm, 'name="create_team"') || !str_contains($clubForm, 'name="create_venue"')
	|| substr_count($clubForm, 'layout="joomla.form.field.radio.switcher"') < 2
	|| !str_contains($clubModel, 'ClubRelatedRecordCreator') || !str_contains($clubModel, 'transactionStart()')) {
	throw new RuntimeException('New clubs must offer transactional Joomla switchers for related team and venue creation.');
}

$teamForm = (string) file_get_contents($admin . '/forms/team.xml');
$teamTemplate = (string) file_get_contents($admin . '/tmpl/team/edit.php');
$historyRepository = (string) file_get_contents($admin . '/src/Service/OrganizationHistoryRepository.php');

if (substr_count($teamTemplate, 'class="col-12"') !== 5
	|| preg_match('/class="col-(?:sm|md|lg|xl|xxl)-/', $teamTemplate) === 1) {
	throw new RuntimeException('Every Team editor tab must use the full Joomla grid width.');
}

foreach ([$clubForm, $teamForm] as $organizationForm) {
	if (!str_contains($organizationForm, 'name="name_history"')
		|| !str_contains($organizationForm, 'name="media_history"')
		|| !str_contains($organizationForm, 'formsource="administrator/components/com_joomleague/forms/organization_name_history.xml"')
		|| !str_contains($organizationForm, 'formsource="administrator/components/com_joomleague/forms/organization_media_history.xml"')
		|| substr_count($organizationForm, 'layout="joomla.form.field.subform.repeatable-table"') < 2
		|| substr_count($organizationForm, 'buttons="add"') < 2) {
		throw new RuntimeException('Club and team forms must expose additive Joomla name and logo history subforms.');
	}
}

foreach (['organization_name_history.xml', 'organization_media_history.xml'] as $historyFormFile) {
	$historyForm = (string) file_get_contents($admin . '/forms/' . $historyFormFile);

	if (!str_contains($historyForm, 'name="remove_record"')
		|| !str_contains($historyForm, 'layout="joomla.form.field.radio.switcher"')
		|| !str_contains($historyForm, 'description="COM_JOOMLEAGUE_FIELD_HISTORY_REMOVE_DESC"')) {
		throw new RuntimeException(sprintf('%s must require an explicit described Joomla removal switch.', $historyFormFile));
	}
}

if (!str_contains($historyRepository, "'club' => ['club_id', 'team_id']")
	|| !str_contains($historyRepository, "'team' => ['team_id', 'club_id']")
	|| !str_contains($historyRepository, "->where(\$this->database->quoteName(\$ownerColumn) . ' = :entityId')")
	|| !str_contains($historyRepository, 'getAffectedRows() !== 1')
	|| str_contains($historyRepository, 'deleteObject(')) {
	throw new RuntimeException('Organization history must validate ownership and only delete explicitly selected records.');
}

$projectEntryController = (string) file_get_contents($admin . '/src/Controller/ProjectentryController.php');
$projectEntriesTemplate = (string) file_get_contents($admin . '/tmpl/projectentries/default.php');
$projectEntryField = (string) file_get_contents($admin . '/src/Field/ProjectentrykindField.php');
$projectEntryModel = (string) file_get_contents($admin . '/src/Model/ProjectentryModel.php');

foreach (["authorise('core.create'", "authorise('core.edit'"] as $aclRequirement) {
	if (!str_contains($projectEntryController, $aclRequirement)) {
		throw new RuntimeException('Project participant form controller is missing Joomla ACL enforcement.');
	}
}

if (!str_contains($projectEntriesTemplate, "HTMLHelper::_('form.token')")
	|| !str_contains($projectEntriesTemplate, "HTMLHelper::_('grid.checkall')")) {
	throw new RuntimeException('Project participant list must retain Joomla CSRF and selection controls.');
}

if (!str_contains($projectEntryField, "['entry_model']['allowed_kinds']")
	|| !str_contains($projectEntryModel, "['entry_model']['allowed_kinds']")) {
	throw new RuntimeException('Project participant types must be constrained by the immutable project profile in both UI and server validation.');
}

if (!str_contains($projectEntryModel, 'loadStoredProjectId($entryId)')) {
	throw new RuntimeException('Project participant edits must retain their stored project boundary.');
}

$entryMemberController = (string) file_get_contents($admin . '/src/Controller/EntrymemberController.php');
$entryMembersTemplate = (string) file_get_contents($admin . '/tmpl/entrymembers/default.php');
$entryMemberModel = (string) file_get_contents($admin . '/src/Model/EntrymemberModel.php');
$entryMemberRoleField = (string) file_get_contents($admin . '/src/Field/EntrymemberroleField.php');

if (!str_contains($entryMemberController, "authorise('core.create'")
	|| !str_contains($entryMemberController, "authorise('core.edit'")
	|| !str_contains($entryMembersTemplate, "HTMLHelper::_('form.token')")) {
	throw new RuntimeException('Project participant member CRUD must retain Joomla ACL and CSRF enforcement.');
}

if (!str_contains($entryMemberModel, 'loadStoredEntryId($memberId)')
	|| !str_contains($entryMemberModel, 'profileHasRole($entry->profile, $roleCode, $personType)')
	|| !str_contains($entryMemberRoleField, 'data-person-type')) {
	throw new RuntimeException('Project participant members must retain entry boundaries and profile-filtered roles.');
}

$matchResultController = (string) file_get_contents($admin . '/src/Controller/MatchresultController.php');
$matchResultTemplate = (string) file_get_contents($admin . '/tmpl/matchresult/default.php');

foreach (["Session::checkToken()", "authorise('joomleague.project.edit.results'", "Log::add(", 'addSegment()', 'removeSegment()', 'preserveResultForm('] as $resultSaveRequirement) {
	if (!str_contains($matchResultController, $resultSaveRequirement)) {
		throw new RuntimeException(sprintf('Match result save is missing security requirement: %s.', $resultSaveRequirement));
	}
}

if (!str_contains($matchResultTemplate, "HTMLHelper::_('form.token')")
	|| !str_contains($matchResultTemplate, 'matchresult.addSegment')
	|| !str_contains($matchResultTemplate, 'matchresult.removeSegment')
	|| preg_match('/<style\b|style\s*=|<script\b/i', $matchResultTemplate) === 1) {
	throw new RuntimeException('Match result editor must use Joomla form security and must not introduce custom CSS or JavaScript.');
}

$matchResultModel = (string) file_get_contents($admin . '/src/Model/MatchresultModel.php');
$matchResultPayloadValidator = (string) file_get_contents($admin . '/src/Service/MatchResultPayloadValidator.php');
$matchResultController = (string) file_get_contents($admin . '/src/Controller/MatchresultController.php');

if (!str_contains($matchResultModel, 'MatchResultFormStateMutator')
	|| substr_count($matchResultModel, 'clearTransient($matchId)') < 1
	|| !str_contains($matchResultModel, 'MatchResultPayloadValidator')) {
	throw new RuntimeException('Match result mutations must be profile-validated and transient state must be cleared after persistence.');
}

if (!str_contains($matchResultPayloadValidator, 'MatchResultAggregationValidator')
	|| !str_contains($matchResultTemplate, 'duration_value')
	|| !is_file($admin . '/src/Service/MatchResultDuration.php')
	|| !is_file($admin . '/src/Service/MatchResultDecimal.php')) {
	throw new RuntimeException('Match result editing must retain exact duration conversion and profile-defined aggregation validation.');
}

if (!is_file($admin . '/src/Service/MatchResultValidationException.php')
	|| !str_contains($matchResultController, 'catch (MatchResultValidationException')
	|| !str_contains($matchResultController, 'getLanguageKey()')
	|| !str_contains($matchResultController, "Log::add(")) {
	throw new RuntimeException('Expected match-result validation errors must be translated without exposing technical exceptions.');
}

$matchesModel = (string) file_get_contents($admin . '/src/Model/MatchesModel.php');
$matchesTemplate = (string) file_get_contents($admin . '/tmpl/matches/default.php');
$matchResultSummaryProvider = (string) file_get_contents($admin . '/src/Service/MatchResultSummaryProvider.php');

if (!str_contains($matchesModel, '#__joomleague_match_result')
	|| !str_contains($matchesModel, 'MatchResultSummaryProvider')
	|| !str_contains($matchResultSummaryProvider, '#__joomleague_match_score_value')
	|| !str_contains($matchResultSummaryProvider, 'whereIn(')
	|| !str_contains($matchesTemplate, 'COM_JOOMLEAGUE_MATCHRESULT_COLUMN_RESULT')) {
	throw new RuntimeException('Match list must expose root result summaries through batched profile-neutral queries.');
}

$adminDomainCatalog = (string) file_get_contents($admin . '/src/Service/AdminDomainCatalog.php');
$projectPanelModel = (string) file_get_contents($admin . '/src/Model/ProjectpanelModel.php');
$projectPanelTemplate = (string) file_get_contents($admin . '/tmpl/projectpanel/default.php');

if (!str_contains($adminDomainCatalog, "'rounds' => self::item('ROUNDS', 'list', 'competition_runtime', 'project_available')")
	|| !str_contains($adminDomainCatalog, "'matches' => self::item('MATCHES', 'play', 'competition_runtime', 'project_available')")) {
	throw new RuntimeException('Implemented rounds and matches must be identified as project-context administration, not planned global views.');
}

foreach (['#__joomleague_project_stage', '#__joomleague_project_entry', '#__joomleague_project_actor_role', '#__joomleague_project_round', '#__joomleague_project_match'] as $aggregateTable) {
	if (!str_contains($projectPanelModel, $aggregateTable)) {
		throw new RuntimeException(sprintf('Project panel aggregate counts are missing %s.', $aggregateTable));
	}
}

if (!str_contains($projectPanelTemplate, 'COM_JOOMLEAGUE_PROJECTPANEL_SCHEDULE_BADGE')
	|| !str_contains($projectPanelTemplate, 'COM_JOOMLEAGUE_PROJECTPANEL_ENTRIES_BADGE')
	|| !str_contains($projectPanelTemplate, 'COM_JOOMLEAGUE_PROJECTPANEL_OFFICIALS_BADGE')) {
	throw new RuntimeException('Project panel must expose translated schedule and participant counts.');
}

$actorRoleRepository = (string) file_get_contents($admin . '/src/Service/MatchActorRoleRepository.php');
$projectOfficialsController = (string) file_get_contents($admin . '/src/Controller/ProjectofficialsController.php');
$matchOfficialsController = (string) file_get_contents($admin . '/src/Controller/MatchofficialsController.php');
$projectOfficialsTemplate = (string) file_get_contents($admin . '/tmpl/projectofficials/default.php');
$matchOfficialsTemplate = (string) file_get_contents($admin . '/tmpl/matchofficials/default.php');

foreach (['#__joomleague_project_actor_role', '#__joomleague_match_actor_role', 'officialRoles', 'rangesOverlap', 'matchDate', 'display_name_snapshot', 'source_project_actor_role_id'] as $actorRoleRequirement) {
	if (!str_contains($actorRoleRepository, $actorRoleRequirement)) throw new RuntimeException(sprintf('Official assignment repository is missing %s.', $actorRoleRequirement));
}

foreach ([$projectOfficialsController, $matchOfficialsController] as $officialController) {
	if (!str_contains($officialController, 'Session::checkToken()') || !str_contains($officialController, "authorise('joomleague.project.manage.officials'") || !str_contains($officialController, 'Log::add(')) {
		throw new RuntimeException('Official assignment controllers must retain Joomla CSRF, ACL and technical logging.');
	}
}

foreach ([$projectOfficialsTemplate, $matchOfficialsTemplate] as $officialTemplate) {
	if (!str_contains($officialTemplate, "HTMLHelper::_('form.token')") || preg_match('/<style\b|style\s*=|<script\b/i', $officialTemplate) === 1) {
		throw new RuntimeException('Official assignment administration must use Joomla form security and native styling.');
	}
}

$matchLineupRepository = (string) file_get_contents($admin . '/src/Service/MatchLineupRepository.php');
$matchLineupController = (string) file_get_contents($admin . '/src/Controller/MatchlineupController.php');
$matchLineupView = (string) file_get_contents($admin . '/src/View/Matchlineup/HtmlView.php');
$matchLineupTemplate = (string) file_get_contents($admin . '/tmpl/matchlineup/default.php');

foreach (['#__joomleague_match_lineup_member', 'source_entry_member_id', 'project_entry_id', 'valid_from', 'valid_until', 'players_on_field', 'captain_supported'] as $lineupRequirement) {
	if (!str_contains($matchLineupRepository, $lineupRequirement)) {
		throw new RuntimeException(sprintf('Match lineup repository is missing domain requirement %s.', $lineupRequirement));
	}
}

foreach (['#__joomleague_match_lineup_change', 'validateSubstitutionSequence', 'substitutionsSupported', "['match']['score']['segment_types']", 'outgoing_lineup_member_id', 'incoming_lineup_member_id'] as $substitutionRequirement) {
	if (!str_contains($matchLineupRepository, $substitutionRequirement)) {
		throw new RuntimeException(sprintf('Match substitution persistence is missing domain requirement %s.', $substitutionRequirement));
	}
}

if (str_contains($matchLineupRepository, "quoteName('#__joomleague_match_lineup_change', 'change')")) {
	throw new RuntimeException('Match substitutions must not use the reserved SQL alias change.');
}

if (!str_contains($matchLineupController, 'Session::checkToken()')
	|| !str_contains($matchLineupController, "authorise('joomleague.project.edit.lineup'")
	|| !str_contains($matchLineupController, 'Log::add(')
	|| !str_contains($matchLineupController, 'addSubstitution')
	|| !str_contains($matchLineupController, 'removeSubstitution')
	|| !str_contains($matchLineupTemplate, "HTMLHelper::_('form.token')")
	|| !str_contains($matchLineupTemplate, 'matchlineup.addSubstitution')
	|| !str_contains($matchLineupTemplate, 'matchlineup.removeSubstitution')
	|| preg_match('/<style\b|style\s*=|<script\b/i', $matchLineupTemplate) === 1) {
	throw new RuntimeException('Match lineup administration must retain Joomla CSRF, ACL, logging and native styling.');
}

if (!str_contains($matchLineupRepository, 'available_member_count')
	|| !str_contains($matchLineupView, '$this->participants[0] ?? null')
	|| !str_contains($matchLineupTemplate, '$participant->available_member_count')) {
	throw new RuntimeException('Match lineup administration must automatically select and summarize an available participant roster.');
}

// Standings read/write logic is split across two Domain\Service classes:
// StandingsReader (read-only, safe for admin/site/modules) and
// StandingsRecalculator (write-only, admin-exclusive). Together they must
// still own every standings table.
$standingsReader = (string) file_get_contents($admin . '/src/Service/StandingsReader.php');
$standingsRecalculator = (string) file_get_contents($admin . '/src/Service/StandingsRecalculator.php');
$standingsRepository = $standingsReader . "\n" . $standingsRecalculator;
$standingsController = (string) file_get_contents($admin . '/src/Controller/StandingsController.php');
$standingsTemplate = (string) file_get_contents($admin . '/tmpl/standings/default.php');

foreach (['#__joomleague_standing_adjustment', '#__joomleague_standing_snapshot', '#__joomleague_standing_snapshot_row', '#__joomleague_standing_current'] as $standingTable) {
	if (!str_contains($standingsRepository, $standingTable)) {
		throw new RuntimeException(sprintf('Standings repository is missing owned table %s.', $standingTable));
	}
}

if (str_contains($standingsReader, 'recalculate') || str_contains($standingsReader, 'transactionStart')) {
	throw new RuntimeException('StandingsReader must stay read-only — no write/recalculation logic belongs there.');
}

$modelIterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($admin . '/src/Model', FilesystemIterator::SKIP_DOTS));
foreach ($modelIterator as $modelFile) {
	if (!$modelFile->isFile() || $modelFile->getExtension() !== 'php') continue;
	$modelSource = (string) file_get_contents($modelFile->getPathname());
	if (preg_match('/->bind\([^;\n]*->getState\(/', $modelSource) === 1) {
		throw new RuntimeException(sprintf('%s passes a temporary getState() value to reference-based DatabaseQuery::bind().', $modelFile->getFilename()));
	}
	// ProjectscheduleModel is a deliberate exception: it is a cross-round calendar
	// view, so it defaults to chronological order (scheduled_start ASC) instead of
	// newest-ID-first like every other admin list.
	if ($modelFile->getFilename() !== 'ProjectscheduleModel.php'
		&& str_contains($modelSource, 'extends ListModel')
		&& preg_match("/populateState\(\\\$ordering = '[a-z_]+\\.id', \\\$direction = 'desc'\)/", $modelSource) !== 1) {
		throw new RuntimeException(sprintf('%s must default to newest ID first.', $modelFile->getFilename()));
	}
}

foreach (glob($admin . '/forms/filter_*.xml') ?: [] as $filterForm) {
	$filterSource = (string) file_get_contents($filterForm);
	// filter_projectschedule.xml is the deliberate exception paired with
	// ProjectscheduleModel above: a cross-round calendar defaults to chronological order.
	if (basename($filterForm) !== 'filter_projectschedule.xml'
		&& str_contains($filterSource, 'name="fullordering"')
		&& preg_match('/<field\b(?=[^>]*\bname="fullordering")(?=[^>]*\bdefault="[a-z_]+\.id DESC")[^>]*>/', $filterSource) !== 1) {
		throw new RuntimeException(sprintf('%s must default to newest ID first.', basename($filterForm)));
	}
}

if (!str_contains($adminDomainCatalog, "'standings' => self::item('STANDINGS', 'ranking-star', 'competition_runtime', 'project_available')")
	|| !str_contains($projectPanelTemplate, 'view=standings&project_id=')) {
	throw new RuntimeException('Standings must remain available only from an explicit project context.');
}

if (!str_contains($standingsController, 'Session::checkToken()')
	|| !str_contains($standingsController, "authorise('core.edit', \$asset)")
	|| !str_contains($standingsController, 'Log::add(')
	|| !str_contains($standingsTemplate, "HTMLHelper::_('form.token')")
	|| preg_match('/<style\b|style\s*=|<script\b/i', $standingsTemplate) === 1) {
	throw new RuntimeException('Standings administration must retain Joomla CSRF, ACL, logging and native styling.');
}

printf(
	"Foundation OK: %d profiles, %d tables on both drivers, %d en-GB keys, %d menu views, %d template definitions, %d project-rule fields\n",
	count($profiles),
	count($mysqlTables),
	count($definedLanguageKeys),
	count(array_unique($menuViews[1] ?? [])),
	count($templateDefinitions),
	$projectRuleFieldCount
);
