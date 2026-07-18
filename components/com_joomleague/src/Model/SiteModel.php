<?php
/**
 * @package     JoomLeague
 * @copyright   Copyright (C) 2026 Ondřej Klučka (https://klucon.cz). All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */


declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Site\Model;

\defined('_JEXEC') or die;

use Joomla\CMS\Cache\CacheControllerFactoryInterface;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\CMS\Factory;
use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;

class SiteModel extends BaseDatabaseModel
{
	public function getTemplateParameters(int $projectId, string $template): array
	{
		if (!preg_match('/^[a-z0-9_]+$/', $template)) {
			return [];
		}

		if ($projectId > 0) {
			$projectParams = $this->loadTemplateParams($projectId, $template);

			if ($projectParams !== null) {
				return $projectParams;
			}
		}

		// Bez per-projektového přepsání se použije centrální (globální) nastavení šablony.
		return $this->loadTemplateParams(null, $template) ?? [];
	}

	/**
	 * @return  array<string, mixed>|null
	 */
	private function loadTemplateParams(?int $projectId, string $template): ?array
	{
		$db = $this->getDatabase();
		$query = $db->getQuery(true)
			->select($db->quoteName('params'))
			->from($db->quoteName('#__joomleague_template_config'))
			->where($db->quoteName('template') . ' = :template')
			->where($db->quoteName('published') . ' = 1')
			->order($db->quoteName('id') . ' DESC')
			->bind(':template', $template);

		if ($projectId === null) {
			$query->where($db->quoteName('project_id') . ' IS NULL');
		} else {
			$query->where($db->quoteName('project_id') . ' = :project_id')->bind(':project_id', $projectId, ParameterType::INTEGER);
		}

		$params = (string) $db->setQuery($query, 0, 1)->loadResult();

		if ($params === '' || !json_validate($params)) {
			return null;
		}

		$decoded = json_decode($params, true);

		return is_array($decoded) ? $decoded : null;
	}

	public function getProject(int $projectId = 0): ?object
	{
		$db = $this->getDatabase();
		$query = $db->getQuery(true)
			->select([
				'p.*',
				$db->quoteName('l.name', 'league_name'),
				$db->quoteName('s.name', 'season_name'),
				$db->quoteName('st.name', 'sport_name'),
			])
			->from($db->quoteName('#__joomleague_project', 'p'))
			->join('LEFT', $db->quoteName('#__joomleague_league', 'l') . ' ON ' . $db->quoteName('l.id') . ' = ' . $db->quoteName('p.league_id'))
			->join('LEFT', $db->quoteName('#__joomleague_season', 's') . ' ON ' . $db->quoteName('s.id') . ' = ' . $db->quoteName('p.season_id'))
			->join('LEFT', $db->quoteName('#__joomleague_sports_type', 'st') . ' ON ' . $db->quoteName('st.id') . ' = ' . $db->quoteName('p.sports_type_id'));

		if ($projectId > 0) {
			$query->where($db->quoteName('p.id') . ' = :project_id')
				->bind(':project_id', $projectId, ParameterType::INTEGER);
		} else {
			$query->where($db->quoteName('p.published') . ' = 1')
				->order($db->quoteName('p.id') . ' DESC');
		}

		$item = $db->setQuery($query, 0, 1)->loadObject();

		return $item ?: null;
	}

	public function getProjects(): array
	{
		$db = $this->getDatabase();
		$query = $db->getQuery(true)
			->select([
				'p.id',
				'p.name',
				'p.alias',
				'p.published',
				$db->quoteName('l.name', 'league_name'),
				$db->quoteName('s.name', 'season_name'),
				$db->quoteName('st.name', 'sport_name'),
				'COUNT(DISTINCT r.id) AS rounds',
				'COUNT(DISTINCT m.id) AS matches',
			])
			->from($db->quoteName('#__joomleague_project', 'p'))
			->join('LEFT', $db->quoteName('#__joomleague_league', 'l') . ' ON ' . $db->quoteName('l.id') . ' = ' . $db->quoteName('p.league_id'))
			->join('LEFT', $db->quoteName('#__joomleague_season', 's') . ' ON ' . $db->quoteName('s.id') . ' = ' . $db->quoteName('p.season_id'))
			->join('LEFT', $db->quoteName('#__joomleague_sports_type', 'st') . ' ON ' . $db->quoteName('st.id') . ' = ' . $db->quoteName('p.sports_type_id'))
			->join('LEFT', $db->quoteName('#__joomleague_round', 'r') . ' ON ' . $db->quoteName('r.project_id') . ' = ' . $db->quoteName('p.id'))
			->join('LEFT', $db->quoteName('#__joomleague_match', 'm') . ' ON ' . $db->quoteName('m.round_id') . ' = ' . $db->quoteName('r.id'))
			->where($db->quoteName('p.published') . ' = 1')
			->group($db->quoteName('p.id'))
			->order($db->quoteName('p.id') . ' DESC');

		return $db->setQuery($query)->loadObjectList();
	}

	public function getProjectTeams(int $projectId, int $divisionId = 0): array
	{
		$db = $this->getDatabase();
		$query = $db->getQuery(true)
			->select([
				'pt.*',
				$db->quoteName('t.name', 'team_name'),
				$db->quoteName('t.short_name', 'team_short_name'),
				$db->quoteName('t.middle_name', 'team_middle_name'),
				$db->quoteName('t.picture', 'team_picture'),
				$db->quoteName('c.id', 'club_id'),
				$db->quoteName('c.name', 'club_name'),
				$db->quoteName('c.country', 'club_country'),
				$db->quoteName('c.logo_small', 'club_logo_small'),
				$db->quoteName('c.logo_middle', 'club_logo_middle'),
				$db->quoteName('c.logo_big', 'club_logo_big'),
				$db->quoteName('d.name', 'division_name'),
			])
			->from($db->quoteName('#__joomleague_project_team', 'pt'))
			->join('INNER', $db->quoteName('#__joomleague_team', 't') . ' ON ' . $db->quoteName('t.id') . ' = ' . $db->quoteName('pt.team_id'))
			->join('LEFT', $db->quoteName('#__joomleague_club', 'c') . ' ON ' . $db->quoteName('c.id') . ' = ' . $db->quoteName('t.club_id'))
			->join('LEFT', $db->quoteName('#__joomleague_division', 'd') . ' ON ' . $db->quoteName('d.id') . ' = ' . $db->quoteName('pt.division_id'))
			->where($db->quoteName('pt.project_id') . ' = :project_id')
			->bind(':project_id', $projectId, ParameterType::INTEGER)
			->order($db->quoteName('pt.ordering') . ' ASC, ' . $db->quoteName('pt.id') . ' ASC');

		if ($divisionId > 0) {
			$query->where($db->quoteName('pt.division_id') . ' = :division_id')
				->bind(':division_id', $divisionId, ParameterType::INTEGER);
		}

		return $db->setQuery($query)->loadObjectList();
	}

	public function getProjectDivisions(int $projectId): array
	{
		$db = $this->getDatabase();
		$query = $db->getQuery(true)
			->select([
				$db->quoteName('d.id'),
				$db->quoteName('d.name'),
				'COUNT(' . $db->quoteName('pt.id') . ') AS team_count',
			])
			->from($db->quoteName('#__joomleague_division', 'd'))
			->join('INNER', $db->quoteName('#__joomleague_project_team', 'pt') . ' ON ' . $db->quoteName('pt.division_id') . ' = ' . $db->quoteName('d.id'))
			->where($db->quoteName('pt.project_id') . ' = :project_id')
			->group([$db->quoteName('d.id'), $db->quoteName('d.name')])
			->order($db->quoteName('d.ordering') . ' ASC, ' . $db->quoteName('d.name') . ' ASC')
			->bind(':project_id', $projectId, ParameterType::INTEGER);

		return $db->setQuery($query)->loadObjectList();
	}

	public function getTeam(?int $projectTeamId): ?object
	{
		if (empty($projectTeamId)) {
			return null;
		}

		$db = $this->getDatabase();
		$query = $db->getQuery(true)
			->select([
				'pt.*',
				$db->quoteName('t.name', 'team_name'),
				$db->quoteName('t.short_name', 'team_short_name'),
				$db->quoteName('t.middle_name', 'team_middle_name'),
				$db->quoteName('t.website', 'team_website'),
				$db->quoteName('t.info', 'team_info'),
				$db->quoteName('t.notes', 'team_notes'),
				$db->quoteName('t.picture', 'team_picture'),
				$db->quoteName('c.id', 'club_id'),
				$db->quoteName('c.name', 'club_name'),
				$db->quoteName('c.country', 'club_country'),
				$db->quoteName('c.website', 'club_website'),
				$db->quoteName('c.logo_small', 'club_logo_small'),
				$db->quoteName('c.logo_middle', 'club_logo_middle'),
				$db->quoteName('c.logo_big', 'club_logo_big'),
				$db->quoteName('pg.id', 'playground_id'),
				$db->quoteName('pg.name', 'playground_name'),
				$db->quoteName('pg.address', 'playground_address'),
				$db->quoteName('pg.zipcode', 'playground_zipcode'),
				$db->quoteName('pg.city', 'playground_city'),
				$db->quoteName('pg.country', 'playground_country'),
				$db->quoteName('pg.latitude', 'playground_latitude'),
				$db->quoteName('pg.longitude', 'playground_longitude'),
				$db->quoteName('pg.website', 'playground_website'),
			])
			->from($db->quoteName('#__joomleague_project_team', 'pt'))
			->join('INNER', $db->quoteName('#__joomleague_team', 't') . ' ON ' . $db->quoteName('t.id') . ' = ' . $db->quoteName('pt.team_id'))
			->join('LEFT', $db->quoteName('#__joomleague_club', 'c') . ' ON ' . $db->quoteName('c.id') . ' = ' . $db->quoteName('t.club_id'))
			->join('LEFT', $db->quoteName('#__joomleague_playground', 'pg') . ' ON ' . $db->quoteName('pg.id') . ' = COALESCE(' . $db->quoteName('pt.standard_playground') . ', ' . $db->quoteName('c.standard_playground') . ')')
			->where($db->quoteName('pt.id') . ' = :id')
			->bind(':id', $projectTeamId, ParameterType::INTEGER);

		$item = $db->setQuery($query)->loadObject();

		return $item ?: null;
	}

	public function getRoster(int $projectTeamId): array
	{
		$db = $this->getDatabase();
		$query = $db->getQuery(true)
			->select([
				'tp.*',
				'CONCAT_WS(' . $db->quote(', ') . ', ' . $db->quoteName('p.lastname') . ', ' . $db->quoteName('p.firstname') . ') AS person_name',
				$db->quoteName('p.firstname'),
				$db->quoteName('p.lastname'),
				$db->quoteName('p.nickname'),
				$db->quoteName('p.birthday'),
				$db->quoteName('p.deathday'),
				$db->quoteName('p.country', 'person_country'),
				'COALESCE(NULLIF(' . $db->quoteName('tp.picture') . ", ''), " . $db->quoteName('p.picture') . ') AS person_picture',
				$db->quoteName('pos.name', 'position_name'),
			])
			->from($db->quoteName('#__joomleague_team_player', 'tp'))
			->join('INNER', $db->quoteName('#__joomleague_person', 'p') . ' ON ' . $db->quoteName('p.id') . ' = ' . $db->quoteName('tp.person_id'))
			->join('LEFT', $db->quoteName('#__joomleague_project_position', 'pp') . ' ON ' . $db->quoteName('pp.id') . ' = ' . $db->quoteName('tp.project_position_id'))
			->join('LEFT', $db->quoteName('#__joomleague_position', 'pos') . ' ON ' . $db->quoteName('pos.id') . ' = ' . $db->quoteName('pp.position_id'))
			->where($db->quoteName('tp.projectteam_id') . ' = :projectteam_id')
			->where($db->quoteName('tp.published') . ' = 1')
			->bind(':projectteam_id', $projectTeamId, ParameterType::INTEGER)
			->order($db->quoteName('tp.jerseynumber') . ' IS NULL ASC, ' . $db->quoteName('tp.jerseynumber') . ' ASC, ' . $db->quoteName('p.lastname') . ' ASC');

		return $db->setQuery($query)->loadObjectList();
	}

	public function getRosterStaff(int $projectTeamId): array
	{
		$db = $this->getDatabase();
		$query = $db->getQuery(true)
			->select([
				'ts.*',
				'CONCAT_WS(' . $db->quote(', ') . ', ' . $db->quoteName('p.lastname') . ', ' . $db->quoteName('p.firstname') . ') AS person_name',
				$db->quoteName('p.firstname'),
				$db->quoteName('p.lastname'),
				$db->quoteName('p.nickname'),
				$db->quoteName('p.birthday'),
				$db->quoteName('p.deathday'),
				$db->quoteName('p.country', 'person_country'),
				'COALESCE(NULLIF(' . $db->quoteName('ts.picture') . ", ''), " . $db->quoteName('p.picture') . ') AS person_picture',
				$db->quoteName('pos.name', 'position_name'),
			])
			->from($db->quoteName('#__joomleague_team_staff', 'ts'))
			->join('INNER', $db->quoteName('#__joomleague_person', 'p') . ' ON ' . $db->quoteName('p.id') . ' = ' . $db->quoteName('ts.person_id'))
			->join('LEFT', $db->quoteName('#__joomleague_project_position', 'pp') . ' ON ' . $db->quoteName('pp.id') . ' = ' . $db->quoteName('ts.project_position_id'))
			->join('LEFT', $db->quoteName('#__joomleague_position', 'pos') . ' ON ' . $db->quoteName('pos.id') . ' = ' . $db->quoteName('pp.position_id'))
			->where($db->quoteName('ts.projectteam_id') . ' = :projectteam_id')
			->where($db->quoteName('ts.published') . ' = 1')
			->bind(':projectteam_id', $projectTeamId, ParameterType::INTEGER)
			->order($db->quoteName('ts.ordering') . ' ASC, ' . $db->quoteName('p.lastname') . ' ASC');

		return $db->setQuery($query)->loadObjectList();
	}

	/**
	 * Syrová data pro PlayerStatsHelper::aggregate() (klíčováno podle teamplayer_id) – odehrané
	 * starty/střídání/minuty a statistiky událostí pro CELOU sestavu týmu najednou, ne pro
	 * jednu osobu. Stejné tři dotazy jako PersonModel::getPlayerCareerData(), jen podle
	 * projectteam_id místo person_id.
	 *
	 * @return array{appearances: object[], subOut: object[], events: object[]}
	 */
	public function getRosterPlayerStats(int $projectTeamId): array
	{
		if ($projectTeamId < 1) {
			return ['appearances' => [], 'subOut' => [], 'events' => []];
		}

		$db = $this->getDatabase();

		$query = $db->getQuery(true)
			->select([
				$db->quoteName('mp.match_id'),
				$db->quoteName('mp.teamplayer_id'),
				$db->quoteName('mp.came_in'),
				$db->quoteName('mp.in_out_time'),
				$db->quoteName('p.game_regular_time'),
			])
			->from($db->quoteName('#__joomleague_match_player', 'mp'))
			->join('INNER', $db->quoteName('#__joomleague_team_player', 'tp') . ' ON ' . $db->quoteName('tp.id') . ' = ' . $db->quoteName('mp.teamplayer_id'))
			->join('INNER', $db->quoteName('#__joomleague_match', 'm') . ' ON ' . $db->quoteName('m.id') . ' = ' . $db->quoteName('mp.match_id'))
			->join('INNER', $db->quoteName('#__joomleague_round', 'r') . ' ON ' . $db->quoteName('r.id') . ' = ' . $db->quoteName('m.round_id'))
			->join('INNER', $db->quoteName('#__joomleague_project', 'p') . ' ON ' . $db->quoteName('p.id') . ' = ' . $db->quoteName('r.project_id'))
			->where($db->quoteName('tp.projectteam_id') . ' = :projectteam_id')
			->where($db->quoteName('m.published') . ' = 1')
			->bind(':projectteam_id', $projectTeamId, ParameterType::INTEGER);

		$appearances = $db->setQuery($query)->loadObjectList();

		if ($appearances === []) {
			return ['appearances' => [], 'subOut' => [], 'events' => []];
		}

		$query = $db->getQuery(true)
			->select([
				$db->quoteName('mp2.match_id'),
				$db->quoteName('mp2.in_for', 'teamplayer_id'),
				$db->quoteName('mp2.in_out_time'),
			])
			->from($db->quoteName('#__joomleague_match_player', 'mp2'))
			->where($db->quoteName('mp2.came_in') . ' = 1')
			->where(
				$db->quoteName('mp2.in_for') . ' IN (SELECT ' . $db->quoteName('id')
				. ' FROM ' . $db->quoteName('#__joomleague_team_player')
				. ' WHERE ' . $db->quoteName('projectteam_id') . ' = :projectteam_id2)'
			)
			->bind(':projectteam_id2', $projectTeamId, ParameterType::INTEGER);

		$subOut = $db->setQuery($query)->loadObjectList();

		$query = $db->getQuery(true)
			->select([
				$db->quoteName('me.match_id'),
				$db->quoteName('me.teamplayer_id'),
				$db->quoteName('me.event_type_id'),
				$db->quoteName('me.event_time'),
				$db->quoteName('me.event_sum'),
				$db->quoteName('et.name', 'event_name'),
				$db->quoteName('et.icon', 'event_icon'),
				$db->quoteName('et.suspension'),
			])
			->from($db->quoteName('#__joomleague_match_event', 'me'))
			->join('INNER', $db->quoteName('#__joomleague_team_player', 'tp3') . ' ON ' . $db->quoteName('tp3.id') . ' = ' . $db->quoteName('me.teamplayer_id'))
			->join('LEFT', $db->quoteName('#__joomleague_eventtype', 'et') . ' ON ' . $db->quoteName('et.id') . ' = ' . $db->quoteName('me.event_type_id'))
			->where($db->quoteName('tp3.projectteam_id') . ' = :projectteam_id3')
			->bind(':projectteam_id3', $projectTeamId, ParameterType::INTEGER);

		$events = $db->setQuery($query)->loadObjectList();

		return ['appearances' => $appearances, 'subOut' => $subOut, 'events' => $events];
	}

