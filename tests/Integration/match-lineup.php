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

$database = Factory::getContainer()->get(DatabaseInterface::class);
$uuid = static fn (): string => UuidFactory::v4();
$insert = static function (string $table, array $data) use ($database): int {
	$query = $database->getQuery(true)->insert($database->quoteName($table))->columns($database->quoteName(array_keys($data)));
	$values = [];
	foreach ($data as $key => &$value) { $values[] = ':' . $key; $query->bind(':' . $key, $value); }
	$query->values(implode(',', $values)); $database->setQuery($query)->execute(); return (int) $database->insertid();
};
$name = 'Match lineup fixture ' . bin2hex(random_bytes(3));
$profileVersion = (int) $database->setQuery($database->getQuery(true)->select('v.id')->from($database->quoteName('#__joomleague_sport_profile_version', 'v'))->innerJoin($database->quoteName('#__joomleague_sport_profile', 'p') . ' ON p.id=v.profile_id')->where('p.code=' . $database->quote('football'))->where('v.state=' . $database->quote('active')))->loadResult();
$competition = $insert('#__joomleague_competition', ['uuid' => $uuid(), 'name' => $name]);
$season = $insert('#__joomleague_season', ['uuid' => $uuid(), 'name' => $name]);
$sport = $insert('#__joomleague_sport_type', ['profile_version_id' => $profileVersion, 'code' => 'lineup-' . bin2hex(random_bytes(3)), 'name' => $name]);
$project = $insert('#__joomleague_project', ['uuid' => $uuid(), 'competition_id' => $competition, 'season_id' => $season, 'sport_type_id' => $sport, 'profile_version_id' => $profileVersion, 'name' => $name, 'project_type' => 'league', 'timezone' => 'Europe/Prague']);
$club = $insert('#__joomleague_club', ['uuid' => $uuid(), 'name' => $name]);
$teamA = $insert('#__joomleague_team', ['uuid' => $uuid(), 'club_id' => $club, 'name' => $name . ' A']);
$teamB = $insert('#__joomleague_team', ['uuid' => $uuid(), 'club_id' => $club, 'name' => $name . ' B']);
$personA = $insert('#__joomleague_person', ['uuid' => $uuid(), 'first_name' => 'Valid', 'last_name' => $name]);
$personSubstitute = $insert('#__joomleague_person', ['uuid' => $uuid(), 'first_name' => 'Substitute', 'last_name' => $name]);
$personExpired = $insert('#__joomleague_person', ['uuid' => $uuid(), 'first_name' => 'Expired', 'last_name' => $name]);
$personForeign = $insert('#__joomleague_person', ['uuid' => $uuid(), 'first_name' => 'Foreign', 'last_name' => $name]);

