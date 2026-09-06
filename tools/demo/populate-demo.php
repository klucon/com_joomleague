<?php

declare(strict_types=1);

define('_JEXEC', 1);
define('JPATH_BASE', '/var/www/html');

require_once JPATH_BASE . '/includes/defines.php';
require_once JPATH_BASE . '/includes/framework.php';

$services = [
	'UuidFactory.php', 'CanonicalJson.php', 'EntryModelValidator.php', 'SportTypeProfileMaterializer.php',
	'ScheduleTemplateService.php', 'SchedulePlannerService.php',
	'MatchResultValidationException.php', 'MatchResultDecimal.php', 'MatchResultAggregationValidator.php',
	'MatchResultPayloadValidator.php', 'MatchResultRepository.php', 'MatchLineupRepository.php',
	'MatchActorRoleRepository.php', 'MatchEventRepository.php', 'MatchStatisticRepository.php',
	'StandingsContractValidator.php', 'StandingsDecimal.php', 'StandingsCalculator.php', 'StandingsReader.php',
	'StandingsSnapshotSynchronizer.php', 'StandingsRecalculator.php',
	'OrganizationHistoryRepository.php', 'PositionCapabilityRepository.php',
];
foreach ($services as $service) {
	$path = JPATH_ADMINISTRATOR . '/components/com_joomleague/src/Service/' . $service;
	if (is_file($path)) require_once $path;
}

use Joomla\CMS\Factory;
use Joomla\Database\DatabaseInterface;
use Joomleague\Component\Joomleague\Administrator\Service\MatchActorRoleRepository;
use Joomleague\Component\Joomleague\Administrator\Service\MatchEventRepository;
use Joomleague\Component\Joomleague\Administrator\Service\MatchLineupRepository;
use Joomleague\Component\Joomleague\Administrator\Service\MatchResultRepository;
use Joomleague\Component\Joomleague\Administrator\Service\MatchStatisticRepository;
use Joomleague\Component\Joomleague\Administrator\Service\OrganizationHistoryRepository;
use Joomleague\Component\Joomleague\Administrator\Service\PositionCapabilityRepository;
use Joomleague\Component\Joomleague\Administrator\Service\SchedulePlannerService;
use Joomleague\Component\Joomleague\Administrator\Service\SportTypeProfileMaterializer;
use Joomleague\Component\Joomleague\Domain\Service\CanonicalJson;
use Joomleague\Component\Joomleague\Domain\Service\StandingsReader;
use Joomleague\Component\Joomleague\Domain\Service\StandingsRecalculator;
use Joomleague\Component\Joomleague\Domain\Service\UuidFactory;

$container = Factory::getContainer();
$container->alias('session', 'session.cli')
	->alias('JSession', 'session.cli')
	->alias(Joomla\CMS\Session\Session::class, 'session.cli')
	->alias(Joomla\Session\Session::class, 'session.cli')
	->alias(Joomla\Session\SessionInterface::class, 'session.cli');
Factory::$application = $container->get(Joomla\Console\Application::class);
$db = $container->get(DatabaseInterface::class);
$actorId = 0;
$now = gmdate('Y-m-d H:i:s');
$templates = json_decode((string) file_get_contents(__DIR__ . '/result-templates.json'), true, 512, JSON_THROW_ON_ERROR);

$sportNames = [
	'football' => 'Football', 'basketball' => 'Basketball', 'ice_hockey' => 'Ice Hockey',
	'volleyball' => 'Volleyball', 'futsal' => 'Futsal', 'floorball' => 'Floorball', 'rugby' => 'Rugby',
	'esports' => 'Esports', 'bowling' => 'Bowling', 'chess' => 'Chess', 'darts' => 'Darts',
	'tennis' => 'Tennis', 'mma_boxing' => 'MMA / Boxing', 'motorsport' => 'Motorsport',
	'running_race' => 'Running Race',
];
$sportOrder = array_keys($sportNames);
$places = ['Northbridge', 'Westmoor', 'Riverdale', 'Ashford', 'Greenwood', 'Stonebridge', 'Lakeside', 'Harborview', 'Oakfield', 'Kingsport'];
$mascots = ['Comets', 'Falcons', 'Knights', 'Rangers', 'Storm', 'Titans', 'Wolves', 'Panthers', 'Eagles', 'Bears'];
$firstNames = ['Alex', 'Jordan', 'Morgan', 'Taylor', 'Casey', 'Riley', 'Jamie', 'Cameron', 'Robin', 'Avery', 'Sam', 'Charlie'];
$lastNames = ['Hart', 'Blake', 'Morgan', 'Reed', 'Cole', 'Parker', 'Hayes', 'Brooks', 'Ellis', 'Ward', 'Bennett', 'Foster'];

