<?php

declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Administrator\View\Projectentries;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\GenericDataException;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Toolbar\ToolbarHelper;

final class HtmlView extends BaseHtmlView
{
	public object $project;
	public array $entries = [];
	public array $entryModel = [];
	public bool $canEdit = false;
	public string $search = '';
	public string $lifecycleState = '';

	public function display($tpl = null): void
	{
		$app = Factory::getApplication();
		$projectId = $app->getInput()->getInt('project_id');
		if ($projectId < 1) {
			$app->enqueueMessage(Text::_('COM_JOOMLEAGUE_CONTEXT_PROJECT_REQUIRED'), 'warning');
			$app->redirect(Route::_('index.php?option=com_joomleague&view=projects', false));
			return;
		}

		$this->search = $app->getUserStateFromRequest('com_joomleague.projectentries.' . $projectId . '.search', 'search', '', 'string');
		$this->lifecycleState = $app->getUserStateFromRequest('com_joomleague.projectentries.' . $projectId . '.lifecycle_state', 'lifecycle_state', '', 'cmd');

		try {
			$this->project = $this->getModel()->getProject($projectId);
			$this->entries = $this->getModel()->getEntries($projectId, $this->search, $this->lifecycleState);
			$this->entryModel = $this->project->profile['entry_model'];
		} catch (\Throwable $exception) {
			throw new GenericDataException($exception->getMessage(), 500);
		}

		$user = Factory::getApplication()->getIdentity();
		$asset = 'com_joomleague.project.' . $projectId;
		$this->canEdit = $user->authorise('core.edit', $asset);
		ToolbarHelper::title(Text::sprintf('COM_JOOMLEAGUE_PROJECTENTRIES_TITLE', $this->project->name), 'users');
		if ($user->authorise('core.create', $asset)) {
			ToolbarHelper::addNew('projectentry.add');
		}
		if ($this->items !== [] && $this->canEdit) {
			ToolbarHelper::editList('projectentry.edit');
		}
		if ($this->items !== [] && $user->authorise('core.delete', $asset)) {
			ToolbarHelper::deleteList('JGLOBAL_CONFIRM_DELETE', 'projectentries.delete');
		}
		ToolbarHelper::link('index.php?option=com_joomleague&view=projectpanel&project_id=' . $projectId, 'JTOOLBAR_CLOSE', 'cancel');
		parent::display($tpl);
	}
}
