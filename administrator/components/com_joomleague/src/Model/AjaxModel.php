<?php

declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Administrator\Model;

\defined('_JEXEC') or die;

use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\Database\ParameterType;

final class AjaxModel extends BaseDatabaseModel
{
	public function getProjectTeams(int $projectId, int $divisionId = 0): array
	{
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

	public function getProjectDivisions(int $projectId): array
	{
		$db = $this->getDatabase();
		$query = $db->createQuery()
			->select('d.id AS value, d.name AS text')
			->from('#__joomleague_division d')
			->where('d.project_id = :project_id')
			->order('d.name ASC')
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

	public function getMatches(int $projectId): array
	{
		$db = $this->getDatabase();
		$query = $db->createQuery()
			->select("m.id AS value, CONCAT(COALESCE(th.name, ''), ' - ', COALESCE(ta.name, '')) AS text")
			->from('#__joomleague_match m')
			->join('INNER', '#__joomleague_round r ON r.id = m.round_id')
			->join('LEFT', '#__joomleague_project_team pth ON pth.id = m.projectteam1_id')
			->join('LEFT', '#__joomleague_project_team pta ON pta.id = m.projectteam2_id')
			->join('LEFT', '#__joomleague_team th ON th.id = pth.team_id')
			->join('LEFT', '#__joomleague_team ta ON ta.id = pta.team_id')
			->where('r.project_id = :project_id')
			->order('m.match_date ASC, m.match_number ASC')
			->bind(':project_id', $projectId, ParameterType::INTEGER);

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
}