$insert = static function (string $table, array $values) use ($db): int {
	$query = $db->getQuery(true)->insert($db->quoteName($table))->columns($db->quoteName(array_keys($values)));
	$holders = [];
	foreach ($values as $key => &$value) {
		$holders[] = ':' . $key;
		$query->bind(':' . $key, $value);
	}
	$query->values(implode(',', $holders));
	$db->setQuery($query)->execute();
	return (int) $db->insertid();
};
$slug = static fn(string $value): string => trim(preg_replace('/[^a-z0-9]+/', '-', strtolower($value)) ?? '', '-');
$loadedProfileRows = $db->setQuery(
	$db->getQuery(true)
		->select(['profile.code', 'version.id', 'version.payload_json'])
		->from($db->quoteName('#__joomleague_sport_profile', 'profile'))
		->innerJoin($db->quoteName('#__joomleague_sport_profile_version', 'version') . ' ON version.profile_id = profile.id')
		->where('version.state = ' . $db->quote('active'))
)->loadObjectList();

$profileRowsByCode = [];
foreach ($loadedProfileRows as $profileRow) {
	$profileRowsByCode[(string) $profileRow->code] = $profileRow;
}
$profileRows = array_map(
	static fn(string $code): object => $profileRowsByCode[$code] ?? throw new RuntimeException('Missing active profile: ' . $code),
	$sportOrder,
);

if (count($profileRows) !== 15) throw new RuntimeException('Exactly 15 active bundled sport profiles are required.');
if ((int) $db->setQuery($db->getQuery(true)->select('COUNT(*)')->from($db->quoteName('#__joomleague_sport_type')))->loadResult() !== 0) {
	throw new RuntimeException('Runtime data must be reset before demo population.');
}

$materializer = new SportTypeProfileMaterializer($db);
$resultRepository = new MatchResultRepository($db);
$lineupRepository = new MatchLineupRepository($db, 'Europe/Prague');
$actorRepository = new MatchActorRoleRepository($db);
$eventRepository = new MatchEventRepository($db);
$statisticRepository = new MatchStatisticRepository($db);
$historyRepository = new OrganizationHistoryRepository($db);
$capabilityRepository = new PositionCapabilityRepository($db);
$summary = [];

