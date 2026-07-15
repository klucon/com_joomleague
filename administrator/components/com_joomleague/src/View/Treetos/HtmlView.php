<?php
/**
 * @package     JoomLeague
 * @copyright   Copyright (C) 2026 Ondřej Klučka (https://klucon.cz). All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */


declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Administrator\View\Treetos;

\defined('_JEXEC') or die;

use Joomleague\Component\Joomleague\Administrator\View\Common\AdminListView;

final class HtmlView extends AdminListView
{
	protected function configure(): array
	{
		$projectId = (int) $this->getModel()->getState('filter.project_id');

		return [
			'title' => 'COM_JOOMLEAGUE_TREES_TITLE',
			'caption' => 'COM_JOOMLEAGUE_TREES_TITLE',
			'icon' => 'tree-2',
			'singular' => 'treeto',
			'plural' => 'treetos',
			'primary' => 'name',
			'state' => true,
			'toolbar_links' => [
				['url' => 'index.php?option=com_joomleague&view=projectpanel&project_id=' . $projectId, 'label' => 'COM_JOOMLEAGUE_BACK_TO_PROJECT_PANEL', 'icon' => 'arrow-left'],
			],
			'columns' => [
				['field' => 'name', 'label' => 'COM_JOOMLEAGUE_FIELD_NAME', 'sort' => 'a.name'],
				['field' => 'trophypic', 'label' => 'COM_JOOMLEAGUE_FIELD_IMAGE', 'type' => 'image', 'image_placeholder' => 'treeto_trophypic'],
				['field' => 'project_name', 'label' => 'COM_JOOMLEAGUE_MENU_PROJECTS', 'sort' => 'p.name'],
				['field' => 'division_name', 'label' => 'COM_JOOMLEAGUE_PROJECT_DIVISIONS'],
				['field' => 'tree_i', 'label' => 'COM_JOOMLEAGUE_TREETO_FIELD_DEPTH', 'sort' => 'a.tree_i'],
				['field' => 'node_count', 'label' => 'COM_JOOMLEAGUE_TREETONODES_TITLE', 'type' => 'treetonodes'],
				['field' => 'id', 'label' => 'COM_JOOMLEAGUE_TREETO_GENERATE', 'type' => 'treetogenerate'],
				['field' => 'id', 'label' => 'JGRID_HEADING_ID', 'sort' => 'a.id'],
			],
		];
	}
}