	public function getTeamSeasons(int $teamId, int $currentProjectTeamId = 0): array
	{
		$db = $this->getDatabase();
		$query = $db->getQuery(true)
			->select([
				$db->quoteName('pt.id', 'projectteam_id'),
				$db->quoteName('pt.project_id'),
				$db->quoteName('pt.division_id'),
				$db->quoteName('pt.start_points'),
				$db->quoteName('pt.info'),
				$db->quoteName('p.name', 'project_name'),
				$db->quoteName('p.start_date'),
				$db->quoteName('l.name', 'league_name'),
				$db->quoteName('s.name', 'season_name'),
				$db->quoteName('d.name', 'division_name'),
				'COUNT(DISTINCT ' . $db->quoteName('tp.id') . ') AS player_count',
				'CASE WHEN ' . $db->quoteName('pt.id') . ' = :current_projectteam_id THEN 1 ELSE 0 END AS is_current',
			])
			->from($db->quoteName('#__joomleague_project_team', 'pt'))
			->join('INNER', $db->quoteName('#__joomleague_project', 'p') . ' ON ' . $db->quoteName('p.id') . ' = ' . $db->quoteName('pt.project_id'))
			->join('LEFT', $db->quoteName('#__joomleague_league', 'l') . ' ON ' . $db->quoteName('l.id') . ' = ' . $db->quoteName('p.league_id'))
			->join('LEFT', $db->quoteName('#__joomleague_season', 's') . ' ON ' . $db->quoteName('s.id') . ' = ' . $db->quoteName('p.season_id'))
			->join('LEFT', $db->quoteName('#__joomleague_division', 'd') . ' ON ' . $db->quoteName('d.id') . ' = ' . $db->quoteName('pt.division_id'))
			->join('LEFT', $db->quoteName('#__joomleague_team_player', 'tp') . ' ON ' . $db->quoteName('tp.projectteam_id') . ' = ' . $db->quoteName('pt.id') . ' AND ' . $db->quoteName('tp.published') . ' = 1')
			->where($db->quoteName('pt.team_id') . ' = :team_id')
			->where($db->quoteName('p.published') . ' = 1')
			->group([
				$db->quoteName('pt.id'),
				$db->quoteName('pt.project_id'),
				$db->quoteName('pt.division_id'),
				$db->quoteName('pt.start_points'),
				$db->quoteName('pt.info'),
				$db->quoteName('p.name'),
				$db->quoteName('p.start_date'),
				$db->quoteName('l.name'),
				$db->quoteName('s.name'),
				$db->quoteName('d.name'),
			])
			->order($db->quoteName('p.start_date') . ' DESC, ' . $db->quoteName('p.id') . ' DESC')
			->bind(':team_id', $teamId, ParameterType::INTEGER)
			->bind(':current_projectteam_id', $currentProjectTeamId, ParameterType::INTEGER);

		return $db->setQuery($query)->loadObjectList();
	}

	public function getTeamStatsSummary(int $projectTeamId): array
	{
		$team = $this->getTeam($projectTeamId);

		if (!$team) {
			return [];
		}

		$summary = [
			'total' => 0,
			'played' => 0,
			'upcoming' => 0,
			'home' => 0,
			'away' => 0,
			'wins' => 0,
			'draws' => 0,
			'losses' => 0,
			'goals_for' => 0.0,
			'goals_against' => 0.0,
			'home_played' => 0,
			'away_played' => 0,
			'home_goals_for' => 0.0,
			'home_goals_against' => 0.0,
			'away_goals_for' => 0.0,
			'away_goals_against' => 0.0,
			'attendance_total' => 0,
			'attendance_best' => 0,
			'attendance_worst' => 0,
			'attendance_average' => 0,
		];

		$attendances = [];

		foreach ($this->getMatches((int) $team->project_id, 0, $projectTeamId) as $match) {
			$summary['total']++;

			$isHome = (int) $match->projectteam1_id === $projectTeamId;
			$isAway = (int) $match->projectteam2_id === $projectTeamId;

			if ($isHome) {
				$summary['home']++;
			}

			if ($isAway) {
				$summary['away']++;
			}

			if ($match->team1_result === null || $match->team2_result === null || (int) ($match->count_result ?? 1) !== 1) {
				$summary['upcoming']++;
				continue;
			}

			$summary['played']++;

			$homeGoals = (float) $match->team1_result;
			$awayGoals = (float) $match->team2_result;
			$goalsFor = $isHome ? $homeGoals : $awayGoals;
			$goalsAgainst = $isHome ? $awayGoals : $homeGoals;

			$summary['goals_for'] += $goalsFor;
			$summary['goals_against'] += $goalsAgainst;

			if ($isHome) {
				$summary['home_played']++;
				$summary['home_goals_for'] += $goalsFor;
				$summary['home_goals_against'] += $goalsAgainst;
			}

			if ($isAway) {
				$summary['away_played']++;
				$summary['away_goals_for'] += $goalsFor;
				$summary['away_goals_against'] += $goalsAgainst;
			}

			if ($goalsFor > $goalsAgainst) {
				$summary['wins']++;
			} elseif ($goalsFor < $goalsAgainst) {
				$summary['losses']++;
			} else {
				$summary['draws']++;
			}

			$attendance = (int) ($match->crowd ?? 0);

			if ($attendance > 0) {
				$attendances[] = $attendance;
			}
		}

		$summary['goal_difference'] = $summary['goals_for'] - $summary['goals_against'];

		if ($attendances !== []) {
			$summary['attendance_total'] = array_sum($attendances);
			$summary['attendance_best'] = max($attendances);
			$summary['attendance_worst'] = min($attendances);
			$summary['attendance_average'] = round($summary['attendance_total'] / count($attendances), 2);
		}

		return $summary;
	}

	public function getTeamPlayerStats(int $projectId, int $projectTeamId): array
	{
		return $this->getStatsRankings($projectId, 0, $projectTeamId);
	}

	public function getTeamRivals(int $projectId, int $projectTeamId): array
	{
		$rivals = [];

		foreach ($this->getMatches($projectId, 0, $projectTeamId) as $match) {
			if ($match->team1_result === null || $match->team2_result === null || (int) ($match->count_result ?? 1) !== 1) {
				continue;
			}

			$isHome = (int) $match->projectteam1_id === $projectTeamId;
			$rivalId = $isHome ? (int) $match->projectteam2_id : (int) $match->projectteam1_id;

			if ($rivalId <= 0) {
				continue;
			}

			if (!isset($rivals[$rivalId])) {
				$rivals[$rivalId] = (object) [
					'projectteam_id' => $rivalId,
					'team_name' => $isHome ? ($match->away_name ?? '') : ($match->home_name ?? ''),
					'matches' => 0,
					'wins' => 0,
					'draws' => 0,
					'losses' => 0,
					'goals_for' => 0.0,
					'goals_against' => 0.0,
					'last_match_id' => 0,
					'last_match_date' => null,
				];
			}

			$goalsFor = $isHome ? (float) $match->team1_result : (float) $match->team2_result;
			$goalsAgainst = $isHome ? (float) $match->team2_result : (float) $match->team1_result;

			$rivals[$rivalId]->matches++;
			$rivals[$rivalId]->goals_for += $goalsFor;
			$rivals[$rivalId]->goals_against += $goalsAgainst;

			if ($goalsFor > $goalsAgainst) {
				$rivals[$rivalId]->wins++;
			} elseif ($goalsFor < $goalsAgainst) {
				$rivals[$rivalId]->losses++;
			} else {
				$rivals[$rivalId]->draws++;
			}

			if (!$rivals[$rivalId]->last_match_date || strtotime((string) $match->match_date) > strtotime((string) $rivals[$rivalId]->last_match_date)) {
				$rivals[$rivalId]->last_match_id = (int) $match->id;
				$rivals[$rivalId]->last_match_date = $match->match_date;
			}
		}

		$list = array_values($rivals);

		usort(
			$list,
			static fn (object $a, object $b): int => [$b->matches, $b->wins, $b->goals_for, $a->team_name] <=> [$a->matches, $a->wins, $a->goals_for, $b->team_name]
		);

		return $list;
	}

	public function getTree(int $treeId = 0, int $projectId = 0): ?object
	{
		$db = $this->getDatabase();
		$query = $db->getQuery(true)
			->select([
				't.*',
				$db->quoteName('p.name', 'project_name'),
				$db->quoteName('l.name', 'league_name'),
				$db->quoteName('s.name', 'season_name'),
				$db->quoteName('d.name', 'division_name'),
				'(SELECT COUNT(*) FROM ' . $db->quoteName('#__joomleague_treeto_node') . ' n WHERE ' . $db->quoteName('n.treeto_id') . ' = ' . $db->quoteName('t.id') . ' AND ' . $db->quoteName('n.published') . ' = 1) AS node_count',
			])
			->from($db->quoteName('#__joomleague_treeto', 't'))
			->join('INNER', $db->quoteName('#__joomleague_project', 'p') . ' ON ' . $db->quoteName('p.id') . ' = ' . $db->quoteName('t.project_id'))
			->join('LEFT', $db->quoteName('#__joomleague_league', 'l') . ' ON ' . $db->quoteName('l.id') . ' = ' . $db->quoteName('p.league_id'))
			->join('LEFT', $db->quoteName('#__joomleague_season', 's') . ' ON ' . $db->quoteName('s.id') . ' = ' . $db->quoteName('p.season_id'))
			->join('LEFT', $db->quoteName('#__joomleague_division', 'd') . ' ON ' . $db->quoteName('d.id') . ' = ' . $db->quoteName('t.division_id'))
			->where($db->quoteName('t.published') . ' = 1')
			->where($db->quoteName('p.published') . ' = 1');

		if ($treeId > 0) {
			$query->where($db->quoteName('t.id') . ' = :tree_id')
				->bind(':tree_id', $treeId, ParameterType::INTEGER);
		} elseif ($projectId > 0) {
			$query->where($db->quoteName('t.project_id') . ' = :project_id')
				->bind(':project_id', $projectId, ParameterType::INTEGER);
		}

		$query->order($db->quoteName('t.id') . ' ASC');

		$item = $db->setQuery($query, 0, 1)->loadObject();

		return $item ?: null;
	}

	public function getTreeNodes(int $treeId): array
	{
		if ($treeId <= 0) {
			return [];
		}

		$db = $this->getDatabase();
		$query = $db->getQuery(true)
			->select([
				'n.*',
				$db->quoteName('pt.id', 'projectteam_id'),
				$db->quoteName('team.name', 'team_name'),
				$db->quoteName('team.short_name', 'team_short_name'),
				$db->quoteName('team.middle_name', 'team_middle_name'),
				$db->quoteName('team.picture', 'team_picture'),
				'GROUP_CONCAT(DISTINCT ' . $db->quoteName('m.id') . ' ORDER BY ' . $db->quoteName('m.match_date') . ' ASC, ' . $db->quoteName('m.id') . ' ASC SEPARATOR ' . $db->quote('|') . ') AS match_ids',
				'GROUP_CONCAT(DISTINCT CONCAT_WS(' . $db->quote('~') . ', '
					. $db->quoteName('m.id') . ', '
					. 'COALESCE(' . $db->quoteName('m.match_date') . ', ' . $db->quote('') . '), '
					. 'COALESCE(' . $db->quoteName('ht.name') . ', ' . $db->quote('') . '), '
					. 'COALESCE(' . $db->quoteName('at.name') . ', ' . $db->quote('') . '), '
					. 'COALESCE(' . $db->quoteName('m.team1_result') . ', ' . $db->quote('') . '), '
					. 'COALESCE(' . $db->quoteName('m.team2_result') . ', ' . $db->quote('') . ')'
					. ') ORDER BY ' . $db->quoteName('m.match_date') . ' ASC, ' . $db->quoteName('m.id') . ' ASC SEPARATOR ' . $db->quote('|') . ') AS matches_summary',
			])
			->from($db->quoteName('#__joomleague_treeto_node', 'n'))
			->join('LEFT', $db->quoteName('#__joomleague_project_team', 'pt') . ' ON ' . $db->quoteName('pt.id') . ' = ' . $db->quoteName('n.team_id'))
			->join('LEFT', $db->quoteName('#__joomleague_team', 'team') . ' ON ' . $db->quoteName('team.id') . ' = ' . $db->quoteName('pt.team_id'))
			->join('LEFT', $db->quoteName('#__joomleague_treeto_match', 'tm') . ' ON ' . $db->quoteName('tm.node_id') . ' = ' . $db->quoteName('n.id'))
			->join('LEFT', $db->quoteName('#__joomleague_match', 'm') . ' ON ' . $db->quoteName('m.id') . ' = ' . $db->quoteName('tm.match_id') . ' AND ' . $db->quoteName('m.published') . ' = 1')
			->join('LEFT', $db->quoteName('#__joomleague_project_team', 'home') . ' ON ' . $db->quoteName('home.id') . ' = ' . $db->quoteName('m.projectteam1_id'))
			->join('LEFT', $db->quoteName('#__joomleague_project_team', 'away') . ' ON ' . $db->quoteName('away.id') . ' = ' . $db->quoteName('m.projectteam2_id'))
			->join('LEFT', $db->quoteName('#__joomleague_team', 'ht') . ' ON ' . $db->quoteName('ht.id') . ' = ' . $db->quoteName('home.team_id'))
			->join('LEFT', $db->quoteName('#__joomleague_team', 'at') . ' ON ' . $db->quoteName('at.id') . ' = ' . $db->quoteName('away.team_id'))
			->where($db->quoteName('n.treeto_id') . ' = :tree_id')
			->where($db->quoteName('n.published') . ' = 1')
			->group([
				$db->quoteName('n.id'),
				$db->quoteName('pt.id'),
				$db->quoteName('team.name'),
				$db->quoteName('team.short_name'),
				$db->quoteName('team.middle_name'),
				$db->quoteName('team.picture'),
			])
			->order($db->quoteName('n.node') . ' ASC, ' . $db->quoteName('n.row') . ' ASC, ' . $db->quoteName('n.id') . ' ASC')
			->bind(':tree_id', $treeId, ParameterType::INTEGER);

		return $db->setQuery($query)->loadObjectList();
	}

	public function getTreeRounds(int $treeId): array
	{
		$rounds = [];

		foreach ($this->getTreeNodes($treeId) as $node) {
			$level = $this->treeNodeLevel((int) $node->node);

			if (!isset($rounds[$level])) {
				$rounds[$level] = (object) [
					'level' => $level,
					'title' => $this->treeRoundTitle($level),
					'nodes' => 0,
				];
			}

			$rounds[$level]->nodes++;
		}

		return array_values($rounds);
	}

	private function treeRoundTitle(int $level): string
	{
		return match ($level) {
			1 => 'COM_JOOMLEAGUE_SITE_TREE_ROUND_FINAL',
			2 => 'COM_JOOMLEAGUE_SITE_TREE_ROUND_SEMIFINAL',
			3 => 'COM_JOOMLEAGUE_SITE_TREE_ROUND_QUARTERFINAL',
			default => 'COM_JOOMLEAGUE_SITE_TREE_ROUND',
		};
	}

	private function treeNodeLevel(int $node): int
	{
		return $node > 0 ? (int) floor(log($node, 2)) + 1 : 1;
	}

	public function getRounds(int $projectId): array
	{
		$db = $this->getDatabase();
		$query = $db->getQuery(true)
			->select([
				$db->quoteName('r') . '.*',
				'MIN(NULLIF(' . $db->quoteName('m.match_date') . ', ' . $db->quote('0000-00-00 00:00:00') . ')) AS round_sort_date',
			])
			->from($db->quoteName('#__joomleague_round', 'r'))
			->join('LEFT', $db->quoteName('#__joomleague_match', 'm') . ' ON ' . $db->quoteName('m.round_id') . ' = ' . $db->quoteName('r.id') . ' AND ' . $db->quoteName('m.published') . ' = 1')
			->where($db->quoteName('r.project_id') . ' = :project_id')
			->where($db->quoteName('r.published') . ' = 1')
			->bind(':project_id', $projectId, ParameterType::INTEGER)
			->group($db->quoteName('r.id'))
			->order('COALESCE(MIN(NULLIF(' . $db->quoteName('m.match_date') . ', ' . $db->quote('0000-00-00 00:00:00') . ')), ' . $db->quote('9999-12-31 23:59:59') . ') ASC, ' . $db->quoteName('r.id') . ' ASC');

		return $db->setQuery($query)->loadObjectList();
	}

	public function getRaceCategories(int $projectId): array
	{
		$db = $this->getDatabase();
		$query = $db->getQuery(true)
			->select('*')
			->from($db->quoteName('#__joomleague_race_category'))
			->where($db->quoteName('project_id') . ' = :project_id')
			->where($db->quoteName('published') . ' = 1')
			->bind(':project_id', $projectId, ParameterType::INTEGER)
			->order($db->quoteName('ordering') . ' ASC, ' . $db->quoteName('name') . ' ASC');

		return $db->setQuery($query)->loadObjectList();
	}

