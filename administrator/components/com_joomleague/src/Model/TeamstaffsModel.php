<?php
/**
 * @package     JoomLeague
 * @copyright   Copyright (C) 2026 Ondřej Klučka (https://klucon.cz). All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */


declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Administrator\Model;

\defined('_JEXEC') or die;

use Joomla\CMS\Application\AdministratorApplication;
use Joomla\CMS\Date\Date;
use Joomla\CMS\Filter\OutputFilter;
use Joomla\CMS\MVC\Factory\MVCFactoryInterface;
use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;
use Joomla\Database\QueryInterface;

final class TeamstaffsModel extends EntityListModel
{
	protected array $searchColumns = ['pe.firstname', 'pe.lastname', 't.name', 'pos.name'];
	protected string $defaultOrdering = 'a.ordering';

	private AdministratorApplication $application;

	public function __construct($config = [], ?MVCFactoryInterface $factory = null)
	{
		$config['filter_fields'] ??= [
			'id',
			'a.id',
			'staff_name',
			'pe.lastname',
			'position_name',
			'pos.name',
			'published',
			'a.published',
			'ordering',
			'a.ordering',
		];

		parent::__construct($config, $factory);
	}

	public function setApplication(AdministratorApplication $application): void
	{
		$this->application = $application;
	}

	protected function populateState($ordering = 'a.ordering', $direction = 'asc'): void
	{
		parent::populateState($ordering, $direction);

		$input = $this->application->getInput();
		$projectteamId = $input->getInt('projectteam_id', 0);
		$projectId = $input->getInt('project_id', 0);

		if ($projectteamId > 0) {
			$this->application->setUserState('com_joomleague.teamstaffs.projectteam_id', $projectteamId);
			$this->application->setUserState('com_joomleague.teamstaffs.project_id', 0);
		} elseif ($projectId > 0) {
			$this->application->setUserState('com_joomleague.teamstaffs.project_id', $projectId);
			$this->application->setUserState('com_joomleague.teamstaffs.projectteam_id', 0);
		} else {
			$projectteamId = (int) $this->application->getUserState('com_joomleague.teamstaffs.projectteam_id');
			$projectId = (int) $this->application->getUserState('com_joomleague.teamstaffs.project_id');
		}

		$this->setState('filter.projectteam_id', $projectteamId);
		$this->setState('filter.project_id', $projectId);
	}

	protected function buildQuery(): QueryInterface
	{
		$db = $this->getDatabase();
		$query = $db->createQuery()
			->select([
				'a.*',
				'TRIM(CONCAT_WS(" ", pe.lastname, pe.firstname)) AS staff_name',
				't.name AS team_name',
				'pt.project_id',
				'pos.name AS position_name',
				'u.name AS editor',
			])
			->from($db->quoteName('#__joomleague_team_staff', 'a'))
			->join('INNER', $db->quoteName('#__joomleague_person', 'pe') . ' ON ' . $db->quoteName('pe.id') . ' = ' . $db->quoteName('a.person_id'))
			->join('INNER', $db->quoteName('#__joomleague_project_team', 'pt') . ' ON ' . $db->quoteName('pt.id') . ' = ' . $db->quoteName('a.projectteam_id'))
			->join('INNER', $db->quoteName('#__joomleague_team', 't') . ' ON ' . $db->quoteName('t.id') . ' = ' . $db->quoteName('pt.team_id'))
			->join('LEFT', $db->quoteName('#__joomleague_project_position', 'pp') . ' ON ' . $db->quoteName('pp.id') . ' = ' . $db->quoteName('a.project_position_id'))
			->join('LEFT', $db->quoteName('#__joomleague_position', 'pos') . ' ON ' . $db->quoteName('pos.id') . ' = ' . $db->quoteName('pp.position_id'))
			->join('LEFT', $db->quoteName('#__users', 'u') . ' ON ' . $db->quoteName('u.id') . ' = ' . $db->quoteName('a.checked_out'));

		$projectteamId = (int) $this->getState('filter.projectteam_id');

		if ($projectteamId > 0) {
			$query->where($db->quoteName('a.projectteam_id') . ' = :projectteam_id')
				->bind(':projectteam_id', $projectteamId, ParameterType::INTEGER);
		} else {
			$projectId = (int) $this->getState('filter.project_id');

			if ($projectId > 0) {
				$query->where($db->quoteName('pt.project_id') . ' = :project_id')
					->bind(':project_id', $projectId, ParameterType::INTEGER);
			}
		}

		return $query;
	}

	public function getProjectTeamProjectId(int $projectteamId): int
	{
		if ($projectteamId < 1) {
			return 0;
		}

		$db = $this->getDatabase();
		$query = $db->createQuery()
			->select($db->quoteName('project_id'))
			->from($db->quoteName('#__joomleague_project_team'))
			->where($db->quoteName('id') . ' = :projectteam_id')
			->bind(':projectteam_id', $projectteamId, ParameterType::INTEGER);

		return (int) $db->setQuery($query)->loadResult();
	}

