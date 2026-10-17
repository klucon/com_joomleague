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
use Joomleague\Module\NextEvent\Site\Helper\NextEventHelper;

$container = Factory::getContainer();
$container->alias('session', 'session.cli')->alias('JSession', 'session.cli')->alias(Session::class, 'session.cli')
	->alias(Joomla\Session\Session::class, 'session.cli')->alias(Joomla\Session\SessionInterface::class, 'session.cli');
Factory::$application = $container->get(SiteApplication::class);
Factory::getApplication()->createExtensionNamespaceMap();
Factory::getApplication()->bootComponent('com_joomleague');
require_once JPATH_ROOT . '/modules/mod_joomleague_next_event/src/Configuration/NextEventConfiguration.php';
require_once JPATH_ROOT . '/modules/mod_joomleague_next_event/src/Helper/NextEventHelper.php';
$db = $container->get(DatabaseInterface::class);

$uuid = static function (): string { $hex = bin2hex(random_bytes(16)); return substr($hex, 0, 8) . '-' . substr($hex, 8, 4) . '-4' . substr($hex, 13, 3) . '-a' . substr($hex, 17, 3) . '-' . substr($hex, 20, 12); };
$insert = static function (string $table, array $data) use ($db): int {
	$query = $db->getQuery(true)->insert($db->quoteName($table))->columns($db->quoteName(array_keys($data)));
	$values = [];
	foreach ($data as $key => &$value) { $values[] = ':' . $key; $query->bind(':' . $key, $value); }
	$db->setQuery($query->values(implode(',', $values)))->execute();
	return (int) $db->insertid();
};
$assert = static function (bool $condition, string $message): void { if (!$condition) throw new RuntimeException($message); };
$suffix = bin2hex(random_bytes(5));
$profile = (int) $db->setQuery($db->getQuery(true)->select('id')->from($db->quoteName('#__joomleague_sport_profile_version'))->where('state = ' . $db->quote('active'))->order('id ASC'), 0, 1)->loadResult();
$competition = $insert('#__joomleague_competition', ['uuid' => $uuid(), 'name' => 'Next competition ' . $suffix, 'published' => 1, 'access' => 1]);
$season = $insert('#__joomleague_season', ['uuid' => $uuid(), 'name' => 'Next season ' . $suffix, 'published' => 1, 'access' => 1]);
$sport = $insert('#__joomleague_sport_type', ['profile_version_id' => $profile, 'code' => 'next_' . $suffix, 'name' => 'Next sport ' . $suffix, 'published' => 1]);
$venue = $insert('#__joomleague_venue', ['uuid' => $uuid(), 'name' => 'Next venue ' . $suffix, 'published' => 1, 'access' => 1]);
$project = $insert('#__joomleague_project', ['uuid' => $uuid(), 'competition_id' => $competition, 'season_id' => $season, 'sport_type_id' => $sport, 'profile_version_id' => $profile, 'name' => 'Next project ' . $suffix, 'project_type' => 'friendly', 'published' => 1, 'access' => 1]);

