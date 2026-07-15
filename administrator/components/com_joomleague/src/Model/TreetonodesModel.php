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

final class TreetonodesModel extends EntityListModel
{
	protected array $searchColumns = ['a.title', 'a.content', 't.name'];
	private AdministratorApplication $application;

	public function __construct($config = [], $factory = null)
	{
		$config['filter_fields'] = ['id', 'a.id', 'node', 'a.node', 'row', 'a.row', 'title', 'a.title', 'published', 'a.published'];
		parent::__construct($config, $factory);
	}

	public function setApplication(AdministratorApplication $application): void
	{
		$this->application = $application;
	}

	protected function populateState($ordering = 'a.node', $direction = 'ASC'): void
	{
		$treeId = $this->application->getInput()->getInt('treeto_id', 0);

		if ($treeId > 0) {
			$this->application->setUserState('com_joomleague.treetonodes.treeto_id', $treeId);
		}

		$this->setState('filter.treeto_id', $treeId ?: (int) $this->application->getUserState('com_joomleague.treetonodes.treeto_id'));
		parent::populateState($ordering, $direction);
	}

	public function getTreeContext(): ?object
	{
		$treeId = (int) $this->getState('filter.treeto_id');

		if ($treeId < 1) {
			return null;
		}

		$db = $this->getDatabase();
		$query = $db->createQuery()
			->select('t.id, t.name, t.project_id, p.name AS project_name, COUNT(n.id) AS node_count')
			->from('#__joomleague_treeto t')
			->join('LEFT', '#__joomleague_project p ON p.id = t.project_id')
			->join('LEFT', '#__joomleague_treeto_node n ON n.treeto_id = t.id')
			->where('t.id = :id')
			->group('t.id, t.name, t.project_id, p.name')
			->bind(':id', $treeId, ParameterType::INTEGER);

		return $db->setQuery($query)->loadObject();
	}

	protected function buildQuery(): QueryInterface
	{
		$db = $this->getDatabase();
		$treeId = (int) $this->getState('filter.treeto_id');
		$query = $db->createQuery()
			->select('a.*, team.name AS team_name, u.name AS editor')
			->from('#__joomleague_treeto_node a')
			->join('LEFT', '#__joomleague_team team ON team.id = a.team_id')
			->join('LEFT', '#__users u ON u.id = a.checked_out');

		if ($treeId > 0) {
			$query->where('a.treeto_id = :tree_id')->bind(':tree_id', $treeId, ParameterType::INTEGER);
		}

		return $query;
	}
}
