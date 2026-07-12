<?php
/**
 * @package     JoomLeague
 * @copyright   Copyright (C) 2026 Ondřej Klučka (https://klucon.cz). All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */


declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Administrator\Model;

\defined('_JEXEC') or die;

use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\CMS\Language\Text;
use Joomla\Database\ParameterType;

final class AjaxModel extends BaseDatabaseModel
{
	public function getProjectTeams(int $projectId, int $divisionId = 0): array
	{
		if ($projectId <= 0) {
			return [];
		}

		$db = $this->getDatabase();
		$query = $db->createQuery()
			->select('pt.id AS value, t.name AS text')
			->from('#__joomleague_project_team pt')
			->join('INNER', '#__joomleague_team t ON t.id = pt.team_id')
			->where('pt.project_id = :project_id')
			->order('pt.ordering ASC, t.name ASC')
			->bind(':project_id', $projectId, ParameterType::INTEGER);

		if ($divisionId > 0) {
			$query->where('pt.division_id = :division_id')->bind(':division_id', $divisionId, ParameterType::INTEGER);
		}

		return $db->setQuery($query)->loadAssocList() ?: [];
	}

	public function getProjectBaseTeams(int $projectId): array
	{
		if ($projectId <= 0) {
			return [];
		}

		$db = $this->getDatabase();
		$query = $db->createQuery()
			->select('DISTINCT t.id AS value, t.name AS text')
			->from('#__joomleague_team t')
			->join('INNER', '#__joomleague_project_team pt ON pt.team_id = t.id')
			->where('pt.project_id = :project_id')
			->order('t.name ASC')
			->bind(':project_id', $projectId, ParameterType::INTEGER);

		return $db->setQuery($query)->loadAssocList() ?: [];
	}

	public function getProjectDivisions(int $projectId): array
	{
		$db = $this->getDatabase();
		$query = $db->createQuery()
			->select('d.id AS value, d.name AS text')
			->from('#__joomleague_division d')
			->where('d.project_id = :project_id')
			->order('d.name ASC')
			->bind(':project_id', $projectId, ParameterType::INTEGER);

		$rows = $db->setQuery($query)->loadAssocList() ?: [];

		foreach ($rows as &$row) {
			$row['text'] = Text::_((string) $row['text']);
		}

		return $rows;
	}

	public function getProjectClubs(int $projectId): array
	{
		if ($projectId <= 0) {
			return [];
		}

		$db = $this->getDatabase();
		$query = $db->createQuery()
			->select('DISTINCT c.id AS value, c.name AS text')
			->from('#__joomleague_club c')
			->join('INNER', '#__joomleague_team t ON t.club_id = c.id')
			->join('INNER', '#__joomleague_project_team pt ON pt.team_id = t.id')
			->where('pt.project_id = :project_id')
			->order('c.name ASC')
			->bind(':project_id', $projectId, ParameterType::INTEGER);

		return $db->setQuery($query)->loadAssocList() ?: [];
	}

	public function getProjectEventTypes(int $projectId): array
	{
		if ($projectId <= 0) {
			return [];
		}

		$db = $this->getDatabase();
		$query = $db->createQuery()
			->select('DISTINCT et.id AS value, et.name AS text')
			->from('#__joomleague_eventtype et')
			->join('INNER', '#__joomleague_match_event e ON e.event_type_id = et.id')
			->join('INNER', '#__joomleague_match m ON m.id = e.match_id')
			->join('INNER', '#__joomleague_round r ON r.id = m.round_id')
			->where('r.project_id = :project_id')
			->where('m.published = 1')
			->where('et.published = 1')
			->order('et.ordering ASC, et.name ASC')
			->bind(':project_id', $projectId, ParameterType::INTEGER);

		$rows = $db->setQuery($query)->loadAssocList() ?: [];

		foreach ($rows as &$row) {
			$row['text'] = Text::_((string) $row['text']);
		}

		return $rows;
	}

	public function getProjectStatistics(int $projectId): array
	{
		if ($projectId <= 0) {
			return [];
		}

		$db = $this->getDatabase();
		$query = $db->createQuery()
			->select('DISTINCT s.id AS value, s.name AS text')
			->from('#__joomleague_statistic s')
			->join('INNER', '#__joomleague_match_statistic ms ON ms.statistic_id = s.id')
			->join('INNER', '#__joomleague_match m ON m.id = ms.match_id')
			->join('INNER', '#__joomleague_round r ON r.id = m.round_id')
			->where('r.project_id = :project_id')
			->where('s.published = 1')
			->order('s.ordering ASC, s.name ASC')
			->bind(':project_id', $projectId, ParameterType::INTEGER);

		$rows = $db->setQuery($query)->loadAssocList() ?: [];

		foreach ($rows as &$row) {
			$row['text'] = Text::_((string) $row['text']);
		}

		return $rows;
	}

