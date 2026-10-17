<?php

declare(strict_types=1);

define('_JEXEC', 1);
define('JPATH_BASE', '/var/www/html');
$_SERVER['HTTP_HOST'] ??= 'localhost';
$_SERVER['REQUEST_URI'] ??= '/';
$_SERVER['SCRIPT_NAME'] = '/index.php';
require_once JPATH_BASE . '/includes/defines.php';
require_once JPATH_BASE . '/includes/framework.php';
require_once JPATH_ADMINISTRATOR . '/components/com_joomleague/src/Extension/JoomleagueComponent.php';

use Joomla\CMS\Factory;
use Joomla\Database\DatabaseInterface;
use Joomla\Registry\Registry;
use Joomleague\Module\Calendar\Site\Helper\CalendarHelper;

$container = Factory::getContainer();
$container->alias('session', 'session.cli')
	->alias('JSession', 'session.cli')
	->alias(Joomla\CMS\Session\Session::class, 'session.cli')
	->alias(Joomla\Session\Session::class, 'session.cli')
	->alias(Joomla\Session\SessionInterface::class, 'session.cli');
Factory::$application = $container->get(Joomla\CMS\Application\SiteApplication::class);
Factory::getApplication()->createExtensionNamespaceMap();
Factory::getApplication()->bootComponent('com_joomleague');

require_once JPATH_ROOT . '/components/com_joomleague/src/Service/ProjectTemplateProvider.php';
$modulePath = getenv('CALENDAR_MODULE_PATH') ?: JPATH_ROOT . '/modules/mod_joomleague_calendar';
require_once $modulePath . '/src/Helper/CalendarOptions.php';
require_once $modulePath . '/src/Helper/CalendarHelper.php';

$database = $container->get(DatabaseInterface::class);
$publishedEvents = (int) $database->setQuery(
	$database->getQuery(true)->select('COUNT(*)')->from($database->quoteName('#__joomleague_project_match'))->where('published = 1')
)->loadResult();
if ($publishedEvents < 1) {
	if (getenv('ALLOW_CALENDAR_FIXTURE') === '1') {
		$database->transactionStart();
		register_shutdown_function(static function () use ($database): void {
			try { $database->transactionRollback(); } catch (Throwable $error) {
				fwrite(STDERR, 'Fixture rollback: ' . $error->getMessage() . "\n");
			}
		});
		$insert = static function (string $table, array $values) use ($database): int {
			$query = $database->getQuery(true)->insert($database->quoteName($table))->columns($database->quoteName(array_keys($values)));
			$placeholders = [];
			foreach ($values as $key => &$value) {
				$placeholders[] = ':' . $key;
				$query->bind(':' . $key, $value);
			}
			$query->values(implode(',', $placeholders));
			$database->setQuery($query)->execute();
			$identity = isset($values['uuid']) ? 'uuid' : 'code';
			$lookup = $database->getQuery(true)->select($database->quoteName('id'))->from($database->quoteName($table))
				->where($database->quoteName($identity) . ' = :identity')->bind(':identity', $values[$identity]);
			return (int) $database->setQuery($lookup)->loadResult();
		};
		$uuid = static function (): string {
			$bytes = random_bytes(16);
			$bytes[6] = chr((ord($bytes[6]) & 15) | 64);
			$bytes[8] = chr((ord($bytes[8]) & 63) | 128);
			return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($bytes), 4));
		};
		$profile = (int) $database->setQuery($database->getQuery(true)->select('id')->from($database->quoteName('#__joomleague_sport_profile_version'))->order('id ASC'), 0, 1)->loadResult();
		if ($profile < 1) throw new RuntimeException('Calendar fixture requires an installed sport profile.');
		$name = 'Calendar integration ' . bin2hex(random_bytes(4));
		$competition = $insert('#__joomleague_competition', ['uuid' => $uuid(), 'name' => $name]);
		$season = $insert('#__joomleague_season', ['uuid' => $uuid(), 'name' => $name]);
		$sport = $insert('#__joomleague_sport_type', ['profile_version_id' => $profile, 'code' => 'calendar-' . bin2hex(random_bytes(4)), 'name' => $name]);
		$project = $insert('#__joomleague_project', ['uuid' => $uuid(), 'competition_id' => $competition, 'season_id' => $season, 'sport_type_id' => $sport, 'profile_version_id' => $profile, 'name' => $name, 'project_type' => 'league', 'published' => 1]);
		$stage = $insert('#__joomleague_project_stage', ['uuid' => $uuid(), 'project_id' => $project, 'name' => 'Stage', 'code' => 'stage', 'stage_type' => 'league', 'published' => 1]);
		$match = $insert('#__joomleague_project_match', ['uuid' => $uuid(), 'project_id' => $project, 'stage_id' => $stage, 'contest_type' => 'head_to_head', 'scheduled_start' => gmdate('Y-m-d') . ' 12:00:00', 'published' => 1]);
		foreach (['Entry A', 'Entry B'] as $slot => $entryName) {
			$entry = $insert('#__joomleague_project_entry', ['uuid' => $uuid(), 'project_id' => $project, 'entry_kind' => 'group', 'display_name' => $entryName, 'published' => 1]);
			$insert('#__joomleague_match_participant', ['uuid' => $uuid(), 'match_id' => $match, 'project_id' => $project, 'project_entry_id' => $entry, 'slot_number' => $slot + 1, 'published' => 1]);
		}
	} else {
	throw new RuntimeException('Required published programme fixture is missing.');
	}
}