	public function getRaceResults(int $projectId, int $roundId = 0, int $categoryId = 0, string $sex = '', string $status = ''): array
	{
		$db = $this->getDatabase();
		$query = $db->getQuery(true)
			->select([
				'rr.*',
				$db->quoteName('rp.sex'),
				$db->quoteName('rp.country'),
				$db->quoteName('rp.date_of_birth'),
				'TRIM(CONCAT_WS(' . $db->quote(' ') . ', ' . $db->quoteName('p.firstname') . ', ' . $db->quoteName('p.lastname') . ')) AS runner_name',
				$db->quoteName('p.alias', 'person_alias'),
				$db->quoteName('rc.name', 'category_name'),
				$db->quoteName('r.name', 'round_name'),
				$db->quoteName('c.name', 'club_name'),
				$db->quoteName('t.name', 'team_name'),
			])
			->from($db->quoteName('#__joomleague_race_result', 'rr'))
			->join('INNER', $db->quoteName('#__joomleague_race_participant', 'rp') . ' ON ' . $db->quoteName('rp.id') . ' = ' . $db->quoteName('rr.participant_id'))
			->join('LEFT', $db->quoteName('#__joomleague_person', 'p') . ' ON ' . $db->quoteName('p.id') . ' = ' . $db->quoteName('rr.person_id'))
			->join('LEFT', $db->quoteName('#__joomleague_race_category', 'rc') . ' ON ' . $db->quoteName('rc.id') . ' = ' . $db->quoteName('rr.category_id'))
			->join('LEFT', $db->quoteName('#__joomleague_round', 'r') . ' ON ' . $db->quoteName('r.id') . ' = ' . $db->quoteName('rr.round_id'))
			->join('LEFT', $db->quoteName('#__joomleague_club', 'c') . ' ON ' . $db->quoteName('c.id') . ' = ' . $db->quoteName('rp.club_id'))
			->join('LEFT', $db->quoteName('#__joomleague_team', 't') . ' ON ' . $db->quoteName('t.id') . ' = ' . $db->quoteName('rp.team_id'))
			->where($db->quoteName('rr.project_id') . ' = :project_id')
			->where($db->quoteName('rr.published') . ' = 1')
			->where($db->quoteName('rp.published') . ' = 1')
			->bind(':project_id', $projectId, ParameterType::INTEGER);

		if ($roundId > 0) {
			$query->where($db->quoteName('rr.round_id') . ' = :round_id')
				->bind(':round_id', $roundId, ParameterType::INTEGER);
		}

		if ($categoryId > 0) {
			$query->where($db->quoteName('rr.category_id') . ' = :category_id')
				->bind(':category_id', $categoryId, ParameterType::INTEGER);
		}

		$sex = strtoupper(trim($sex));

		if (in_array($sex, ['M', 'F', 'X'], true)) {
			$query->where($db->quoteName('rp.sex') . ' = :sex')
				->bind(':sex', $sex);
		}

		$status = strtoupper(trim($status));

		if (in_array($status, ['FINISHED', 'DNS', 'DNF', 'DSQ', 'NC'], true)) {
			$query->where($db->quoteName('rr.status') . ' = :status')
				->bind(':status', $status);
		}

		$query->order([
			$db->quoteName('rr.overall_place') . ' = 0 ASC',
			$db->quoteName('rr.overall_place') . ' ASC',
			$db->quoteName('rr.duration_ms') . ' ASC',
			$db->quoteName('rr.id') . ' ASC',
		]);

		return $db->setQuery($query)->loadObjectList();
	}

	public function getMatches(int $projectId, int $roundId = 0, int $teamId = 0, int $limit = 0, bool $upcomingOnly = false): array
	{
		$db = $this->getDatabase();
		$query = $db->getQuery(true)
			->select([
				'm.*',
				$db->quoteName('r.name', 'round_name'),
				$db->quoteName('r.roundcode'),
				$db->quoteName('r.round_date_first'),
				$db->quoteName('r.round_date_last'),
				$db->quoteName('p.name', 'project_name'),
				$db->quoteName('l.name', 'league_name'),
				$db->quoteName('s.name', 'season_name'),
				$db->quoteName('home.id', 'home_projectteam_id'),
				$db->quoteName('away.id', 'away_projectteam_id'),
				$db->quoteName('ht.name', 'home_name'),
				$db->quoteName('at.name', 'away_name'),
				$db->quoteName('ht.short_name', 'home_team_short_name'),
				$db->quoteName('at.short_name', 'away_team_short_name'),
				$db->quoteName('ht.middle_name', 'home_team_middle_name'),
				$db->quoteName('at.middle_name', 'away_team_middle_name'),
				$db->quoteName('hp.name', 'playground_name'),
				$db->quoteName('home.division_id'),
				$db->quoteName('hd.name', 'division_name'),
				$db->quoteName('hd.shortname', 'division_short_name'),
				$db->quoteName('home.standard_playground', 'home_standard_playground'),
				$db->quoteName('hc.standard_playground', 'home_club_standard_playground'),
				$db->quoteName('hc.id', 'home_club_id'),
				$db->quoteName('hc.country', 'home_club_country'),
				$db->quoteName('hc.logo_small', 'home_club_logo_small'),
				$db->quoteName('hc.logo_middle', 'home_club_logo_middle'),
				$db->quoteName('hc.logo_big', 'home_club_logo_big'),
				$db->quoteName('ac.id', 'away_club_id'),
				$db->quoteName('ac.country', 'away_club_country'),
				$db->quoteName('ac.logo_small', 'away_club_logo_small'),
				$db->quoteName('ac.logo_middle', 'away_club_logo_middle'),
				$db->quoteName('ac.logo_big', 'away_club_logo_big'),
			])
			->from($db->quoteName('#__joomleague_match', 'm'))
			->join('INNER', $db->quoteName('#__joomleague_round', 'r') . ' ON ' . $db->quoteName('r.id') . ' = ' . $db->quoteName('m.round_id'))
			->join('INNER', $db->quoteName('#__joomleague_project', 'p') . ' ON ' . $db->quoteName('p.id') . ' = ' . $db->quoteName('r.project_id'))
			->join('LEFT', $db->quoteName('#__joomleague_league', 'l') . ' ON ' . $db->quoteName('l.id') . ' = ' . $db->quoteName('p.league_id'))
			->join('LEFT', $db->quoteName('#__joomleague_season', 's') . ' ON ' . $db->quoteName('s.id') . ' = ' . $db->quoteName('p.season_id'))
			->join('LEFT', $db->quoteName('#__joomleague_project_team', 'home') . ' ON ' . $db->quoteName('home.id') . ' = ' . $db->quoteName('m.projectteam1_id'))
			->join('LEFT', $db->quoteName('#__joomleague_project_team', 'away') . ' ON ' . $db->quoteName('away.id') . ' = ' . $db->quoteName('m.projectteam2_id'))
			->join('LEFT', $db->quoteName('#__joomleague_team', 'ht') . ' ON ' . $db->quoteName('ht.id') . ' = ' . $db->quoteName('home.team_id'))
			->join('LEFT', $db->quoteName('#__joomleague_team', 'at') . ' ON ' . $db->quoteName('at.id') . ' = ' . $db->quoteName('away.team_id'))
			->join('LEFT', $db->quoteName('#__joomleague_playground', 'hp') . ' ON ' . $db->quoteName('hp.id') . ' = ' . $db->quoteName('m.playground_id'))
			->join('LEFT', $db->quoteName('#__joomleague_division', 'hd') . ' ON ' . $db->quoteName('hd.id') . ' = ' . $db->quoteName('home.division_id'))
			->join('LEFT', $db->quoteName('#__joomleague_club', 'hc') . ' ON ' . $db->quoteName('hc.id') . ' = ' . $db->quoteName('ht.club_id'))
			->join('LEFT', $db->quoteName('#__joomleague_club', 'ac') . ' ON ' . $db->quoteName('ac.id') . ' = ' . $db->quoteName('at.club_id'))
			->where($db->quoteName('r.project_id') . ' = :project_id')
			->where($db->quoteName('m.published') . ' = 1')
			->bind(':project_id', $projectId, ParameterType::INTEGER);

		if ($roundId > 0) {
			$query->where($db->quoteName('r.id') . ' = :round_id')
				->bind(':round_id', $roundId, ParameterType::INTEGER);
		}

		if ($teamId > 0) {
			$query->where('(' . $db->quoteName('m.projectteam1_id') . ' = :team_id OR ' . $db->quoteName('m.projectteam2_id') . ' = :team_id)')
				->bind(':team_id', $teamId, ParameterType::INTEGER);
		}

		if ($upcomingOnly) {
			$query->where($db->quoteName('m.match_date') . ' >= CURRENT_DATE()');
		}

		$query->order($db->quoteName('m.match_date') . ' ASC, ' . $db->quoteName('m.id') . ' ASC');

		return $db->setQuery($query, 0, $limit > 0 ? $limit : 0)->loadObjectList();
	}

	/**
	 * @return  array<int, array<int, object>> rozhodčí seskupení podle match_id
	 */
	public function getMatchesReferees(array $matchIds): array
	{
		$matchIds = array_values(array_unique(array_map('intval', $matchIds)));

		if ($matchIds === []) {
			return [];
		}

		$db = $this->getDatabase();
		$query = $db->getQuery(true)
			->select([
				$db->quoteName('mr.match_id'),
				$db->quoteName('mr.external_referee_name'),
				$db->quoteName('pos.name', 'position_name'),
				'COALESCE(NULLIF(CONCAT_WS(' . $db->quote(' ') . ', ' . $db->quoteName('p.firstname') . ', ' . $db->quoteName('p.lastname') . '), ' . $db->quote('') . '), NULLIF(' . $db->quoteName('mr.external_referee_name') . ', ' . $db->quote('') . ')) AS person_name',
			])
			->from($db->quoteName('#__joomleague_match_referee', 'mr'))
			->join('LEFT', $db->quoteName('#__joomleague_project_referee', 'pr') . ' ON ' . $db->quoteName('pr.id') . ' = ' . $db->quoteName('mr.project_referee_id'))
			->join('LEFT', $db->quoteName('#__joomleague_person', 'p') . ' ON ' . $db->quoteName('p.id') . ' = ' . $db->quoteName('pr.person_id'))
			->join('LEFT', $db->quoteName('#__joomleague_project_position', 'pp') . ' ON ' . $db->quoteName('pp.id') . ' = ' . $db->quoteName('mr.project_position_id'))
			->join('LEFT', $db->quoteName('#__joomleague_position', 'pos') . ' ON ' . $db->quoteName('pos.id') . ' = ' . $db->quoteName('pp.position_id'))
			->whereIn($db->quoteName('mr.match_id'), $matchIds)
			->where('((' . $db->quoteName('p.id') . ' IS NOT NULL AND ' . $db->quoteName('p.published') . ' = 1) OR NULLIF(' . $db->quoteName('mr.external_referee_name') . ', ' . $db->quote('') . ') IS NOT NULL)')
			->order($db->quoteName('mr.match_id') . ' ASC, ' . $db->quoteName('mr.ordering') . ' ASC, ' . $db->quoteName('mr.id') . ' ASC');

		$byMatch = [];

		foreach ($db->setQuery($query)->loadObjectList() as $referee) {
			$byMatch[(int) $referee->match_id][] = $referee;
		}

		return $byMatch;
	}

	/**
	 * @return  array<int, array<int, object>> události seskupené podle match_id
	 */
	public function getMatchesEvents(array $matchIds): array
	{
		$matchIds = array_values(array_unique(array_map('intval', $matchIds)));

		if ($matchIds === []) {
			return [];
		}

		$db = $this->getDatabase();
		$query = $db->getQuery(true)
			->select([
				'e.*',
				$db->quoteName('et.name', 'event_name'),
				$db->quoteName('et.icon', 'event_icon'),
				$db->quoteName('t.name', 'team_name'),
				$db->quoteName('p.id', 'person_id'),
				'COALESCE(NULLIF(CONCAT_WS(' . $db->quote(' ') . ', ' . $db->quoteName('p.firstname') . ', ' . $db->quoteName('p.lastname') . '), ' . $db->quote('') . '), NULLIF(' . $db->quoteName('e.external_person_name') . ', ' . $db->quote('') . ')) AS person_name',
			])
			->from($db->quoteName('#__joomleague_match_event', 'e'))
			->join('LEFT', $db->quoteName('#__joomleague_eventtype', 'et') . ' ON ' . $db->quoteName('et.id') . ' = ' . $db->quoteName('e.event_type_id'))
			->join('LEFT', $db->quoteName('#__joomleague_project_team', 'pt') . ' ON ' . $db->quoteName('pt.id') . ' = ' . $db->quoteName('e.projectteam_id'))
			->join('LEFT', $db->quoteName('#__joomleague_team', 't') . ' ON ' . $db->quoteName('t.id') . ' = ' . $db->quoteName('pt.team_id'))
			->join('LEFT', $db->quoteName('#__joomleague_team_player', 'tp') . ' ON ' . $db->quoteName('tp.id') . ' = ' . $db->quoteName('e.teamplayer_id'))
			->join('LEFT', $db->quoteName('#__joomleague_person', 'p') . ' ON ' . $db->quoteName('p.id') . ' = ' . $db->quoteName('tp.person_id'))
			->whereIn($db->quoteName('e.match_id'), $matchIds)
			->order('CAST(NULLIF(' . $db->quoteName('e.event_time') . ', ' . $db->quote('') . ') AS UNSIGNED) ASC, ' . $db->quoteName('e.id') . ' ASC');

		$byMatch = [];

		foreach ($db->setQuery($query)->loadObjectList() as $event) {
			$byMatch[(int) $event->match_id][] = $event;
		}

		return $byMatch;
	}

	/**
	 * @return  array<int, object> nominace hráčů k danému zápasu (sestava + střídání)
	 */
	public function getMatchRoster(int $matchId): array
	{
		if ($matchId <= 0) {
			return [];
		}

		$db = $this->getDatabase();
		$query = $db->getQuery(true)
			->select([
				'mp.*',
				$db->quoteName('tp.projectteam_id'),
				$db->quoteName('tp.jerseynumber'),
				$db->quoteName('tp.person_id'),
				$db->quoteName('tp.picture', 'person_teampicture'),
				$db->quoteName('p.firstname'),
				$db->quoteName('p.lastname'),
				$db->quoteName('p.nickname'),
				$db->quoteName('p.country', 'person_country'),
				$db->quoteName('p.picture', 'person_picture'),
				'CONCAT_WS(' . $db->quote(' ') . ', ' . $db->quoteName('p.firstname') . ', ' . $db->quoteName('p.lastname') . ') AS person_name',
				$db->quoteName('pos.name', 'position_name'),
			])
			->from($db->quoteName('#__joomleague_match_player', 'mp'))
			->join('INNER', $db->quoteName('#__joomleague_team_player', 'tp') . ' ON ' . $db->quoteName('tp.id') . ' = ' . $db->quoteName('mp.teamplayer_id'))
			->join('INNER', $db->quoteName('#__joomleague_person', 'p') . ' ON ' . $db->quoteName('p.id') . ' = ' . $db->quoteName('tp.person_id'))
			->join('LEFT', $db->quoteName('#__joomleague_project_position', 'pp') . ' ON ' . $db->quoteName('pp.id') . ' = ' . $db->quoteName('mp.project_position_id'))
			->join('LEFT', $db->quoteName('#__joomleague_position', 'pos') . ' ON ' . $db->quoteName('pos.id') . ' = ' . $db->quoteName('pp.position_id'))
			->where($db->quoteName('mp.match_id') . ' = :match_id')
			->bind(':match_id', $matchId, ParameterType::INTEGER)
			->order($db->quoteName('mp.came_in') . ' ASC, ' . $db->quoteName('mp.ordering') . ' ASC, ' . $db->quoteName('mp.id') . ' ASC');

		return $db->setQuery($query)->loadObjectList();
	}

	/**
	 * @return  array<int, object> realizační tým nominovaný k danému zápasu
	 */
	public function getMatchStaffList(int $matchId): array
	{
		if ($matchId <= 0) {
			return [];
		}

		$db = $this->getDatabase();
		$query = $db->getQuery(true)
			->select([
				'ms.*',
				$db->quoteName('ts.projectteam_id'),
				$db->quoteName('ts.person_id'),
				$db->quoteName('ts.picture', 'person_teampicture'),
				$db->quoteName('p.firstname'),
				$db->quoteName('p.lastname'),
				$db->quoteName('p.nickname'),
				$db->quoteName('p.country', 'person_country'),
				$db->quoteName('p.picture', 'person_picture'),
				'CONCAT_WS(' . $db->quote(' ') . ', ' . $db->quoteName('p.firstname') . ', ' . $db->quoteName('p.lastname') . ') AS person_name',
				$db->quoteName('pos.name', 'position_name'),
			])
			->from($db->quoteName('#__joomleague_match_staff', 'ms'))
			->join('INNER', $db->quoteName('#__joomleague_team_staff', 'ts') . ' ON ' . $db->quoteName('ts.id') . ' = ' . $db->quoteName('ms.team_staff_id'))
			->join('INNER', $db->quoteName('#__joomleague_person', 'p') . ' ON ' . $db->quoteName('p.id') . ' = ' . $db->quoteName('ts.person_id'))
			->join('LEFT', $db->quoteName('#__joomleague_project_position', 'pp') . ' ON ' . $db->quoteName('pp.id') . ' = ' . $db->quoteName('ms.project_position_id'))
			->join('LEFT', $db->quoteName('#__joomleague_position', 'pos') . ' ON ' . $db->quoteName('pos.id') . ' = ' . $db->quoteName('pp.position_id'))
			->where($db->quoteName('ms.match_id') . ' = :match_id')
			->bind(':match_id', $matchId, ParameterType::INTEGER)
			->order($db->quoteName('ms.ordering') . ' ASC, ' . $db->quoteName('ms.id') . ' ASC');

		return $db->setQuery($query)->loadObjectList();
	}

