<?php

declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Administrator\View\Stagetransitions;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\GenericDataException;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Toolbar\ToolbarHelper;

final class HtmlView extends BaseHtmlView
{
	public array $items=[]; public $pagination; public $state; public $filterForm; public array $activeFilters=[]; public object $project;
	public function display($tpl=null):void {
		$app = Factory::getApplication();
		if ($app->getInput()->getInt('project_id', 0) < 1) {
			$app->enqueueMessage(Text::_('COM_JOOMLEAGUE_CONTEXT_PROJECT_REQUIRED'), 'warning');
			$app->redirect(Route::_('index.php?option=com_joomleague&view=projects', false));
			return;
		}
		$this->items=$this->get('Items');$this->pagination=$this->get('Pagination');$this->state=$this->get('State');$this->filterForm=$this->get('FilterForm');$this->activeFilters=$this->get('ActiveFilters');$this->project=$this->getModel()->getProject();if($errors=$this->get('Errors'))throw new GenericDataException(implode("\n",$errors),500);$user=Factory::getApplication()->getIdentity();ToolbarHelper::title(Text::sprintf('COM_JOOMLEAGUE_STAGE_TRANSITIONS_TITLE_PROJECT',$this->project->name),'shuffle');$asset='com_joomleague.project.'.(int)$this->project->id;$canRun=$user->authorise('joomleague.project.run.transitions',$asset);if($canRun)ToolbarHelper::addNew('stagetransition.add');if($this->items!==[]&&$canRun){ToolbarHelper::editList('stagetransition.edit');ToolbarHelper::publish('stagetransitions.publish','JTOOLBAR_PUBLISH',true);ToolbarHelper::unpublish('stagetransitions.unpublish','JTOOLBAR_UNPUBLISH',true);ToolbarHelper::deleteList('JGLOBAL_CONFIRM_DELETE','stagetransitions.delete');}ToolbarHelper::link('index.php?option=com_joomleague&view=stages&project_id='.(int)$this->project->id,'JTOOLBAR_CLOSE','cancel');parent::display($tpl); }
}