$calendar = (new CalendarHelper())->getCalendar(new Registry([
	'days_before' => 365,
	'days_after' => 5000,
	'limit' => 200,
	'template_show_match_detail_button' => '0',
]));
if (isset($calendar['error']) || ($calendar['groups'] ?? []) === []) {
	throw new RuntimeException('Calendar module did not aggregate the published programme fixture.');
}
foreach ($calendar['groups'] as $group) {
	if ($group['items'] === []) {
		throw new RuntimeException('Calendar module emitted an empty date group.');
	}
	foreach ($group['items'] as $item) {
		if (($item['show_detail'] ?? true) !== false) {
			throw new RuntimeException('Calendar module detail-link override was not applied.');
		}
	}
}

$first = $calendar['groups'][0]['items'][0];
foreach (['1' => true, '' => null] as $override => $expected) {
	$detailCalendar = (new CalendarHelper())->getCalendar(new Registry([
		'days_before' => 365, 'days_after' => 5000, 'limit' => 200,
		'template_show_match_detail_button' => (string) $override,
	]));
	if (isset($detailCalendar['error']) || empty($detailCalendar['groups'])) {
		throw new RuntimeException('Detail override verification returned no programme.');
	}
	foreach ($detailCalendar['groups'] as $group) foreach ($group['items'] as $item) {
		if (!is_bool($item['show_detail']) || ($expected !== null && $item['show_detail'] !== $expected)) {
			throw new RuntimeException('Enabled/inherited detail-link setting was not applied.');
		}
	}
}
$monthly = (new CalendarHelper())->getCalendar(new Registry([
	'presentation' => 'month', 'initial_date' => substr($first['scheduled_start'], 0, 10), 'first_weekday' => 0,
	'limit' => 200,
]), 777);
if (empty($monthly['grid']) || $monthly['module_id'] !== 777 || !str_contains($monthly['next_url'], 'jl_calendar_777_month')) {
	throw new RuntimeException('Monthly navigation missing or not scoped to module instance.');
}
foreach ($monthly['groups'] ?? [] as $group) {
	if (substr($group['date'], 0, 7) !== substr($monthly['month'], 0, 7)) throw new RuntimeException('Monthly range leaked another month.');
}
foreach (['project_id', 'season_id', 'stage_id', 'venue_id'] as $filter) {
	$id = (int) ($first[$filter] ?? 0);
	if ($id < 1) continue;
	$filtered = (new CalendarHelper())->getCalendar(new Registry([
		'days_before' => 365, 'days_after' => 5000, 'limit' => 200, $filter => $id,
	]));
	if (isset($filtered['error'])) throw new RuntimeException('Scope returned no records: ' . $filter);
	foreach ($filtered['groups'] as $group) foreach ($group['items'] as $item) {
		if ((int) $item[$filter] !== $id) throw new RuntimeException('Scope leaked unrelated records: ' . $filter);
	}
}
$completed = (new CalendarHelper())->getCalendar(new Registry([
	'days_before' => 365, 'days_after' => 5000, 'limit' => 200, 'mode' => 'completed',
]));
foreach ($completed['groups'] ?? [] as $group) foreach ($group['items'] as $item) {
	if (!$item['played']) throw new RuntimeException('Completed mode exposed unfinished event.');
}
printf("Calendar module OK on %s: %d date groups aggregated\n", $database->getServerType(), count($calendar['groups']));