	/**
	 * @return  array<int, object> statistiky hráčů k danému zápasu
	 */
	public function getMatchPlayerStatistics(int $matchId): array
	{
		if ($matchId <= 0) {
			return [];
		}

		$db = $this->getDatabase();
		$query = $db->getQuery(true)
			->select([
				'mst.*',
				$db->quoteName('st.name', 'statistic_name'),
				$db->quoteName('st.short', 'statistic_short'),
				$db->quoteName('st.icon', 'statistic_icon'),
				$db->quoteName('tp.jerseynumber'),
				$db->quoteName('p.firstname'),
				$db->quoteName('p.lastname'),
				'CONCAT_WS(' . $db->quote(' ') . ', ' . $db->quoteName('p.firstname') . ', ' . $db->quoteName('p.lastname') . ') AS person_name',
			])
			->from($db->quoteName('#__joomleague_match_statistic', 'mst'))
			->join('LEFT', $db->quoteName('#__joomleague_statistic', 'st') . ' ON ' . $db->quoteName('st.id') . ' = ' . $db->quoteName('mst.statistic_id'))
			->join('LEFT', $db->quoteName('#__joomleague_team_player', 'tp') . ' ON ' . $db->quoteName('tp.id') . ' = ' . $db->quoteName('mst.teamplayer_id'))
			->join('LEFT', $db->quoteName('#__joomleague_person', 'p') . ' ON ' . $db->quoteName('p.id') . ' = ' . $db->quoteName('tp.person_id'))
			->where($db->quoteName('mst.match_id') . ' = :match_id')
			->where($db->quoteName('mst.value') . ' != 0')
			->bind(':match_id', $matchId, ParameterType::INTEGER)
			->order($db->quoteName('mst.projectteam_id') . ' ASC, ' . $db->quoteName('tp.jerseynumber') . ' ASC, ' . $db->quoteName('st.ordering') . ' ASC');

		return $db->setQuery($query)->loadObjectList();
	}

	public function getClubMatches(int $clubId, int $projectId = 0, int $limit = 0, bool $upcomingOnly = false): array
	{
		$db = $this->getDatabase();
		$query = $db->getQuery(true)
			->select([
				'm.*',
				$db->quoteName('r.name', 'round_name'),
				$db->quoteName('r.roundcode'),
				$db->quoteName('r.project_id'),
				$db->quoteName('p.name', 'project_name'),
				$db->quoteName('l.name', 'league_name'),
				$db->quoteName('s.name', 'season_name'),
				$db->quoteName('home.id', 'home_projectteam_id'),
				$db->quoteName('away.id', 'away_projectteam_id'),
				$db->quoteName('ht.name', 'home_name'),
				$db->quoteName('at.name', 'away_name'),
				$db->quoteName('hc.id', 'home_club_id'),
				$db->quoteName('ac.id', 'away_club_id'),
				$db->quoteName('hp.name', 'playground_name'),
			])
			->from($db->quoteName('#__joomleague_match', 'm'))
			->join('INNER', $db->quoteName('#__joomleague_round', 'r') . ' ON ' . $db->quoteName('r.id') . ' = ' . $db->quoteName('m.round_id'))
			->join('INNER', $db->quoteName('#__joomleague_project', 'p') . ' ON ' . $db->quoteName('p.id') . ' = ' . $db->quoteName('r.project_id'))
			->join('LEFT', $db->quoteName('#__joomleague_league', 'l') . ' ON ' . $db->quoteName('l.id') . ' = ' . $db->quoteName('p.league_id'))
			->join('LEFT', $db->quoteName('#__joomleague_season', 's') . ' ON ' . $db->quoteName('s.id') . ' = ' . $db->quoteName('p.season_id'))
			->join('LEFT', $db->quoteName('#__joomleague_project_team', 'home') . ' ON ' . $db->quoteName('home.id') . ' = ' . $db->quoteName('m.projectteam1_id'))
			->join('LEFT', $db->quoteName('#__joomleague_project_team', 'away') . ' ON ' . $db->quoteName('away.id') . ' = ' . $db->quoteName('m.projectteam2_id'))
			->join('LEFT', $db->quoteName('#__joomleague_team', 'ht') . ' ON ' . $db->quoteName('ht.id') . ' = ' . $db->quoteName('home.team_id'))
			->join('LEFT', $db->quoteName('#__joomleague_team', 'at') . ' ON ' . $db->quoteName('at.id') . ' = ' . $db->quoteName('away.team_id'))
			->join('LEFT', $db->quoteName('#__joomleague_club', 'hc') . ' ON ' . $db->quoteName('hc.id') . ' = ' . $db->quoteName('ht.club_id'))
			->join('LEFT', $db->quoteName('#__joomleague_club', 'ac') . ' ON ' . $db->quoteName('ac.id') . ' = ' . $db->quoteName('at.club_id'))
			->join('LEFT', $db->quoteName('#__joomleague_playground', 'hp') . ' ON ' . $db->quoteName('hp.id') . ' = ' . $db->quoteName('m.playground_id'))
			->where('(' . $db->quoteName('hc.id') . ' = :home_club_id OR ' . $db->quoteName('ac.id') . ' = :away_club_id)')
			->where($db->quoteName('m.published') . ' = 1')
			->bind(':home_club_id', $clubId, ParameterType::INTEGER)
			->bind(':away_club_id', $clubId, ParameterType::INTEGER);

		if ($projectId > 0) {
			$query->where($db->quoteName('r.project_id') . ' = :project_id')
				->bind(':project_id', $projectId, ParameterType::INTEGER);
		}

		if ($upcomingOnly) {
			$query->where($db->quoteName('m.match_date') . ' >= CURRENT_DATE()');
		}

		$query->order($db->quoteName('m.match_date') . ' ASC, ' . $db->quoteName('m.id') . ' ASC');

		return $db->setQuery($query, 0, $limit > 0 ? $limit : 0)->loadObjectList();
	}

	public function summarizeMatches(array $matches, int $projectTeamId = 0): array
	{
		$summary = [
			'total' => count($matches),
			'played' => 0,
			'upcoming' => 0,
			'home' => 0,
			'away' => 0,
		];

		foreach ($matches as $match) {
			if ($match->team1_result === null || $match->team2_result === null) {
				$summary['upcoming']++;
			} else {
				$summary['played']++;
			}

			if ($projectTeamId > 0 && (int) ($match->home_projectteam_id ?? 0) === $projectTeamId) {
				$summary['home']++;
			}

			if ($projectTeamId > 0 && (int) ($match->away_projectteam_id ?? 0) === $projectTeamId) {
				$summary['away']++;
			}
		}

		return $summary;
	}

	public function getMatch(?int $matchId): ?object
	{
		if (empty($matchId)) {
			return null;
		}

		$matches = $this->getMatchesByIds([$matchId]);

		return $matches[0] ?? null;
	}

	public function getNextMatch(int $projectId = 0, int $divisionId = 0, int $projectTeamId = 0, int $teamId = 0, int $matchId = 0): ?object
	{
		if ($matchId > 0) {
			return $this->getMatch($matchId);
		}

		$db = $this->getDatabase();
		$query = $db->getQuery(true)
			->select($db->quoteName('m.id'))
			->from($db->quoteName('#__joomleague_match', 'm'))
			->join('INNER', $db->quoteName('#__joomleague_round', 'r') . ' ON ' . $db->quoteName('r.id') . ' = ' . $db->quoteName('m.round_id'))
			->join('INNER', $db->quoteName('#__joomleague_project', 'p') . ' ON ' . $db->quoteName('p.id') . ' = ' . $db->quoteName('r.project_id'))
			->join('LEFT', $db->quoteName('#__joomleague_project_team', 'home') . ' ON ' . $db->quoteName('home.id') . ' = ' . $db->quoteName('m.projectteam1_id'))
			->join('LEFT', $db->quoteName('#__joomleague_project_team', 'away') . ' ON ' . $db->quoteName('away.id') . ' = ' . $db->quoteName('m.projectteam2_id'))
			->where($db->quoteName('m.published') . ' = 1')
			->where($db->quoteName('p.published') . ' = 1')
			->where('(' . $db->quoteName('m.team1_result') . ' IS NULL OR ' . $db->quoteName('m.team2_result') . ' IS NULL)')
			->where($db->quoteName('m.match_date') . ' >= CURRENT_DATE()');

		if ($projectId > 0) {
			$query->where($db->quoteName('r.project_id') . ' = :project_id')
				->bind(':project_id', $projectId, ParameterType::INTEGER);
		}

		if ($divisionId > 0) {
			$query->where('(' . $db->quoteName('home.division_id') . ' = :home_division_id OR ' . $db->quoteName('away.division_id') . ' = :away_division_id)')
				->bind(':home_division_id', $divisionId, ParameterType::INTEGER)
				->bind(':away_division_id', $divisionId, ParameterType::INTEGER);
		}

		if ($projectTeamId > 0) {
			$query->where('(' . $db->quoteName('m.projectteam1_id') . ' = :home_projectteam_id OR ' . $db->quoteName('m.projectteam2_id') . ' = :away_projectteam_id)')
				->bind(':home_projectteam_id', $projectTeamId, ParameterType::INTEGER)
				->bind(':away_projectteam_id', $projectTeamId, ParameterType::INTEGER);
		}

		if ($teamId > 0) {
			$query->where('(' . $db->quoteName('home.team_id') . ' = :home_team_id OR ' . $db->quoteName('away.team_id') . ' = :away_team_id)')
				->bind(':home_team_id', $teamId, ParameterType::INTEGER)
				->bind(':away_team_id', $teamId, ParameterType::INTEGER);
		}

		$query->order($db->quoteName('m.match_date') . ' ASC, ' . $db->quoteName('m.id') . ' ASC');
		$id = (int) $db->setQuery($query, 0, 1)->loadResult();

		return $id > 0 ? $this->getMatch($id) : null;
	}

	public function getMatchesByIds(array $ids): array
	{
		$ids = array_values(array_filter(array_map('intval', $ids)));

		if ($ids === []) {
			return [];
		}

		$db = $this->getDatabase();
		$query = $db->getQuery(true)
			->select([
				'm.*',
				$db->quoteName('r.name', 'round_name'),
				$db->quoteName('r.project_id'),
				$db->quoteName('p.name', 'project_name'),
				$db->quoteName('l.name', 'league_name'),
				$db->quoteName('s.name', 'season_name'),
				$db->quoteName('st.name', 'sport_name'),
				$db->quoteName('home.id', 'home_projectteam_id'),
				$db->quoteName('away.id', 'away_projectteam_id'),
				$db->quoteName('ht.name', 'home_name'),
				$db->quoteName('at.name', 'away_name'),
				$db->quoteName('ht.short_name', 'home_team_short_name'),
				$db->quoteName('at.short_name', 'away_team_short_name'),
				$db->quoteName('ht.middle_name', 'home_team_middle_name'),
				$db->quoteName('at.middle_name', 'away_team_middle_name'),
				$db->quoteName('ht.picture', 'home_team_picture'),
				$db->quoteName('at.picture', 'away_team_picture'),
				$db->quoteName('home.picture', 'home_projectteam_picture'),
				$db->quoteName('away.picture', 'away_projectteam_picture'),
				$db->quoteName('hp.name', 'playground_name'),
				$db->quoteName('hp.address', 'playground_address'),
				$db->quoteName('hp.zipcode', 'playground_zipcode'),
				$db->quoteName('hp.city', 'playground_city'),
				$db->quoteName('hp.country', 'playground_country'),
				$db->quoteName('hp.latitude', 'playground_latitude'),
				$db->quoteName('hp.longitude', 'playground_longitude'),
				$db->quoteName('hp.max_visitors', 'playground_max_visitors'),
				$db->quoteName('hp.website', 'playground_website'),
				$db->quoteName('hc.id', 'home_club_id'),
				$db->quoteName('hc.country', 'home_club_country'),
				$db->quoteName('hc.website', 'home_club_website'),
				$db->quoteName('hc.logo_small', 'home_club_logo_small'),
				$db->quoteName('hc.logo_middle', 'home_club_logo_middle'),
				$db->quoteName('hc.logo_big', 'home_club_logo_big'),
				$db->quoteName('ac.id', 'away_club_id'),
				$db->quoteName('ac.country', 'away_club_country'),
				$db->quoteName('ac.website', 'away_club_website'),
				$db->quoteName('ac.logo_small', 'away_club_logo_small'),
				$db->quoteName('ac.logo_middle', 'away_club_logo_middle'),
				$db->quoteName('ac.logo_big', 'away_club_logo_big'),
			])
			->from($db->quoteName('#__joomleague_match', 'm'))
			->join('INNER', $db->quoteName('#__joomleague_round', 'r') . ' ON ' . $db->quoteName('r.id') . ' = ' . $db->quoteName('m.round_id'))
			->join('INNER', $db->quoteName('#__joomleague_project', 'p') . ' ON ' . $db->quoteName('p.id') . ' = ' . $db->quoteName('r.project_id'))
			->join('LEFT', $db->quoteName('#__joomleague_league', 'l') . ' ON ' . $db->quoteName('l.id') . ' = ' . $db->quoteName('p.league_id'))
			->join('LEFT', $db->quoteName('#__joomleague_season', 's') . ' ON ' . $db->quoteName('s.id') . ' = ' . $db->quoteName('p.season_id'))
			->join('LEFT', $db->quoteName('#__joomleague_sports_type', 'st') . ' ON ' . $db->quoteName('st.id') . ' = ' . $db->quoteName('p.sports_type_id'))
			->join('LEFT', $db->quoteName('#__joomleague_project_team', 'home') . ' ON ' . $db->quoteName('home.id') . ' = ' . $db->quoteName('m.projectteam1_id'))
			->join('LEFT', $db->quoteName('#__joomleague_project_team', 'away') . ' ON ' . $db->quoteName('away.id') . ' = ' . $db->quoteName('m.projectteam2_id'))
			->join('LEFT', $db->quoteName('#__joomleague_team', 'ht') . ' ON ' . $db->quoteName('ht.id') . ' = ' . $db->quoteName('home.team_id'))
			->join('LEFT', $db->quoteName('#__joomleague_team', 'at') . ' ON ' . $db->quoteName('at.id') . ' = ' . $db->quoteName('away.team_id'))
			->join('LEFT', $db->quoteName('#__joomleague_playground', 'hp') . ' ON ' . $db->quoteName('hp.id') . ' = ' . $db->quoteName('m.playground_id'))
			->join('LEFT', $db->quoteName('#__joomleague_club', 'hc') . ' ON ' . $db->quoteName('hc.id') . ' = ' . $db->quoteName('ht.club_id'))
			->join('LEFT', $db->quoteName('#__joomleague_club', 'ac') . ' ON ' . $db->quoteName('ac.id') . ' = ' . $db->quoteName('at.club_id'))
			->whereIn($db->quoteName('m.id'), $ids);

		return $db->setQuery($query)->loadObjectList();
	}

	public function getHeadToHeadMatches(int $homeProjectTeamId, int $awayProjectTeamId, int $currentMatchId = 0, int $limit = 5): array
	{
		if ($homeProjectTeamId <= 0 || $awayProjectTeamId <= 0) {
			return [];
		}

		$db = $this->getDatabase();
		$query = $db->getQuery(true)
			->select([
				'm.*',
				$db->quoteName('r.name', 'round_name'),
				$db->quoteName('r.roundcode'),
				$db->quoteName('r.project_id'),
				$db->quoteName('p.name', 'project_name'),
				$db->quoteName('home.id', 'home_projectteam_id'),
				$db->quoteName('away.id', 'away_projectteam_id'),
				$db->quoteName('ht.name', 'home_name'),
				$db->quoteName('at.name', 'away_name'),
				$db->quoteName('hp.name', 'playground_name'),
			])
			->from($db->quoteName('#__joomleague_match', 'm'))
			->join('INNER', $db->quoteName('#__joomleague_round', 'r') . ' ON ' . $db->quoteName('r.id') . ' = ' . $db->quoteName('m.round_id'))
			->join('INNER', $db->quoteName('#__joomleague_project', 'p') . ' ON ' . $db->quoteName('p.id') . ' = ' . $db->quoteName('r.project_id'))
			->join('LEFT', $db->quoteName('#__joomleague_project_team', 'home') . ' ON ' . $db->quoteName('home.id') . ' = ' . $db->quoteName('m.projectteam1_id'))
			->join('LEFT', $db->quoteName('#__joomleague_project_team', 'away') . ' ON ' . $db->quoteName('away.id') . ' = ' . $db->quoteName('m.projectteam2_id'))
			->join('LEFT', $db->quoteName('#__joomleague_team', 'ht') . ' ON ' . $db->quoteName('ht.id') . ' = ' . $db->quoteName('home.team_id'))
			->join('LEFT', $db->quoteName('#__joomleague_team', 'at') . ' ON ' . $db->quoteName('at.id') . ' = ' . $db->quoteName('away.team_id'))
			->join('LEFT', $db->quoteName('#__joomleague_playground', 'hp') . ' ON ' . $db->quoteName('hp.id') . ' = ' . $db->quoteName('m.playground_id'))
			->where('(('
				. $db->quoteName('m.projectteam1_id') . ' = :home_projectteam_id_a AND ' . $db->quoteName('m.projectteam2_id') . ' = :away_projectteam_id_a'
				. ') OR ('
				. $db->quoteName('m.projectteam1_id') . ' = :away_projectteam_id_b AND ' . $db->quoteName('m.projectteam2_id') . ' = :home_projectteam_id_b'
				. '))')
			->where($db->quoteName('m.published') . ' = 1')
			->where($db->quoteName('m.team1_result') . ' IS NOT NULL')
			->where($db->quoteName('m.team2_result') . ' IS NOT NULL')
			->bind(':home_projectteam_id_a', $homeProjectTeamId, ParameterType::INTEGER)
			->bind(':away_projectteam_id_a', $awayProjectTeamId, ParameterType::INTEGER)
			->bind(':home_projectteam_id_b', $homeProjectTeamId, ParameterType::INTEGER)
			->bind(':away_projectteam_id_b', $awayProjectTeamId, ParameterType::INTEGER);

		if ($currentMatchId > 0) {
			$query->where($db->quoteName('m.id') . ' <> :current_match_id')
				->bind(':current_match_id', $currentMatchId, ParameterType::INTEGER);
		}

		$query->order($db->quoteName('m.match_date') . ' DESC, ' . $db->quoteName('m.id') . ' DESC');

		return $db->setQuery($query, 0, $limit)->loadObjectList();
	}

