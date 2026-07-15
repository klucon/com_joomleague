<?php
/**
 * @package     JoomLeague
 * @copyright   Copyright (C) 2026 Ondřej Klučka (https://klucon.cz). All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */


declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Administrator\Model;

\defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\Database\ParameterType;
use Joomla\Database\QueryInterface;

final class PredictiongamesModel extends EntityListModel
{
	protected array $searchColumns = ['a.name', 'p.name'];

	public function __construct($config = [], $factory = null)
	{
		$config['filter_fields'] = ['id', 'a.id', 'name', 'a.name', 'project_name', 'p.name', 'published', 'a.published', 'deadline_minutes', 'a.deadline_minutes', 'points_exact', 'a.points_exact', 'points_tendency', 'a.points_tendency', 'points_goal_diff', 'a.points_goal_diff'];
		parent::__construct($config, $factory);
	}

	protected function populateState($ordering = 'a.id', $direction = 'desc'): void
	{
		$app = Factory::getApplication();
		$projectId = $app->getInput()->getInt('project_id', 0);

		if ($projectId > 0) {
			$app->setUserState('com_joomleague.predictiongames.project_id', $projectId);
		}

		$this->setState('filter.project_id', $projectId ?: (int) $app->getUserState('com_joomleague.predictiongames.project_id'));
		parent::populateState($ordering, $direction);
	}

	protected function buildQuery(): QueryInterface
	{
		$db = $this->getDatabase();
		$projectId = (int) $this->getState('filter.project_id');
		return $db->createQuery()
			->select('a.*, p.name AS project_name, u.name AS editor, (SELECT COUNT(*) FROM #__joomleague_prediction_tip t WHERE t.game_id = a.id) AS tip_count')
			->from('#__joomleague_prediction_game a')
			->join('LEFT', '#__joomleague_project p ON p.id = a.project_id')
			->join('LEFT', '#__users u ON u.id = a.checked_out')
			->where('a.project_id = :project_id')->bind(':project_id', $projectId, ParameterType::INTEGER);
	}
}
