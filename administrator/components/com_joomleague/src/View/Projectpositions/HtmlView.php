<?php

declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Administrator\View\Projectpositions;

\defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;

class HtmlView extends BaseHtmlView
{
	public ?object $project = null;
	public array $options;
	public string $section = 'positions';

	public function display($tpl = null): void
	{
		$model = $this->getModel();
		$id = (int) $model->getState('project_id');

		$this->project = $id > 0 ? $model->getProject($id) : null;

		if (!$this->project) {
			$this->options = [];
			$this->addTemplatePath(JPATH_COMPONENT_ADMINISTRATOR . '/tmpl/projectassignments');
			ToolbarHelper::title(Text::_('COM_JOOMLEAGUE_PROJECT_CONTEXT_MISSING'), 'warning');
			parent::display($tpl);
			return;
		}

		$method = [
			'positions' => 'getPositions',
			'teams' => 'getTeams',
			'referees' => 'getReferees',
		][$this->section];

		$this->options = $model->$method((int) $this->project->id);
		$this->addTemplatePath(JPATH_COMPONENT_ADMINISTRATOR . '/tmpl/projectassignments');
		ToolbarHelper::title($this->project->name, 'address');
		parent::display($tpl);
	}
}