foreach ($profileRows as $profileIndex => $profileRow) {
	$code = (string) $profileRow->code;
	$profile = json_decode((string) $profileRow->payload_json, true, 512, JSON_THROW_ON_ERROR);
	$name = $sportNames[$code] ?? ucwords(str_replace('_', ' ', $code));
	$profileVersionId = (int) $profileRow->id;
	$projectType = in_array('league', $profile['project']['types'] ?? [], true) ? 'league' : (string) (($profile['project']['types'][0] ?? 'tournament'));
	$contestType = (string) ($profile['contest']['type'] ?? 'head_to_head');
	$entryModel = $profile['entry_model'];
	$entryKind = (string) $entryModel['default_kind'];
	$matchLineupSupported = in_array('player', $entryModel['member_person_types'] ?? [], true);
	$templatePayload = $templates[$code] ?? null;
	if (!is_array($templatePayload)) throw new RuntimeException('Missing result template for ' . $code);
	$templateJson = json_encode($templatePayload, JSON_THROW_ON_ERROR);
	preg_match_all('/\{\{participant_(\d+)\}\}/', $templateJson, $participantMatches);
	$requiredParticipants = $participantMatches[1] === [] ? 0 : max(array_map('intval', $participantMatches[1]));
	$entryCount = $contestType === 'race' ? max(10, $requiredParticipants) : 8;

	$db->transactionStart();
	try {
		$sportTypeId = $insert('#__joomleague_sport_type', [
			'profile_version_id' => $profileVersionId, 'code' => $code, 'name' => $name, 'alias' => $slug($name),
			'published' => 1, 'ordering' => $profileIndex + 1, 'created' => $now, 'created_by' => $actorId,
		]);
		$counts = $materializer->materialize($sportTypeId, $profileVersionId, ['positions' => true, 'event_types' => true, 'statistics' => true], $actorId);
		$competitionId = $insert('#__joomleague_competition', [
			'uuid' => UuidFactory::v4(), 'name' => $name . ' Demo Championship', 'middle_name' => $name . ' Demo',
			'short_name' => strtoupper(substr(str_replace('_', '', $code), 0, 8)), 'alias' => $slug($name . ' demo championship'),
			'code' => 'DEMO-' . strtoupper(str_replace('_', '-', $code)), 'external_code' => 'JL-DEMO-' . str_pad((string) ($profileIndex + 1), 2, '0', STR_PAD_LEFT),
			'organisation' => 'JoomLeague Demo Federation', 'country_code' => 'GB',
			'description' => 'A complete fictional competition demonstrating the universal ' . strtolower($name) . ' workflow in JoomLeague.',
			'metadata_json' => CanonicalJson::encodeObject(['demo' => true, 'profile' => $code]), 'published' => 1, 'access' => 1, 'ordering' => $profileIndex + 1,
		]);
		$seasonId = $insert('#__joomleague_season', [
			'uuid' => UuidFactory::v4(), 'name' => '2035/2036', 'alias' => '2035-2036-' . $code,
			'start_date' => '2035-08-01', 'end_date' => '2036-06-30',
			'description' => 'Fictional 2035/2036 demonstration season for ' . $name . '.',
			'metadata_json' => CanonicalJson::encodeObject(['demo' => true]), 'published' => 1, 'access' => 1, 'ordering' => $profileIndex + 1,
		]);
		$projectId = $insert('#__joomleague_project', [
			'uuid' => UuidFactory::v4(), 'competition_id' => $competitionId, 'season_id' => $seasonId,
			'sport_type_id' => $sportTypeId, 'profile_version_id' => $profileVersionId,
			'name' => $name . ' Demo League 2035/2036', 'alias' => $slug($name . ' demo league 2035 2036'),
			'code' => 'DEMO-' . strtoupper(str_replace('_', '-', $code)) . '-2627', 'external_code' => 'JL-' . (1000 + $profileIndex),
			'project_type' => $projectType, 'timezone' => null, 'start_date' => '2035-08-01', 'end_date' => '2036-06-30',
			'default_start_time' => (string) ($profile['match']['structure']['defaults']['start_time'] ?? '17:00'),
			'current_round_mode' => 'start', 'auto_advance_seconds' => 7200, 'lifecycle_state' => 'active',
			'description' => 'The complete ' . $name . ' showcase: participants, personnel, programme, results, event reports, statistics and profile-driven standings.',
			'metadata_json' => CanonicalJson::encodeObject(['demo' => true, 'features' => ['programme', 'results', 'lineups', 'events', 'statistics']]),
			'published' => 1, 'access' => 1, 'ordering' => $profileIndex + 1,
		]);
		$stageId = $insert('#__joomleague_project_stage', [
			'uuid' => UuidFactory::v4(), 'project_id' => $projectId, 'name' => $contestType === 'race' ? 'Race programme' : 'Regular season',
			'alias' => $contestType === 'race' ? 'race-programme' : 'regular-season', 'code' => 'main',
			'stage_type' => $contestType === 'race' ? 'race' : 'league', 'entry_selection_mode' => 'inherit_project',
			'sequence_number' => 1, 'start_date' => '2035-08-01', 'end_date' => '2036-06-30',
			'description' => 'Primary demonstration stage generated from the sport profile.', 'published' => 1, 'ordering' => 1,
		]);
		$entries = [];
		$entryMembers = [];
		$venueIds = [];
		for ($i = 1; $i <= $entryCount; $i++) {
			$place = $places[($profileIndex + $i) % count($places)];
			$label = $place . ' ' . $mascots[($profileIndex * 2 + $i) % count($mascots)];
			$clubId = null; $teamId = null; $personId = null;
			if ($entryKind === 'team') {
				$clubId = $insert('#__joomleague_club', [
					'uuid' => UuidFactory::v4(), 'name' => $label . ' Club', 'alias' => $slug($label . ' club'), 'short_name' => strtoupper(substr($place, 0, 3)) . $i,
					'country_code' => 'GB', 'website' => 'https://demo.joomleague.eu/', 'founded_date' => (1905 + (($profileIndex * 7 + $i) % 90)) . '-01-01',
					'description' => 'A fictional ' . strtolower($name) . ' club created for the complete JoomLeague demonstration.',
					'metadata_json' => CanonicalJson::encodeObject(['demo' => true, 'sport' => $code]), 'published' => 1, 'access' => 1,
				]);
				$teamId = $insert('#__joomleague_team', [
					'uuid' => UuidFactory::v4(), 'club_id' => $clubId, 'name' => $label, 'middle_name' => $label, 'short_name' => strtoupper(substr($place, 0, 3)) . $i,
					'alias' => $slug($label), 'website' => 'https://demo.joomleague.eu/',
					'description' => 'The fictional first team of ' . $label . ' Club.', 'metadata_json' => CanonicalJson::encodeObject(['demo' => true, 'sport' => $code]),
					'published' => 1, 'access' => 1,
				]);
				$historyRepository->save('club', $clubId, [
					['name' => $label . ' Athletic', 'short_name' => strtoupper(substr($place, 0, 3)) . 'A', 'valid_from' => '1995-01-01', 'valid_to' => '2014-06-30', 'notes' => 'Previous fictional club name.'],
					['name' => $label . ' Club', 'short_name' => strtoupper(substr($place, 0, 3)) . $i, 'valid_from' => '2014-07-01', 'notes' => 'Current fictional club name.'],
				], [], $actorId);
				$historyRepository->save('team', $teamId, [
					['name' => $label . ' Development', 'short_name' => 'DEV' . $i, 'valid_from' => '2010-01-01', 'valid_to' => '2019-06-30', 'notes' => 'Previous fictional team name.'],
					['name' => $label, 'short_name' => strtoupper(substr($place, 0, 3)) . $i, 'valid_from' => '2019-07-01', 'notes' => 'Current fictional team name.'],
				], [], $actorId);
			} else {
				$personId = $insert('#__joomleague_person', [
					'uuid' => UuidFactory::v4(), 'first_name' => $firstNames[($i + $profileIndex) % count($firstNames)],
					'last_name' => $lastNames[($i * 2 + $profileIndex) % count($lastNames)] . ' ' . ($profileIndex + 1) . $i,
					'nickname' => 'Demo ' . $i, 'alias' => $slug($label . '-' . $i), 'country_code' => ['GB','CZ','DE','FI','PL'][$i % 5],
					'birth_date' => (1986 + ($i % 16)) . '-' . str_pad((string) (($i % 12) + 1), 2, '0', STR_PAD_LEFT) . '-15',
					'description' => 'A fictional individual competitor demonstrating the ' . strtolower($name) . ' profile.',
					'metadata_json' => CanonicalJson::encodeObject(['demo' => true, 'sport' => $code]), 'published' => 1, 'access' => 1,
				]);
				$label = $firstNames[($i + $profileIndex) % count($firstNames)] . ' ' . $lastNames[($i * 2 + $profileIndex) % count($lastNames)] . ' ' . ($profileIndex + 1) . $i;
			}
			$venueId = $insert('#__joomleague_venue', [
				'uuid' => UuidFactory::v4(), 'owner_club_id' => $clubId, 'name' => $place . ' ' . ($contestType === 'race' ? 'Course' : 'Arena'),
				'alias' => $slug($place . '-' . $code . '-venue-' . $i), 'short_name' => $place, 'nickname' => 'Demo venue ' . $i,
				'address' => (10 + $i) . ' Competition Way', 'postal_code' => 'JL' . str_pad((string) ($profileIndex + 1), 2, '0', STR_PAD_LEFT) . ' ' . $i . 'DE',
				'city' => $place, 'region' => 'Demo Region', 'country_code' => 'GB', 'latitude' => 49.0 + (($profileIndex * 10 + $i) / 1000),
				'longitude' => 16.0 + (($profileIndex * 10 + $i) / 1000), 'timezone' => 'Europe/London', 'capacity' => 1000 + ($i * 750),
				'website' => 'https://demo.joomleague.eu/', 'description' => 'A fictional fully described venue used by the ' . $name . ' demo project.',
				'metadata_json' => CanonicalJson::encodeObject(['demo' => true, 'sport' => $code]), 'published' => 1, 'access' => 1,
			]);
			$venueIds[$i] = $venueId;
			$entryId = $insert('#__joomleague_project_entry', [
				'uuid' => UuidFactory::v4(), 'project_id' => $projectId, 'entry_kind' => $entryKind,
				'team_id' => $teamId, 'person_id' => $personId, 'display_name' => $label, 'entry_code' => strtoupper(substr($code, 0, 3)) . '-' . $i,
				'seed_number' => $i, 'bib_number' => $contestType === 'race' ? (string) (100 + $i) : null,
				'included_in_standings' => 1, 'lifecycle_state' => 'active', 'published' => 1, 'ordering' => $i,
			]);
			$entries[] = $entryId;
			$entryMembers[$entryId] = [];
			if (!empty($entryModel['members_supported'])) {
				$playerType = in_array('player', $entryModel['member_person_types'], true) ? 'player' : (in_array('participant', $entryModel['member_person_types'], true) ? 'participant' : null);
				$playerPositions = array_values(array_filter($profile['positions'] ?? [], static fn(array $position): bool => ($position['person_type'] ?? '') === $playerType));
				$memberTotal = $entryKind === 'team' ? 12 : 1;
				for ($member = 1; $member <= $memberTotal; $member++) {
					$memberPerson = $insert('#__joomleague_person', [
						'uuid' => UuidFactory::v4(), 'club_id' => $clubId,
						'first_name' => $firstNames[($member + $i + $profileIndex) % count($firstNames)],
						'last_name' => $lastNames[($member * 3 + $i) % count($lastNames)] . ' ' . ($profileIndex + 1) . $i . $member,
						'alias' => 'demo-' . $code . '-' . $i . '-' . $member, 'country_code' => ['GB','CZ','DE','FI','PL'][$member % 5],
						'birth_date' => (1988 + ($member % 15)) . '-' . str_pad((string) (($member % 12) + 1), 2, '0', STR_PAD_LEFT) . '-10',
						'description' => 'A fictional roster member used to demonstrate line-ups and statistics.',
						'metadata_json' => CanonicalJson::encodeObject(['demo' => true]), 'published' => 1, 'access' => 1,
					]);
					$role = $playerPositions === [] ? null : (string) ($playerPositions[($member - 1) % count($playerPositions)]['code'] ?? null);
					$memberId = $insert('#__joomleague_project_entry_member', [
						'uuid' => UuidFactory::v4(), 'entry_id' => $entryId, 'person_id' => $memberPerson,
						'member_person_type' => $playerType ?? 'player', 'role_code' => $role, 'shirt_number' => (string) $member,
						'is_captain' => $member === 1 && !empty($profile['lineup']['captain_supported']) ? 1 : 0,
						'valid_from' => '2035-08-01', 'valid_until' => '2036-06-30',
						'lifecycle_state' => 'active', 'notes' => 'Fictional active roster membership.', 'published' => 1, 'ordering' => $member,
					]);
					$entryMembers[$entryId][] = $memberId;
				}
			}
		}
		$db->transactionCommit();
	} catch (Throwable $exception) {
		$db->transactionRollback();
		throw $exception;
	}

	$planner = new SchedulePlannerService($db);
	$options = $planner->defaults($stageId);
	$options['start_date'] = '2035-08-01';
	$options['start_time'] = (string) ($profile['match']['structure']['defaults']['start_time'] ?? '17:00');
	$options['round_interval_days'] = 7;
	$options['published'] = 1;
	$options['assign_home_venues'] = 1;
	$options['race_rounds'] = $contestType === 'race' ? 3 : 1;
	$generation = $planner->apply($stageId, $options, $actorId);

	$officialRoles = array_values(array_filter($profile['positions'] ?? [], static fn(array $position): bool => ($position['person_type'] ?? '') === 'official'));
	$projectAssignments = [];
	foreach (array_slice($officialRoles, 0, 2) as $officialIndex => $role) {
		$person = $insert('#__joomleague_person', [
			'uuid' => UuidFactory::v4(), 'first_name' => $firstNames[($profileIndex + $officialIndex + 7) % count($firstNames)],
			'last_name' => 'Official ' . ($profileIndex + 1) . ($officialIndex + 1), 'alias' => 'demo-official-' . $code . '-' . ($officialIndex + 1),
			'country_code' => 'GB', 'birth_date' => '1980-05-15', 'description' => 'A fictional competition official.',
			'metadata_json' => CanonicalJson::encodeObject(['demo' => true]), 'published' => 1, 'access' => 1,
		]);
		$projectAssignments[] = $actorRepository->addProjectAssignment($projectId, 'person:' . $person, (string) $role['code'], '2035-08-01', '2036-06-30', 'Season-long fictional official assignment.', $actorId);
	}

	$matches = $db->setQuery($db->getQuery(true)->select(['id', 'scheduled_start'])->from($db->quoteName('#__joomleague_project_match'))->where('project_id=' . $projectId)->order('scheduled_start ASC,id ASC'))->loadObjectList();
	$completed = max(1, (int) floor(count($matches) * 0.65));
	foreach ($matches as $matchIndex => $rawMatch) {
		$matchId = (int) $rawMatch->id;
		$participantRows = $db->setQuery($db->getQuery(true)->select(['id','project_entry_id','slot_number'])->from($db->quoteName('#__joomleague_match_participant'))->where('match_id=' . $matchId)->order('slot_number ASC'))->loadObjectList();
		foreach ($projectAssignments as $assignment) $actorRepository->assignToMatch($matchId, $assignment, 'Demo event assignment.', $actorId);
		$lineups = [];
		foreach ($participantRows as $participant) {
			if (!$matchLineupSupported) continue;
			$members = $entryMembers[(int) $participant->project_entry_id] ?? [];
			foreach (array_slice($members, 0, 6) as $memberIndex => $memberId) {
				$captain = $memberIndex === 0 && !empty($profile['lineup']['captain_supported']);
				$lineups[(int) $participant->id][] = $lineupRepository->assign($matchId, (int) $participant->id, $memberId, $memberIndex < 4 ? 'starter' : 'substitute', $captain, $actorId);
			}
		}
		if ($matchIndex >= $completed) continue;
		$template = $templatePayload;
		$map = [];
		foreach ($participantRows as $participant) $map['{{participant_' . (int) $participant->slot_number . '}}'] = (int) $participant->id;
		if ($matchIndex % 2 === 1 && isset($map['{{participant_1}}'], $map['{{participant_2}}'])) [$map['{{participant_1}}'], $map['{{participant_2}}']] = [$map['{{participant_2}}'], $map['{{participant_1}}']];
		$replace = static function (&$value) use (&$replace, $map): void {
			if (is_string($value) && isset($map[$value])) { $value = $map[$value]; return; }
			if (is_array($value)) foreach ($value as &$child) $replace($child);
		};
		$replace($template);
		$resultRepository->replace($matchId, $template, $actorId);
		$finishedStart = $rawMatch->scheduled_start === null ? null : (new DateTimeImmutable((string) $rawMatch->scheduled_start))->modify('-10 years')->format('Y-m-d H:i:s');
		$finishedMatch = (object) ['id' => $matchId, 'status_code' => 'finished', 'scheduled_start' => $finishedStart];
		$db->updateObject('#__joomleague_project_match', $finishedMatch, 'id');
		$eventContext = $eventRepository->getContext($matchId);
		foreach ($eventContext['events'] as $eventCode => $definition) {
			if (!empty($definition['system_event']) || !empty($definition['requires_second_person'])) continue;
			$firstParticipant = $participantRows[0] ?? null;
			if (!$firstParticipant) break;
			$data = ['event_code' => $eventCode, 'match_participant_id' => (int) $firstParticipant->id, 'notes' => 'Profile-driven fictional demo event.'];
			if (($profile['match']['structure']['type'] ?? '') === 'timed_periods') {
				$data['clock_value'] = '12';
				$data['clock_unit'] = 'minute';
			}
			$firstLineup = $lineups[(int) $firstParticipant->id][0] ?? null;
			if (($definition['person_type'] ?? null) === 'player' && $firstLineup) $data['primary_lineup_member_id'] = $firstLineup;
			try { $eventRepository->add($matchId, $data, $actorId); } catch (Throwable) {}
			break;
		}
		$statContext = $statisticRepository->getContext($matchId);
		foreach ($statContext['statistics'] as $statCode => $definition) {
			if (!in_array((string) ($definition['source'] ?? ''), ['manual','manual_or_import'], true)) continue;
			$scope = (string) ($definition['scope'] ?? '');
			$targets = [];
			if (in_array($scope, ['team','participant'], true)) {
				foreach ($participantRows as $participantRow) {
					if ($scope === 'participant' || (string) $participantRow->entry_kind === $scope) $targets[] = 'participant:' . (int) $participantRow->id;
				}
			} elseif (isset($participantRows[0], $lineups[(int) $participantRows[0]->id][0])) {
				$targets[] = 'person:' . $lineups[(int) $participantRows[0]->id][0];
			}
			foreach ($targets as $targetIndex => $target) {
				try { $statisticRepository->save($matchId, ['statistic_code' => $statCode, 'target' => $target, 'value' => (string) (($matchIndex + $targetIndex) % 7 + 1), 'notes' => 'Fictional demo statistic.'], $actorId); } catch (Throwable) {}
			}
			break;
		}
	}

	foreach ($db->setQuery($db->getQuery(true)->select('id')->from($db->quoteName('#__joomleague_sport_position'))->where('sport_type_id=' . $sportTypeId)->where("person_type='player'"))->loadColumn() as $positionId) {
		$events = array_map('intval', $db->setQuery($db->getQuery(true)->select('id')->from($db->quoteName('#__joomleague_event_type'))->where('sport_type_id=' . $sportTypeId))->loadColumn());
		$statistics = array_map('intval', $db->setQuery($db->getQuery(true)->select('id')->from($db->quoteName('#__joomleague_statistic'))->where('sport_type_id=' . $sportTypeId)->where("scope IN ('player','goalkeeper')"))->loadColumn());
		try { $capabilityRepository->replaceEvents((int) $positionId, $events, $actorId); } catch (Throwable) {}
		try { $capabilityRepository->replaceStatistics((int) $positionId, $statistics, $actorId); } catch (Throwable) {}
	}

	$reader = new StandingsReader($db);
	$recalculator = new StandingsRecalculator($db, $reader);
	try {
		$description = $reader->describe($projectId, null);
		foreach ($description['available_scopes'] as $scope) $recalculator->recalculate($projectId, null, (string) $scope, $actorId);
		$stageDescription = $reader->describe($projectId, $stageId);
		foreach ($stageDescription['available_scopes'] as $scope) $recalculator->recalculate($projectId, $stageId, (string) $scope, $actorId);
	} catch (Throwable $exception) {
		fwrite(STDERR, 'Standings skipped for ' . $code . ': ' . $exception->getMessage() . PHP_EOL);
	}
	$summary[$code] = ['project_id' => $projectId, 'sport_type_id' => $sportTypeId, 'entries' => count($entries), 'matches' => count($matches), 'completed' => $completed, 'catalogue' => $counts, 'generation' => $generation];
	echo sprintf("%-14s project=%d entries=%d matches=%d completed=%d\n", $code, $projectId, count($entries), count($matches), $completed);
}

file_put_contents(JPATH_ROOT . '/tmp/joomleague-demo-population.json', json_encode($summary, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR));
echo "Demo population completed.\n";
