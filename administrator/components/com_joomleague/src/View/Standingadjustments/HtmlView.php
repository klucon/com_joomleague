<?php

declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Administrator\View\Standingadjustments;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\GenericDataException;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Toolbar\ToolbarHelper;

final class HtmlView extends BaseHtmlView
{
	public array $items = []; public $pagination; public $state; public $filterForm; public array $activeFilters = []; public object $project;
	public function display($tpl = null): void
	{
		$app = Factory::getApplication();
		if ($app->getInput()->getInt('project_id', 0) < 1) {
			$app->enqueueMessage(Text::_('COM_JOOMLEAGUE_CONTEXT_PROJECT_REQUIRED'), 'warning');
			$app->redirect(Route::_('index.php?option=com_joomleague&view=projects', false));
			return;
		}
		$this->items = $this->get('Items'); $this->pagination = $this->get('Pagination'); $this->state = $this->get('State'); $this->filterForm = $this->get('FilterForm'); $this->activeFilters = $this->get('ActiveFilters'); $this->project = $this->getModel()->getProject(); if ($errors = $this->get('Errors')) throw new GenericDataException(implode("\n", $errors), 500);
		$user = Factory::getApplication()->getIdentity(); ToolbarHelper::title(Text::sprintf('COM_JOOMLEAGUE_STANDING_ADJUSTMENTS_TITLE_PROJECT', $this->project->name), 'plus-circle');
		$asset = 'com_joomleague.project.' . (int) $this->project->id;
		if ($user->authorise('core.create', $asset)) ToolbarHelper::addNew('standingadjustment.add'); if ($user->authorise('core.edit', $asset)) ToolbarHelper::editList('standingadjustment.edit');
		if ($user->authorise('core.edit.state', $asset)) { ToolbarHelper::publish('standingadjustments.publish', 'JTOOLBAR_PUBLISH', true); ToolbarHelper::unpublish('standingadjustments.unpublish', 'JTOOLBAR_UNPUBLISH', true); }
		if ($user->authorise('core.delete', $asset)) ToolbarHelper::deleteList('JGLOBAL_CONFIRM_DELETE', 'standingadjustments.delete');
		$stageId = (int) $this->state->get('stage_id'); $url = 'index.php?option=com_joomleague&view=standings&project_id=' . (int) $this->project->id; if ($stageId > 0) $url .= '&stage_id=' . $stageId; ToolbarHelper::link($url, 'JTOOLBAR_CLOSE', 'cancel'); parent::display($tpl);
	}
}
