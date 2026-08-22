<?php

declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Administrator\View\Projectofficials;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\GenericDataException;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Toolbar\ToolbarHelper;

final class HtmlView extends BaseHtmlView
{
	public object $project; public array $roles = []; public array $persons = []; public array $teams = []; public array $assignments = []; public bool $canEdit = false;
	public function display($tpl = null): void
	{
		$app = Factory::getApplication();
		$projectId = $app->getInput()->getInt('project_id');
		if ($projectId < 1) {
			$app->enqueueMessage(Text::_('COM_JOOMLEAGUE_CONTEXT_PROJECT_REQUIRED'), 'warning');
			$app->redirect(Route::_('index.php?option=com_joomleague&view=projects', false));
			return;
		}
		try { $context = $this->getModel()->getContext($projectId); $actors = $this->getModel()->getActors(); $this->project = $context['project']; $this->roles = $context['roles']; $this->persons = $actors['persons']; $this->teams = $actors['teams']; $this->assignments = $this->getModel()->getAssignments($projectId); }
		catch (\Throwable $error) { throw new GenericDataException($error->getMessage(), 500); }
		$this->canEdit = Factory::getApplication()->getIdentity()->authorise('joomleague.project.manage.officials', 'com_joomleague.project.' . $projectId);
		ToolbarHelper::title(Text::sprintf('COM_JOOMLEAGUE_PROJECTOFFICIALS_TITLE', $this->project->name), 'user-secret');
		ToolbarHelper::link('index.php?option=com_joomleague&view=projectpanel&project_id=' . (int) $this->project->id, 'JTOOLBAR_CLOSE', 'cancel');
		parent::display($tpl);
	}
}
