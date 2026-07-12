<?php
/**
 * @package     JoomLeague
 * @copyright   Copyright (C) 2026 Ondřej Klučka (https://klucon.cz). All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */


declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Site\Model;

\defined('_JEXEC') or die;

use Joomla\Database\ParameterType;

final class PersonModel extends SiteModel
{
	public function getPerson(?int $personId): ?object
	{
		if (empty($personId)) {
			return null;
		}

		$db = $this->getDatabase();
		$query = $db->getQuery(true)
			->select([
				'p.*',
				$db->quoteName('pos.name', 'default_position_name'),
			])
			->from($db->quoteName('#__joomleague_person', 'p'))
			->join('LEFT', $db->quoteName('#__joomleague_position', 'pos') . ' ON ' . $db->quoteName('pos.id') . ' = ' . $db->quoteName('p.position_id'))
			->where($db->quoteName('p.id') . ' = :id')
			->where($db->quoteName('p.published') . ' = 1')
			->where($db->quoteName('p.show_on_frontend') . ' = 1')
			->bind(':id', $personId, ParameterType::INTEGER);

		$item = $db->setQuery($query, 0, 1)->loadObject();

		return $item ?: null;
	}

	public function getPlayerHistory(int $personId): array
	{
		if ($personId < 1) {
			return [];
		}

		$db = $this->getDatabase();
		$query = $db->getQuery(true)
			->select([
				'tp.id',
				'tp.projectteam_id',
				'tp.jerseynumber',
				'tp.active',
				'tp.published',
				$db->quoteName('pt.project_id'),
				$db->quoteName('t.name', 'team_name'),
				$db->quoteName('t.alias', 'team_alias'),
				$db->quoteName('p.name', 'project_name'),
				$db->quoteName('p.alias', 'project_alias'),
				$db->quoteName('l.name', 'league_name'),
				$db->quoteName('s.name', 'season_name'),
				$db->quoteName('pos.name', 'position_name'),
			])
			->from($db->quoteName('#__joomleague_team_player', 'tp'))
			->join('INNER', $db->quoteName('#__joomleague_project_team', 'pt') . ' ON ' . $db->quoteName('pt.id') . ' = ' . $db->quoteName('tp.projectteam_id'))
			->join('INNER', $db->quoteName('#__joomleague_team', 't') . ' ON ' . $db->quoteName('t.id') . ' = ' . $db->quoteName('pt.team_id'))
			->join('INNER', $db->quoteName('#__joomleague_project', 'p') . ' ON ' . $db->quoteName('p.id') . ' = ' . $db->quoteName('pt.project_id'))
			->join('LEFT', $db->quoteName('#__joomleague_league', 'l') . ' ON ' . $db->quoteName('l.id') . ' = ' . $db->quoteName('p.league_id'))
			->join('LEFT', $db->quoteName('#__joomleague_season', 's') . ' ON ' . $db->quoteName('s.id') . ' = ' . $db->quoteName('p.season_id'))
			->join('LEFT', $db->quoteName('#__joomleague_project_position', 'pp') . ' ON ' . $db->quoteName('pp.id') . ' = ' . $db->quoteName('tp.project_position_id'))
			->join('LEFT', $db->quoteName('#__joomleague_position', 'pos') . ' ON ' . $db->quoteName('pos.id') . ' = ' . $db->quoteName('pp.position_id'))
			->where($db->quoteName('tp.person_id') . ' = :person_id')
			->where($db->quoteName('tp.published') . ' = 1')
			->bind(':person_id', $personId, ParameterType::INTEGER)
			->order($db->quoteName('p.id') . ' DESC, ' . $db->quoteName('tp.ordering') . ' ASC, ' . $db->quoteName('tp.id') . ' DESC');

		return $db->setQuery($query)->loadObjectList();
	}

	public function getStaffHistory(int $personId): array
	{
		if ($personId < 1) {
			return [];
		}

		$db = $this->getDatabase();
		$query = $db->getQuery(true)
			->select([
				'ts.id',
				'ts.projectteam_id',
				'ts.active',
				$db->quoteName('pt.project_id'),
				$db->quoteName('t.name', 'team_name'),
				$db->quoteName('p.name', 'project_name'),
				$db->quoteName('l.name', 'league_name'),
				$db->quoteName('s.name', 'season_name'),
				$db->quoteName('pos.name', 'position_name'),
			])
			->from($db->quoteName('#__joomleague_team_staff', 'ts'))
			->join('INNER', $db->quoteName('#__joomleague_project_team', 'pt') . ' ON ' . $db->quoteName('pt.id') . ' = ' . $db->quoteName('ts.projectteam_id'))
			->join('INNER', $db->quoteName('#__joomleague_team', 't') . ' ON ' . $db->quoteName('t.id') . ' = ' . $db->quoteName('pt.team_id'))
			->join('INNER', $db->quoteName('#__joomleague_project', 'p') . ' ON ' . $db->quoteName('p.id') . ' = ' . $db->quoteName('pt.project_id'))
			->join('LEFT', $db->quoteName('#__joomleague_league', 'l') . ' ON ' . $db->quoteName('l.id') . ' = ' . $db->quoteName('p.league_id'))
			->join('LEFT', $db->quoteName('#__joomleague_season', 's') . ' ON ' . $db->quoteName('s.id') . ' = ' . $db->quoteName('p.season_id'))
			->join('LEFT', $db->quoteName('#__joomleague_project_position', 'pp') . ' ON ' . $db->quoteName('pp.id') . ' = ' . $db->quoteName('ts.project_position_id'))
			->join('LEFT', $db->quoteName('#__joomleague_position', 'pos') . ' ON ' . $db->quoteName('pos.id') . ' = ' . $db->quoteName('pp.position_id'))
			->where($db->quoteName('ts.person_id') . ' = :person_id')
			->where($db->quoteName('ts.published') . ' = 1')
			->bind(':person_id', $personId, ParameterType::INTEGER)
			->order($db->quoteName('p.id') . ' DESC, ' . $db->quoteName('ts.ordering') . ' ASC, ' . $db->quoteName('ts.id') . ' DESC');

		return $db->setQuery($query)->loadObjectList();
	}

