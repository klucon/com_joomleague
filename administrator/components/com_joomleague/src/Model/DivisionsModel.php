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
use Joomla\Database\ParameterType;
use Joomla\Database\QueryInterface;

final class DivisionsModel extends EntityListModel
{
	protected array $searchColumns = ['a.name', 'a.shortname'];
	private AdministratorApplication $application;

	public function setApplication(AdministratorApplication $application): void
	{
		$this->application = $application;
	}

	public function __construct($config = [], $factory = null)
	{
		$config['filter_fields'] = ['id', 'a.id', 'name', 'a.name', 'shortname', 'a.shortname', 'parent_name', 'published', 'a.published', 'ordering', 'a.ordering'];
		parent::__construct($config, $factory);
	}

	protected function populateState($ordering = 'a.ordering', $direction = 'asc'): void
	{
		$projectId = $this->application->getInput()->getInt('project_id', 0);

		if ($projectId > 0) {
			$this->application->setUserState('com_joomleague.project_context.project_id', $projectId);
		} else {
			$projectId = (int) $this->application->getUserState('com_joomleague.project_context.project_id');
		}

		$this->setState('filter.project_id', $projectId);
		parent::populateState($ordering, $direction);
	}

	public function getProjectContext(): ?object
	{
		$id = (int) $this->getState('filter.project_id');

		if ($id < 1) {
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
				'(SELECT COUNT(*) FROM #__joomleague_division d WHERE d.project_id = p.id) AS division_count',
				'(SELECT COUNT(*) FROM #__joomleague_round r WHERE r.project_id = p.id) AS round_count',
				'(SELECT COUNT(*) FROM #__joomleague_match m JOIN #__joomleague_round r2 ON r2.id = m.round_id WHERE r2.project_id = p.id) AS match_count',
			])
			->from('#__joomleague_project p')
			->join('LEFT', '#__joomleague_league l ON l.id = p.league_id')
			->join('LEFT', '#__joomleague_season s ON s.id = p.season_id')
			->join('LEFT', '#__joomleague_sports_type st ON st.id = p.sports_type_id')
			->where('p.id = :id')
			->bind(':id', $id, ParameterType::INTEGER);

		return $db->setQuery($query)->loadObject();
	}

	protected function buildQuery(): QueryInterface
	{
		$db = $this->getDatabase();
		$projectId = (int) $this->getState('filter.project_id');

		return $db->createQuery()
			->select('a.*, parent.name AS parent_name, u.name AS editor')
			->from('#__joomleague_division a')
			->join('LEFT', '#__joomleague_division parent ON parent.id = a.parent_id')
			->join('LEFT', '#__users u ON u.id = a.checked_out')
			->where('a.project_id = ' . (int) $projectId);
	}
}
