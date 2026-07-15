<?php
/**
 * @package     JoomLeague
 * @copyright   Copyright (C) 2026 Ondřej Klučka (https://klucon.cz). All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */


declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Administrator\View\Templates;

\defined('_JEXEC') or die;

use Joomleague\Component\Joomleague\Administrator\View\Common\AdminListView;

final class HtmlView extends AdminListView
{
	public ?object $projectContext = null;
	public bool $isGlobalScope = false;

	public function display($tpl = null): void
	{
		$model = $this->getModel();
		$this->isGlobalScope = (string) $model->getState('filter.scope', 'project') === 'global';
		// Lazy self-heal: chybějící řádky (centrální i projektové) vzniknou při prvním otevření obrazovky.
		$model->ensureSeeded();

		$this->projectContext = $model->getProjectContext();
		parent::display($tpl);
	}

	protected function configure(): array
	{
		$projectId = (int) $this->getModel()->getState('filter.project_id');
		$isGlobal = (string) $this->getModel()->getState('filter.scope', 'project') === 'global';

		return [
			'title' => $isGlobal ? 'COM_JOOMLEAGUE_TEMPLATES_GLOBAL_TITLE' : 'COM_JOOMLEAGUE_TEMPLATES_TITLE',
			'caption' => $isGlobal ? 'COM_JOOMLEAGUE_TEMPLATES_GLOBAL_TITLE' : 'COM_JOOMLEAGUE_TEMPLATES_TITLE',
			'icon' => 'palette',
			'singular' => 'template',
			'plural' => 'templates',
			'primary' => 'title',
			'state' => true,
			'can_create' => false,
			'can_delete' => false,
			'toolbar_links' => $isGlobal
				? [['url' => 'index.php?option=com_joomleague&view=tools', 'label' => 'COM_JOOMLEAGUE_TOOLS_BACK', 'icon' => 'arrow-left']]
				: [['url' => 'index.php?option=com_joomleague&view=projectpanel&project_id=' . $projectId, 'label' => 'COM_JOOMLEAGUE_BACK_TO_PROJECT_PANEL', 'icon' => 'arrow-left']],
			'columns' => [
				['field' => 'title', 'label' => 'COM_JOOMLEAGUE_TEMPLATE_FIELD_TITLE', 'sort' => 'a.title', 'type' => 'lang'],
				['field' => 'template', 'label' => 'COM_JOOMLEAGUE_TEMPLATE_FIELD_TEMPLATE', 'sort' => 'a.template'],
				['field' => 'func', 'label' => 'COM_JOOMLEAGUE_TEMPLATE_FIELD_FUNCTION', 'sort' => 'a.func'],
				['field' => 'id', 'label' => 'JGRID_HEADING_ID', 'sort' => 'a.id'],
			],
		];
	}
}