	public function getProjectContext(): ?object
	{
		$projectteamId = (int) $this->getState('filter.projectteam_id');

		if ($projectteamId < 1) {
			return null;
		}

		$db = $this->getDatabase();
		$query = $db->createQuery()
			->select([
				'p.id',
				'p.name',
				'l.name AS league',
				's.name AS season',
				'st.name AS sport',
				't.name AS team_name',
				'pt.id AS projectteam_id',
				'(SELECT COUNT(*) FROM ' . $db->quoteName('#__joomleague_round', 'r') . ' WHERE ' . $db->quoteName('r.project_id') . ' = ' . $db->quoteName('p.id') . ') AS round_count',
				'(SELECT COUNT(*) FROM ' . $db->quoteName('#__joomleague_match', 'm') . ' JOIN ' . $db->quoteName('#__joomleague_round', 'r2') . ' ON ' . $db->quoteName('r2.id') . ' = ' . $db->quoteName('m.round_id') . ' WHERE ' . $db->quoteName('r2.project_id') . ' = ' . $db->quoteName('p.id') . ') AS match_count',
			])
			->from($db->quoteName('#__joomleague_project_team', 'pt'))
			->join('INNER', $db->quoteName('#__joomleague_project', 'p') . ' ON ' . $db->quoteName('p.id') . ' = ' . $db->quoteName('pt.project_id'))
			->join('INNER', $db->quoteName('#__joomleague_team', 't') . ' ON ' . $db->quoteName('t.id') . ' = ' . $db->quoteName('pt.team_id'))
			->join('LEFT', $db->quoteName('#__joomleague_league', 'l') . ' ON ' . $db->quoteName('l.id') . ' = ' . $db->quoteName('p.league_id'))
			->join('LEFT', $db->quoteName('#__joomleague_season', 's') . ' ON ' . $db->quoteName('s.id') . ' = ' . $db->quoteName('p.season_id'))
			->join('LEFT', $db->quoteName('#__joomleague_sports_type', 'st') . ' ON ' . $db->quoteName('st.id') . ' = ' . $db->quoteName('p.sports_type_id'))
			->where($db->quoteName('pt.id') . ' = :projectteam_id')
			->bind(':projectteam_id', $projectteamId, ParameterType::INTEGER);

		return $db->setQuery($query)->loadObject() ?: null;
	}

	public function searchAvailablePersons(int $projectteamId, string $search): array
	{
		$projectteamId = max(0, $projectteamId);
		$search = trim($search);

		if ($projectteamId < 1 || mb_strlen($search) < 2) {
			return [];
		}

		$db = $this->getDatabase();
		$like = '%' . str_replace(' ', '%', $search) . '%';
		$query = $db->createQuery()
			->select([
				'p.id',
				'p.firstname',
				'p.lastname',
				'p.nickname',
				'p.knvbnr',
			])
			->from($db->quoteName('#__joomleague_person', 'p'))
			->where('NOT EXISTS ('
				. 'SELECT 1 FROM ' . $db->quoteName('#__joomleague_team_staff', 'ts')
				. ' WHERE ' . $db->quoteName('ts.person_id') . ' = ' . $db->quoteName('p.id')
				. ' AND ' . $db->quoteName('ts.projectteam_id') . ' = :projectteam_id'
				. ')')
			->where('('
				. $db->quoteName('p.firstname') . ' LIKE :search'
				. ' OR ' . $db->quoteName('p.lastname') . ' LIKE :search'
				. ' OR ' . $db->quoteName('p.nickname') . ' LIKE :search'
				. ' OR ' . $db->quoteName('p.knvbnr') . ' LIKE :search'
				. ' OR CONCAT(' . $db->quoteName('p.firstname') . ', " ", ' . $db->quoteName('p.lastname') . ') LIKE :search'
				. ' OR CONCAT(' . $db->quoteName('p.lastname') . ', " ", ' . $db->quoteName('p.firstname') . ') LIKE :search'
				. ')')
			->order($db->quoteName('p.lastname') . ' COLLATE utf8mb4_czech_ci ASC, ' . $db->quoteName('p.firstname') . ' COLLATE utf8mb4_czech_ci ASC, ' . $db->quoteName('p.id') . ' ASC')
			->setLimit(20)
			->bind(':projectteam_id', $projectteamId, ParameterType::INTEGER)
			->bind(':search', $like, ParameterType::STRING);

		$rows = $db->setQuery($query)->loadObjectList();

		return array_map([$this, 'formatPersonOption'], $rows ?: []);
	}