	public function getMatchReferees(int $matchId): array
	{
		if ($matchId <= 0) {
			return [];
		}

		$db = $this->getDatabase();
		$query = $db->getQuery(true)
			->select([
				'mr.id',
				$db->quoteName('pr.person_id'),
				$db->quoteName('mr.external_referee_name'),
				$db->quoteName('pos.name', 'position_name'),
				$db->quoteName('p.firstname'),
				$db->quoteName('p.lastname'),
				$db->quoteName('p.nickname'),
				$db->quoteName('p.country'),
				'COALESCE(NULLIF(CONCAT_WS(' . $db->quote(' ') . ', ' . $db->quoteName('p.firstname') . ', ' . $db->quoteName('p.lastname') . '), ' . $db->quote('') . '), NULLIF(' . $db->quoteName('mr.external_referee_name') . ', ' . $db->quote('') . ')) AS person_name',
			])
			->from($db->quoteName('#__joomleague_match_referee', 'mr'))
			->join('LEFT', $db->quoteName('#__joomleague_project_referee', 'pr') . ' ON ' . $db->quoteName('pr.id') . ' = ' . $db->quoteName('mr.project_referee_id'))
			->join('LEFT', $db->quoteName('#__joomleague_person', 'p') . ' ON ' . $db->quoteName('p.id') . ' = ' . $db->quoteName('pr.person_id'))
			->join('LEFT', $db->quoteName('#__joomleague_project_position', 'pp') . ' ON ' . $db->quoteName('pp.id') . ' = ' . $db->quoteName('mr.project_position_id'))
			->join('LEFT', $db->quoteName('#__joomleague_position', 'pos') . ' ON ' . $db->quoteName('pos.id') . ' = ' . $db->quoteName('pp.position_id'))
			->where($db->quoteName('mr.match_id') . ' = :match_id')
			->where('((' . $db->quoteName('p.id') . ' IS NOT NULL AND ' . $db->quoteName('p.published') . ' = 1) OR NULLIF(' . $db->quoteName('mr.external_referee_name') . ', ' . $db->quote('') . ') IS NOT NULL)')
			->bind(':match_id', $matchId, ParameterType::INTEGER)
			->order($db->quoteName('mr.ordering') . ' ASC, ' . $db->quoteName('mr.id') . ' ASC');

		return $db->setQuery($query)->loadObjectList();
	}

	public function getMatchTeamComparison(object $match): array
	{
		$homeId = (int) ($match->projectteam1_id ?? 0);
		$awayId = (int) ($match->projectteam2_id ?? 0);
		$projectId = (int) ($match->project_id ?? 0);

		if ($homeId <= 0 || $awayId <= 0 || $projectId <= 0) {
			return [];
		}

		$totals = [];

		foreach ($this->getStandings($projectId) as $index => $team) {
			$totals[(int) $team->projectteam_id] = ['rank' => $index + 1, 'row' => $team];
		}

		$homeSplit = [];

		foreach ($this->getStandings($projectId, 'home') as $team) {
			$homeSplit[(int) $team->projectteam_id] = $team;
		}

		$awaySplit = [];

		foreach ($this->getStandings($projectId, 'away') as $team) {
			$awaySplit[(int) $team->projectteam_id] = $team;
		}

		$buildSide = function (int $projectTeamId) use ($projectId, $totals, $homeSplit, $awaySplit): array {
			$total = $totals[$projectTeamId]['row'] ?? null;
			$home = $homeSplit[$projectTeamId] ?? null;
			$away = $awaySplit[$projectTeamId] ?? null;
			$stats = $this->getTeamStatsSummary($projectTeamId);

			return [
				'rank' => $totals[$projectTeamId]['rank'] ?? null,
				'stats' => $stats,
				'points' => $total !== null ? (int) $total->points : null,
				'home_split' => $home !== null ? ['won' => (int) $home->won, 'drawn' => (int) $home->drawn, 'lost' => (int) $home->lost] : null,
				'away_split' => $away !== null ? ['won' => (int) $away->won, 'drawn' => (int) $away->drawn, 'lost' => (int) $away->lost] : null,
				'records' => $this->getTeamMatchRecords($projectId, $projectTeamId),
			];
		};

		$home = $buildSide($homeId);
		$away = $buildSide($awayId);

		$chances = $this->getMatchChances($home['stats'], $away['stats']);

		return [
			'home' => $home + ['chance' => $chances[0] ?? null],
			'away' => $away + ['chance' => $chances[1] ?? null],
		];
	}

	/**
	 * Nejvyšší domácí/venkovní výhra a prohra tohoto týmu v rámci daného projektu (soutěže) –
	 * ne napříč celou historií klubu. Ověřeno 1:1 podle JoomLeague 3
	 * (models/nextmatch.php::_getHighestHomeWin()/_getHighestHomeDef()/_getHighestAwayWin()/_getHighestAwayDef()),
	 * jen počítáno v PHP nad už načtenými zápasy místo 4 samostatných SQL dotazů.
	 *
	 * @return array{highest_home_win: object|null, highest_home_loss: object|null, highest_away_win: object|null, highest_away_loss: object|null}
	 */
	public function getTeamMatchRecords(int $projectId, int $projectTeamId): array
	{
		$records = [
			'highest_home_win' => null,
			'highest_home_loss' => null,
			'highest_away_win' => null,
			'highest_away_loss' => null,
		];

		if ($projectId < 1 || $projectTeamId < 1) {
			return $records;
		}

		$bestMagnitude = ['highest_home_win' => 0.0, 'highest_home_loss' => 0.0, 'highest_away_win' => 0.0, 'highest_away_loss' => 0.0];

		foreach ($this->getMatches($projectId, 0, $projectTeamId) as $candidate) {
			if ($candidate->team1_result === null || $candidate->team2_result === null || (int) ($candidate->count_result ?? 1) !== 1) {
				continue;
			}

			$isHome = (int) $candidate->projectteam1_id === $projectTeamId;
			$goalsFor = (float) ($isHome ? $candidate->team1_result : $candidate->team2_result);
			$goalsAgainst = (float) ($isHome ? $candidate->team2_result : $candidate->team1_result);
			$diff = $goalsFor - $goalsAgainst;

			if ($diff === 0.0) {
				continue;
			}

			$key = $isHome
				? ($diff > 0 ? 'highest_home_win' : 'highest_home_loss')
				: ($diff > 0 ? 'highest_away_win' : 'highest_away_loss');

			if (abs($diff) > $bestMagnitude[$key]) {
				$bestMagnitude[$key] = abs($diff);
				$records[$key] = $candidate;
			}
		}

		return $records;
	}

	/**
	 * Procentuální "šance na výhru" obou týmů z celkových statistik v tomto projektu (ne home/away
	 * specifických) – ověřeno 1:1 podle JoomLeague 3 (models/nextmatch.php::getChances()): kombinace
	 * podílu výher/proher a podílu vstřelených/obdržených branek na zápas, zprůměrováno.
	 *
	 * @return array{0: string, 1: string}|array{}
	 */
	private function getMatchChances(array $homeStats, array $awayStats): array
	{
		$homePlayed = (int) ($homeStats['played'] ?? 0);
		$awayPlayed = (int) ($awayStats['played'] ?? 0);

		if ($homePlayed < 1 || $awayPlayed < 1) {
			return [];
		}

		$ax = (100 * $homeStats['wins'] / $homePlayed) + (100 * $awayStats['losses'] / $awayPlayed);
		$bx = (100 * $awayStats['wins'] / $awayPlayed) + (100 * $homeStats['losses'] / $homePlayed);
		$cx = ($homeStats['goals_for'] / $homePlayed) + ($awayStats['goals_against'] / $awayPlayed);
		$dx = ($awayStats['goals_for'] / $awayPlayed) + ($homeStats['goals_against'] / $homePlayed);
		$ex = $ax + $bx;
		$fx = $cx + $dx;

		if ($ex <= 0 || $fx <= 0) {
			return [];
		}

		$ax = round(10000 * $ax / $ex);
		$bx = round(10000 * $bx / $ex);
		$cx = round(10000 * $cx / $fx);
		$dx = round(10000 * $dx / $fx);

		return [
			number_format(($ax + $cx) / 200, 2),
			number_format(($bx + $dx) / 200, 2),
		];
	}

	public function getResultMatrix(int $projectId): array
	{
		$db    = $this->getDatabase();
		$query = $db->getQuery(true)
			->select([
				$db->quoteName('m.id'),
				$db->quoteName('m.projectteam1_id'),
				$db->quoteName('m.projectteam2_id'),
				$db->quoteName('m.team1_result', 'e1'),
				$db->quoteName('m.team2_result', 'e2'),
				$db->quoteName('m.match_result_type', 'rtype'),
				$db->quoteName('m.alt_decision', 'decision'),
				$db->quoteName('m.team1_result_decision', 'v1'),
				$db->quoteName('m.team2_result_decision', 'v2'),
				$db->quoteName('m.cancel'),
				$db->quoteName('m.cancel_reason'),
				$db->quoteName('m.new_match_id'),
				$db->quoteName('m.count_result'),
				$db->quoteName('m.match_date'),
				$db->quoteName('r.id', 'round_id'),
				$db->quoteName('r.name', 'round_name'),
				$db->quoteName('r.roundcode', 'round_code'),
			])
			->from($db->quoteName('#__joomleague_match', 'm'))
			->join('INNER', $db->quoteName('#__joomleague_round', 'r') . ' ON ' . $db->quoteName('r.id') . ' = ' . $db->quoteName('m.round_id'))
			->where($db->quoteName('r.project_id') . ' = :project_id')
			->where($db->quoteName('m.published') . ' = 1')
			->bind(':project_id', $projectId, ParameterType::INTEGER)
			->order($db->quoteName('r.ordering') . ' ASC, ' . $db->quoteName('r.roundcode') . ' ASC');

		$matrix = [];

		foreach ($db->setQuery($query)->loadObjectList() as $m) {
			$homeId = (int) $m->projectteam1_id;
			$awayId = (int) $m->projectteam2_id;

			if ($homeId <= 0 || $awayId <= 0) {
				continue;
			}

			$played = $m->e1 !== null && $m->e2 !== null && (int) ($m->count_result ?? 1) === 1;

			// více výsledků na buňku (např. dvojkolově) drží pole
			$matrix[$homeId][$awayId][] = (object) [
				'id'            => (int) $m->id,
				'home_result'   => $m->e1,
				'away_result'   => $m->e2,
				'rtype'         => (int) $m->rtype,          // 1 = prodloužení, 2 = nájezdy
				'decision'      => (int) $m->decision,       // != 0 = kontumace
				'v1'            => $m->v1,
				'v2'            => $m->v2,
				'cancel'        => (int) $m->cancel,
				'cancel_reason' => $m->cancel_reason,
				'new_match_id'  => (int) $m->new_match_id,
				'round_id'      => (int) $m->round_id,
				'round_name'    => $m->round_name,
				'round_code'    => $m->round_code,
				'played'        => $played,
			];
		}

		return $matrix;
	}

	public function getRankingCurve(int $projectId, int $divisionId = 0, int $projectTeam1Id = 0, int $projectTeam2Id = 0): array
	{
		$teams = [];
		$selected = array_values(array_filter([$projectTeam1Id, $projectTeam2Id]));

		foreach ($this->getProjectTeams($projectId, $divisionId) as $team) {
			$teams[(int) $team->id] = (object) [
				'projectteam_id' => (int) $team->id,
				'team_id' => (int) $team->team_id,
				'team_name' => $team->team_name,
				'team_short_name' => $team->team_short_name,
				'division_name' => $team->division_name,
				'points' => (int) ($team->start_points ?? 0),
				'played' => 0,
				'won' => 0,
				'drawn' => 0,
				'lost' => 0,
				'goals_for' => 0.0,
				'goals_against' => 0.0,
				'goal_diff' => 0.0,
				'positions' => [],
			];
		}

		if ($teams === []) {
			return [
				'rounds' => [],
				'teams' => [],
				'max_rank' => 0,
			];
		}

		$rounds = $this->getRounds($projectId);
		$matchesByRound = [];

		foreach ($this->getMatches($projectId) as $match) {
			$homeId = (int) $match->projectteam1_id;
			$awayId = (int) $match->projectteam2_id;

			if (!isset($teams[$homeId]) && !isset($teams[$awayId])) {
				continue;
			}

			if ($divisionId > 0 && (!isset($teams[$homeId]) || !isset($teams[$awayId]))) {
				continue;
			}

			$matchesByRound[(int) $match->round_id][] = $match;
		}

		$roundLabels = [];

		foreach ($rounds as $round) {
			$roundId = (int) $round->id;
			$roundLabels[] = (object) [
				'id' => $roundId,
				'name' => $round->name ?: (string) $round->roundcode,
			];

			foreach ($matchesByRound[$roundId] ?? [] as $match) {
				$this->applyMatchToCurveTeams($teams, $match);
			}

			$ranking = $this->rankCurveTeams($teams);

			foreach ($ranking as $rank => $team) {
				$teams[(int) $team->projectteam_id]->positions[$roundId] = $rank + 1;
			}
		}

		$teamList = array_values(array_filter(
			$teams,
			static fn (object $team): bool => $selected === [] || in_array((int) $team->projectteam_id, $selected, true)
		));
		usort(
			$teamList,
			static fn (object $a, object $b): int => [(end($a->positions) ?: 999), $a->team_name] <=> [(end($b->positions) ?: 999), $b->team_name]
		);

		return [
			'rounds' => $roundLabels,
			'teams' => $teamList,
			'max_rank' => count($teams),
		];
	}

	private function applyMatchToCurveTeams(array &$teams, object $match): void
	{
		if ($match->team1_result === null || $match->team2_result === null || (int) ($match->count_result ?? 1) !== 1) {
			return;
		}

		$homeId = (int) $match->projectteam1_id;
		$awayId = (int) $match->projectteam2_id;

		if (!isset($teams[$homeId], $teams[$awayId])) {
			return;
		}

		$homeGoals = (float) $match->team1_result;
		$awayGoals = (float) $match->team2_result;

		$teams[$homeId]->played++;
		$teams[$awayId]->played++;
		$teams[$homeId]->goals_for += $homeGoals;
		$teams[$homeId]->goals_against += $awayGoals;
		$teams[$awayId]->goals_for += $awayGoals;
		$teams[$awayId]->goals_against += $homeGoals;

		if ($homeGoals > $awayGoals) {
			$teams[$homeId]->won++;
			$teams[$awayId]->lost++;
			$teams[$homeId]->points += 3;
		} elseif ($homeGoals < $awayGoals) {
			$teams[$awayId]->won++;
			$teams[$homeId]->lost++;
			$teams[$awayId]->points += 3;
		} else {
			$teams[$homeId]->drawn++;
			$teams[$awayId]->drawn++;
			$teams[$homeId]->points++;
			$teams[$awayId]->points++;
		}

		$teams[$homeId]->goal_diff = $teams[$homeId]->goals_for - $teams[$homeId]->goals_against;
		$teams[$awayId]->goal_diff = $teams[$awayId]->goals_for - $teams[$awayId]->goals_against;
	}

	private function rankCurveTeams(array $teams): array
	{
		$list = array_values($teams);

		usort(
			$list,
			static fn (object $a, object $b): int => [$b->points, $b->goal_diff, $b->goals_for, $a->team_name] <=> [$a->points, $a->goal_diff, $a->goals_for, $b->team_name]
		);

		return $list;
	}

