<?php

declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Site\Model;

\defined('_JEXEC') or die;

use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\CMS\Factory;
use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;

class SiteModel extends BaseDatabaseModel
{
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
				$db->quoteName('t.picture', 'team_picture'),
				$db->quoteName('c.name', 'club_name'),
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

	public function getTeam(int $projectTeamId): ?object
	{
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
				$db->quoteName('pg.id', 'playground_id'),
				$db->quoteName('pg.name', 'playground_name'),
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
				$db->quoteName('p.country', 'person_country'),
				$db->quoteName('p.picture', 'person_picture'),
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
			->select('*')
			->from($db->quoteName('#__joomleague_round'))
			->where($db->quoteName('project_id') . ' = :project_id')
			->where($db->quoteName('published') . ' = 1')
			->bind(':project_id', $projectId, ParameterType::INTEGER)
			->order($db->quoteName('roundcode') . ' ASC, ' . $db->quoteName('id') . ' ASC');

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
				$db->quoteName('p.name', 'project_name'),
				$db->quoteName('l.name', 'league_name'),
				$db->quoteName('s.name', 'season_name'),
				$db->quoteName('home.id', 'home_projectteam_id'),
				$db->quoteName('away.id', 'away_projectteam_id'),
				$db->quoteName('ht.name', 'home_name'),
				$db->quoteName('at.name', 'away_name'),
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
			->join('LEFT', $db->quoteName('#__joomleague_playground', 'hp') . ' ON ' . $db->quoteName('hp.id') . ' = ' . $db->quoteName('m.playground_id'))
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

	public function getMatch(int $matchId): ?object
	{
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
				$db->quoteName('hp.name', 'playground_name'),
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
				$db->quoteName('pos.name', 'position_name'),
				$db->quoteName('p.firstname'),
				$db->quoteName('p.lastname'),
				$db->quoteName('p.nickname'),
				$db->quoteName('p.country'),
				'CONCAT_WS(' . $db->quote(' ') . ', ' . $db->quoteName('p.firstname') . ', ' . $db->quoteName('p.lastname') . ') AS person_name',
			])
			->from($db->quoteName('#__joomleague_match_referee', 'mr'))
			->join('INNER', $db->quoteName('#__joomleague_project_referee', 'pr') . ' ON ' . $db->quoteName('pr.id') . ' = ' . $db->quoteName('mr.project_referee_id'))
			->join('INNER', $db->quoteName('#__joomleague_person', 'p') . ' ON ' . $db->quoteName('p.id') . ' = ' . $db->quoteName('pr.person_id'))
			->join('LEFT', $db->quoteName('#__joomleague_project_position', 'pp') . ' ON ' . $db->quoteName('pp.id') . ' = ' . $db->quoteName('mr.project_position_id'))
			->join('LEFT', $db->quoteName('#__joomleague_position', 'pos') . ' ON ' . $db->quoteName('pos.id') . ' = ' . $db->quoteName('pp.position_id'))
			->where($db->quoteName('mr.match_id') . ' = :match_id')
			->where($db->quoteName('p.published') . ' = 1')
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

		$ranks = [];

		foreach ($this->getStandings($projectId) as $index => $team) {
			$ranks[(int) $team->projectteam_id] = $index + 1;
		}

		return [
			'home' => [
				'rank' => $ranks[$homeId] ?? null,
				'stats' => $this->getTeamStatsSummary($homeId),
			],
			'away' => [
				'rank' => $ranks[$awayId] ?? null,
				'stats' => $this->getTeamStatsSummary($awayId),
			],
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
				'CONCAT_WS(' . $db->quote(' ') . ', ' . $db->quoteName('p.firstname') . ', ' . $db->quoteName('p.lastname') . ') AS person_name',
			])
			->from($db->quoteName('#__joomleague_match_event', 'e'))
			->join('LEFT', $db->quoteName('#__joomleague_eventtype', 'et') . ' ON ' . $db->quoteName('et.id') . ' = ' . $db->quoteName('e.event_type_id'))
			->join('LEFT', $db->quoteName('#__joomleague_project_team', 'pt') . ' ON ' . $db->quoteName('pt.id') . ' = ' . $db->quoteName('e.projectteam_id'))
			->join('LEFT', $db->quoteName('#__joomleague_team', 't') . ' ON ' . $db->quoteName('t.id') . ' = ' . $db->quoteName('pt.team_id'))
			->join('LEFT', $db->quoteName('#__joomleague_team_player', 'tp') . ' ON ' . $db->quoteName('tp.id') . ' = ' . $db->quoteName('e.teamplayer_id'))
			->join('LEFT', $db->quoteName('#__joomleague_person', 'p') . ' ON ' . $db->quoteName('p.id') . ' = ' . $db->quoteName('tp.person_id'))
			->where($db->quoteName('e.match_id') . ' = :match_id')
			->bind(':match_id', $matchId, ParameterType::INTEGER)
			->order($db->quoteName('e.event_time') . ' ASC, ' . $db->quoteName('e.id') . ' ASC');

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

	public function getClub(int $clubId): ?object
	{
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

	public function getPlayground(int $playgroundId): ?object
	{
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

	public function getStandings(int $projectId): array
	{
		$teams = [];

		foreach ($this->getProjectTeams($projectId) as $team) {
			$teams[(int) $team->id] = (object) [
				'projectteam_id' => (int) $team->id,
				'team_name' => $team->team_name,
				'played' => 0,
				'won' => 0,
				'drawn' => 0,
				'lost' => 0,
				'goals_for' => 0.0,
				'goals_against' => 0.0,
				'goal_diff' => 0.0,
				'points' => (int) ($team->start_points ?? 0),
			];
		}

		foreach ($this->getMatches($projectId) as $match) {
			if ($match->team1_result === null || $match->team2_result === null || (int) $match->count_result !== 1) {
				continue;
			}

			$homeId = (int) $match->projectteam1_id;
			$awayId = (int) $match->projectteam2_id;

			if (!isset($teams[$homeId], $teams[$awayId])) {
				continue;
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
		}

		foreach ($teams as $team) {
			$team->goal_diff = $team->goals_for - $team->goals_against;
		}

		$list = array_values($teams);

		usort(
			$list,
			static fn (object $a, object $b): int => [$b->points, $b->goal_diff, $b->goals_for, $a->team_name] <=> [$a->points, $a->goal_diff, $a->goals_for, $b->team_name]
		);

		return $list;
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
