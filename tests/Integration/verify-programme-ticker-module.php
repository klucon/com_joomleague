<?php

declare(strict_types=1);

define('_JEXEC', 1);
define('JPATH_BASE', '/var/www/html');
$_SERVER['HTTP_HOST'] ??= 'localhost'; $_SERVER['REQUEST_URI'] ??= '/'; $_SERVER['SCRIPT_NAME'] = '/index.php';
require_once JPATH_BASE . '/includes/defines.php';
require_once JPATH_BASE . '/includes/framework.php';
require_once JPATH_ADMINISTRATOR . '/components/com_joomleague/src/Extension/JoomleagueComponent.php';

use Joomla\CMS\Application\SiteApplication;
use Joomla\CMS\Factory;
use Joomla\CMS\Session\Session;
use Joomla\Database\DatabaseInterface;
use Joomla\Registry\Registry;
use Joomleague\Module\ProgrammeTicker\Site\Helper\ProgrammeTickerHelper;

$container = Factory::getContainer();
$container->alias('session', 'session.cli')->alias('JSession', 'session.cli')->alias(Session::class, 'session.cli')->alias(Joomla\Session\Session::class, 'session.cli')->alias(Joomla\Session\SessionInterface::class, 'session.cli');
Factory::$application = $container->get(SiteApplication::class);
Factory::getApplication()->createExtensionNamespaceMap();
Factory::getApplication()->bootComponent('com_joomleague');
require_once JPATH_ROOT . '/components/com_joomleague/src/Service/ProjectTemplateProvider.php';
require_once JPATH_ROOT . '/modules/mod_joomleague_programme_ticker/src/Configuration/ProgrammeTickerConfiguration.php';
require_once JPATH_ROOT . '/modules/mod_joomleague_programme_ticker/src/Helper/ProgrammeTickerHelper.php';
$db = $container->get(DatabaseInterface::class);

$uuid = static function (): string { $hex = bin2hex(random_bytes(16)); return substr($hex, 0, 8) . '-' . substr($hex, 8, 4) . '-4' . substr($hex, 13, 3) . '-a' . substr($hex, 17, 3) . '-' . substr($hex, 20, 12); };
$insert = static function (string $table, array $data) use ($db): int {
	$query = $db->getQuery(true)->insert($db->quoteName($table))->columns($db->quoteName(array_keys($data))); $values = [];
	foreach ($data as $key => &$value) { $values[] = ':' . $key; $query->bind(':' . $key, $value); }
	$db->setQuery($query->values(implode(',', $values)))->execute(); return (int) $db->insertid();
};
$assert = static function (bool $condition, string $message): void { if (!$condition) throw new RuntimeException($message); };
$suffix = bin2hex(random_bytes(5));
$profile = (int) $db->setQuery($db->getQuery(true)->select('id')->from($db->quoteName('#__joomleague_sport_profile_version'))->where('state = ' . $db->quote('active'))->order('id ASC'), 0, 1)->loadResult();
$competition = $insert('#__joomleague_competition', ['uuid' => $uuid(), 'name' => 'Ticker competition ' . $suffix, 'published' => 1, 'access' => 1]);
$season = $insert('#__joomleague_season', ['uuid' => $uuid(), 'name' => 'Ticker season ' . $suffix, 'published' => 1, 'access' => 1]);
$sport = $insert('#__joomleague_sport_type', ['profile_version_id' => $profile, 'code' => 'ticker_' . $suffix, 'name' => 'Ticker sport ' . $suffix, 'published' => 1]);
$venue = $insert('#__joomleague_venue', ['uuid' => $uuid(), 'name' => 'Ticker venue ' . $suffix, 'published' => 1, 'access' => 1]);
$project = $insert('#__joomleague_project', ['uuid' => $uuid(), 'competition_id' => $competition, 'season_id' => $season, 'sport_type_id' => $sport, 'profile_version_id' => $profile, 'name' => 'Ticker project ' . $suffix, 'project_type' => 'friendly', 'published' => 1, 'access' => 1]);