	public function getMatchEvents(int $matchId): array
	{
		$db = $this->getDatabase();
		$query = $db->getQuery(true)
			->select([
				'e.*',
				$db->quoteName('et.name', 'event_name'),
				$db->quoteName('et.icon', 'event_icon'),
				$db->quoteName('t.name', 'team_name'),
				$db->quoteName('p.id', 'person_id'),
				'COALESCE(NULLIF(CONCAT_WS(' . $db->quote(' ') . ', ' . $db->quoteName('p.firstname') . ', ' . $db->quoteName('p.lastname') . '), ' . $db->quote('') . '), NULLIF(' . $db->quoteName('e.external_person_name') . ', ' . $db->quote('') . ')) AS person_name',
			])
			->from($db->quoteName('#__joomleague_match_event', 'e'))
			->join('LEFT', $db->quoteName('#__joomleague_eventtype', 'et') . ' ON ' . $db->quoteName('et.id') . ' = ' . $db->quoteName('e.event_type_id'))
			->join('LEFT', $db->quoteName('#__joomleague_project_team', 'pt') . ' ON ' . $db->quoteName('pt.id') . ' = ' . $db->quoteName('e.projectteam_id'))
			->join('LEFT', $db->quoteName('#__joomleague_team', 't') . ' ON ' . $db->quoteName('t.id') . ' = ' . $db->quoteName('pt.team_id'))
			->join('LEFT', $db->quoteName('#__joomleague_team_player', 'tp') . ' ON ' . $db->quoteName('tp.id') . ' = ' . $db->quoteName('e.teamplayer_id'))
			->join('LEFT', $db->quoteName('#__joomleague_person', 'p') . ' ON ' . $db->quoteName('p.id') . ' = ' . $db->quoteName('tp.person_id'))
			->where($db->quoteName('e.match_id') . ' = :match_id')
			->bind(':match_id', $matchId, ParameterType::INTEGER)
			->order('CAST(NULLIF(' . $db->quoteName('e.event_time') . ', ' . $db->quote('') . ') AS UNSIGNED) ASC, ' . $db->quoteName('e.id') . ' ASC');

		return $db->setQuery($query)->loadObjectList();
	}

	public function getClubs(): array
	{
		$db = $this->getDatabase();
		$query = $db->getQuery(true)
			->select(['c.*', 'COUNT(t.id) AS teams'])
			->from($db->quoteName('#__joomleague_club', 'c'))
			->join('LEFT', $db->quoteName('#__joomleague_team', 't') . ' ON ' . $db->quoteName('t.club_id') . ' = ' . $db->quoteName('c.id'))
			->group($db->quoteName('c.id'))
			->order($db->quoteName('c.name') . ' ASC');

		return $db->setQuery($query)->loadObjectList();
	}

	public function getClub(?int $clubId): ?object
	{
		if (empty($clubId)) {
			return null;
		}

		$db = $this->getDatabase();
		$query = $db->getQuery(true)
			->select(['c.*', $db->quoteName('pg.name', 'playground_name')])
			->from($db->quoteName('#__joomleague_club', 'c'))
			->join('LEFT', $db->quoteName('#__joomleague_playground', 'pg') . ' ON ' . $db->quoteName('pg.id') . ' = ' . $db->quoteName('c.standard_playground'))
			->where($db->quoteName('c.id') . ' = :id')
			->bind(':id', $clubId, ParameterType::INTEGER);

		$item = $db->setQuery($query)->loadObject();

		return $item ?: null;
	}

	public function getClubTeams(int $clubId): array
	{
		$db = $this->getDatabase();
		$query = $db->getQuery(true)
			->select([
				't.*',
				$db->quoteName('pt.id', 'projectteam_id'),
				$db->quoteName('p.id', 'project_id'),
				$db->quoteName('p.name', 'project_name'),
				$db->quoteName('l.name', 'league_name'),
				$db->quoteName('s.name', 'season_name'),
				$db->quoteName('d.name', 'division_name'),
			])
			->from($db->quoteName('#__joomleague_team', 't'))
			->join('LEFT', $db->quoteName('#__joomleague_project_team', 'pt') . ' ON ' . $db->quoteName('pt.team_id') . ' = ' . $db->quoteName('t.id'))
			->join('LEFT', $db->quoteName('#__joomleague_project', 'p') . ' ON ' . $db->quoteName('p.id') . ' = ' . $db->quoteName('pt.project_id'))
			->join('LEFT', $db->quoteName('#__joomleague_league', 'l') . ' ON ' . $db->quoteName('l.id') . ' = ' . $db->quoteName('p.league_id'))
			->join('LEFT', $db->quoteName('#__joomleague_season', 's') . ' ON ' . $db->quoteName('s.id') . ' = ' . $db->quoteName('p.season_id'))
			->join('LEFT', $db->quoteName('#__joomleague_division', 'd') . ' ON ' . $db->quoteName('d.id') . ' = ' . $db->quoteName('pt.division_id'))
			->where($db->quoteName('t.club_id') . ' = :club_id')
			->bind(':club_id', $clubId, ParameterType::INTEGER)
			->order($db->quoteName('t.name') . ' ASC, ' . $db->quoteName('p.start_date') . ' DESC, ' . $db->quoteName('p.id') . ' DESC');

		return $db->setQuery($query)->loadObjectList();
	}

	public function getClubPlaygrounds(int $clubId): array
	{
		$db = $this->getDatabase();
		$subquery = $db->getQuery(true)
			->select('DISTINCT ' . $db->quoteName('pt.standard_playground'))
			->from($db->quoteName('#__joomleague_project_team', 'pt'))
			->join('INNER', $db->quoteName('#__joomleague_team', 't') . ' ON ' . $db->quoteName('t.id') . ' = ' . $db->quoteName('pt.team_id'))
			->where($db->quoteName('t.club_id') . ' = :club_id_projectteams')
			->where($db->quoteName('pt.standard_playground') . ' IS NOT NULL')
			->where($db->quoteName('pt.standard_playground') . ' > 0');

		$query = $db->getQuery(true)
			->select('DISTINCT ' . $db->quoteName('pg') . '.*')
			->from($db->quoteName('#__joomleague_playground', 'pg'))
			->join('LEFT', $db->quoteName('#__joomleague_club', 'c') . ' ON ' . $db->quoteName('c.id') . ' = :club_id_standard')
			->where('('
				. $db->quoteName('pg.club_id') . ' = :club_id_playground'
				. ' OR ' . $db->quoteName('pg.id') . ' = ' . $db->quoteName('c.standard_playground')
				. ' OR ' . $db->quoteName('pg.id') . ' IN (' . $subquery . ')'
				. ')')
			->order($db->quoteName('pg.name') . ' ASC')
			->bind(':club_id_standard', $clubId, ParameterType::INTEGER)
			->bind(':club_id_playground', $clubId, ParameterType::INTEGER)
			->bind(':club_id_projectteams', $clubId, ParameterType::INTEGER);

		return $db->setQuery($query)->loadObjectList();
	}

	public function getPlayground(?int $playgroundId): ?object
	{
		if (empty($playgroundId)) {
			return null;
		}

		$db = $this->getDatabase();
		$query = $db->getQuery(true)
			->select(['pg.*', $db->quoteName('c.name', 'club_name')])
			->from($db->quoteName('#__joomleague_playground', 'pg'))
			->join('LEFT', $db->quoteName('#__joomleague_club', 'c') . ' ON ' . $db->quoteName('c.id') . ' = ' . $db->quoteName('pg.club_id'))
			->where($db->quoteName('pg.id') . ' = :id')
			->bind(':id', $playgroundId, ParameterType::INTEGER);

		$item = $db->setQuery($query)->loadObject();

		return $item ?: null;
	}

	public function getReferees(int $projectId): array
	{
		$db = $this->getDatabase();
		$query = $db->getQuery(true)
			->select([
				'pr.*',
				'CONCAT_WS(' . $db->quote(' ') . ', ' . $db->quoteName('p.firstname') . ', ' . $db->quoteName('p.lastname') . ') AS person_name',
				$db->quoteName('pos.name', 'position_name'),
			])
			->from($db->quoteName('#__joomleague_project_referee', 'pr'))
			->join('INNER', $db->quoteName('#__joomleague_person', 'p') . ' ON ' . $db->quoteName('p.id') . ' = ' . $db->quoteName('pr.person_id'))
			->join('LEFT', $db->quoteName('#__joomleague_project_position', 'pp') . ' ON ' . $db->quoteName('pp.id') . ' = ' . $db->quoteName('pr.project_position_id'))
			->join('LEFT', $db->quoteName('#__joomleague_position', 'pos') . ' ON ' . $db->quoteName('pos.id') . ' = ' . $db->quoteName('pp.position_id'))
			->where($db->quoteName('pr.project_id') . ' = :project_id')
			->where($db->quoteName('pr.published') . ' = 1')
			->bind(':project_id', $projectId, ParameterType::INTEGER)
			->order($db->quoteName('p.lastname') . ' ASC');

		return $db->setQuery($query)->loadObjectList();
	}

	public function getStats(int $projectId): array
	{
		$db = $this->getDatabase();
		$query = $db->getQuery(true)
			->select([
				$db->quoteName('s.name', 'statistic_name'),
				$db->quoteName('s.short', 'statistic_short'),
				'SUM(ms.value) AS value',
				$db->quoteName('t.name', 'team_name'),
				'CONCAT_WS(' . $db->quote(' ') . ', ' . $db->quoteName('p.firstname') . ', ' . $db->quoteName('p.lastname') . ') AS person_name',
			])
			->from($db->quoteName('#__joomleague_match_statistic', 'ms'))
			->join('INNER', $db->quoteName('#__joomleague_statistic', 's') . ' ON ' . $db->quoteName('s.id') . ' = ' . $db->quoteName('ms.statistic_id'))
			->join('INNER', $db->quoteName('#__joomleague_match', 'm') . ' ON ' . $db->quoteName('m.id') . ' = ' . $db->quoteName('ms.match_id'))
			->join('INNER', $db->quoteName('#__joomleague_round', 'r') . ' ON ' . $db->quoteName('r.id') . ' = ' . $db->quoteName('m.round_id'))
			->join('LEFT', $db->quoteName('#__joomleague_project_team', 'pt') . ' ON ' . $db->quoteName('pt.id') . ' = ' . $db->quoteName('ms.projectteam_id'))
			->join('LEFT', $db->quoteName('#__joomleague_team', 't') . ' ON ' . $db->quoteName('t.id') . ' = ' . $db->quoteName('pt.team_id'))
			->join('LEFT', $db->quoteName('#__joomleague_team_player', 'tp') . ' ON ' . $db->quoteName('tp.id') . ' = ' . $db->quoteName('ms.teamplayer_id'))
			->join('LEFT', $db->quoteName('#__joomleague_person', 'p') . ' ON ' . $db->quoteName('p.id') . ' = ' . $db->quoteName('tp.person_id'))
			->where($db->quoteName('r.project_id') . ' = :project_id')
			->bind(':project_id', $projectId, ParameterType::INTEGER)
			->group([$db->quoteName('s.id'), $db->quoteName('ms.teamplayer_id'), $db->quoteName('ms.projectteam_id')])
			->order($db->quoteName('s.name') . ' ASC, value DESC');

		return $db->setQuery($query)->loadObjectList();
	}

	public function getProjectStatistics(int $projectId): array
	{
		$db = $this->getDatabase();
		$query = $db->getQuery(true)
			->select([
				'DISTINCT ' . $db->quoteName('s.id'),
				$db->quoteName('s.name'),
				$db->quoteName('s.short'),
				$db->quoteName('s.icon'),
			])
			->from($db->quoteName('#__joomleague_statistic', 's'))
			->join('INNER', $db->quoteName('#__joomleague_match_statistic', 'ms') . ' ON ' . $db->quoteName('ms.statistic_id') . ' = ' . $db->quoteName('s.id'))
			->join('INNER', $db->quoteName('#__joomleague_match', 'm') . ' ON ' . $db->quoteName('m.id') . ' = ' . $db->quoteName('ms.match_id'))
			->join('INNER', $db->quoteName('#__joomleague_round', 'r') . ' ON ' . $db->quoteName('r.id') . ' = ' . $db->quoteName('m.round_id'))
			->where($db->quoteName('r.project_id') . ' = :project_id')
			->where($db->quoteName('m.published') . ' = 1')
			->where($db->quoteName('s.published') . ' = 1')
			->bind(':project_id', $projectId, ParameterType::INTEGER)
			->order($db->quoteName('s.ordering') . ' ASC, ' . $db->quoteName('s.name') . ' ASC');

		return $db->setQuery($query)->loadObjectList();
	}

	public function getStatsRankings(int $projectId, int $statisticId = 0, int $projectTeamId = 0): array
	{
		$db = $this->getDatabase();
		$query = $db->getQuery(true)
			->select([
				$db->quoteName('s.id', 'statistic_id'),
				$db->quoteName('s.name', 'statistic_name'),
				$db->quoteName('s.short', 'statistic_short'),
				$db->quoteName('s.icon', 'statistic_icon'),
				$db->quoteName('tp.id', 'teamplayer_id'),
				$db->quoteName('tp.person_id'),
				$db->quoteName('ms.projectteam_id'),
				$db->quoteName('t.name', 'team_name'),
				$db->quoteName('p.firstname'),
				$db->quoteName('p.lastname'),
				$db->quoteName('p.nickname'),
				$db->quoteName('p.country', 'person_country'),
				'CONCAT_WS(' . $db->quote(' ') . ', ' . $db->quoteName('p.firstname') . ', ' . $db->quoteName('p.lastname') . ') AS person_name',
				'SUM(' . $db->quoteName('ms.value') . ') AS value',
			])
			->from($db->quoteName('#__joomleague_match_statistic', 'ms'))
			->join('INNER', $db->quoteName('#__joomleague_statistic', 's') . ' ON ' . $db->quoteName('s.id') . ' = ' . $db->quoteName('ms.statistic_id'))
			->join('INNER', $db->quoteName('#__joomleague_match', 'm') . ' ON ' . $db->quoteName('m.id') . ' = ' . $db->quoteName('ms.match_id'))
			->join('INNER', $db->quoteName('#__joomleague_round', 'r') . ' ON ' . $db->quoteName('r.id') . ' = ' . $db->quoteName('m.round_id'))
			->join('LEFT', $db->quoteName('#__joomleague_project_team', 'pt') . ' ON ' . $db->quoteName('pt.id') . ' = ' . $db->quoteName('ms.projectteam_id'))
			->join('LEFT', $db->quoteName('#__joomleague_team', 't') . ' ON ' . $db->quoteName('t.id') . ' = ' . $db->quoteName('pt.team_id'))
			->join('LEFT', $db->quoteName('#__joomleague_team_player', 'tp') . ' ON ' . $db->quoteName('tp.id') . ' = ' . $db->quoteName('ms.teamplayer_id'))
			->join('LEFT', $db->quoteName('#__joomleague_person', 'p') . ' ON ' . $db->quoteName('p.id') . ' = ' . $db->quoteName('tp.person_id'))
			->where($db->quoteName('r.project_id') . ' = :project_id')
			->where($db->quoteName('m.published') . ' = 1')
			->where($db->quoteName('s.published') . ' = 1')
			->group([
				$db->quoteName('s.id'),
				$db->quoteName('s.name'),
				$db->quoteName('s.short'),
				$db->quoteName('s.icon'),
				$db->quoteName('tp.id'),
				$db->quoteName('tp.person_id'),
				$db->quoteName('ms.projectteam_id'),
				$db->quoteName('t.name'),
				$db->quoteName('p.firstname'),
				$db->quoteName('p.lastname'),
				$db->quoteName('p.nickname'),
				$db->quoteName('p.country'),
			])
			->order($db->quoteName('s.ordering') . ' ASC, value DESC, ' . $db->quoteName('p.lastname') . ' ASC')
			->bind(':project_id', $projectId, ParameterType::INTEGER);

		if ($statisticId > 0) {
			$query->where($db->quoteName('ms.statistic_id') . ' = :statistic_id')
				->bind(':statistic_id', $statisticId, ParameterType::INTEGER);
		}

		if ($projectTeamId > 0) {
			$query->where($db->quoteName('ms.projectteam_id') . ' = :projectteam_id')
				->bind(':projectteam_id', $projectTeamId, ParameterType::INTEGER);
		}

		return $db->setQuery($query)->loadObjectList();
	}

	public function getProjectEventTypes(int $projectId): array
	{
		$db = $this->getDatabase();
		$query = $db->getQuery(true)
			->select([
				'DISTINCT ' . $db->quoteName('et.id'),
				$db->quoteName('et.name'),
				$db->quoteName('et.icon'),
				$db->quoteName('et.direction'),
			])
			->from($db->quoteName('#__joomleague_eventtype', 'et'))
			->join('INNER', $db->quoteName('#__joomleague_match_event', 'e') . ' ON ' . $db->quoteName('e.event_type_id') . ' = ' . $db->quoteName('et.id'))
			->join('INNER', $db->quoteName('#__joomleague_match', 'm') . ' ON ' . $db->quoteName('m.id') . ' = ' . $db->quoteName('e.match_id'))
			->join('INNER', $db->quoteName('#__joomleague_round', 'r') . ' ON ' . $db->quoteName('r.id') . ' = ' . $db->quoteName('m.round_id'))
			->where($db->quoteName('r.project_id') . ' = :project_id')
			->where($db->quoteName('m.published') . ' = 1')
			->where($db->quoteName('et.published') . ' = 1')
			->bind(':project_id', $projectId, ParameterType::INTEGER)
			->order($db->quoteName('et.ordering') . ' ASC, ' . $db->quoteName('et.name') . ' ASC');

		return $db->setQuery($query)->loadObjectList();
	}

