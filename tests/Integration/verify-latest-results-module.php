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

use Joomla\CMS\Application\SiteApplication;
use Joomla\CMS\Factory;
use Joomla\CMS\Session\Session;
use Joomla\Database\DatabaseInterface;
use Joomla\Registry\Registry;
use Joomleague\Module\LatestResults\Site\Helper\LatestResultsHelper;

$container = Factory::getContainer();
$container->alias('session', 'session.cli')->alias('JSession', 'session.cli')->alias(Session::class, 'session.cli')
	->alias(Joomla\Session\Session::class, 'session.cli')->alias(Joomla\Session\SessionInterface::class, 'session.cli');
Factory::$application = $container->get(SiteApplication::class);
Factory::getApplication()->createExtensionNamespaceMap();
Factory::getApplication()->bootComponent('com_joomleague');
require_once JPATH_ROOT . '/components/com_joomleague/src/Service/ProjectTemplateProvider.php';
require_once JPATH_ROOT . '/modules/mod_joomleague_latest_results/src/Configuration/LatestResultsConfiguration.php';
require_once JPATH_ROOT . '/modules/mod_joomleague_latest_results/src/Helper/LatestResultsHelper.php';
$db = $container->get(DatabaseInterface::class);

$uuid = static function (): string {
	$hex = bin2hex(random_bytes(16));
	return substr($hex, 0, 8) . '-' . substr($hex, 8, 4) . '-4' . substr($hex, 13, 3) . '-a' . substr($hex, 17, 3) . '-' . substr($hex, 20, 12);
};
$insert = static function (string $table, array $data) use ($db): int {
	$query = $db->getQuery(true)->insert($db->quoteName($table))->columns($db->quoteName(array_keys($data)));
	$values = [];
	foreach ($data as $key => &$value) {
		$values[] = ':' . $key;
		$query->bind(':' . $key, $value);
	}
	$db->setQuery($query->values(implode(',', $values)))->execute();
	return (int) $db->insertid();
};
$assert = static function (bool $condition, string $message): void {
	if (!$condition) throw new RuntimeException($message);
};

$suffix = bin2hex(random_bytes(5));
$profile = (int) $db->setQuery($db->getQuery(true)->select('id')->from($db->quoteName('#__joomleague_sport_profile_version'))->where('state = ' . $db->quote('active'))->order('id ASC'), 0, 1)->loadResult();
$competition = $insert('#__joomleague_competition', ['uuid' => $uuid(), 'name' => 'Latest competition ' . $suffix, 'published' => 1, 'access' => 1]);
$season = $insert('#__joomleague_season', ['uuid' => $uuid(), 'name' => 'Latest season ' . $suffix, 'published' => 1, 'access' => 1]);
$sport = $insert('#__joomleague_sport_type', ['profile_version_id' => $profile, 'code' => 'latest_' . $suffix, 'name' => 'Latest sport ' . $suffix, 'published' => 1]);
$venue = $insert('#__joomleague_venue', ['uuid' => $uuid(), 'name' => 'Latest venue ' . $suffix, 'published' => 1, 'access' => 1]);
$person = $insert('#__joomleague_person', ['uuid' => $uuid(), 'first_name' => 'Person', 'last_name' => 'Gamma', 'published' => 1, 'access' => 1]);
$project = $insert('#__joomleague_project', ['uuid' => $uuid(), 'competition_id' => $competition, 'season_id' => $season, 'sport_type_id' => $sport, 'profile_version_id' => $profile, 'name' => 'Latest project ' . $suffix, 'project_type' => 'friendly', 'published' => 1, 'access' => 1]);

