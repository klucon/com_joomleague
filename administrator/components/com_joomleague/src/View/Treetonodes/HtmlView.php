<?php
/**
 * @package     JoomLeague
 * @copyright   Copyright (C) 2026 Ondřej Klučka (https://klucon.cz). All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */


declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Administrator\View\Treetonodes;

\defined('_JEXEC') or die;

use Joomleague\Component\Joomleague\Administrator\View\Common\AdminListView;

final class HtmlView extends AdminListView
{
	public ?object $treeContext = null;

	public function display($tpl = null): void
	{
		$this->treeContext = $this->getModel()->getTreeContext();
		parent::display($tpl);
	}

	protected function configure(): array
	{
		$treeId = (int) $this->getModel()->getState('filter.treeto_id');

		return [
			'title' => 'COM_JOOMLEAGUE_TREETONODES_TITLE',
			'caption' => 'COM_JOOMLEAGUE_TREETONODES_TITLE',
			'icon' => 'tree-2',
			'singular' => 'treetonode',
			'plural' => 'treetonodes',
			'primary' => 'title',
			'state' => true,
			'toolbar_links' => [
				['url' => 'index.php?option=com_joomleague&view=treetos', 'label' => 'COM_JOOMLEAGUE_TREES_TITLE', 'icon' => 'arrow-left'],
			],
			'columns' => [
				['field' => 'title', 'label' => 'COM_JOOMLEAGUE_TREETONODE_FIELD_TITLE', 'sort' => 'a.title'],
				['field' => 'node', 'label' => 'COM_JOOMLEAGUE_TREETONODE_FIELD_NODE', 'sort' => 'a.node'],
				['field' => 'row', 'label' => 'COM_JOOMLEAGUE_TREETONODE_FIELD_ROW', 'sort' => 'a.row'],
				['field' => 'team_name', 'label' => 'COM_JOOMLEAGUE_MENU_TEAMS'],
				['field' => 'id', 'label' => 'JGRID_HEADING_ID', 'sort' => 'a.id'],
			],
			'list_action_append' => '&treeto_id=' . $treeId,
		];
	}
}
