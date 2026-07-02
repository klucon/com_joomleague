<?php

declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Site\Model;

\defined('_JEXEC') or die;

use Joomla\CMS\MVC\Model\BaseDatabaseModel;
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

	public function getProjectTeams(int $projectId): array
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
				$db->quoteName('home.id', 'home_projectteam_id'),
				$db->quoteName('away.id', 'away_projectteam_id'),
				$db->quoteName('ht.name', 'home_name'),
				$db->quoteName('at.name', 'away_name'),
				$db->quoteName('hp.name', 'playground_name'),
			])
			->from($db->quoteName('#__joomleague_match', 'm'))
			->join('INNER', $db->quoteName('#__joomleague_round', 'r') . ' ON ' . $db->quoteName('r.id') . ' = ' . $db->quoteName('m.round_id'))
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

	public function getMatch(int $matchId): ?object
	{
		$matches = $this->getMatchesByIds([$matchId]);

		return $matches[0] ?? null;
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
				$db->quoteName('st.name', 'sport_name'),
				$db->quoteName('ht.name', 'home_name'),
				$db->quoteName('at.name', 'away_name'),
				$db->quoteName('hp.name', 'playground_name'),
			])
			->from($db->quoteName('#__joomleague_match', 'm'))
			->join('INNER', $db->quoteName('#__joomleague_round', 'r') . ' ON ' . $db->quoteName('r.id') . ' = ' . $db->quoteName('m.round_id'))
			->join('INNER', $db->quoteName('#__joomleague_project', 'p') . ' ON ' . $db->quoteName('p.id') . ' = ' . $db->quoteName('r.project_id'))
			->join('LEFT', $db->quoteName('#__joomleague_sports_type', 'st') . ' ON ' . $db->quoteName('st.id') . ' = ' . $db->quoteName('p.sports_type_id'))
			->join('LEFT', $db->quoteName('#__joomleague_project_team', 'home') . ' ON ' . $db->quoteName('home.id') . ' = ' . $db->quoteName('m.projectteam1_id'))
			->join('LEFT', $db->quoteName('#__joomleague_project_team', 'away') . ' ON ' . $db->quoteName('away.id') . ' = ' . $db->quoteName('m.projectteam2_id'))
			->join('LEFT', $db->quoteName('#__joomleague_team', 'ht') . ' ON ' . $db->quoteName('ht.id') . ' = ' . $db->quoteName('home.team_id'))
			->join('LEFT', $db->quoteName('#__joomleague_team', 'at') . ' ON ' . $db->quoteName('at.id') . ' = ' . $db->quoteName('away.team_id'))
			->join('LEFT', $db->quoteName('#__joomleague_playground', 'hp') . ' ON ' . $db->quoteName('hp.id') . ' = ' . $db->quoteName('m.playground_id'))
			->whereIn($db->quoteName('m.id'), $ids);

		return $db->setQuery($query)->loadObjectList();
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
			->select(['t.*', $db->quoteName('pt.id', 'projectteam_id'), $db->quoteName('p.name', 'project_name')])
			->from($db->quoteName('#__joomleague_team', 't'))
			->join('LEFT', $db->quoteName('#__joomleague_project_team', 'pt') . ' ON ' . $db->quoteName('pt.team_id') . ' = ' . $db->quoteName('t.id'))
			->join('LEFT', $db->quoteName('#__joomleague_project', 'p') . ' ON ' . $db->quoteName('p.id') . ' = ' . $db->quoteName('pt.project_id'))
			->where($db->quoteName('t.club_id') . ' = :club_id')
			->bind(':club_id', $clubId, ParameterType::INTEGER)
			->order($db->quoteName('t.name') . ' ASC');

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
}
