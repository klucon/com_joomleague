<?php

declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Administrator\View\Projectschedule;

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
	public array $stageOptions = [];
	public array $roundOptions = [];

	public function display($tpl = null): void
	{
		$app = Factory::getApplication();
		$input = $app->getInput();
		$projectId = $input->getInt('project_id');
		if ($projectId < 1) {
			$app->enqueueMessage(Text::_('COM_JOOMLEAGUE_CONTEXT_PROJECT_REQUIRED'), 'warning');
			$app->redirect(Route::_('index.php?option=com_joomleague&view=projects', false));
			return;
		}

		$this->items = $this->get('Items');
		$this->pagination = $this->get('Pagination');
		$this->state = $this->get('State');
		$this->filterForm = $this->get('FilterForm');
		$this->activeFilters = $this->get('ActiveFilters');

		try {
			$this->project = $this->getModel()->getProject($projectId);
			$this->stageOptions = $this->getModel()->getStageOptions($projectId);
			$this->roundOptions = $this->getModel()->getRoundOptions($projectId);
		} catch (\Throwable $exception) {
			throw new GenericDataException($exception->getMessage(), 500);
		}

		if ($errors = $this->get('Errors')) {
			throw new GenericDataException(implode("\n", $errors), 500);
		}

		ToolbarHelper::title(Text::sprintf('COM_JOOMLEAGUE_PROJECTSCHEDULE_TITLE', $this->project->name), 'calendar');
		if ($app->getIdentity()->authorise('joomleague.project.edit.schedule', 'com_joomleague.project.' . $projectId)) {
			ToolbarHelper::custom('projectschedule.exportCsv', 'download', 'download', 'COM_JOOMLEAGUE_PROJECTSCHEDULE_EXPORT_CSV', false);
		}
		ToolbarHelper::link('index.php?option=com_joomleague&view=projectpanel&project_id=' . $projectId, 'JTOOLBAR_CLOSE', 'cancel');
		parent::display($tpl);
	}
}
