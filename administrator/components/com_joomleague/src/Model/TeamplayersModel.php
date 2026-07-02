<?php

declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Administrator\Model;

\defined('_JEXEC') or die;

use Joomla\CMS\Application\AdministratorApplication;
use Joomla\CMS\MVC\Factory\MVCFactoryInterface;
use Joomla\Database\ParameterType;
use Joomla\Database\QueryInterface;

final class TeamplayersModel extends EntityListModel
{
	protected array $searchColumns = ['pe.firstname', 'pe.lastname', 't.name', 'p.name'];
	protected string $defaultOrdering = 'a.ordering';

	private AdministratorApplication $application;

	public function __construct($config = [], ?MVCFactoryInterface $factory = null)
	{
		$config['filter_fields'] ??= [
			'id',
			'a.id',
			'player_name',
			'pe.lastname',
			'jerseynumber',
			'a.jerseynumber',
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

		$projectteamId = $this->application->getInput()->getInt('projectteam_id');

		if ($projectteamId > 0) {
			$this->application->setUserState('com_joomleague.teamplayers.projectteam_id', $projectteamId);
		} else {
			$projectteamId = (int) $this->application->getUserState('com_joomleague.teamplayers.projectteam_id');
		}

		$this->setState('filter.projectteam_id', $projectteamId);
	}

	protected function buildQuery(): QueryInterface
	{
		$db = $this->getDatabase();
		$query = $db->createQuery()
			->select([
				'a.*',
				'TRIM(CONCAT_WS(" ", pe.firstname, pe.lastname)) AS player_name',
				't.name AS team_name',
				'pt.project_id',
				'pos.name AS position_name',
				'u.name AS editor',
			])
			->from($db->quoteName('#__joomleague_team_player', 'a'))
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
		}

		return $query;
	}
}