	public function getRefereeHistory(int $personId): array
	{
		if ($personId < 1) {
			return [];
		}

		$db = $this->getDatabase();
		$query = $db->getQuery(true)
			->select([
				'pr.id',
				'pr.project_id',
				$db->quoteName('p.name', 'project_name'),
				$db->quoteName('l.name', 'league_name'),
				$db->quoteName('s.name', 'season_name'),
				$db->quoteName('pos.name', 'position_name'),
			])
			->from($db->quoteName('#__joomleague_project_referee', 'pr'))
			->join('INNER', $db->quoteName('#__joomleague_project', 'p') . ' ON ' . $db->quoteName('p.id') . ' = ' . $db->quoteName('pr.project_id'))
			->join('LEFT', $db->quoteName('#__joomleague_league', 'l') . ' ON ' . $db->quoteName('l.id') . ' = ' . $db->quoteName('p.league_id'))
			->join('LEFT', $db->quoteName('#__joomleague_season', 's') . ' ON ' . $db->quoteName('s.id') . ' = ' . $db->quoteName('p.season_id'))
			->join('LEFT', $db->quoteName('#__joomleague_project_position', 'pp') . ' ON ' . $db->quoteName('pp.id') . ' = ' . $db->quoteName('pr.project_position_id'))
			->join('LEFT', $db->quoteName('#__joomleague_position', 'pos') . ' ON ' . $db->quoteName('pos.id') . ' = ' . $db->quoteName('pp.position_id'))
			->where($db->quoteName('pr.person_id') . ' = :person_id')
			->where($db->quoteName('pr.published') . ' = 1')
			->bind(':person_id', $personId, ParameterType::INTEGER)
			->order($db->quoteName('p.id') . ' DESC, ' . $db->quoteName('pr.ordering') . ' ASC, ' . $db->quoteName('pr.id') . ' DESC');

		return $db->setQuery($query)->loadObjectList();
	}

	public function getPersonStats(int $personId): array
	{
		if ($personId < 1) {
			return [];
		}

		$db = $this->getDatabase();
		$query = $db->getQuery(true)
			->select([
				$db->quoteName('s.id', 'statistic_id'),
				$db->quoteName('s.name', 'statistic_name'),
				$db->quoteName('s.short', 'statistic_short'),
				$db->quoteName('pt.id', 'projectteam_id'),
				$db->quoteName('pt.project_id'),
				$db->quoteName('t.name', 'team_name'),
				$db->quoteName('p.name', 'project_name'),
				$db->quoteName('l.name', 'league_name'),
				$db->quoteName('se.name', 'season_name'),
				'SUM(' . $db->quoteName('ms.value') . ') AS value',
				'COUNT(DISTINCT ' . $db->quoteName('ms.match_id') . ') AS matches',
			])
			->from($db->quoteName('#__joomleague_match_statistic', 'ms'))
			->join('INNER', $db->quoteName('#__joomleague_team_player', 'tp') . ' ON ' . $db->quoteName('tp.id') . ' = ' . $db->quoteName('ms.teamplayer_id'))
			->join('INNER', $db->quoteName('#__joomleague_statistic', 's') . ' ON ' . $db->quoteName('s.id') . ' = ' . $db->quoteName('ms.statistic_id'))
			->join('INNER', $db->quoteName('#__joomleague_project_team', 'pt') . ' ON ' . $db->quoteName('pt.id') . ' = ' . $db->quoteName('ms.projectteam_id'))
			->join('INNER', $db->quoteName('#__joomleague_team', 't') . ' ON ' . $db->quoteName('t.id') . ' = ' . $db->quoteName('pt.team_id'))
			->join('INNER', $db->quoteName('#__joomleague_project', 'p') . ' ON ' . $db->quoteName('p.id') . ' = ' . $db->quoteName('pt.project_id'))
			->join('LEFT', $db->quoteName('#__joomleague_league', 'l') . ' ON ' . $db->quoteName('l.id') . ' = ' . $db->quoteName('p.league_id'))
			->join('LEFT', $db->quoteName('#__joomleague_season', 'se') . ' ON ' . $db->quoteName('se.id') . ' = ' . $db->quoteName('p.season_id'))
			->where($db->quoteName('tp.person_id') . ' = :person_id')
			->where($db->quoteName('s.published') . ' = 1')
			->group([
				$db->quoteName('s.id'),
				$db->quoteName('s.name'),
				$db->quoteName('s.short'),
				$db->quoteName('pt.id'),
				$db->quoteName('pt.project_id'),
				$db->quoteName('t.name'),
				$db->quoteName('p.name'),
				$db->quoteName('l.name'),
				$db->quoteName('se.name'),
			])
			->order($db->quoteName('p.id') . ' DESC, ' . $db->quoteName('s.ordering') . ' ASC, ' . $db->quoteName('s.name') . ' ASC')
			->bind(':person_id', $personId, ParameterType::INTEGER);

		return $db->setQuery($query)->loadObjectList();
	}

