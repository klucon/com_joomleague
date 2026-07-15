<?php
/**
 * @package     JoomLeague
 * @copyright   Copyright (C) 2026 Ondřej Klučka (https://klucon.cz). All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */


declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Administrator\View\Predictiongames;

\defined('_JEXEC') or die;

use Joomleague\Component\Joomleague\Administrator\View\Common\AdminListView;

final class HtmlView extends AdminListView
{
	protected function configure(): array
	{
		$projectId = (int) $this->getModel()->getState('filter.project_id');

		return [
			'title' => 'COM_JOOMLEAGUE_PREDICTIONGAMES_TITLE',
			'caption' => 'COM_JOOMLEAGUE_PREDICTIONGAMES_TITLE',
			'icon' => 'star',
			'singular' => 'predictiongame',
			'plural' => 'predictiongames',
			'primary' => 'name',
			'state' => true,
			'toolbar_links' => [
				['url' => 'index.php?option=com_joomleague&view=projectpanel&project_id=' . $projectId, 'label' => 'COM_JOOMLEAGUE_BACK_TO_PROJECT_PANEL', 'icon' => 'arrow-left'],
				['url' => 'index.php?option=com_joomleague&view=predictiontips', 'label' => 'COM_JOOMLEAGUE_PREDICTIONTIPS_TITLE', 'icon' => 'list'],
				['url' => 'index.php?option=com_joomleague&view=predictionscores', 'label' => 'COM_JOOMLEAGUE_PREDICTIONSCORES_TITLE', 'icon' => 'chart'],
			],
			'columns' => [
				['field' => 'name', 'label' => 'COM_JOOMLEAGUE_FIELD_NAME', 'sort' => 'a.name'],
				['field' => 'project_name', 'label' => 'COM_JOOMLEAGUE_MENU_PROJECTS', 'sort' => 'p.name'],
				['field' => 'deadline_minutes', 'label' => 'COM_JOOMLEAGUE_PREDICTIONGAME_FIELD_DEADLINE', 'sort' => 'a.deadline_minutes'],
				['field' => 'points_exact', 'label' => 'COM_JOOMLEAGUE_PREDICTIONGAME_FIELD_POINTS_EXACT', 'sort' => 'a.points_exact'],
				['field' => 'points_tendency', 'label' => 'COM_JOOMLEAGUE_PREDICTIONGAME_FIELD_POINTS_TENDENCY', 'sort' => 'a.points_tendency'],
				['field' => 'points_goal_diff', 'label' => 'COM_JOOMLEAGUE_PREDICTIONGAME_FIELD_POINTS_GOAL_DIFF', 'sort' => 'a.points_goal_diff'],
				['field' => 'tip_count', 'label' => 'COM_JOOMLEAGUE_PREDICTIONGAME_TIP_COUNT'],
				['field' => 'id', 'label' => 'COM_JOOMLEAGUE_PREDICTIONGAME_RECALCULATE', 'type' => 'predictionrecalculate'],
				['field' => 'id', 'label' => 'JGRID_HEADING_ID', 'sort' => 'a.id'],
			],
		];
	}
}