try {
	$entries = [];
	foreach (['Alpha', 'Beta', 'Gamma'] as $name) $entries[] = $insert('#__joomleague_project_entry', ['uuid' => $uuid(), 'project_id' => $project, 'entry_kind' => 'group', 'display_name' => $name, 'published' => 1]);
	$stage = $insert('#__joomleague_project_stage', ['uuid' => $uuid(), 'project_id' => $project, 'name' => 'Main stage', 'code' => 'main', 'stage_type' => 'league', 'published' => 1]);
	$round = $insert('#__joomleague_project_round', ['uuid' => $uuid(), 'project_id' => $project, 'stage_id' => $stage, 'name' => 'Round 1', 'code' => 'round_1', 'round_type' => 'regular', 'sequence_number' => 1, 'published' => 1]);
	$createEvent = static function (?string $start, string $status, bool $final, array $entryIndexes = [0, 1], bool $withDecider = false) use ($insert, $uuid, $project, $stage, $round, $venue, $entries): int {
		$match = $insert('#__joomleague_project_match', ['uuid' => $uuid(), 'project_id' => $project, 'stage_id' => $stage, 'round_id' => $round, 'venue_id' => $venue, 'contest_type' => count($entryIndexes) > 2 ? 'race' : 'head_to_head', 'scheduled_start' => $start, 'status_code' => $status, 'published' => 1]);
		$participants = [];
		foreach ($entryIndexes as $slot => $index) $participants[] = $insert('#__joomleague_match_participant', ['uuid' => $uuid(), 'match_id' => $match, 'project_id' => $project, 'project_entry_id' => $entries[$index], 'slot_number' => $slot + 1, 'published' => 1]);
		if ($final) {
			$insert('#__joomleague_match_result', ['uuid' => $uuid(), 'match_id' => $match, 'result_type' => 'numeric_score', 'status_code' => 'final', 'outcome_code' => 'completed']);
			$segment = $insert('#__joomleague_match_score_segment', ['uuid' => $uuid(), 'match_id' => $match, 'level_code' => 'result', 'sequence_number' => 1]);
			foreach ($participants as $index => $participant) $insert('#__joomleague_match_score_value', ['uuid' => $uuid(), 'match_id' => $match, 'segment_id' => $segment, 'participant_id' => $participant, 'numeric_value' => (string) (2 - $index), 'result_rank' => $index + 1]);
			if ($withDecider) {
				$decider = $insert('#__joomleague_match_score_segment', ['uuid' => $uuid(), 'match_id' => $match, 'parent_id' => $segment, 'level_code' => 'shootout', 'segment_type_ordinal' => 30, 'sequence_number' => 1, 'metadata_json' => '{"decider":true}']);
				foreach ($participants as $index => $participant) $insert('#__joomleague_match_score_value', ['uuid' => $uuid(), 'match_id' => $match, 'segment_id' => $decider, 'participant_id' => $participant, 'numeric_value' => (string) (5 - $index)]);
			}
		}
		return $match;
	};

	$oldPast = $createEvent((new DateTimeImmutable('-30 days'))->format('Y-m-d H:i:s'), 'finished', true);
	$recentPastA = $createEvent((new DateTimeImmutable('-2 days'))->format('Y-m-d H:i:s'), 'finished', true);
	$recentPastB = $createEvent((new DateTimeImmutable('-1 day'))->format('Y-m-d H:i:s'), 'finished', true, [0, 1, 2], true);
	$equalStart = (new DateTimeImmutable('+2 days'))->format('Y-m-d H:i:s');
	$futureA = $createEvent($equalStart, 'scheduled', false);
	$futureB = $createEvent($equalStart, 'scheduled', false, [1, 2]);
	$farFuture = $createEvent((new DateTimeImmutable('+40 days'))->format('Y-m-d H:i:s'), 'scheduled', false);
	$unscheduled = $createEvent(null, 'scheduled', false);

	$helper = new NextEventHelper();
	$base = ['project_id' => $project, 'sport_type_id' => $sport, 'stage_id' => $stage, 'venue_id' => $venue, 'previous_days' => 10, 'future_days' => 10, 'previous_limit' => 1, 'upcoming_limit' => 1, 'participant_limit' => 2];
	$data = $helper->getEvent(new Registry($base));
	$assert(!isset($data['error']), 'Next Event unexpectedly returned an error.');
	$assert(array_column($data['previous'], 'id') === [$recentPastB], 'Previous limit/window was interchanged or ordered incorrectly.');
	$assert(array_column($data['upcoming'], 'id') === [$futureA], 'Upcoming limit/window or equal-time ascending order failed.');
	$assert($data['previous'][0]['participant_count'] === 3 && count($data['previous'][0]['participants']) === 2, 'Participant truncation did not preserve total count.');
	$assert(array_column($data['previous'][0]['participants'], 'rank') === [1, 2] && array_values($data['previous'][0]['decider']['values'] ?? []) === ['5', '4', '3'], 'Winner ranks or deciding segment were not projected.');
	$assert(!in_array($oldPast, array_column($data['previous'], 'id'), true) && !in_array($farFuture, array_column($data['upcoming'], 'id'), true), 'Independent time windows failed.');

	$descending = $helper->getEvent(new Registry(array_replace($base, ['tie_breaker' => 'id_desc', 'upcoming_limit' => 2, 'previous_limit' => 0])));
	$assert(array_column($descending['upcoming'], 'id') === [$futureB, $futureA], 'Equal-time descending ID ordering failed.');
	$withUndated = $helper->getEvent(new Registry(array_replace($base, ['unscheduled_mode' => 'after_upcoming', 'upcoming_limit' => 3, 'previous_limit' => 0])));
	$assert(array_column($withUndated['upcoming'], 'id') === [$futureA, $futureB, $unscheduled] && $withUndated['upcoming'][2]['timestamp'] === null, 'Undated event handling failed.');

	$entryScope = $helper->getEvent(new Registry(array_replace($base, ['scope' => 'entry', 'entry_id' => $entries[2], 'previous_limit' => 2, 'upcoming_limit' => 2])));
	$assert(array_column($entryScope['previous'], 'id') === [$recentPastB] && array_column($entryScope['upcoming'], 'id') === [$futureB], 'Entry scope leaked unrelated events.');
	$wrongSport = $helper->getEvent(new Registry(array_replace($base, ['sport_type_id' => $sport + 999999])));
	$assert(($wrongSport['error'] ?? '') === 'MOD_JOOMLEAGUE_NEXT_EVENT_EMPTY', 'Sport filter did not exclude a mismatched project.');
	$status = $helper->getEvent(new Registry(array_replace($base, ['status_codes' => ['scheduled'], 'previous_limit' => 2, 'upcoming_limit' => 2])));
	$assert(($status['previous'] ?? []) === [] && array_column($status['upcoming'], 'id') === [$futureA, $futureB], 'Status filter did not affect both collections.');

	printf("Next Programme Event OK on %s: independent limits/windows, filters, scope, tie order and undated handling validated\n", $db->getServerType());
} finally {
	$db->setQuery($db->getQuery(true)->delete($db->quoteName('#__joomleague_project'))->where('id = ' . (int) $project))->execute();
	foreach ([['#__joomleague_venue', $venue], ['#__joomleague_sport_type', $sport], ['#__joomleague_competition', $competition], ['#__joomleague_season', $season]] as [$table, $id]) $db->setQuery($db->getQuery(true)->delete($db->quoteName($table))->where('id = ' . (int) $id))->execute();
}
