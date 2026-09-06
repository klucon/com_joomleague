<?php

declare(strict_types=1);

define('_JEXEC', 1);
define('JPATH_BASE', '/var/www/html');
require_once JPATH_BASE . '/includes/defines.php';
require_once JPATH_BASE . '/includes/framework.php';
foreach (['UuidFactory.php', 'EntryModelValidator.php', 'MatchLineupRepository.php'] as $service) require_once JPATH_ADMINISTRATOR . '/components/com_joomleague/src/Service/' . $service;

use Joomla\CMS\Factory;
use Joomla\Database\DatabaseInterface;
use Joomleague\Component\Joomleague\Administrator\Service\MatchLineupRepository;
use Joomleague\Component\Joomleague\Domain\Service\UuidFactory;

$container = Factory::getContainer();
$container->alias('session', 'session.cli')->alias('JSession', 'session.cli')
	->alias(Joomla\CMS\Session\Session::class, 'session.cli')->alias(Joomla\Session\Session::class, 'session.cli')
	->alias(Joomla\Session\SessionInterface::class, 'session.cli');
Factory::$application = $container->get(Joomla\Console\Application::class);
$db = $container->get(DatabaseInterface::class);
$lineups = new MatchLineupRepository($db, 'Europe/Prague');
$now = gmdate('Y-m-d H:i:s');

$insert = static function (string $table, array $values) use ($db): int {
	$query = $db->getQuery(true)->insert($db->quoteName($table))->columns($db->quoteName(array_keys($values)));
	$holders = [];
	foreach ($values as $key => &$value) { $holders[] = ':' . $key; $query->bind(':' . $key, $value); }
	$query->values(implode(',', $holders)); $db->setQuery($query)->execute();
	return (int) $db->insertid();
};

$firstNames = ['Ariel', 'Dakota', 'Emery', 'Finley', 'Hayden', 'Jules', 'Kendall', 'Lane'];
$lastNames = ['North', 'Vale', 'Summers', 'Quill', 'Meadow', 'Hollis', 'Winter', 'Gray'];
$entries = $db->setQuery($db->getQuery(true)
	->select(['entry.id', 'entry.project_id', 'team.club_id', 'version.payload_json'])
	->from($db->quoteName('#__joomleague_project_entry', 'entry'))
	->innerJoin($db->quoteName('#__joomleague_team', 'team') . ' ON team.id=entry.team_id')
	->innerJoin($db->quoteName('#__joomleague_project', 'project') . ' ON project.id=entry.project_id')
	->innerJoin($db->quoteName('#__joomleague_sport_profile_version', 'version') . ' ON version.id=project.profile_version_id')
	->where("entry.entry_kind='team'")->order('entry.id ASC'))->loadObjectList();

