<?php

declare(strict_types=1);

define('_JEXEC', 1);
define('JPATH_BASE', '/var/www/html');
require_once JPATH_BASE . '/includes/defines.php';
require_once JPATH_BASE . '/includes/framework.php';

use Joomla\CMS\Factory;
use Joomla\Database\DatabaseInterface;

$container = Factory::getContainer();
$container->alias('session', 'session.cli')
	->alias('JSession', 'session.cli')
	->alias(Joomla\CMS\Session\Session::class, 'session.cli')
	->alias(Joomla\Session\Session::class, 'session.cli')
	->alias(Joomla\Session\SessionInterface::class, 'session.cli');
Factory::$application = $container->get(Joomla\Console\Application::class);
$db = $container->get(DatabaseInterface::class);

$finishedIds = array_map('intval', $db->setQuery(
	$db->getQuery(true)
		->select('match.id')
		->from($db->quoteName('#__joomleague_project_match', 'match'))
		->innerJoin($db->quoteName('#__joomleague_match_result', 'result') . " ON result.match_id = match.id AND result.status_code = 'final'")
		->where("match.status_code <> 'finished'")
)->loadColumn());

foreach ($finishedIds as $matchId) {
	$finishedMatch = (object) ['id' => $matchId, 'status_code' => 'finished'];
	$db->updateObject('#__joomleague_project_match', $finishedMatch, 'id');
}

$matchIds = array_map('intval', $db->setQuery(
	$db->getQuery(true)
		->select('DISTINCT event.match_id')
		->from($db->quoteName('#__joomleague_match_event', 'event'))
		->innerJoin($db->quoteName('#__joomleague_project_match', 'match') . ' ON match.id = event.match_id')
		->innerJoin($db->quoteName('#__joomleague_project', 'project') . ' ON project.id = match.project_id')
		->where("project.metadata_json LIKE '%\"demo\":true%'")
		->order('event.match_id ASC')
)->loadColumn());

$clockValues = [7, 14, 23, 36, 52, 68, 79, 87];
$updatedEvents = 0;

foreach ($matchIds as $matchIndex => $matchId) {
	$participants = $db->setQuery(
		$db->getQuery(true)->select('id')->from($db->quoteName('#__joomleague_match_participant'))
			->where('match_id = ' . $matchId)->where('published = 1')->order('slot_number ASC, id ASC')
	)->loadColumn();
	$participants = array_map('intval', $participants);
	if ($participants === []) {
		continue;
	}

	$lineup = $db->setQuery(
		$db->getQuery(true)
			->select(['lineup.id', 'lineup.match_participant_id', 'lineup.person_id', 'lineup.member_person_type', 'person.first_name', 'person.last_name', 'person.nickname'])
			->from($db->quoteName('#__joomleague_match_lineup_member', 'lineup'))
			->innerJoin($db->quoteName('#__joomleague_person', 'person') . ' ON person.id = lineup.person_id')
			->where('lineup.match_id = ' . $matchId)->where('lineup.published = 1')
			->order('lineup.match_participant_id ASC, lineup.ordering ASC, lineup.id ASC')
	)->loadObjectList();
	foreach ($lineup as $member) {
		$member->display_name = trim((string) $member->first_name . ((string) $member->nickname !== '' ? " '" . (string) $member->nickname . "'" : '') . ' ' . (string) $member->last_name);
	}

	$events = $db->setQuery(
		$db->getQuery(true)->select('*')->from($db->quoteName('#__joomleague_match_event'))
			->where('match_id = ' . $matchId)->order('sequence_number ASC, id ASC')
	)->loadObjectList();

	foreach ($events as $eventIndex => $event) {
		$definition = json_decode((string) $event->profile_metadata_json, true);
		if (!is_array($definition) || !empty($definition['system_event']) || (string) ($definition['person_type'] ?? '') === 'official') {
			continue;
		}

		$participantId = $participants[($matchIndex + $eventIndex) % count($participants)];
		$personType = (string) ($definition['person_type'] ?? '');
		$eligible = array_values(array_filter(
			$lineup,
			static fn(object $member): bool => (int) $member->match_participant_id === $participantId
				&& ($personType === '' || (string) $member->member_person_type === $personType)
		));
		if ($eligible === []) {
			continue;
		}

		$primaryIndex = ($matchIndex + $eventIndex) % count($eligible);
		$primary = $eligible[$primaryIndex];
		$secondary = !empty($definition['requires_second_person']) && count($eligible) > 1
			? $eligible[($primaryIndex + 1) % count($eligible)]
			: null;
		$record = (object) [
			'id' => (int) $event->id,
			'match_participant_id' => $participantId,
			'primary_lineup_member_id' => (int) $primary->id,
			'primary_person_id' => (int) $primary->person_id,
			'primary_name_snapshot' => (string) $primary->display_name,
			'secondary_lineup_member_id' => $secondary ? (int) $secondary->id : null,
			'secondary_person_id' => $secondary ? (int) $secondary->person_id : null,
			'secondary_name_snapshot' => $secondary ? (string) $secondary->display_name : null,
		];
		if ($event->clock_unit === 'minute') {
			$record->clock_value = (string) $clockValues[($matchIndex + $eventIndex) % count($clockValues)];
		}
		$db->updateObject('#__joomleague_match_event', $record, 'id', true);
		$updatedEvents++;
	}
}

echo sprintf("Demo credibility repair: %d programme items marked finished; %d timeline events redistributed.\n", count($finishedIds), $updatedEvents);