	public function getProjectTrees(int $projectId): array
	{
		if ($projectId <= 0) {
			return [];
		}

		$db = $this->getDatabase();
		$query = $db->createQuery()
			->select('t.id AS value, t.name AS text')
			->from('#__joomleague_treeto t')
			->where('t.project_id = :project_id')
			->where('t.published = 1')
			->order('t.name ASC, t.id ASC')
			->bind(':project_id', $projectId, ParameterType::INTEGER);

		return $db->setQuery($query)->loadAssocList() ?: [];
	}

	public function getProjectPredictionGames(int $projectId): array
	{
		if ($projectId <= 0) {
			return [];
		}

		$db = $this->getDatabase();
		$query = $db->createQuery()
			->select('g.id AS value, g.name AS text')
			->from('#__joomleague_prediction_game g')
			->where('g.project_id = :project_id')
			->where('g.published = 1')
			->order('g.name ASC, g.id ASC')
			->bind(':project_id', $projectId, ParameterType::INTEGER);

		return $db->setQuery($query)->loadAssocList() ?: [];
	}

	public function getProjectRounds(int $projectId): array
	{
		$db = $this->getDatabase();
		$query = $db->createQuery()
			->select('r.id AS value, r.name AS text')
			->from('#__joomleague_round r')
			->where('r.project_id = :project_id')
			->order('r.roundcode ASC')
			->bind(':project_id', $projectId, ParameterType::INTEGER);

		return $db->setQuery($query)->loadAssocList() ?: [];
	}

	public function getMatches(int $projectId, int $projectTeamId = 0): array
	{
		if ($projectId <= 0) {
			return [];
		}

		$db = $this->getDatabase();
		$query = $db->createQuery()
			->select("m.id AS value, CONCAT(COALESCE(th.name, ''), ' - ', COALESCE(ta.name, ''), CASE WHEN m.match_date IS NULL OR m.match_date LIKE '0000-00-00%' THEN '' ELSE CONCAT(' (', DATE_FORMAT(m.match_date, '%d.%m.%Y'), ')') END) AS text")
			->from('#__joomleague_match m')
			->join('INNER', '#__joomleague_round r ON r.id = m.round_id')
			->join('LEFT', '#__joomleague_project_team pth ON pth.id = m.projectteam1_id')
			->join('LEFT', '#__joomleague_project_team pta ON pta.id = m.projectteam2_id')
			->join('LEFT', '#__joomleague_team th ON th.id = pth.team_id')
			->join('LEFT', '#__joomleague_team ta ON ta.id = pta.team_id')
			->where('r.project_id = :project_id')
			->order('m.match_date ASC, m.match_number ASC')
			->bind(':project_id', $projectId, ParameterType::INTEGER);

		if ($projectTeamId > 0) {
			$query->where('(m.projectteam1_id = :projectteam_id OR m.projectteam2_id = :projectteam_id)')
				->bind(':projectteam_id', $projectTeamId, ParameterType::INTEGER);
		}

		return $db->setQuery($query)->loadAssocList() ?: [];
	}

	public function searchPersons(string $search, int $limit = 20): array
	{
		$db = $this->getDatabase();
		$needle = '%' . str_replace(' ', '%', trim($search)) . '%';
		$query = $db->createQuery()
			->select("id AS value, TRIM(CONCAT(COALESCE(firstname, ''), ' ', COALESCE(lastname, ''))) AS text")
			->from('#__joomleague_person')
			->where("(firstname LIKE :search OR lastname LIKE :search OR nickname LIKE :search OR CAST(id AS CHAR) = :exact)")
			->order('lastname ASC, firstname ASC')
			->setLimit($limit)
			->bind(':search', $needle)
			->bind(':exact', $search);

		return $db->setQuery($query)->loadAssocList() ?: [];
	}

	public function searchClubs(string $search, int $limit = 20): array
	{
		$search = trim($search);

		if (mb_strlen($search) < 3) {
			return [];
		}

		$db = $this->getDatabase();
		$needle = '%' . str_replace(' ', '%', $search) . '%';
		$query = $db->createQuery()
			->select('id AS value, name AS text')
			->from('#__joomleague_club')
			->where('(name LIKE :search OR alias LIKE :search OR location LIKE :search OR CAST(id AS CHAR) = :exact)')
			->order('name ASC')
			->setLimit($limit)
			->bind(':search', $needle)
			->bind(':exact', $search);

		return $db->setQuery($query)->loadAssocList() ?: [];
	}
}
