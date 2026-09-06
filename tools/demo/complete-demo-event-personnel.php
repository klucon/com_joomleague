<?php

declare(strict_types=1);

define('_JEXEC', 1);
define('JPATH_BASE', '/var/www/html');
require_once JPATH_BASE . '/includes/defines.php';
require_once JPATH_BASE . '/includes/framework.php';

foreach (['UuidFactory.php', 'EntryModelValidator.php', 'MatchLineupRepository.php', 'MatchActorRoleRepository.php'] as $service) {
	require_once JPATH_ADMINISTRATOR . '/components/com_joomleague/src/Service/' . $service;
}

use Joomla\CMS\Factory;
use Joomla\Database\DatabaseInterface;
use Joomleague\Component\Joomleague\Administrator\Service\MatchActorRoleRepository;
use Joomleague\Component\Joomleague\Administrator\Service\MatchLineupRepository;

$container = Factory::getContainer();
$container->alias('session', 'session.cli')->alias('JSession', 'session.cli')
	->alias(Joomla\CMS\Session\Session::class, 'session.cli')->alias(Joomla\Session\Session::class, 'session.cli')
	->alias(Joomla\Session\SessionInterface::class, 'session.cli');
Factory::$application = $container->get(Joomla\Console\Application::class);

$db = $container->get(DatabaseInterface::class);
$lineups = new MatchLineupRepository($db, 'Europe/Prague');
$officials = new MatchActorRoleRepository($db, 'Europe/Prague');
$lineupAssignments = 0;
$officialAssignments = 0;

$matches = $db->setQuery(
	$db->getQuery(true)
		->select(['match.id', 'result.id AS result_id'])
		->from($db->quoteName('#__joomleague_project_match', 'match'))
		->leftJoin($db->quoteName('#__joomleague_match_result', 'result') . ' ON result.match_id = match.id')
		->where('match.published = 1')
		->order('match.id ASC')
)->loadObjectList();

foreach ($matches as $match) {
	$matchId = (int) $match->id;
	$context = $lineups->getContext($matchId);
	$profile = $context['profile'];
	if ($match->result_id !== null && ($profile['entry_model']['members_supported'] ?? false) === true) {
		$maximumStarters = max(1, (int) ($profile['lineup']['players_on_field'] ?? 1));
		$captainSupported = ($profile['lineup']['captain_supported'] ?? false) === true;
		$substitutionsSupported = ($profile['lineup']['substitutions']['supported'] ?? false) === true;
		foreach ($context['participants'] as $participant) {
			$participantId = (int) $participant->id;
			if ($lineups->getAssignedMembers($matchId, $participantId) !== []) {
				continue;
			}

			$playerIndex = 0;
			foreach ($lineups->getAvailableMembers($matchId, $participantId) as $member) {
				if ($member->lineup_id !== null) {
					continue;
				}
				$personType = (string) $member->member_person_type;
				if ($personType === 'player') {
					$status = $playerIndex < $maximumStarters ? 'starter' : ($substitutionsSupported ? 'substitute' : 'available');
					$lineups->assign($matchId, $participantId, (int) $member->id, $status, $captainSupported && $playerIndex === 0, 0);
					$playerIndex++;
					$lineupAssignments++;
				} elseif ($personType === 'staff') {
					$lineups->assign($matchId, $participantId, (int) $member->id, 'active', false, 0);
					$lineupAssignments++;
				}
			}
		}
	}

	if ($officials->getMatchAssignments($matchId) === []) {
		foreach (array_slice($officials->getAvailableForMatch($matchId), 0, 2) as $assignment) {
			$officials->assignToMatch($matchId, (int) $assignment->id, 'Fictional official assignment for the public demo.', 0);
			$officialAssignments++;
		}
	}
}

echo "Event personnel completion: {$lineupAssignments} lineup members and {$officialAssignments} officials assigned.\n";
