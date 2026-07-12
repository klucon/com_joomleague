<?php
/**
 * @package     JoomLeague
 * @copyright   Copyright (C) 2026 Ondřej Klučka (https://klucon.cz). All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */


declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Administrator\View\Schedule;

\defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;

final class HtmlView extends BaseHtmlView
{
	public object $project;

	/** @var array<string, object> */
	public array $templates = [];

	public function display($tpl = null): void
	{
		$model = $this->getModel();
		$id = (int) $model->getState('project_id');
		$this->project = $model->getProject($id) ?? throw new \RuntimeException(Text::_('COM_JOOMLEAGUE_PROJECT_NOT_FOUND'), 404);
		$this->templates = $model->getTemplates();

		ToolbarHelper::title($this->project->name, 'calendar');

		parent::display($tpl);
	}
}