$createdStaff = 0; $assignedStaff = 0; $substitutions = 0;
foreach ($entries as $entryIndex => $entry) {
	$profile = json_decode((string) $entry->payload_json, true, 512, JSON_THROW_ON_ERROR);
	if (!in_array('staff', $profile['entry_model']['member_person_types'] ?? [], true)) continue;
	$roles = array_values(array_filter($profile['positions'] ?? [], static fn(array $position): bool => ($position['person_type'] ?? '') === 'staff'));
	if ($roles === []) continue;
	$staffIds = array_map('intval', $db->setQuery($db->getQuery(true)->select('id')->from($db->quoteName('#__joomleague_project_entry_member'))
		->where('entry_id=' . (int) $entry->id)->where("member_person_type='staff'")->order('id ASC'))->loadColumn());
	if ($staffIds === []) {
		foreach (array_slice($roles, 0, 2) as $roleIndex => $role) {
			$personId = $insert('#__joomleague_person', [
				'uuid' => UuidFactory::v4(), 'club_id' => (int) $entry->club_id,
				'first_name' => $firstNames[($entryIndex + $roleIndex) % count($firstNames)],
				'last_name' => $lastNames[($entryIndex * 2 + $roleIndex) % count($lastNames)] . ' Staff ' . $entry->id,
				'alias' => 'fictional-staff-' . $entry->id . '-' . ($roleIndex + 1), 'country_code' => 'GB',
				'birth_date' => (1975 + (($entryIndex + $roleIndex) % 20)) . '-06-15',
				'description' => 'A fictional staff member demonstrating profile-defined team personnel.',
				'metadata_json' => '{"demo":true}', 'published' => 1, 'access' => 1, 'created' => $now, 'created_by' => 0,
			]);
			$staffIds[] = $insert('#__joomleague_project_entry_member', [
				'uuid' => UuidFactory::v4(), 'entry_id' => (int) $entry->id, 'person_id' => $personId,
				'member_person_type' => 'staff', 'role_code' => (string) $role['code'], 'is_captain' => 0,
				'valid_from' => '2035-08-01', 'valid_until' => '2036-06-30', 'lifecycle_state' => 'active',
				'notes' => 'Fictional active staff assignment.', 'published' => 1, 'ordering' => 100 + $roleIndex,
				'created' => $now, 'created_by' => 0,
			]);
			$createdStaff++;
		}
	}

	$participants = $db->setQuery($db->getQuery(true)->select(['participant.id', 'participant.match_id'])
		->from($db->quoteName('#__joomleague_match_participant', 'participant'))
		->where('participant.project_entry_id=' . (int) $entry->id)->order('participant.id ASC'))->loadObjectList();
	foreach ($participants as $participant) {
		$existing = (int) $db->setQuery($db->getQuery(true)->select('COUNT(*)')->from($db->quoteName('#__joomleague_match_lineup_member'))
			->where('match_participant_id=' . (int) $participant->id)->where("member_person_type='staff'"))->loadResult();
		if ($existing === 0) foreach ($staffIds as $staffId) {
			$lineups->assign((int) $participant->match_id, (int) $participant->id, $staffId, 'active', false, 0);
			$assignedStaff++;
		}
	}
}

$completedParticipants = $db->setQuery($db->getQuery(true)->select(['participant.id', 'participant.match_id', 'version.payload_json'])
	->from($db->quoteName('#__joomleague_match_participant', 'participant'))
	->innerJoin($db->quoteName('#__joomleague_match_result', 'result') . ' ON result.match_id=participant.match_id')
	->innerJoin($db->quoteName('#__joomleague_project_match', 'match') . ' ON match.id=participant.match_id')
	->innerJoin($db->quoteName('#__joomleague_project', 'project') . ' ON project.id=match.project_id')
	->innerJoin($db->quoteName('#__joomleague_sport_profile_version', 'version') . ' ON version.id=project.profile_version_id')
	->order('participant.id ASC'))->loadObjectList();
foreach ($completedParticipants as $participant) {
	$profile = json_decode((string) $participant->payload_json, true, 512, JSON_THROW_ON_ERROR);
	$supported = ($profile['lineup']['substitutions']['supported'] ?? $profile['lineup']['substitutions_supported'] ?? false) === true;
	if (!$supported || $lineups->getSubstitutions((int) $participant->match_id, (int) $participant->id) !== []) continue;
	$members = $db->setQuery($db->getQuery(true)->select(['id', 'lineup_status'])->from($db->quoteName('#__joomleague_match_lineup_member'))
		->where('match_participant_id=' . (int) $participant->id)->where("member_person_type='player'")->order('ordering ASC,id ASC'))->loadObjectList();
	$outgoing = null; $incoming = null;
	foreach ($members as $member) { if ($member->lineup_status === 'starter' && $outgoing === null) $outgoing = (int) $member->id; if ($member->lineup_status === 'substitute' && $incoming === null) $incoming = (int) $member->id; }
	if ($outgoing && $incoming) {
		$segmentScoped = ($profile['lineup']['substitutions']['limit_scope'] ?? 'match') === 'segment';
		$phaseCode = $segmentScoped ? (string) ($profile['match']['score']['segment_types'][0]['code'] ?? '') : null;
		if ($segmentScoped && $phaseCode === '') continue;
		$lineups->addSubstitution((int) $participant->match_id, (int) $participant->id, $outgoing, $incoming,
			$phaseCode, $segmentScoped ? 1 : null, null, null, 'Fictional substitution demonstrating the profile workflow.', 0);
		$substitutions++;
	}
}

echo "Roster enrichment: {$createdStaff} staff created, {$assignedStaff} match assignments, {$substitutions} substitutions.\n";
