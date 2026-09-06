<?php

declare(strict_types=1);

define('_JEXEC', 1);
define('JPATH_BASE', '/var/www/html');
require_once JPATH_BASE . '/includes/defines.php';
require_once JPATH_BASE . '/includes/framework.php';
foreach (['UuidFactory.php', 'EntryModelValidator.php', 'MatchResultDuration.php', 'MatchEventRepository.php', 'MatchStatisticRepository.php'] as $service) {
	require_once JPATH_ADMINISTRATOR . '/components/com_joomleague/src/Service/' . $service;
}

use Joomla\CMS\Factory;
use Joomla\Database\DatabaseInterface;
use Joomleague\Component\Joomleague\Administrator\Service\MatchEventRepository;
use Joomleague\Component\Joomleague\Administrator\Service\MatchStatisticRepository;

$container = Factory::getContainer();
$container->alias('session', 'session.cli')->alias('JSession', 'session.cli')
	->alias(Joomla\CMS\Session\Session::class, 'session.cli')->alias(Joomla\Session\Session::class, 'session.cli')
	->alias(Joomla\Session\SessionInterface::class, 'session.cli');
Factory::$application = $container->get(Joomla\Console\Application::class);
$db = $container->get(DatabaseInterface::class);
$events = new MatchEventRepository($db);
$statistics = new MatchStatisticRepository($db);
$addedEvents = 0; $savedStatistics = 0; $skippedEvents = 0;

$matchIds = array_map('intval', $db->setQuery($db->getQuery(true)->select('match.id')
	->from($db->quoteName('#__joomleague_project_match', 'match'))
	->innerJoin($db->quoteName('#__joomleague_match_result', 'result') . ' ON result.match_id=match.id')
	->order('match.id ASC'))->loadColumn());

foreach ($matchIds as $matchIndex => $matchId) {
	$context = $events->getContext($matchId);
	$existingCodes = array_fill_keys(array_map(static fn(object $row): string => (string) $row->event_code, $events->getEvents($matchId)), true);
	$participants = $context['participants'];
	$official = $context['officials'][0] ?? null;
	$position = count($existingCodes);
	$clockValues = [7, 14, 23, 36, 52, 68, 79, 87];
	foreach ($context['events'] as $eventCode => $definition) {
		if (isset($existingCodes[$eventCode]) || $position >= 6) continue;
		$data = ['event_code' => $eventCode, 'notes' => 'Fictional profile-defined timeline event.'];
		$personType = (string) ($definition['person_type'] ?? '');
		if (!empty($definition['system_event'])) {
			$data['occurred_at'] = '2035-08-01 17:' . str_pad((string) (10 + $position), 2, '0', STR_PAD_LEFT) . ':00';
		} elseif ($personType === 'official') {
			if (!$official) { $skippedEvents++; continue; }
			$data['source_match_actor_role_id'] = (int) $official->id;
		} else {
			$participant = $participants[($matchIndex + $position) % max(1, count($participants))] ?? null;
			if (!$participant) { $skippedEvents++; continue; }
			$participantId = (int) $participant->id;
			$participantLineup = array_values(array_filter(
				$context['lineup'],
				static fn(object $row): bool => (int) $row->match_participant_id === $participantId
			));
			$data['match_participant_id'] = $participantId;
			$eligible = array_values(array_filter($participantLineup, static fn(object $row): bool => $personType === '' || (string) $row->member_person_type === $personType));
			$primaryIndex = ($matchIndex + $position) % max(1, count($eligible));
			if ($eligible !== []) $data['primary_lineup_member_id'] = (int) $eligible[$primaryIndex]->id;
			if (!empty($definition['requires_second_person'])) {
				if (count($eligible) < 2) { $skippedEvents++; continue; }
				$data['secondary_lineup_member_id'] = (int) $eligible[($primaryIndex + 1) % count($eligible)]->id;
			}
		}
		if (($context['profile']['match']['structure']['type'] ?? '') === 'timed_periods') {
			$data['clock_value'] = (string) $clockValues[($matchIndex + $position) % count($clockValues)];
			$data['clock_unit'] = 'minute';
		}
		if (($definition['value_type'] ?? null) === 'integer') $data['numeric_value'] = (string) ($position + 1);
		try { $events->add($matchId, $data, 0); $addedEvents++; $position++; } catch (Throwable) { $skippedEvents++; }
	}

	$statContext = $statistics->getContext($matchId);
	$position = 0;
	foreach ($statContext['statistics'] as $statCode => $definition) {
		if (!in_array((string) ($definition['source'] ?? ''), ['manual', 'manual_or_import'], true) || $position >= 6) continue;
		$scope = (string) ($definition['scope'] ?? ''); $targets = [];
		if (in_array($scope, ['team', 'person', 'group', 'participant'], true)) {
			foreach ($statContext['participants'] as $row) if ($scope === 'participant' || (string) $row->entry_kind === $scope) $targets[] = 'participant:' . (int) $row->id;
		}
		if ($targets === []) {
			foreach ($statContext['lineup'] as $row) {
				if ((string) $row->member_person_type === $scope || (string) $row->role_code === $scope) { $targets[] = 'person:' . (int) $row->id; break; }
				foreach ($statContext['profile']['positions'] ?? [] as $profilePosition) if (($profilePosition['code'] ?? null) === $row->role_code && ($profilePosition['lineup_group'] ?? null) === $scope) { $targets[] = 'person:' . (int) $row->id; break 2; }
			}
		}
		if ($targets === []) continue;
		$valueType = (string) ($definition['value_type'] ?? 'integer');
		$firstPercentage = 45 + (($matchIndex + $position) % 11);
		foreach ($targets as $targetIndex => $target) {
			$value = match ($valueType) { 'percentage' => (string) ($targetIndex === 1 && count($targets) === 2 ? 100 - $firstPercentage : $firstPercentage), 'decimal' => number_format(1.5 + $position + ($targetIndex * .2), 1, '.', ''), 'duration' => '00:' . str_pad((string) (20 + $position + $targetIndex), 2, '0', STR_PAD_LEFT) . ':15', 'text', 'string' => 'Demo value ' . ($position + $targetIndex + 1), default => (string) (3 + (($matchIndex + $position + ($targetIndex * 2)) % 12)) };
			try { $statistics->save($matchId, ['statistic_code' => $statCode, 'target' => $target, 'value' => $value, 'notes' => 'Fictional profile-defined statistic.'], 0); $savedStatistics++; } catch (Throwable) {}
		}
		$position++;
	}
}

echo "Event enrichment: {$addedEvents} timeline events added, {$savedStatistics} statistics saved, {$skippedEvents} unsupported event combinations skipped.\n";
