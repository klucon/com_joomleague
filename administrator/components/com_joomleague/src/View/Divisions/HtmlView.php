<?php
/**
 * @package     JoomLeague
 * @copyright   Copyright (C) 2026 Ondřej Klučka (https://klucon.cz). All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */


declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Administrator\View\Divisions;

\defined('_JEXEC') or die;

use Joomleague\Component\Joomleague\Administrator\View\Common\AdminListView;

final class HtmlView extends AdminListView
{
	public ?object $projectContext = null;

	public function display($tpl = null): void
	{
		$this->projectContext = $this->getModel()->getProjectContext();
		parent::display($tpl);
	}

	protected function configure(): array
	{
		$projectId = (int) $this->getModel()->getState('filter.project_id');

		return [
			'title' => 'COM_JOOMLEAGUE_DIVISIONS_TITLE',
			'caption' => 'COM_JOOMLEAGUE_DIVISIONS_TITLE',
			'icon' => 'tree-2',
			'singular' => 'division',
			'plural' => 'divisions',
			'primary' => 'name',
			'state' => true,
			'toolbar_links' => [
				['url' => 'index.php?option=com_joomleague&view=projectpanel&project_id=' . $projectId, 'label' => 'COM_JOOMLEAGUE_BACK_TO_PROJECT_PANEL', 'icon' => 'arrow-left'],
			],
			'columns' => [
				['field' => 'name', 'label' => 'COM_JOOMLEAGUE_FIELD_NAME', 'sort' => 'a.name'],
				['field' => 'picture', 'label' => 'COM_JOOMLEAGUE_FIELD_IMAGE', 'type' => 'image', 'image_placeholder' => 'division_picture'],
				['field' => 'shortname', 'label' => 'COM_JOOMLEAGUE_DIVISION_FIELD_SHORTNAME', 'sort' => 'a.shortname'],
				['field' => 'parent_name', 'label' => 'COM_JOOMLEAGUE_FIELD_PARENT', 'sort' => 'parent_name'],
				['field' => 'ordering', 'label' => 'JFIELD_ORDERING_LABEL', 'sort' => 'a.ordering'],
				['field' => 'id', 'label' => 'JGRID_HEADING_ID', 'sort' => 'a.id'],
			],
		];
	}
}