try {
	$entries = [];
	foreach (['Ticker Alpha', 'Ticker Beta'] as $name) $entries[] = $insert('#__joomleague_project_entry', ['uuid' => $uuid(), 'project_id' => $project, 'entry_kind' => 'group', 'display_name' => $name, 'published' => 1]);
	$stage = $insert('#__joomleague_project_stage', ['uuid' => $uuid(), 'project_id' => $project, 'name' => 'Ticker stage', 'code' => 'main', 'stage_type' => 'league', 'published' => 1]);
	$round = $insert('#__joomleague_project_round', ['uuid' => $uuid(), 'project_id' => $project, 'stage_id' => $stage, 'name' => 'Ticker round', 'code' => 'round_1', 'round_type' => 'regular', 'sequence_number' => 1, 'published' => 1]);
	$createEvent = static function (string $start, string $status, bool $final) use ($insert, $uuid, $project, $stage, $round, $venue, $entries): int {
		$event = $insert('#__joomleague_project_match', ['uuid' => $uuid(), 'project_id' => $project, 'stage_id' => $stage, 'round_id' => $round, 'venue_id' => $venue, 'contest_type' => 'head_to_head', 'scheduled_start' => $start, 'status_code' => $status, 'published' => 1]);
		$participants = [];
		foreach ($entries as $slot => $entry) $participants[] = $insert('#__joomleague_match_participant', ['uuid' => $uuid(), 'match_id' => $event, 'project_id' => $project, 'project_entry_id' => $entry, 'slot_number' => $slot + 1, 'published' => 1]);
		if ($final) {
			$insert('#__joomleague_match_result', ['uuid' => $uuid(), 'match_id' => $event, 'result_type' => 'numeric_score', 'status_code' => 'final', 'outcome_code' => 'completed']);
			$segment = $insert('#__joomleague_match_score_segment', ['uuid' => $uuid(), 'match_id' => $event, 'level_code' => 'result', 'sequence_number' => 1]);
			foreach ($participants as $index => $participant) $insert('#__joomleague_match_score_value', ['uuid' => $uuid(), 'match_id' => $event, 'segment_id' => $segment, 'participant_id' => $participant, 'numeric_value' => (string) (2 - $index), 'result_rank' => $index + 1]);
		}
		return $event;
	};
	$pastA = $createEvent((new DateTimeImmutable('-2 days'))->format('Y-m-d H:i:s'), 'finished', true);
	$pastB = $createEvent((new DateTimeImmutable('-1 day'))->format('Y-m-d H:i:s'), 'finished', true);
	$futureA = $createEvent((new DateTimeImmutable('+1 day'))->format('Y-m-d H:i:s'), 'scheduled', false);
	$futureB = $createEvent((new DateTimeImmutable('+2 days'))->format('Y-m-d H:i:s'), 'scheduled', false);

	$helper = new ProgrammeTickerHelper();
	$base = ['sport_type_id' => $sport, 'season_id' => $season, 'project_id' => $project, 'stage_id' => $stage, 'round_id' => $round, 'entry_id' => $entries[0], 'venue_id' => $venue, 'days_before' => 10, 'days_after' => 10, 'completed_limit' => 2, 'upcoming_limit' => 2, 'item_limit' => 3, 'template_show_match_detail_button' => '0'];
	$data = $helper->getTicker(new Registry($base));
	$assert(!isset($data['error']), 'Ticker unexpectedly returned an error.');
	$assert(count($data['items']) === 3, 'Requested three ticker items did not produce exactly three originals.');
	$assert(array_column($data['completed'], 'id') === [$pastB, $pastA] && array_column($data['upcoming'], 'id') === [$futureA, $futureB], 'Completed/upcoming ordering failed.');
	foreach ($data['items'] as $item) {
		$assert($item['project_name'] === 'Ticker project ' . $suffix && $item['season_name'] === 'Ticker season ' . $suffix && $item['stage_name'] === 'Ticker stage' && $item['venue_name'] === 'Ticker venue ' . $suffix, 'Context projection failed.');
		$assert($item['participant_count'] === 2 && $item['show_detail'] === false, 'Participant count or detail override failed.');
	}
	$upcoming = $helper->getTicker(new Registry(array_replace($base, ['content_mode' => 'upcoming', 'status_codes' => ['scheduled'], 'item_limit' => 10])));
	$assert(($upcoming['completed'] ?? []) === [] && array_column($upcoming['items'], 'id') === [$futureA, $futureB], 'Content mode or status filter failed.');
	$wrongVenue = $helper->getTicker(new Registry(array_replace($base, ['venue_id' => $venue + 999999])));
	$assert(($wrongVenue['error'] ?? '') === 'MOD_JOOMLEAGUE_PROGRAMME_TICKER_EMPTY', 'Venue filter leaked unrelated events.');
	printf("Programme ticker OK on %s: filters, ordering, content mode and exactly three rendered items validated\n", $db->getServerType());
} finally {
	$db->setQuery($db->getQuery(true)->delete($db->quoteName('#__joomleague_project'))->where('id = ' . (int) $project))->execute();
	foreach ([['#__joomleague_venue', $venue], ['#__joomleague_sport_type', $sport], ['#__joomleague_competition', $competition], ['#__joomleague_season', $season]] as [$table, $id]) $db->setQuery($db->getQuery(true)->delete($db->quoteName($table))->where('id = ' . (int) $id))->execute();
}
