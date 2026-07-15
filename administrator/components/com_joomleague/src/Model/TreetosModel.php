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
use Joomla\Database\QueryInterface;

final class TreetosModel extends EntityListModel
{
	protected array $searchColumns = ['a.name', 'p.name'];
	private AdministratorApplication $application;

	public function setApplication(AdministratorApplication $application): void
	{
		$this->application = $application;
	}

	public function __construct($config = [], $factory = null)
	{
		$config['filter_fields'] = ['id', 'a.id', 'name', 'a.name', 'project_name', 'p.name', 'published', 'a.published', 'tree_i', 'a.tree_i'];
		parent::__construct($config, $factory);
	}

	protected function populateState($ordering = 'a.id', $direction = 'desc'): void
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

	protected function buildQuery(): QueryInterface
	{
		$db = $this->getDatabase();
		$projectId = (int) $this->getState('filter.project_id');

		return $db->createQuery()
			->select('a.*, p.name AS project_name, d.name AS division_name, u.name AS editor, (SELECT COUNT(*) FROM #__joomleague_treeto_node n WHERE n.treeto_id = a.id) AS node_count')
			->from('#__joomleague_treeto a')
			->join('LEFT', '#__joomleague_project p ON p.id = a.project_id')
			->join('LEFT', '#__joomleague_division d ON d.id = a.division_id')
			->join('LEFT', '#__users u ON u.id = a.checked_out')
			->where('a.project_id = ' . $projectId);
	}
}
