<?php

declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Administrator\View\Rounds;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\GenericDataException;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Toolbar\ToolbarHelper;

final class HtmlView extends BaseHtmlView
{
	public array $items = []; public $pagination; public $state; public $filterForm; public array $activeFilters = []; public object $stage;
	public function display($tpl = null): void
	{
		$app = Factory::getApplication(); $input = $app->getInput();
		if ($input->getInt('stage_id') < 1) {
			$app->enqueueMessage(Text::_('COM_JOOMLEAGUE_CONTEXT_STAGE_REQUIRED'), 'warning');
			$projectId = $input->getInt('project_id');
			$app->redirect(Route::_($projectId > 0 ? 'index.php?option=com_joomleague&view=stages&project_id=' . $projectId : 'index.php?option=com_joomleague&view=projects', false));
			return;
		}
		$this->items = $this->get('Items'); $this->pagination = $this->get('Pagination'); $this->state = $this->get('State'); $this->filterForm = $this->get('FilterForm'); $this->activeFilters = $this->get('ActiveFilters'); $this->stage = $this->getModel()->getStage();
		if ($errors = $this->get('Errors')) throw new GenericDataException(implode("\n", $errors), 500);
		$user = Factory::getApplication()->getIdentity(); ToolbarHelper::title(Text::sprintf('COM_JOOMLEAGUE_ROUNDS_TITLE_STAGE', $this->stage->name), 'list');
		$asset = 'com_joomleague.project.' . (int) $this->stage->project_id;
		if ($user->authorise('joomleague.project.edit.schedule', $asset)) ToolbarHelper::link('index.php?option=com_joomleague&view=scheduleplanner&stage_id=' . (int) $this->stage->id, 'COM_JOOMLEAGUE_SCHEDULE_GENERATE', 'calendar');
		if ($user->authorise('core.create', $asset)) ToolbarHelper::addNew('round.add'); if ($this->items !== [] && $user->authorise('core.edit', $asset)) ToolbarHelper::editList('round.edit');
		if ($this->items !== [] && $user->authorise('core.edit.state', $asset)) { ToolbarHelper::publish('rounds.publish', 'JTOOLBAR_PUBLISH', true); ToolbarHelper::unpublish('rounds.unpublish', 'JTOOLBAR_UNPUBLISH', true); ToolbarHelper::checkin('rounds.checkin'); }
		if ($this->items !== [] && $user->authorise('core.delete', $asset)) ToolbarHelper::deleteList('JGLOBAL_CONFIRM_DELETE', 'rounds.delete');
		ToolbarHelper::link('index.php?option=com_joomleague&view=stages&project_id=' . (int) $this->stage->project_id, 'JTOOLBAR_CLOSE', 'cancel'); parent::display($tpl);
	}
}