	public function addPerson(int $projectteamId, int $personId): object
	{
		$projectteamId = max(0, $projectteamId);
		$personId = max(0, $personId);

		if ($projectteamId < 1 || $personId < 1) {
			throw new \InvalidArgumentException('COM_JOOMLEAGUE_TEAMPERSON_ERROR_REQUIRED');
		}

		$db = $this->getDatabase();

		if (!$this->personExists($db, $personId)) {
			throw new \InvalidArgumentException('COM_JOOMLEAGUE_TEAMPERSON_NOT_FOUND');
		}

		if ($this->assignmentExists($db, $projectteamId, $personId)) {
			throw new \InvalidArgumentException('COM_JOOMLEAGUE_TEAMPERSON_ALREADY_ASSIGNED');
		}

		$row = (object) [
			'projectteam_id' => $projectteamId,
			'person_id' => $personId,
			'active' => 1,
			'published' => 1,
			'ordering' => $this->nextOrdering($db, '#__joomleague_team_staff', $projectteamId),
			'modified' => (new Date())->toSql(),
			'modified_by' => (int) $this->getCurrentUser()->id ?: null,
			'alias' => $this->buildPersonAlias($db, $personId),
		];

		$db->insertObject('#__joomleague_team_staff', $row, 'id');

		return $this->getAssignment((int) $row->id);
	}

	public function getAssignment(int $id): object
	{
		$db = $this->getDatabase();
		$query = $db->createQuery()
			->select([
				'a.id',
				'a.projectteam_id',
				'a.person_id',
				'a.ordering',
				'TRIM(CONCAT_WS(" ", p.lastname, p.firstname)) AS name',
				'p.knvbnr',
			])
			->from($db->quoteName('#__joomleague_team_staff', 'a'))
			->join('INNER', $db->quoteName('#__joomleague_person', 'p') . ' ON ' . $db->quoteName('p.id') . ' = ' . $db->quoteName('a.person_id'))
			->where($db->quoteName('a.id') . ' = :id')
			->bind(':id', $id, ParameterType::INTEGER);

		$row = $db->setQuery($query)->loadObject();

		if (!$row) {
			throw new \RuntimeException('COM_JOOMLEAGUE_TEAMPERSON_NOT_FOUND');
		}

		return $row;
	}

	private function formatPersonOption(object $row): array
	{
		$label = trim(implode(' ', array_filter([(string) $row->lastname, (string) $row->firstname])));
		$details = array_filter([(string) $row->nickname, (string) $row->knvbnr]);

		if ($label === '') {
			$label = '#' . (int) $row->id;
		}

		if ($details !== []) {
			$label .= ' (' . implode(', ', $details) . ')';
		}

		return [
			'value' => (int) $row->id,
			'text' => $label,
		];
	}

	private function personExists(DatabaseInterface $db, int $personId): bool
	{
		$query = $db->createQuery()
			->select('1')
			->from($db->quoteName('#__joomleague_person'))
			->where($db->quoteName('id') . ' = :person_id')
			->bind(':person_id', $personId, ParameterType::INTEGER);

		return (bool) $db->setQuery($query)->loadResult();
	}

	private function buildPersonAlias(DatabaseInterface $db, int $personId): string
	{
		$query = $db->createQuery()
			->select('TRIM(CONCAT_WS(" ", ' . $db->quoteName('firstname') . ', ' . $db->quoteName('lastname') . '))')
			->from($db->quoteName('#__joomleague_person'))
			->where($db->quoteName('id') . ' = :person_id')
			->bind(':person_id', $personId, ParameterType::INTEGER);

		$alias = OutputFilter::stringURLSafe((string) $db->setQuery($query)->loadResult());

		return $alias !== '' ? $alias : 'person-' . $personId;
	}

	private function assignmentExists(DatabaseInterface $db, int $projectteamId, int $personId): bool
	{
		$query = $db->createQuery()
			->select('1')
			->from($db->quoteName('#__joomleague_team_staff'))
			->where($db->quoteName('projectteam_id') . ' = :projectteam_id')
			->where($db->quoteName('person_id') . ' = :person_id')
			->bind(':projectteam_id', $projectteamId, ParameterType::INTEGER)
			->bind(':person_id', $personId, ParameterType::INTEGER);

		return (bool) $db->setQuery($query)->loadResult();
	}

	private function nextOrdering(DatabaseInterface $db, string $table, int $projectteamId): int
	{
		$query = $db->createQuery()
			->select('COALESCE(MAX(' . $db->quoteName('ordering') . '), 0) + 1')
			->from($db->quoteName($table))
			->where($db->quoteName('projectteam_id') . ' = :projectteam_id')
			->bind(':projectteam_id', $projectteamId, ParameterType::INTEGER);

		return (int) $db->setQuery($query)->loadResult();
	}
}