	/**
	 * Historie zápasů osoby jako hráče (zápas po zápasu) — účast v sestavě,
	 * střídání, odehraný čas. Doplňuje kariéru po sezónách o detail za každý zápas.
	 */
	public function getPlayerMatches(int $personId): array
	{
		if ($personId < 1) {
			return [];
		}

		$db    = $this->getDatabase();
		$query = $db->getQuery(true)
			->select([
				$db->quoteName('m.id', 'match_id'),
				$db->quoteName('m.match_date'),
				$db->quoteName('m.team1_result'),
				$db->quoteName('m.team2_result'),
				$db->quoteName('m.projectteam1_id'),
				$db->quoteName('m.projectteam2_id'),
				$db->quoteName('r.name', 'round_name'),
				$db->quoteName('p.id', 'project_id'),
				$db->quoteName('p.name', 'project_name'),
				$db->quoteName('l.name', 'league_name'),
				$db->quoteName('s.name', 'season_name'),
				$db->quoteName('ht.name', 'home_team_name'),
				$db->quoteName('at.name', 'away_team_name'),
				$db->quoteName('tp.projectteam_id', 'player_projectteam_id'),
				$db->quoteName('mp.came_in'),
				$db->quoteName('mp.out'),
				$db->quoteName('mp.in_out_time'),
				$db->quoteName('pos.name', 'position_name'),
			])
			->from($db->quoteName('#__joomleague_team_player', 'tp'))
			->join('INNER', $db->quoteName('#__joomleague_match_player', 'mp') . ' ON ' . $db->quoteName('mp.teamplayer_id') . ' = ' . $db->quoteName('tp.id'))
			->join('INNER', $db->quoteName('#__joomleague_match', 'm') . ' ON ' . $db->quoteName('m.id') . ' = ' . $db->quoteName('mp.match_id'))
			->join('INNER', $db->quoteName('#__joomleague_round', 'r') . ' ON ' . $db->quoteName('r.id') . ' = ' . $db->quoteName('m.round_id'))
			->join('INNER', $db->quoteName('#__joomleague_project', 'p') . ' ON ' . $db->quoteName('p.id') . ' = ' . $db->quoteName('r.project_id'))
			->join('LEFT', $db->quoteName('#__joomleague_league', 'l') . ' ON ' . $db->quoteName('l.id') . ' = ' . $db->quoteName('p.league_id'))
			->join('LEFT', $db->quoteName('#__joomleague_season', 's') . ' ON ' . $db->quoteName('s.id') . ' = ' . $db->quoteName('p.season_id'))
			->join('LEFT', $db->quoteName('#__joomleague_project_team', 'home') . ' ON ' . $db->quoteName('home.id') . ' = ' . $db->quoteName('m.projectteam1_id'))
			->join('LEFT', $db->quoteName('#__joomleague_project_team', 'away') . ' ON ' . $db->quoteName('away.id') . ' = ' . $db->quoteName('m.projectteam2_id'))
			->join('LEFT', $db->quoteName('#__joomleague_team', 'ht') . ' ON ' . $db->quoteName('ht.id') . ' = ' . $db->quoteName('home.team_id'))
			->join('LEFT', $db->quoteName('#__joomleague_team', 'at') . ' ON ' . $db->quoteName('at.id') . ' = ' . $db->quoteName('away.team_id'))
			->join('LEFT', $db->quoteName('#__joomleague_project_position', 'pp') . ' ON ' . $db->quoteName('pp.id') . ' = ' . $db->quoteName('mp.project_position_id'))
			->join('LEFT', $db->quoteName('#__joomleague_position', 'pos') . ' ON ' . $db->quoteName('pos.id') . ' = ' . $db->quoteName('pp.position_id'))
			->where($db->quoteName('tp.person_id') . ' = :person_id')
			->where($db->quoteName('m.published') . ' = 1')
			->bind(':person_id', $personId, ParameterType::INTEGER)
			->order($db->quoteName('m.match_date') . ' DESC, ' . $db->quoteName('m.id') . ' DESC');

		return $db->setQuery($query)->loadObjectList();
	}
}