try {
	$entries = [];
	foreach (['Group Alpha', 'Group Beta', 'Person Gamma', 'Group Delta'] as $index => $name) {
		$entries[] = $insert('#__joomleague_project_entry', ['uuid' => $uuid(), 'project_id' => $project, 'entry_kind' => $index === 2 ? 'person' : 'group', 'person_id' => $index === 2 ? $person : null, 'display_name' => $name, 'published' => 1]);
	}
	$stage = $insert('#__joomleague_project_stage', ['uuid' => $uuid(), 'project_id' => $project, 'name' => 'Main stage', 'code' => 'main', 'stage_type' => 'league', 'published' => 1]);
	$round = $insert('#__joomleague_project_round', ['uuid' => $uuid(), 'project_id' => $project, 'stage_id' => $stage, 'name' => 'Round 1', 'code' => 'round_1', 'round_type' => 'regular', 'sequence_number' => 1, 'published' => 1]);

	$createMatch = static function (string $start, string $matchStatus, string $resultStatus, array $scores, bool $withDecider = false) use ($insert, $uuid, $project, $stage, $round, $venue, $entries): int {
		$match = $insert('#__joomleague_project_match', ['uuid' => $uuid(), 'project_id' => $project, 'stage_id' => $stage, 'round_id' => $round, 'venue_id' => $venue, 'contest_type' => count($scores) > 2 ? 'race' : 'head_to_head', 'scheduled_start' => $start, 'status_code' => $matchStatus, 'published' => 1]);
		$participants = [];
		foreach (array_slice($entries, 0, count($scores)) as $index => $entry) {
			$participants[] = $insert('#__joomleague_match_participant', ['uuid' => $uuid(), 'match_id' => $match, 'project_id' => $project, 'project_entry_id' => $entry, 'slot_number' => $index + 1, 'published' => 1]);
		}
		$insert('#__joomleague_match_result', ['uuid' => $uuid(), 'match_id' => $match, 'result_type' => 'numeric_score', 'status_code' => $resultStatus, 'outcome_code' => $resultStatus === 'final' ? 'completed' : null]);
		$root = $insert('#__joomleague_match_score_segment', ['uuid' => $uuid(), 'match_id' => $match, 'level_code' => 'result', 'sequence_number' => 1]);
		foreach ($participants as $index => $participant) {
			$insert('#__joomleague_match_score_value', ['uuid' => $uuid(), 'match_id' => $match, 'segment_id' => $root, 'participant_id' => $participant, 'numeric_value' => (string) $scores[$index][0], 'result_rank' => $scores[$index][1]]);
		}
		if ($withDecider) {
			$decider = $insert('#__joomleague_match_score_segment', ['uuid' => $uuid(), 'match_id' => $match, 'parent_id' => $root, 'level_code' => 'shootout', 'segment_type_ordinal' => 30, 'sequence_number' => 1, 'metadata_json' => '{"decider":true}']);
			foreach ($participants as $index => $participant) {
				$insert('#__joomleague_match_score_value', ['uuid' => $uuid(), 'match_id' => $match, 'segment_id' => $decider, 'participant_id' => $participant, 'numeric_value' => (string) (5 - $index)]);
			}
		}
		return $match;
	};

	$oldMatch = $createMatch((new DateTimeImmutable('-3 days'))->format('Y-m-d H:i:s'), 'completed', 'final', [[0, 1], [0, 1]]);
	$newMatch = $createMatch((new DateTimeImmutable('-1 day'))->format('Y-m-d H:i:s'), 'finished', 'final', [[8, 1], [6, 2], [4, 3], [2, 4]], true);
	$draftMatch = $createMatch((new DateTimeImmutable('-2 days'))->format('Y-m-d H:i:s'), 'finished', 'draft', [[3, 1], [1, 2]]);

	$helper = new LatestResultsHelper();
	$data = $helper->getResults(new Registry(['project_id' => $project, 'season_id' => $season, 'stage_id' => $stage, 'round_id' => $round, 'entry_id' => $entries[2], 'venue_id' => $venue, 'status_codes' => ['finished'], 'days_before' => 30, 'limit' => 7, 'participant_limit' => 2, 'order' => 'newest']));
	$assert(!isset($data['error']), 'Latest Results unexpectedly returned an error.');
	$assert(count($data['items'] ?? []) === 1 && (int) $data['items'][0]['id'] === $newMatch, 'Combined source filters did not isolate the expected result.');
	$item = $data['items'][0];
	$assert($item['project_name'] === 'Latest project ' . $suffix && $item['season_name'] === 'Latest season ' . $suffix && $item['stage_name'] === 'Main stage', 'Context metadata was not projected.');
	$assert($item['participant_count'] === 4 && count($item['participants']) === 2, 'Participant display limit did not preserve total count.');
	$assert(array_column($item['participants'], 'score') === ['8', '6'] && array_column($item['participants'], 'rank') === [1, 2], 'Scores or result ranks were not projected.');
	$assert(array_values($item['decider']['values'] ?? []) === ['5', '4', '3', '2'], 'Nested deciding segment was not projected.');

	$all = $helper->getResults(new Registry(['project_id' => $project, 'days_before' => 30, 'limit' => 10, 'participant_limit' => 0, 'order' => 'oldest']));
	$assert(!isset($all['error']) && array_column($all['items'], 'id') === [$oldMatch, $newMatch], 'Oldest ordering or non-final exclusion failed.');
	$assert(array_column($all['items'][0]['participants'], 'score') === ['0', '0'], 'Valid zero-score draw was not preserved.');
	$assert(!in_array($draftMatch, array_column($all['items'], 'id'), true), 'Non-final result leaked into Latest Results.');
	$assert(in_array('Person Gamma', array_column($all['items'][1]['participants'], 'name'), true), 'Non-team participant was not rendered in a multi-participant result.');
	printf("Latest Results OK on %s: filters, ordering, zero draw, person, race, nested segment and final-state exclusion validated\n", $db->getServerType());
} finally {
	$db->setQuery($db->getQuery(true)->delete($db->quoteName('#__joomleague_project'))->where('id = ' . (int) $project))->execute();
	foreach ([['#__joomleague_person', $person], ['#__joomleague_venue', $venue], ['#__joomleague_sport_type', $sport], ['#__joomleague_competition', $competition], ['#__joomleague_season', $season]] as [$table, $id]) {
		$db->setQuery($db->getQuery(true)->delete($db->quoteName($table))->where('id = ' . (int) $id))->execute();
	}
}