try {
	$entryA = $insert('#__joomleague_project_entry', ['uuid' => $uuid(), 'project_id' => $project, 'entry_kind' => 'team', 'team_id' => $teamA]);
	$entryB = $insert('#__joomleague_project_entry', ['uuid' => $uuid(), 'project_id' => $project, 'entry_kind' => 'team', 'team_id' => $teamB]);
	$memberA = $insert('#__joomleague_project_entry_member', ['uuid' => $uuid(), 'entry_id' => $entryA, 'person_id' => $personA, 'member_person_type' => 'player', 'role_code' => 'goalkeeper', 'shirt_number' => '1', 'valid_from' => '2026-01-01']);
	$memberSubstitute = $insert('#__joomleague_project_entry_member', ['uuid' => $uuid(), 'entry_id' => $entryA, 'person_id' => $personSubstitute, 'member_person_type' => 'player', 'role_code' => 'defender', 'shirt_number' => '2', 'valid_from' => '2026-01-01']);
	$memberExpired = $insert('#__joomleague_project_entry_member', ['uuid' => $uuid(), 'entry_id' => $entryA, 'person_id' => $personExpired, 'member_person_type' => 'player', 'role_code' => 'defender', 'valid_until' => '2026-06-30']);
	$memberForeign = $insert('#__joomleague_project_entry_member', ['uuid' => $uuid(), 'entry_id' => $entryB, 'person_id' => $personForeign, 'member_person_type' => 'player', 'role_code' => 'forward']);
	$stage = $insert('#__joomleague_project_stage', ['uuid' => $uuid(), 'project_id' => $project, 'name' => 'League', 'code' => 'league', 'stage_type' => 'league']);
	$round = $insert('#__joomleague_project_round', ['uuid' => $uuid(), 'project_id' => $project, 'stage_id' => $stage, 'name' => 'Round 1', 'code' => 'round_1', 'round_type' => 'regular', 'sequence_number' => 1]);
	$match = $insert('#__joomleague_project_match', ['uuid' => $uuid(), 'project_id' => $project, 'stage_id' => $stage, 'round_id' => $round, 'match_number' => '1', 'contest_type' => 'head_to_head', 'scheduled_start' => '2026-08-15 15:00:00']);
	$participantA = $insert('#__joomleague_match_participant', ['uuid' => $uuid(), 'match_id' => $match, 'project_id' => $project, 'project_entry_id' => $entryA, 'slot_number' => 1]);
	$participantB = $insert('#__joomleague_match_participant', ['uuid' => $uuid(), 'match_id' => $match, 'project_id' => $project, 'project_entry_id' => $entryB, 'slot_number' => 2]);
	$repository = new MatchLineupRepository($database, 'UTC');
	$available = $repository->getAvailableMembers($match, $participantA);
	$availableIds = array_map(static fn (object $row): int => (int) $row->id, $available);
	sort($availableIds);
	$expectedAvailableIds = [$memberA, $memberSubstitute];
	sort($expectedAvailableIds);
	if ($availableIds !== $expectedAvailableIds) throw new RuntimeException('Match-date roster eligibility is invalid.');
	$lineupId = $repository->assign($match, $participantA, $memberA, 'starter', true, 0);
	$substituteLineupId = $repository->assign($match, $participantA, $memberSubstitute, 'substitute', false, 0);
	$assigned = $repository->getAssignedMembers($match, $participantA);
	if (count($assigned) !== 2 || (int) $assigned[0]->id !== $lineupId || $assigned[0]->shirt_number !== '1' || (int) $assigned[0]->is_captain !== 1) throw new RuntimeException('Lineup snapshot was not stored.');
	$firstChange = $repository->addSubstitution($match, $participantA, $lineupId, $substituteLineupId, 'period', 2, '12.5', 'minute', 'Tactical change', 0);
	$changes = $repository->getSubstitutions($match, $participantA);
	if (count($changes) !== 1 || (int) $changes[0]->id !== $firstChange || (int) $changes[0]->sequence_number !== 1 || (string) $changes[0]->clock_value !== '12.500000000') throw new RuntimeException('The substitution was not persisted consistently.');
	try { $repository->addSubstitution($match, $participantA, $lineupId, $substituteLineupId, null, null, null, null, null, 0); throw new RuntimeException('An inactive outgoing player was accepted.'); } catch (InvalidArgumentException) {}
	$secondChange = $repository->addSubstitution($match, $participantA, $substituteLineupId, $lineupId, 'period', 2, '28', 'minute', null, 0);
	try { $repository->removeSubstitution($match, $participantA, $firstChange); throw new RuntimeException('A substitution whose removal breaks the sequence was removed.'); } catch (InvalidArgumentException) {}
	try { $repository->removeSubstitution($match, $participantB, $secondChange); throw new RuntimeException('A foreign participant removed a substitution.'); } catch (InvalidArgumentException) {}
	$repository->removeSubstitution($match, $participantA, $secondChange);
	$changes = $repository->getSubstitutions($match, $participantA);
	if (count($changes) !== 1 || (int) $changes[0]->id !== $firstChange || (int) $changes[0]->sequence_number !== 1) throw new RuntimeException('Substitution removal or resequencing failed.');
	$repository->removeSubstitution($match, $participantA, $firstChange);
	foreach ([$memberExpired, $memberForeign] as $invalidMember) {
		try { $repository->assign($match, $participantA, $invalidMember, 'starter', false, 0); throw new RuntimeException('An unavailable or foreign roster member was accepted.'); } catch (InvalidArgumentException) {}
	}
	$query = $database->getQuery(true)->delete($database->quoteName('#__joomleague_project_entry_member'))->where('id=' . $memberA); $database->setQuery($query)->execute();
	$assigned = $repository->getAssignedMembers($match, $participantA);
	if (count($assigned) !== 2 || $assigned[0]->source_entry_member_id !== null || (int) $assigned[0]->person_id !== $personA) throw new RuntimeException('Lineup snapshot did not survive source membership removal.');
	try { $repository->remove($match, $participantB, $lineupId); throw new RuntimeException('A foreign participant removed a lineup row.'); } catch (InvalidArgumentException) {}
	$repository->remove($match, $participantA, $lineupId);
	$repository->remove($match, $participantA, $substituteLineupId);
	if ($repository->getAssignedMembers($match, $participantA) !== []) throw new RuntimeException('Owned lineup rows were not removed.');
	echo "Match lineup ownership, eligibility, snapshots and substitutions OK\n";
} finally {
	$query = $database->getQuery(true)->delete($database->quoteName('#__joomleague_project'))->where('id=' . $project); $database->setQuery($query)->execute();
	foreach ([['#__joomleague_sport_type', $sport], ['#__joomleague_competition', $competition], ['#__joomleague_season', $season], ['#__joomleague_team', $teamA], ['#__joomleague_team', $teamB], ['#__joomleague_person', $personA], ['#__joomleague_person', $personSubstitute], ['#__joomleague_person', $personExpired], ['#__joomleague_person', $personForeign], ['#__joomleague_club', $club]] as [$table, $id]) {
		$query = $database->getQuery(true)->delete($database->quoteName($table))->where('id=' . (int) $id); $database->setQuery($query)->execute();
	}
}
