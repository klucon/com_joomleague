<?php

declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Administrator\View\Stages;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\GenericDataException;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Toolbar\ToolbarHelper;

final class HtmlView extends BaseHtmlView
{
	public array $items = [];
	public $pagination;
	public $state;
	public $filterForm;
	public array $activeFilters = [];
	public object $project;

	public function display($tpl = null): void
	{
		$app = Factory::getApplication();
		if ($app->getInput()->getInt('project_id', 0) < 1) {
			$app->enqueueMessage(Text::_('COM_JOOMLEAGUE_CONTEXT_PROJECT_REQUIRED'), 'warning');
			$app->redirect(Route::_('index.php?option=com_joomleague&view=projects', false));
			return;
		}
		$this->items = $this->get('Items');
		$this->pagination = $this->get('Pagination');
		$this->state = $this->get('State');
		$this->filterForm = $this->get('FilterForm');
		$this->activeFilters = $this->get('ActiveFilters');
		$this->project = $this->getModel()->getProject();
		if ($errors = $this->get('Errors')) throw new GenericDataException(implode("\n", $errors), 500);

		$user = Factory::getApplication()->getIdentity();
		ToolbarHelper::title(Text::sprintf('COM_JOOMLEAGUE_STAGES_TITLE_PROJECT', $this->project->name), 'tree');
		$asset = 'com_joomleague.project.' . (int) $this->project->id;
		$canEditSchedule = $user->authorise('joomleague.project.edit.schedule', $asset);
		if ($canEditSchedule) ToolbarHelper::addNew('stage.add');
		if ($this->items !== [] && $canEditSchedule) { ToolbarHelper::editList('stage.edit'); ToolbarHelper::publish('stages.publish', 'JTOOLBAR_PUBLISH', true); ToolbarHelper::unpublish('stages.unpublish', 'JTOOLBAR_UNPUBLISH', true); ToolbarHelper::checkin('stages.checkin'); ToolbarHelper::deleteList('JGLOBAL_CONFIRM_DELETE', 'stages.delete'); }
		ToolbarHelper::link('index.php?option=com_joomleague&view=stagetransitions&project_id=' . (int) $this->project->id, 'COM_JOOMLEAGUE_STAGE_TRANSITIONS_MANAGE', 'shuffle');
		ToolbarHelper::link('index.php?option=com_joomleague&view=projectpanel&project_id=' . (int) $this->project->id, 'JTOOLBAR_CLOSE', 'cancel');
		parent::display($tpl);
	}
}
