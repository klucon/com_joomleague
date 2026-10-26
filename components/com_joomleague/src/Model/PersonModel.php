<?php

declare(strict_types=1);

/**
 * @package     Joomleague.Site
 * @subpackage  com_joomleague
 *
 * @copyright   (C) 2026 Ondrej Klucka (https://klucon.cz). All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomleague\Component\Joomleague\Site\Model;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;
use Joomleague\Component\Joomleague\Domain\Service\StandingsDecimal;
use Joomleague\Component\Joomleague\Domain\Service\PersonMembershipPeriod;

final class PersonModel extends BaseDatabaseModel
{
	protected function populateState($ordering = null, $direction = null): void
	{
		$input = Factory::getApplication()->getInput();
		$this->setState('person_id', $input->getInt('person_id', 0));
		$this->setState('filter.project_id', $input->getInt('filter_project_id', 0));
		$this->setState('filter.role_code', $input->getCmd('filter_role_code', ''));
		$this->setState('filter.opponent_club_id', $input->getInt('filter_opponent_club_id', 0));
		$this->setState('filter.team_id', max(0, $input->getInt('filter_team_id', 0)));
		$this->setState('carom.project_id', $input->getInt('carom_project_id', 0));
		$this->setState('carom.season_id', $input->getInt('carom_season_id', 0));
		$this->setState('carom.start', max(0, $input->getInt('carom_start', 0)));
		$this->setState('player.project_id', max(0, $input->getInt('player_project_id', $input->getInt('project_id', 0))));
		$this->setState('player.start', max(0, $input->getInt('player_limitstart', $input->getInt('player_start', 0))));
		$playerLimit = $input->getInt('player_limit', 5);
		$this->setState('player.limit', in_array($playerLimit, [5, 10, 25], true) ? $playerLimit : 5);
	}

	/** @return array<string,mixed> */
	public function getPerson(): array
	{
		$personId = (int) $this->getState('person_id');

		if ($personId < 1) {
			return ['error' => 'COM_JOOMLEAGUE_PERSON_NOT_CONFIGURED'];
		}

		$db = Factory::getContainer()->get(DatabaseInterface::class);
		$person = $db->setQuery(
			$db->getQuery(true)
				->select([
					'person.id', 'person.first_name', 'person.last_name', 'person.nickname', 'person.country_code',
					'person.birth_date', 'person.death_date', 'person.height_cm', 'person.weight_kg',
					'person.picture', 'person.description', $db->quoteName('club.id', 'club_id'), $db->quoteName('club.name', 'club_name'),
				])
				->from($db->quoteName('#__joomleague_person', 'person'))
				->leftJoin($db->quoteName('#__joomleague_club', 'club') . ' ON club.id = person.club_id AND club.published = 1 AND ' . \Joomleague\Component\Joomleague\Site\Service\PublicAccess::condition($db, 'club'))
				->where('person.id = :personId')
				->where('person.published = 1')
				->where(\Joomleague\Component\Joomleague\Site\Service\PublicAccess::condition($db, 'person'))
				->bind(':personId', $personId, ParameterType::INTEGER)
		)->loadObject();

		if (!$person) {
			return ['error' => 'COM_JOOMLEAGUE_PERSON_UNAVAILABLE'];
		}

		$today = Factory::getDate()->format('Y-m-d');
		$memberships = $db->setQuery(
			$db->getQuery(true)
				->select([
					'member.member_person_type', 'member.role_code', 'member.shirt_number', 'member.is_captain',
					'member.valid_from', 'member.valid_until', 'member.lifecycle_state',
					$db->quoteName('entry.id', 'entry_id'), $db->quoteName('project.id', 'project_id'),
					$db->quoteName('project.name', 'project_name'), $db->quoteName('sport_type.name', 'sport_type_name'),
					$db->quoteName('season.name', 'season_name'),
					$db->quoteName('season.id', 'season_id'), $db->quoteName('season.ordering', 'season_ordering'),
					$db->quoteName('membership_profile.name_key', 'profile_name_key'),
					$db->quoteName('competition.name', 'competition_name'),
					$db->quoteName('membership_club.id', 'membership_club_id'), $db->quoteName('membership_club.name', 'membership_club_name'),
					$db->quoteName('project.start_date', 'project_start_date'), $db->quoteName('project.end_date', 'project_end_date'),
					$db->quoteName('project.lifecycle_state', 'project_lifecycle_state'),
					$db->quoteName('season.start_date', 'season_start_date'), $db->quoteName('season.end_date', 'season_end_date'),
					$db->quoteName('position.name', 'role_name'), $db->quoteName('position.name_key', 'role_name_key'),
					'COALESCE(NULLIF(' . $db->quoteName('entry.display_name') . ", ''), "
						. $db->quoteName('team.name') . ', NULLIF(TRIM(CONCAT('
						. $db->quoteName('entry_person.first_name') . ", ' ', " . $db->quoteName('entry_person.last_name')
						. ")), ''), CONCAT('ID ', " . $db->quoteName('entry.id') . ')) AS ' . $db->quoteName('entry_name'),
				])
				->from($db->quoteName('#__joomleague_project_entry_member', 'member'))
				->innerJoin($db->quoteName('#__joomleague_project_entry', 'entry') . ' ON entry.id = member.entry_id AND entry.published = 1')
				->innerJoin($db->quoteName('#__joomleague_project', 'project') . ' ON project.id = entry.project_id AND project.published = 1')
				->innerJoin($db->quoteName('#__joomleague_competition', 'competition') . ' ON competition.id = project.competition_id AND competition.published = 1')
				->innerJoin($db->quoteName('#__joomleague_season', 'season') . ' ON season.id = project.season_id AND season.published = 1')
				->innerJoin($db->quoteName('#__joomleague_sport_type', 'sport_type') . ' ON sport_type.id = project.sport_type_id AND sport_type.published = 1')
				->leftJoin($db->quoteName('#__joomleague_sport_position', 'position') . ' ON position.sport_type_id = project.sport_type_id AND position.code = member.role_code AND position.published = 1')
				->leftJoin($db->quoteName('#__joomleague_team', 'team') . ' ON team.id = entry.team_id AND team.published = 1')
				->leftJoin($db->quoteName('#__joomleague_person', 'entry_person') . ' ON entry_person.id = entry.person_id AND entry_person.published = 1')
				->leftJoin($db->quoteName('#__joomleague_sport_profile_version', 'membership_version') . ' ON membership_version.id = project.profile_version_id')
				->leftJoin($db->quoteName('#__joomleague_sport_profile', 'membership_profile') . ' ON membership_profile.id = membership_version.profile_id')
				->leftJoin($db->quoteName('#__joomleague_club', 'membership_club') . ' ON membership_club.id = COALESCE(team.club_id, entry_person.club_id) AND membership_club.published = 1 AND ' . \Joomleague\Component\Joomleague\Site\Service\PublicAccess::condition($db, 'membership_club'))
				->where('member.person_id = :personId')
				->where('member.published = 1')
				->where(\Joomleague\Component\Joomleague\Site\Service\PublicAccess::condition($db, 'project'))
				->where(\Joomleague\Component\Joomleague\Site\Service\PublicAccess::condition($db, 'competition'))
				->where(\Joomleague\Component\Joomleague\Site\Service\PublicAccess::condition($db, 'season'))
				->where("(entry.entry_kind = 'group' OR (entry.entry_kind = 'team' AND team.id IS NOT NULL) OR (entry.entry_kind = 'person' AND entry_person.id IS NOT NULL))")
				->bind(':personId', $personId, ParameterType::INTEGER)
				->order('season.name DESC, season.id DESC, project.start_date DESC, project.id DESC, entry.ordering ASC, entry.id ASC, member.id ASC')
		)->loadObjectList();

		$currentMemberships = [];
		$membershipHistory = [];
		$upcomingMemberships = [];
		$undatedMemberships = [];
		$membershipToday = Factory::getDate('now', (string) Factory::getApplication()->get('offset', 'UTC'))->format('Y-m-d', true);

		foreach ($memberships as $membership) {
			switch (PersonMembershipPeriod::classify($membership, $membershipToday)) {
				case 'current': $currentMemberships[] = $membership; break;
				case 'history': $membershipHistory[] = $membership; break;
				case 'upcoming': $upcomingMemberships[] = $membership; break;
				default: $undatedMemberships[] = $membership;
			}
		}

		$competitionRoles = $db->setQuery(
			$db->getQuery(true)
				->select([
					'role.id', 'role.role_code', 'role.role_domain', 'role.valid_from', 'role.valid_until',
					'role.lifecycle_state', 'role.notes', 'project.id AS project_id', 'project.name AS project_name',
					'competition.name AS competition_name', 'season.name AS season_name', 'sport_type.name AS sport_type_name',
					'position.name AS role_name', 'position.name_key AS role_name_key',
				])
				->from($db->quoteName('#__joomleague_project_actor_role', 'role'))
				->innerJoin($db->quoteName('#__joomleague_project', 'project') . ' ON project.id = role.project_id AND project.published = 1')
				->innerJoin($db->quoteName('#__joomleague_competition', 'competition') . ' ON competition.id = project.competition_id AND competition.published = 1')
				->innerJoin($db->quoteName('#__joomleague_season', 'season') . ' ON season.id = project.season_id AND season.published = 1')
				->innerJoin($db->quoteName('#__joomleague_sport_type', 'sport_type') . ' ON sport_type.id = project.sport_type_id AND sport_type.published = 1')
				->leftJoin($db->quoteName('#__joomleague_sport_position', 'position') . ' ON position.sport_type_id = project.sport_type_id AND position.code = role.role_code AND position.published = 1')
				->where('role.person_id = :rolePersonId')->where("role.actor_kind = 'person'")->where('role.published = 1')
				->where(\Joomleague\Component\Joomleague\Site\Service\PublicAccess::condition($db, 'project'))
				->where(\Joomleague\Component\Joomleague\Site\Service\PublicAccess::condition($db, 'competition'))
				->where(\Joomleague\Component\Joomleague\Site\Service\PublicAccess::condition($db, 'season'))
				->bind(':rolePersonId', $personId, ParameterType::INTEGER)
				->order('role.valid_from DESC, project.name ASC, role.ordering ASC, role.id ASC')
		)->loadObjectList();

		$currentCompetitionRoles = [];
		$competitionRoleHistory = [];
		foreach ($competitionRoles as $role) {
			$isCurrent = ($role->valid_from === null || $role->valid_from <= $today)
				&& ($role->valid_until === null || $role->valid_until >= $today)
				&& (string) $role->lifecycle_state === 'active';
			if ($isCurrent) {
				$currentCompetitionRoles[] = $role;
			} else {
				$competitionRoleHistory[] = $role;
			}
		}

		$eventAssignments = $db->setQuery(
			$db->getQuery(true)
				->select([
					'assignment.id', 'assignment.role_code', 'assignment.role_domain', 'assignment.delegation_status',
					'assignment.display_name_snapshot', 'assignment.notes', 'assignment.published',
					'match.id AS match_id', 'match.match_number', 'match.scheduled_start', 'match.timezone', 'result.status_code AS result_status',
					'round.name AS round_name', 'project.id AS project_id', 'project.name AS project_name',
					'competition.name AS competition_name', 'season.name AS season_name', 'sport_type.name AS sport_type_name',
					'position.name AS role_name', 'position.name_key AS role_name_key',
				])
				->from($db->quoteName('#__joomleague_match_actor_role', 'assignment'))
				->innerJoin($db->quoteName('#__joomleague_project_match', 'match') . ' ON match.id = assignment.match_id AND match.published = 1')
				->innerJoin($db->quoteName('#__joomleague_project_round', 'round') . ' ON round.id = match.round_id AND round.published = 1')
				->innerJoin($db->quoteName('#__joomleague_project_stage', 'stage') . ' ON stage.id = match.stage_id AND stage.project_id = match.project_id AND stage.published = 1')
				->innerJoin($db->quoteName('#__joomleague_project', 'project') . ' ON project.id = assignment.project_id AND project.published = 1')
				->innerJoin($db->quoteName('#__joomleague_competition', 'competition') . ' ON competition.id = project.competition_id AND competition.published = 1')
				->innerJoin($db->quoteName('#__joomleague_season', 'season') . ' ON season.id = project.season_id AND season.published = 1')
				->innerJoin($db->quoteName('#__joomleague_sport_type', 'sport_type') . ' ON sport_type.id = project.sport_type_id AND sport_type.published = 1')
				->leftJoin($db->quoteName('#__joomleague_sport_position', 'position') . ' ON position.sport_type_id = project.sport_type_id AND position.code = assignment.role_code AND position.published = 1')
				->leftJoin($db->quoteName('#__joomleague_match_result', 'result') . ' ON result.match_id = match.id')
				->where('assignment.person_id = :assignmentPersonId')->where("assignment.actor_kind = 'person'")
				->where(\Joomleague\Component\Joomleague\Site\Service\PublicAccess::condition($db, 'project'))
				->where(\Joomleague\Component\Joomleague\Site\Service\PublicAccess::condition($db, 'competition'))
				->where(\Joomleague\Component\Joomleague\Site\Service\PublicAccess::condition($db, 'season'))
				->bind(':assignmentPersonId', $personId, ParameterType::INTEGER)
				->order('match.scheduled_start DESC, match.id DESC, assignment.ordering ASC, assignment.id ASC')
		)->loadObjectList();

		$assignmentIds = array_map(static fn (object $row): int => (int) $row->id, $eventAssignments);
		$roleChanges = [];
		if ($assignmentIds !== []) {
			$idList = implode(',', array_map('intval', $assignmentIds));
			$roleChanges = $db->setQuery(
				$db->getQuery(true)
					->select([
						'role_change.id', 'role_change.match_id', 'role_change.role_code', 'role_change.role_domain', 'role_change.sequence_number',
						'role_change.phase_code', 'role_change.phase_sequence', 'role_change.clock_value', 'role_change.clock_unit',
						'role_change.reason_code', 'role_change.notes', 'role_change.outgoing_match_actor_role_id', 'role_change.incoming_match_actor_role_id',
						'outgoing.display_name_snapshot AS outgoing_name', 'incoming.display_name_snapshot AS incoming_name',
						'position.name AS role_name', 'position.name_key AS role_name_key',
					])
					->from($db->quoteName('#__joomleague_match_actor_role_change', 'role_change'))
					->leftJoin($db->quoteName('#__joomleague_match_actor_role', 'outgoing') . ' ON outgoing.id = role_change.outgoing_match_actor_role_id')
					->innerJoin($db->quoteName('#__joomleague_match_actor_role', 'incoming') . ' ON incoming.id = role_change.incoming_match_actor_role_id')
					->innerJoin($db->quoteName('#__joomleague_project_match', 'match') . ' ON match.id = role_change.match_id')
					->innerJoin($db->quoteName('#__joomleague_project', 'project') . ' ON project.id = match.project_id')
					->leftJoin($db->quoteName('#__joomleague_sport_position', 'position') . ' ON position.sport_type_id = project.sport_type_id AND position.code = role_change.role_code AND position.published = 1')
					->where('(role_change.outgoing_match_actor_role_id IN (' . $idList . ') OR role_change.incoming_match_actor_role_id IN (' . $idList . '))')
					->order('role_change.match_id DESC, role_change.sequence_number ASC, role_change.id ASC')
			)->loadObjectList();
		}

		$changesByMatch = [];
		foreach ($roleChanges as $change) $changesByMatch[(int) $change->match_id][] = $change;

		$officiatedMatches = [];
		$filterProjects = [];
		$filterRoles = [];
		foreach ($eventAssignments as $assignment) {
			if ((string) $assignment->role_domain !== 'adjudicator' || (int) $assignment->published !== 1) continue;
			$officiatedMatches[(int) $assignment->match_id] = $assignment;
			$filterProjects[(int) $assignment->project_id] = (string) $assignment->project_name;
			$filterRoles[(string) $assignment->role_code] = ['name' => (string) ($assignment->role_name ?? ''), 'name_key' => (string) ($assignment->role_name_key ?? '')];
		}
		$officiatingByProject = [];
		$siteClubId = (int) $this->getState('site_club_id', ComponentHelper::getParams('com_joomleague')->get('site_club_id', 0));
		$teamContext = $this->officiatingTeamContext($db, array_keys($officiatedMatches), $siteClubId);
		$teamFilter = max(0, (int) $this->getState('filter.team_id', 0));
		$siteClubStats = $this->loadOfficiatedSiteClubStats($db, array_keys($officiatedMatches), $siteClubId, $teamFilter);
		$filterOpponents = [];
		foreach (($siteClubStats['all']['opponents'] ?? []) as $opponent) $filterOpponents[(int) $opponent['club_id']] = (string) $opponent['club_name'];
		$projectFilter = (int) $this->getState('filter.project_id', 0);
		$roleFilter = (string) $this->getState('filter.role_code', '');
		$opponentFilter = (int) $this->getState('filter.opponent_club_id', 0);
		$opponentMatchIds = [];
		if ($opponentFilter > 0) foreach (($siteClubStats['all']['matches'] ?? []) as $match) if ((int) $match['opponent_club_id'] === $opponentFilter) $opponentMatchIds[(int) $match['match_id']] = true;
		$officiatedMatches = array_filter($officiatedMatches, static fn (object $assignment, int $matchId): bool =>
			($projectFilter < 1 || (int) $assignment->project_id === $projectFilter)
			&& ($teamFilter < 1 || isset($teamContext['matches'][$matchId][$teamFilter]))
			&& ($roleFilter === '' || (string) $assignment->role_code === $roleFilter)
			&& ($opponentFilter < 1 || isset($opponentMatchIds[$matchId])), ARRAY_FILTER_USE_BOTH);
		$siteClubStats = $this->loadOfficiatedSiteClubStats($db, array_keys($officiatedMatches), $siteClubId, $teamFilter);
		$roleBreakdown = [];
		$careerProjects = [];
		$careerSeasons = [];
		$careerDates = [];
		foreach ($officiatedMatches as $assignment) {
			$projectId = (int) $assignment->project_id;
			$roleCode = (string) $assignment->role_code;
			$roleBreakdown[$roleCode] ??= ['role_code' => $roleCode, 'role_name' => (string) ($assignment->role_name ?? ''), 'role_name_key' => (string) ($assignment->role_name_key ?? ''), 'count' => 0];
			$roleBreakdown[$roleCode]['count']++;
			$careerProjects[$projectId] = true;
			$careerSeasons[(string) $assignment->season_name] = true;
			if ($assignment->scheduled_start) $careerDates[] = (string) $assignment->scheduled_start;
			$officiatingByProject[$projectId] ??= [
				'project_id' => $projectId, 'project_name' => (string) $assignment->project_name,
				'competition_name' => (string) $assignment->competition_name, 'season_name' => (string) $assignment->season_name,
				'total' => 0, 'completed' => 0, 'upcoming' => 0,
			];
			$officiatingByProject[$projectId]['total']++;
			$officiatingByProject[$projectId][$assignment->result_status === 'final' ? 'completed' : 'upcoming']++;
		}
		$officiatingByProject = array_values($officiatingByProject);
		usort($officiatingByProject, static fn (array $left, array $right): int => strnatcasecmp($left['project_name'], $right['project_name']));
		foreach ($officiatingByProject as &$projectSummary) {
			$projectSummary['site_club_stats'] = $siteClubStats['projects'][$projectSummary['project_id']] ?? null;
			$projectSummary['assignments'] = array_values(array_filter(
				$officiatedMatches,
				static fn (object $assignment): bool => (int) $assignment->project_id === $projectSummary['project_id']
			));
		}
		unset($projectSummary);
		$roleBreakdown = array_values($roleBreakdown);
		usort($roleBreakdown, static fn (array $left, array $right): int => $right['count'] <=> $left['count'] ?: strnatcasecmp($left['role_code'], $right['role_code']));
		sort($careerDates, SORT_STRING);

		return [
			'person' => $person,
			'player_history' => $this->playerHistory($db, (int) $person->id),
			'carom' => $this->caromHistory($db, (int) $person->id),
			'memberships' => $currentMemberships,
			'membership_history' => $membershipHistory,
			'career_memberships' => $memberships,
			'membership_upcoming' => $upcomingMemberships,
			'membership_undated' => $undatedMemberships,
			'competition_roles' => $currentCompetitionRoles,
			'competition_role_history' => $competitionRoleHistory,
			'event_assignments' => $eventAssignments,
			'role_changes_by_match' => $changesByMatch,
			'officiating_summary' => [
				'total' => count($officiatedMatches),
				'completed' => array_sum(array_column($officiatingByProject, 'completed')),
				'project_count' => count($careerProjects),
				'season_count' => count($careerSeasons),
				'first_assignment' => $careerDates[0] ?? null,
				'last_assignment' => $careerDates !== [] ? $careerDates[array_key_last($careerDates)] : null,
				'roles' => $roleBreakdown,
				'projects' => $officiatingByProject,
				'site_club' => $siteClubStats['club'],
				'team' => $teamFilter > 0 && isset($teamContext['teams'][$teamFilter]) ? ['id'=>$teamFilter,'name'=>$teamContext['teams'][$teamFilter]] : null,
				'site_club_stats' => $siteClubStats['all'],
				'filters' => [
					'project_id' => $projectFilter, 'role_code' => $roleFilter, 'opponent_club_id' => $opponentFilter,
					'team_id' => $teamFilter, 'teams' => $teamContext['teams'], 'site_club_id' => $siteClubId,
					'projects' => $filterProjects, 'roles' => $filterRoles, 'opponents' => $filterOpponents,
				],
			],
		];
	}

	private function playerHistory(DatabaseInterface $db, int $personId): array
	{
		$data = (new \Joomleague\Component\Joomleague\Site\Service\PlayerHistoryReader($db))->forPerson($personId, Factory::getApplication()->getIdentity()->getAuthorisedViewLevels());
		$projectId = (int) $this->getState('player.project_id', 0);
		$data['profiles'] = \Joomleague\Component\Joomleague\Site\Service\PlayerHistoryReader::profilesForProject($data['profiles'], $projectId);
		$history = array_values(array_filter($data['history'], static fn(array $row): bool => $projectId === 0 || $row['project_id'] === $projectId));
		$total = count($history); $limit = (int) $this->getState('player.limit', 5);
		if (!in_array($limit, [5, 10, 25], true)) $limit = 5;
		$start = min(intdiv((int) $this->getState('player.start', 0), $limit) * $limit, intdiv(max(0, $total - 1), $limit) * $limit);
		$pagination = new \Joomla\CMS\Pagination\Pagination($total, $start, $limit, 'player_');
		foreach (['option' => 'com_joomleague', 'view' => 'person', 'person_id' => $personId, 'player_project_id' => $projectId, 'player_limit' => $limit] as $key => $value) $pagination->setAdditionalUrlParam($key, $value);
		return ['available' => $data['history'] !== [], 'history' => array_slice($history, $start, $limit), 'profiles' => $data['profiles'], 'projects' => $data['projects'], 'project_id' => $projectId, 'total' => $total, 'start' => $start, 'limit' => $limit, 'pagination' => $pagination];
	}

	private function caromHistory(DatabaseInterface $db, int $personId): array
	{
		$data = (new \Joomleague\Component\Joomleague\Site\Service\CaromPlayerHistoryReader($db))->forPerson(
			$personId, Factory::getApplication()->getIdentity()->getAuthorisedViewLevels()
		);
		$projects = []; $seasons = [];
		foreach ($data['projects'] as $summary) {
			$projects[$summary['project_id']] = $summary['project_name'];
			$seasons[$summary['season_id']] = $summary['season_name'];
		}
		$projectId = (int) $this->getState('carom.project_id', 0);
		$seasonId = (int) $this->getState('carom.season_id', 0);
		$matchesFilter = static fn (array $row): bool => ($projectId < 1 || $row['project_id'] === $projectId)
			&& ($seasonId < 1 || $row['season_id'] === $seasonId);
		$history = array_values(array_filter($data['history'], $matchesFilter));
		$total = count($history); $limit = 20;
		$start = min((int) $this->getState('carom.start', 0), max(0, (int) (ceil($total / $limit) - 1) * $limit));
		return [
			'available' => $data['projects'] !== [], 'projects' => array_values(array_filter($data['projects'], $matchesFilter)),
			'history' => array_slice($history, $start, $limit), 'total' => $total, 'start' => $start, 'limit' => $limit,
			'filters' => ['project_id' => $projectId, 'season_id' => $seasonId, 'projects' => $projects, 'seasons' => $seasons],
		];
	}

	/** @param list<int> $matchIds @return array{teams:array<int,string>,matches:array<int,array<int,true>>} */
	private function officiatingTeamContext(DatabaseInterface $db, array $matchIds, int $siteClubId): array
	{
		$context = ['teams'=>[], 'matches'=>[]];
		if ($matchIds === []) return $context;
		$rows = $db->setQuery($db->getQuery(true)
			->select(['participant.match_id', 'team.id', 'team.name', 'team.club_id'])
			->from($db->quoteName('#__joomleague_match_participant', 'participant'))
			->innerJoin($db->quoteName('#__joomleague_project_match', 'match') . ' ON match.id=participant.match_id')
			->innerJoin($db->quoteName('#__joomleague_project_entry', 'entry') . " ON entry.id=participant.project_entry_id AND entry.project_id=match.project_id AND entry.entry_kind='team' AND entry.published=1")
			->innerJoin($db->quoteName('#__joomleague_team', 'team') . ' ON team.id=entry.team_id AND team.published=1')
			->innerJoin($db->quoteName('#__joomleague_club', 'club') . ' ON club.id=team.club_id AND club.published=1')
			->whereIn('participant.match_id', array_map('intval', $matchIds), ParameterType::INTEGER)
			->where('participant.published=1')
			->where('(SELECT COUNT(*) FROM ' . $db->quoteName('#__joomleague_match_participant') . ' visible_count WHERE visible_count.match_id=match.id AND visible_count.published=1)=2')
			->where(\Joomleague\Component\Joomleague\Site\Service\PublicAccess::condition($db, 'team'))
			->where(\Joomleague\Component\Joomleague\Site\Service\PublicAccess::condition($db, 'club'))
			->order('participant.match_id ASC, team.name ASC, team.id ASC'))->loadObjectList();
		$byMatch = [];
		foreach ($rows as $row) $byMatch[(int) $row->match_id][] = $row;
		foreach ($byMatch as $id=>$participants) {
			if (count($participants) !== 2) continue;
			foreach ($participants as $participant) {
				if ($siteClubId > 0 && (int) $participant->club_id !== $siteClubId) continue;
				$teamId = (int) $participant->id;
				$context['teams'][$teamId] = (string) $participant->name;
				$context['matches'][$id][$teamId] = true;
			}
		}
		natcasesort($context['teams']);
		return $context;
	}

	/**
	 * Build the configured site club's W/D/L balance for final two-team events.
	 *
	 * @param list<int> $matchIds
	 * @return array{club:?array{id:int,name:string},projects:array<int,array<string,mixed>>,all:?array<string,mixed>}
	 */
	private function loadOfficiatedSiteClubStats(DatabaseInterface $db, array $matchIds, int $siteClubId, int $teamFilter = 0): array
	{
		if ($matchIds === [] || $siteClubId < 1) {
			return ['club' => null, 'projects' => [], 'all' => null];
		}

		$rows = $db->setQuery(
			$db->getQuery(true)
				->select([
					'match.id AS match_id', 'match.project_id', 'participant.id AS participant_id',
					'entry.team_id', 'team.name AS team_name', 'team.club_id', 'club.name AS club_name',
					'value.numeric_value', 'value.result_rank',
				])
				->from($db->quoteName('#__joomleague_project_match', 'match'))
				->innerJoin($db->quoteName('#__joomleague_match_result', 'result') . " ON result.match_id = match.id AND result.status_code = 'final' AND match.status_code <> 'cancelled'")
				->innerJoin($db->quoteName('#__joomleague_match_score_segment', 'segment') . " ON segment.match_id = match.id AND segment.level_code = 'result'")
				->innerJoin($db->quoteName('#__joomleague_match_score_value', 'value') . ' ON value.segment_id = segment.id AND value.match_id = match.id')
				->innerJoin($db->quoteName('#__joomleague_match_participant', 'participant') . ' ON participant.id = value.participant_id AND participant.match_id = match.id AND participant.published = 1')
				->innerJoin($db->quoteName('#__joomleague_project_entry', 'entry') . ' ON entry.id = participant.project_entry_id AND entry.project_id = match.project_id AND entry.published = 1')
				->innerJoin($db->quoteName('#__joomleague_team', 'team') . ' ON team.id = entry.team_id AND team.published = 1')
				->innerJoin($db->quoteName('#__joomleague_club', 'club') . ' ON club.id = team.club_id AND club.published = 1')
				->whereIn($db->quoteName('match.id'), array_map('intval', $matchIds), ParameterType::INTEGER)
				->where('match.published = 1')
				->where('(SELECT COUNT(*) FROM ' . $db->quoteName('#__joomleague_match_participant') . ' visible_count WHERE visible_count.match_id=match.id AND visible_count.published=1)=2')
				->where('value.numeric_value IS NOT NULL')
				->where(\Joomleague\Component\Joomleague\Site\Service\PublicAccess::condition($db, 'team'))
				->where(\Joomleague\Component\Joomleague\Site\Service\PublicAccess::condition($db, 'club'))
				->order('match.id ASC, participant.slot_number ASC, participant.id ASC')
		)->loadObjectList();

		$byMatch = [];
		foreach ($rows as $row) {
			$byMatch[(int) $row->match_id][] = $row;
		}

		$club = null;
		$projects = [];
		$all = null;
		foreach ($byMatch as $participants) {
			if (count($participants) !== 2) {
				continue;
			}
			$siteIndexes = array_keys(array_filter($participants, static fn (object $participant): bool => (int) $participant->club_id === $siteClubId && ($teamFilter < 1 || (int) $participant->team_id === $teamFilter)));
			if (count($siteIndexes) !== 1) continue;

			$siteIndex = $siteIndexes[0];
			$opponentIndex = 1 - $siteIndex;
			$firstRank = $participants[0]->result_rank !== null ? (int) $participants[0]->result_rank : null;
			$secondRank = $participants[1]->result_rank !== null ? (int) $participants[1]->result_rank : null;
			$comparison = $firstRank !== null && $secondRank !== null
				? ($firstRank === $secondRank ? 0 : ($firstRank < $secondRank ? 1 : -1))
				: StandingsDecimal::compare((string) $participants[0]->numeric_value, (string) $participants[1]->numeric_value);
			$site = $participants[$siteIndex];
			$opponent = $participants[$opponentIndex];
			$siteComparison = $siteIndex === 0 ? $comparison : -$comparison;
			$projectId = (int) $site->project_id;
			$club ??= ['id' => $siteClubId, 'name' => (string) $site->club_name];
			$this->addClubResult($projects[$projectId], $site, $opponent, $siteComparison);
			$this->addClubResult($projects[$projectId]['opponents'][(int) $opponent->team_id], $site, $opponent, $siteComparison, true);
			$this->addClubResult($all, $site, $opponent, $siteComparison);
			$this->addClubResult($all['opponents'][(int) $opponent->team_id], $site, $opponent, $siteComparison, true);
			$match = [
				'match_id' => (int) $site->match_id, 'project_id' => $projectId,
				'site_team_name' => (string) $site->team_name, 'opponent_team_name' => (string) $opponent->team_name,
				'site_team_id' => (int) $site->team_id, 'opponent_team_id' => (int) $opponent->team_id,
				'opponent_club_id' => (int) $opponent->club_id, 'opponent_club_name' => (string) $opponent->club_name,
				'scored' => StandingsDecimal::normalize((string) $site->numeric_value),
				'conceded' => StandingsDecimal::normalize((string) $opponent->numeric_value),
				'outcome' => $siteComparison > 0 ? 'win' : ($siteComparison < 0 ? 'loss' : 'draw'),
			];
			$projects[$projectId]['matches'][] = $match;
			$projects[$projectId]['opponents'][(int) $opponent->team_id]['matches'][] = $match;
			$all['matches'][] = $match;
			$all['opponents'][(int) $opponent->team_id]['matches'][] = $match;
		}

		foreach ($projects as &$projectStats) $projectStats['opponents'] = $this->sortClubStats($projectStats['opponents'] ?? []);
		unset($projectStats);
		if ($all !== null) $all['opponents'] = $this->sortClubStats($all['opponents'] ?? []);

		return ['club' => $club, 'projects' => $projects, 'all' => $all];
	}

	/** @param array<string,int|string>|null $stats */
	private function addClubResult(?array &$stats, object $team, object $opponent, int $comparison, bool $opponentRow = false): void
	{
		$stats ??= [
			'club_id' => (int) ($opponentRow ? $opponent->club_id : $team->club_id),
			'club_name' => (string) ($opponentRow ? $opponent->club_name : $team->club_name),
			'played' => 0, 'wins' => 0, 'draws' => 0, 'losses' => 0,
			'scored' => '0', 'conceded' => '0',
		];
		if ($opponentRow) {
			$stats['team_id'] = (int) $opponent->team_id;
			$stats['team_name'] = (string) $opponent->team_name;
		}
		$stats['played']++;
		$stats[$comparison > 0 ? 'wins' : ($comparison < 0 ? 'losses' : 'draws')]++;
		$stats['scored'] = StandingsDecimal::add((string) $stats['scored'], (string) $team->numeric_value);
		$stats['conceded'] = StandingsDecimal::add((string) $stats['conceded'], (string) $opponent->numeric_value);
	}

	/**
	 * @param array<int,array<string,int|string>> $stats
	 * @return list<array<string,int|string>>
	 */
	private function sortClubStats(array $stats): array
	{
		$stats = array_values($stats);
		usort($stats, static fn (array $left, array $right): int => strnatcasecmp((string) $left['club_name'], (string) $right['club_name']) ?: strnatcasecmp((string) $left['team_name'], (string) $right['team_name']) ?: $left['team_id'] <=> $right['team_id']);
		return $stats;
	}
}