	public function getEventRankings(int $projectId, int $eventTypeId = 0, int $projectTeamId = 0, int $matchId = 0): array
	{
		$db = $this->getDatabase();
		$query = $db->getQuery(true)
			->select([
				$db->quoteName('et.id', 'event_type_id'),
				$db->quoteName('et.name', 'event_name'),
				$db->quoteName('et.icon', 'event_icon'),
				$db->quoteName('tp.id', 'teamplayer_id'),
				$db->quoteName('tp.person_id'),
				$db->quoteName('e.projectteam_id'),
				$db->quoteName('t.name', 'team_name'),
				$db->quoteName('p.firstname'),
				$db->quoteName('p.lastname'),
				$db->quoteName('p.nickname'),
				$db->quoteName('p.country', 'person_country'),
				'CONCAT_WS(' . $db->quote(' ') . ', ' . $db->quoteName('p.firstname') . ', ' . $db->quoteName('p.lastname') . ') AS person_name',
				'COUNT(' . $db->quoteName('e.id') . ') AS value',
			])
			->from($db->quoteName('#__joomleague_match_event', 'e'))
			->join('INNER', $db->quoteName('#__joomleague_eventtype', 'et') . ' ON ' . $db->quoteName('et.id') . ' = ' . $db->quoteName('e.event_type_id'))
			->join('INNER', $db->quoteName('#__joomleague_match', 'm') . ' ON ' . $db->quoteName('m.id') . ' = ' . $db->quoteName('e.match_id'))
			->join('INNER', $db->quoteName('#__joomleague_round', 'r') . ' ON ' . $db->quoteName('r.id') . ' = ' . $db->quoteName('m.round_id'))
			->join('LEFT', $db->quoteName('#__joomleague_project_team', 'pt') . ' ON ' . $db->quoteName('pt.id') . ' = ' . $db->quoteName('e.projectteam_id'))
			->join('LEFT', $db->quoteName('#__joomleague_team', 't') . ' ON ' . $db->quoteName('t.id') . ' = ' . $db->quoteName('pt.team_id'))
			->join('LEFT', $db->quoteName('#__joomleague_team_player', 'tp') . ' ON ' . $db->quoteName('tp.id') . ' = ' . $db->quoteName('e.teamplayer_id'))
			->join('LEFT', $db->quoteName('#__joomleague_person', 'p') . ' ON ' . $db->quoteName('p.id') . ' = ' . $db->quoteName('tp.person_id'))
			->where($db->quoteName('r.project_id') . ' = :project_id')
			->where($db->quoteName('m.published') . ' = 1')
			->where($db->quoteName('et.published') . ' = 1')
			->group([
				$db->quoteName('et.id'),
				$db->quoteName('et.name'),
				$db->quoteName('et.icon'),
				$db->quoteName('tp.id'),
				$db->quoteName('tp.person_id'),
				$db->quoteName('e.projectteam_id'),
				$db->quoteName('t.name'),
				$db->quoteName('p.firstname'),
				$db->quoteName('p.lastname'),
				$db->quoteName('p.nickname'),
				$db->quoteName('p.country'),
			])
			->order($db->quoteName('et.ordering') . ' ASC, value DESC, ' . $db->quoteName('p.lastname') . ' ASC')
			->bind(':project_id', $projectId, ParameterType::INTEGER);

		if ($eventTypeId > 0) {
			$query->where($db->quoteName('e.event_type_id') . ' = :event_type_id')
				->bind(':event_type_id', $eventTypeId, ParameterType::INTEGER);
		}

		if ($projectTeamId > 0) {
			$query->where($db->quoteName('e.projectteam_id') . ' = :projectteam_id')
				->bind(':projectteam_id', $projectTeamId, ParameterType::INTEGER);
		}

		if ($matchId > 0) {
			$query->where($db->quoteName('e.match_id') . ' = :match_id')
				->bind(':match_id', $matchId, ParameterType::INTEGER);
		}

		return $db->setQuery($query)->loadObjectList();
	}

	public function getStandings(int $projectId, string $scope = 'total', ?string $rankingOrder = null, int $divisionId = 0, int $asOfRoundId = 0): array
	{
		$scope = in_array($scope, ['total', 'home', 'away'], true) ? $scope : 'total';
		$cacheKey = implode('_', ['standings', $projectId, $scope, $divisionId, $asOfRoundId, md5((string) $rankingOrder)]);

		try {
			$cache = Factory::getContainer()->get(CacheControllerFactoryInterface::class)
				->createCacheController('callback', ['defaultgroup' => 'com_joomleague', 'caching' => true, 'lifetime' => 5]);

			return $cache->get(
				fn (): array => $this->computeStandings($projectId, $scope, $rankingOrder, $divisionId, $asOfRoundId),
				[],
				$cacheKey,
				false
			);
		} catch (\Exception) {
			// Cache je jen optimalizace – při jakémkoliv problému s cache vrstvou počítáme normálně.
			return $this->computeStandings($projectId, $scope, $rankingOrder, $divisionId, $asOfRoundId);
		}
	}

	/**
	 * Chronologicky seřazený seznam kol, ve kterých padl alespoň jeden výsledek (jen zápasy
	 * týmů z dané divize, pokud je zadaná) – podklad pro historickou navigaci "tabulka po kole X".
	 *
	 * @return  array<int, array{id: int, name: string, date: string}>
	 */
	public function getStandingsRounds(int $projectId, int $divisionId = 0): array
	{
		$teamIds = [];

		foreach ($this->getProjectTeams($projectId, $divisionId) as $team) {
			$teamIds[(int) $team->id] = true;
		}

		$rounds = [];

		foreach ($this->getMatches($projectId) as $match) {
			if ($match->team1_result === null || $match->team2_result === null || (int) $match->count_result !== 1) {
				continue;
			}

			$homeId = (int) $match->projectteam1_id;
			$awayId = (int) $match->projectteam2_id;

			if (!isset($teamIds[$homeId], $teamIds[$awayId])) {
				continue;
			}

			$roundId = (int) ($match->round_id ?? 0);
			$date = (string) ($match->match_date ?? '');

			if ($roundId < 1 || $date === '') {
				continue;
			}

			if (!isset($rounds[$roundId]) || $date > $rounds[$roundId]['date']) {
				$rounds[$roundId] = [
					'id' => $roundId,
					'name' => (string) ($match->round_name ?? ''),
					'date' => $date,
					'roundcode' => (int) ($match->roundcode ?? 0),
				];
			}
		}

		$list = array_values($rounds);

		// round.ordering je v reálných datech vždy 0 a číslo v názvu kola ("1. kolo")
		// neodpovídá skutečnému pořadí sezóny (kolo pojmenované "1. kolo" může být
		// odehráno jako poslední, viz odložené zápasy). round.roundcode je pole, které
		// admin sám vyplňuje jako pořadové číslo kola v sezóně (ověřeno na reálných
		// datech - odpovídá 1:1 chronologickému pořadí i adminem zobrazenému "Číslo
		// kola"), takže je jediným spolehlivým klíčem pro řazení. Pokud by admin
		// roundcode nevyplnil (všechny 0), spadneme zpět na datum odehrání.
		$allZero = true;

		foreach ($list as $round) {
			if ($round['roundcode'] !== 0) {
				$allZero = false;

				break;
			}
		}

		if ($allZero) {
			usort($list, static fn (array $a, array $b): int => $a['date'] <=> $b['date']);
		} else {
			usort($list, static fn (array $a, array $b): int => $a['roundcode'] <=> $b['roundcode']);
		}

		return $list;
	}

	/**
	 * @return  array<int, object>
	 */
	private function computeStandings(int $projectId, string $scope, ?string $rankingOrder, int $divisionId, int $asOfRoundId = 0): array
	{
		$project = $this->getProject($projectId);

		$pointsRegular = $this->parsePointsTriple((string) ($project->points_after_regular_time ?? ''), [3, 1, 0]);
		$allowAddTime = (int) ($project->allow_add_time ?? 0) === 1;
		$pointsAddTime = $allowAddTime ? $this->parsePointsTriple((string) ($project->points_after_add_time ?? ''), $pointsRegular) : $pointsRegular;
		$pointsPenalty = $allowAddTime ? $this->parsePointsTriple((string) ($project->points_after_penalty ?? ''), $pointsRegular) : $pointsRegular;

		$teams = [];

		foreach ($this->getProjectTeams($projectId, $divisionId) as $team) {
			$teams[(int) $team->id] = $this->newStandingsRow($team);
		}

		$playedMatches = [];
		// "Poslední kolo" se určuje podle data zápasu, ne podle round.ordering – to pole
		// v reálných datech často zůstává 0 u všech kol a jako klíč k řazení je nespolehlivé.
		$roundLatestDate = [];

		foreach ($this->getMatches($projectId) as $match) {
			if ($match->team1_result === null || $match->team2_result === null || (int) $match->count_result !== 1) {
				continue;
			}

			$homeId = (int) $match->projectteam1_id;
			$awayId = (int) $match->projectteam2_id;

			if (!isset($teams[$homeId], $teams[$awayId])) {
				continue;
			}

			$playedMatches[] = $match;
			$roundId = (int) ($match->round_id ?? 0);
			$matchDate = (string) ($match->match_date ?? '');

			if ($matchDate !== '' && (!isset($roundLatestDate[$roundId]) || $matchDate > $roundLatestDate[$roundId])) {
				$roundLatestDate[$roundId] = $matchDate;
			}
		}

		// Historická tabulka "k danému kolu" – ponechá jen zápasy z kol odehraných nejpozději
		// do data vybraného kola (včetně), zbytek logiky (aktuální/předchozí kolo) na to navazuje
		// beze změny, jen počítá s tímto (případně užším) výběrem zápasů.
		if ($asOfRoundId > 0 && isset($roundLatestDate[$asOfRoundId])) {
			$cutoffDate = $roundLatestDate[$asOfRoundId];
			$playedMatches = array_values(array_filter(
				$playedMatches,
				static fn (object $m): bool => ($roundLatestDate[(int) ($m->round_id ?? 0)] ?? '') <= $cutoffDate
			));
			$roundLatestDate = array_filter($roundLatestDate, static fn (string $date): bool => $date <= $cutoffDate);
		}

		$currentRoundId = null;
		$currentRoundDate = null;

		foreach ($roundLatestDate as $roundId => $date) {
			if ($currentRoundDate === null || $date > $currentRoundDate) {
				$currentRoundDate = $date;
				$currentRoundId = $roundId;
			}
		}

		foreach ($playedMatches as $match) {
			$homeId = (int) $match->projectteam1_id;
			$awayId = (int) $match->projectteam2_id;
			$isPrevious = $currentRoundId !== null && (int) ($match->round_id ?? 0) !== $currentRoundId;

			if ($scope !== 'away') {
				$this->applyMatchResult($teams[$homeId], $match, true, $pointsRegular, $pointsAddTime, $pointsPenalty, false);

				if ($isPrevious) {
					$this->applyMatchResult($teams[$homeId], $match, true, $pointsRegular, $pointsAddTime, $pointsPenalty, true);
				}
			}

			if ($scope !== 'home') {
				$this->applyMatchResult($teams[$awayId], $match, false, $pointsRegular, $pointsAddTime, $pointsPenalty, false);

				if ($isPrevious) {
					$this->applyMatchResult($teams[$awayId], $match, false, $pointsRegular, $pointsAddTime, $pointsPenalty, true);
				}
			}
		}

		foreach ($teams as $team) {
			$team->goal_diff = $team->goals_for - $team->goals_against;
			$team->previous_goal_diff = $team->previous_goals_for - $team->previous_goals_against;
			$team->legs_diff = $team->legs_for - $team->legs_against;
		}

		$criteria = $this->parseRankingOrder($rankingOrder);
		$matchesByTeamPair = $this->groupMatchesByTeamPair($playedMatches);

		$list = array_values($teams);
		usort($list, fn (object $a, object $b): int => $this->compareStandings($a, $b, $criteria, $matchesByTeamPair));

		foreach ($list as $index => $team) {
			$team->rank = $index + 1;
		}

		$previousList = $list;
		usort($previousList, fn (object $a, object $b): int => $this->comparePreviousStandings($a, $b));

		foreach ($previousList as $index => $team) {
			$team->previous_rank = $team->previous_played > 0 ? $index + 1 : null;
		}

		return $list;
	}

	private function newStandingsRow(object $team): object
	{
		$startPoints = (int) ($team->start_points ?? 0);

		return (object) [
			'projectteam_id' => (int) $team->id,
			'team_name' => $team->team_name,
			'team_short_name' => $team->team_short_name ?? '',
			'team_middle_name' => $team->team_middle_name ?? '',
			'team_picture' => $team->team_picture ?? '',
			'projectteam_picture' => $team->picture ?? '',
			'club_id' => (int) ($team->club_id ?? 0),
			'club_name' => $team->club_name ?? '',
			'club_country' => $team->club_country ?? '',
			'club_logo_small' => $team->club_logo_small ?? '',
			'club_logo_middle' => $team->club_logo_middle ?? '',
			'club_logo_big' => $team->club_logo_big ?? '',
			'played' => 0,
			'won' => 0,
			'drawn' => 0,
			'lost' => 0,
			'goals_for' => 0.0,
			'goals_against' => 0.0,
			'goal_diff' => 0.0,
			'points' => $startPoints,
			'previous_played' => 0,
			'previous_won' => 0,
			'previous_drawn' => 0,
			'previous_lost' => 0,
			'previous_goals_for' => 0.0,
			'previous_goals_against' => 0.0,
			'previous_goal_diff' => 0.0,
			'previous_points' => $startPoints,
			'cnt_wot' => 0,
			'cnt_wso' => 0,
			'cnt_lot' => 0,
			'cnt_lso' => 0,
			'sum_bonus' => 0.0,
			'legs_for' => 0.0,
			'legs_against' => 0.0,
			'legs_diff' => 0.0,
			'start_points' => $startPoints,
			'neg_points' => (int) ($team->neg_points_finally ?? 0),
			'rank' => 0,
			'previous_rank' => null,
		];
	}

	/**
	 * Zapíše výsledek jednoho zápasu do akumulátoru daného týmu. Rozhoduje typ výsledku
	 * (řádná hrací doba / prodloužení / nájezdy), podle kterého se použije odpovídající
	 * bodová tabulka projektu a — u prodloužení/nájezdů — se navýší cnt_wot/wso/lot/lso.
	 */
	private function applyMatchResult(object $team, object $match, bool $isHome, array $pointsRegular, array $pointsAddTime, array $pointsPenalty, bool $previous): void
	{
		$prefix = $previous ? 'previous_' : '';
		$ownFinal = (float) ($isHome ? $match->team1_result : $match->team2_result);
		$oppFinal = (float) ($isHome ? $match->team2_result : $match->team1_result);

		$team->{$prefix . 'played'}++;
		$team->{$prefix . 'goals_for'} += $ownFinal;
		$team->{$prefix . 'goals_against'} += $oppFinal;

		$resultType = (int) ($match->match_result_type ?? 0);
		$ownSo = $isHome ? $match->team1_result_so : $match->team2_result_so;
		$oppSo = $isHome ? $match->team2_result_so : $match->team1_result_so;
		$ownOt = $isHome ? $match->team1_result_ot : $match->team2_result_ot;
		$oppOt = $isHome ? $match->team2_result_ot : $match->team1_result_ot;

		$bucket = null;
		// Řádná hrací doba vždy rozhoduje jako první; prodloužení/nájezdy nastupují
		// jen při remíze v řádné hrací době (stejné pořadí jako v originálu).
		$decided = $ownFinal <=> $oppFinal;

		if ($decided === 0) {
			if ($resultType === 2 && $ownSo !== null && $oppSo !== null) {
				$decided = (float) $ownSo <=> (float) $oppSo;
				$bucket = 'so';
			} elseif ($resultType === 1 && $ownOt !== null && $oppOt !== null) {
				$decided = (float) $ownOt <=> (float) $oppOt;
				$bucket = 'ot';
			}
		}

		$points = match ($resultType) {
			1 => $pointsAddTime,
			2 => $pointsPenalty,
			default => $pointsRegular,
		};

		if ($decided > 0) {
			$team->{$prefix . 'won'}++;
			$team->{$prefix . 'points'} += $points[0];

			if (!$previous) {
				if ($bucket === 'ot') {
					$team->cnt_wot++;
				} elseif ($bucket === 'so') {
					$team->cnt_wso++;
				}
			}
		} elseif ($decided < 0) {
			$team->{$prefix . 'lost'}++;
			$team->{$prefix . 'points'} += $points[2];

			if (!$previous) {
				if ($bucket === 'ot') {
					$team->cnt_lot++;
				} elseif ($bucket === 'so') {
					$team->cnt_lso++;
				}
			}
		} else {
			$team->{$prefix . 'drawn'}++;
			$team->{$prefix . 'points'} += $points[1];
		}

		if (!$previous) {
			$team->sum_bonus += (float) ($isHome ? ($match->team1_bonus ?? 0) : ($match->team2_bonus ?? 0));
			$team->legs_for += (float) ($isHome ? ($match->team1_legs ?? 0) : ($match->team2_legs ?? 0));
			$team->legs_against += (float) ($isHome ? ($match->team2_legs ?? 0) : ($match->team1_legs ?? 0));
		}
	}

	/**
	 * @return  array{0: int, 1: int, 2: int}
	 */
	private function parsePointsTriple(string $raw, array $default): array
	{
		$parts = array_map('trim', explode(',', $raw));

		if (count($parts) !== 3 || !is_numeric($parts[0]) || !is_numeric($parts[1]) || !is_numeric($parts[2])) {
			return $default;
		}

		return [(int) $parts[0], (int) $parts[1], (int) $parts[2]];
	}

	/**
	 * @return  array<int, string>
	 */
	private function parseRankingOrder(?string $raw): array
	{
		$default = ['POINTS', 'DIFF', 'FOR'];

		if ($raw === null || trim($raw) === '') {
			return $default;
		}

		$criteria = array_values(array_filter(array_map('trim', explode(',', strtoupper($raw)))));

		return $criteria !== [] ? $criteria : $default;
	}

	/**
	 * @return  array<string, array<int, object>>
	 */
	private function groupMatchesByTeamPair(array $matches): array
	{
		$grouped = [];

		foreach ($matches as $match) {
			$t1 = (int) $match->projectteam1_id;
			$t2 = (int) $match->projectteam2_id;
			$key = min($t1, $t2) . ':' . max($t1, $t2);
			$grouped[$key][] = $match;
		}

		return $grouped;
	}

	/**
	 * Vrátí [hodnota, směr] pro dané kritérium řazení (směr: 1 = vyšší je lepší, -1 = nižší je lepší),
	 * nebo null pro kritéria, která nelze v aktuálním datovém modelu vyhodnotit (např. GB je samo
	 * odvozené od výsledného pořadí, takže jako řadicí kritérium nedává smysl).
	 *
	 * @return  array{0: float, 1: int}|null
	 */
	private function criterionValue(object $team, string $criterion): ?array
	{
		return match ($criterion) {
			'POINTS' => [(float) $team->points, 1],
			'DIFF' => [$team->goal_diff, 1],
			'FOR' => [$team->goals_for, 1],
			'AGAINST' => [$team->goals_against, -1],
			'SCOREPCT' => [$team->goals_against > 0 ? $team->goals_for / $team->goals_against * 100 : $team->goals_for * 100, 1],
			'PLAYED' => [(float) $team->played, 1],
			'PLAYEDASC' => [(float) $team->played, -1],
			'WINS' => [(float) $team->won, 1],
			'BONUS' => [$team->sum_bonus, 1],
			'SCOREAVG' => [$team->played > 0 ? $team->goals_for / $team->played : 0.0, 1],
			'WINPCT' => [$team->played > 0 ? $team->won / $team->played * 100 : 0.0, 1],
			'LEGS_DIFF' => [$team->legs_diff, 1],
			'LEGS_WIN' => [$team->legs_for, 1],
			'LEGS_RATIO' => [$team->legs_against > 0 ? $team->legs_for / $team->legs_against : $team->legs_for, 1],
			default => null,
		};
	}

	private function compareStandings(object $a, object $b, array $criteria, array $matchesByTeamPair): int
	{
		foreach ($criteria as $criterion) {
			if (in_array($criterion, ['H2H', 'H2H_DIFF'], true)) {
				$result = $this->compareHeadToHead($a, $b, $criterion, $matchesByTeamPair);

				if ($result !== 0) {
					return $result;
				}

				continue;
			}

			$valueA = $this->criterionValue($a, $criterion);
			$valueB = $this->criterionValue($b, $criterion);

			if ($valueA === null || $valueB === null || $valueA[0] == $valueB[0]) {
				continue;
			}

			return $valueA[1] > 0 ? ($valueB[0] <=> $valueA[0]) : ($valueA[0] <=> $valueB[0]);
		}

		return $a->team_name <=> $b->team_name;
	}

	/**
	 * Vzájemné zápasy mezi právě těmito dvěma týmy (párové porovnání, ne mini-tabulka pro 3+ týmů
	 * se stejným počtem bodů — u vícenásobných remíz proto nemusí přesně odpovídat oficiálním
	 * pravidlům soutěže).
	 */
	private function compareHeadToHead(object $a, object $b, string $criterion, array $matchesByTeamPair): int
	{
		$key = min($a->projectteam_id, $b->projectteam_id) . ':' . max($a->projectteam_id, $b->projectteam_id);
		$matches = $matchesByTeamPair[$key] ?? [];

		if ($matches === []) {
			return 0;
		}

		$pointsA = 0;
		$pointsB = 0;
		$forA = 0.0;
		$againstA = 0.0;

		foreach ($matches as $match) {
			$aIsHome = (int) $match->projectteam1_id === $a->projectteam_id;
			$scoreA = (float) ($aIsHome ? $match->team1_result : $match->team2_result);
			$scoreB = (float) ($aIsHome ? $match->team2_result : $match->team1_result);
			$forA += $scoreA;
			$againstA += $scoreB;

			if ($scoreA > $scoreB) {
				$pointsA += 3;
			} elseif ($scoreA < $scoreB) {
				$pointsB += 3;
			} else {
				$pointsA++;
				$pointsB++;
			}
		}

		if ($criterion === 'H2H') {
			return -($pointsA <=> $pointsB);
		}

		return -(($forA - $againstA) <=> 0.0);
	}

	private function comparePreviousStandings(object $a, object $b): int
	{
		return [$b->previous_points, $b->previous_goal_diff, $b->previous_goals_for, $a->team_name]
			<=> [$a->previous_points, $a->previous_goal_diff, $a->previous_goals_for, $b->team_name];
	}

	public function getPredictionGame(int $projectId = 0, int $gameId = 0): ?object
	{
		$db = $this->getDatabase();
		$query = $db->getQuery(true)
			->select(['g.*', $db->quoteName('p.name', 'project_name')])
			->from($db->quoteName('#__joomleague_prediction_game', 'g'))
			->join('INNER', $db->quoteName('#__joomleague_project', 'p') . ' ON ' . $db->quoteName('p.id') . ' = ' . $db->quoteName('g.project_id'))
			->where($db->quoteName('g.published') . ' = 1');

		if ($gameId > 0) {
			$query->where($db->quoteName('g.id') . ' = :game_id')
				->bind(':game_id', $gameId, ParameterType::INTEGER);
		} elseif ($projectId > 0) {
			$query->where($db->quoteName('g.project_id') . ' = :project_id')
				->bind(':project_id', $projectId, ParameterType::INTEGER);
		}

		$query->order($db->quoteName('g.id') . ' DESC');
		$item = $db->setQuery($query, 0, 1)->loadObject();

		return $item ?: null;
	}

	public function getPredictionMatches(int $gameId, int $roundId = 0): array
	{
		$game = $this->getPredictionGame(0, $gameId);

		if (!$game) {
			return [];
		}

		$matches = $this->getMatches((int) $game->project_id, $roundId);
		$now = time();

		foreach ($matches as $match) {
			$kickoff = strtotime((string) ($match->match_date ?? '')) ?: 0;
			$deadline = $kickoff > 0 ? $kickoff - ((int) $game->deadline_minutes * 60) : 0;
			$match->prediction_locked = $deadline > 0 && $deadline <= $now;
			$match->prediction_deadline = $deadline > 0 ? date('Y-m-d H:i:s', $deadline) : '';
			$match->prediction_played = $match->team1_result !== null && $match->team2_result !== null;
		}

		return $matches;
	}

	public function getPredictionTips(int $gameId, int $userId): array
	{
		if ($gameId < 1 || $userId < 1) {
			return [];
		}

		$db = $this->getDatabase();
		$query = $db->getQuery(true)
			->select('*')
			->from($db->quoteName('#__joomleague_prediction_tip'))
			->where($db->quoteName('game_id') . ' = :game_id')
			->where($db->quoteName('user_id') . ' = :user_id')
			->bind(':game_id', $gameId, ParameterType::INTEGER)
			->bind(':user_id', $userId, ParameterType::INTEGER);

		$tips = [];

		foreach ($db->setQuery($query)->loadObjectList() as $tip) {
			$tips[(int) $tip->match_id] = $tip;
		}

		return $tips;
	}

	public function getPredictionRanking(int $gameId, int $roundId = 0): array
	{
		$this->recalculatePredictionGame($gameId);

		$db = $this->getDatabase();
		$query = $db->getQuery(true)
			->select([
				$db->quoteName('s.user_id'),
				$db->quoteName('u.name', 'user_name'),
				'SUM(' . $db->quoteName('s.tips') . ') AS tips',
				'SUM(' . $db->quoteName('s.points') . ') AS points',
				'SUM(' . $db->quoteName('s.exact_hits') . ') AS exact_hits',
				'SUM(' . $db->quoteName('s.tendency_hits') . ') AS tendency_hits',
			])
			->from($db->quoteName('#__joomleague_prediction_score', 's'))
			->join('INNER', $db->quoteName('#__users', 'u') . ' ON ' . $db->quoteName('u.id') . ' = ' . $db->quoteName('s.user_id'))
			->where($db->quoteName('s.game_id') . ' = :game_id')
			->group([$db->quoteName('s.user_id'), $db->quoteName('u.name')])
			->order($db->quoteName('points') . ' DESC, ' . $db->quoteName('exact_hits') . ' DESC, ' . $db->quoteName('tendency_hits') . ' DESC, ' . $db->quoteName('u.name') . ' ASC')
			->bind(':game_id', $gameId, ParameterType::INTEGER);

		if ($roundId > 0) {
			$query->where($db->quoteName('s.round_id') . ' = :round_id')
				->bind(':round_id', $roundId, ParameterType::INTEGER);
		}

		return $db->setQuery($query)->loadObjectList();
	}

	public function savePredictionTips(int $gameId, int $userId, array $tips): int
	{
		$game = $this->getPredictionGame(0, $gameId);

		if (!$game || $userId < 1) {
			throw new \RuntimeException('COM_JOOMLEAGUE_SITE_PREDICTION_NOT_FOUND');
		}

		$db = $this->getDatabase();
		$matches = [];

		foreach ($this->getPredictionMatches((int) $game->id) as $match) {
			$matches[(int) $match->id] = $match;
		}

		$saved = 0;
		$now = Factory::getDate()->toSql();

		foreach ($tips as $matchId => $tip) {
			$matchId = (int) $matchId;

			if (!isset($matches[$matchId]) || !is_array($tip) || !empty($matches[$matchId]->prediction_locked) || !empty($matches[$matchId]->prediction_played)) {
				continue;
			}

			$homeRaw = trim((string) ($tip['home'] ?? ''));
			$awayRaw = trim((string) ($tip['away'] ?? ''));

			if ($homeRaw === '' || $awayRaw === '' || !ctype_digit($homeRaw) || !ctype_digit($awayRaw)) {
				continue;
			}

			$home = min(999, (int) $homeRaw);
			$away = min(999, (int) $awayRaw);
			$existingId = (int) $db->setQuery(
				$db->getQuery(true)
					->select($db->quoteName('id'))
					->from($db->quoteName('#__joomleague_prediction_tip'))
					->where($db->quoteName('game_id') . ' = :game_id')
					->where($db->quoteName('match_id') . ' = :match_id')
					->where($db->quoteName('user_id') . ' = :user_id')
					->bind(':game_id', $gameId, ParameterType::INTEGER)
					->bind(':match_id', $matchId, ParameterType::INTEGER)
					->bind(':user_id', $userId, ParameterType::INTEGER)
			)->loadResult();

			if ($existingId > 0) {
				$query = $db->getQuery(true)
					->update($db->quoteName('#__joomleague_prediction_tip'))
					->set($db->quoteName('home_score') . ' = :home_score')
					->set($db->quoteName('away_score') . ' = :away_score')
					->set($db->quoteName('modified') . ' = :modified')
					->where($db->quoteName('id') . ' = :id')
					->bind(':home_score', $home, ParameterType::INTEGER)
					->bind(':away_score', $away, ParameterType::INTEGER)
					->bind(':modified', $now)
					->bind(':id', $existingId, ParameterType::INTEGER);
			} else {
				$query = $db->getQuery(true)
					->insert($db->quoteName('#__joomleague_prediction_tip'))
					->columns($db->quoteName(['game_id', 'match_id', 'user_id', 'home_score', 'away_score', 'created', 'modified']))
					->values(':game_id, :match_id, :user_id, :home_score, :away_score, :created, :modified')
					->bind(':game_id', $gameId, ParameterType::INTEGER)
					->bind(':match_id', $matchId, ParameterType::INTEGER)
					->bind(':user_id', $userId, ParameterType::INTEGER)
					->bind(':home_score', $home, ParameterType::INTEGER)
					->bind(':away_score', $away, ParameterType::INTEGER)
					->bind(':created', $now)
					->bind(':modified', $now);
			}

			$db->setQuery($query)->execute();
			$saved++;
		}

		$this->recalculatePredictionUser($gameId, $userId);

		return $saved;
	}

	public function recalculatePredictionGame(int $gameId): void
	{
		$db = $this->getDatabase();
		$userIds = $db->setQuery(
			$db->getQuery(true)
				->select('DISTINCT ' . $db->quoteName('user_id'))
				->from($db->quoteName('#__joomleague_prediction_tip'))
				->where($db->quoteName('game_id') . ' = :game_id')
				->bind(':game_id', $gameId, ParameterType::INTEGER)
		)->loadColumn();

		foreach ($userIds as $userId) {
			$this->recalculatePredictionUser($gameId, (int) $userId);
		}
	}

	private function recalculatePredictionUser(int $gameId, int $userId): void
	{
		$game = $this->getPredictionGame(0, $gameId);

		if (!$game || $userId < 1) {
			return;
		}

		$db = $this->getDatabase();
		$query = $db->getQuery(true)
			->select([
				't.*',
				$db->quoteName('m.team1_result'),
				$db->quoteName('m.team2_result'),
				$db->quoteName('m.count_result'),
				$db->quoteName('m.round_id'),
			])
			->from($db->quoteName('#__joomleague_prediction_tip', 't'))
			->join('INNER', $db->quoteName('#__joomleague_match', 'm') . ' ON ' . $db->quoteName('m.id') . ' = ' . $db->quoteName('t.match_id'))
			->where($db->quoteName('t.game_id') . ' = :game_id')
			->where($db->quoteName('t.user_id') . ' = :user_id')
			->bind(':game_id', $gameId, ParameterType::INTEGER)
			->bind(':user_id', $userId, ParameterType::INTEGER);

		$byRound = [];

		foreach ($db->setQuery($query)->loadObjectList() as $tip) {
			if ($tip->team1_result === null || $tip->team2_result === null || (int) $tip->count_result !== 1) {
				continue;
			}

			$roundId = (int) $tip->round_id;
			$byRound[$roundId] ??= ['tips' => 0, 'points' => 0, 'exact' => 0, 'tendency' => 0];
			$points = $this->calculatePredictionPoints($tip, $game);
			$byRound[$roundId]['tips']++;
			$byRound[$roundId]['points'] += $points;
			$byRound[$roundId]['exact'] += ((int) $tip->home_score === (int) $tip->team1_result && (int) $tip->away_score === (int) $tip->team2_result) ? 1 : 0;
			$byRound[$roundId]['tendency'] += $this->predictionOutcome((int) $tip->home_score, (int) $tip->away_score) === $this->predictionOutcome((int) $tip->team1_result, (int) $tip->team2_result) ? 1 : 0;

			$tipId = (int) $tip->id;

			$db->setQuery(
				$db->getQuery(true)
					->update($db->quoteName('#__joomleague_prediction_tip'))
					->set($db->quoteName('points') . ' = :points')
					->set($db->quoteName('calculated') . ' = 1')
					->where($db->quoteName('id') . ' = :id')
					->bind(':points', $points, ParameterType::INTEGER)
					->bind(':id', $tipId, ParameterType::INTEGER)
			)->execute();
		}

		$db->setQuery(
			$db->getQuery(true)
				->delete($db->quoteName('#__joomleague_prediction_score'))
				->where($db->quoteName('game_id') . ' = :game_id')
				->where($db->quoteName('user_id') . ' = :user_id')
				->bind(':game_id', $gameId, ParameterType::INTEGER)
				->bind(':user_id', $userId, ParameterType::INTEGER)
		)->execute();

		$now = Factory::getDate()->toSql();

		foreach ($byRound as $roundId => $row) {
			$tips = (int) $row['tips'];
			$points = (int) $row['points'];
			$exactHits = (int) $row['exact'];
			$tendencyHits = (int) $row['tendency'];

			$db->setQuery(
				$db->getQuery(true)
					->insert($db->quoteName('#__joomleague_prediction_score'))
					->columns($db->quoteName(['game_id', 'user_id', 'round_id', 'tips', 'points', 'exact_hits', 'tendency_hits', 'modified']))
					->values(':game_id, :user_id, :round_id, :tips, :points, :exact_hits, :tendency_hits, :modified')
					->bind(':game_id', $gameId, ParameterType::INTEGER)
					->bind(':user_id', $userId, ParameterType::INTEGER)
					->bind(':round_id', $roundId, ParameterType::INTEGER)
					->bind(':tips', $tips, ParameterType::INTEGER)
					->bind(':points', $points, ParameterType::INTEGER)
					->bind(':exact_hits', $exactHits, ParameterType::INTEGER)
					->bind(':tendency_hits', $tendencyHits, ParameterType::INTEGER)
					->bind(':modified', $now)
			)->execute();
		}
	}

	private function calculatePredictionPoints(object $tip, object $game): int
	{
		$homeTip = (int) $tip->home_score;
		$awayTip = (int) $tip->away_score;
		$home = (int) $tip->team1_result;
		$away = (int) $tip->team2_result;

		if ($homeTip === $home && $awayTip === $away) {
			return (int) $game->points_exact;
		}

		if (($homeTip - $awayTip) === ($home - $away)) {
			return (int) $game->points_goal_diff;
		}

		return $this->predictionOutcome($homeTip, $awayTip) === $this->predictionOutcome($home, $away) ? (int) $game->points_tendency : 0;
	}

	private function predictionOutcome(int $home, int $away): int
	{
		return $home <=> $away;
	}
}
