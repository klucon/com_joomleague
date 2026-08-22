<?php

declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Administrator\View\Projectpanel;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\GenericDataException;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Toolbar\ToolbarHelper;

final class HtmlView extends BaseHtmlView
{
	public $project;
	public array $overrideCounts = [];
	public array $aggregateCounts = [];
	public bool $canEdit = false;

	public function display($tpl = null): void
	{
		$app = Factory::getApplication();
		$projectId = $app->getInput()->getInt('project_id');
		if ($projectId < 1) {
			$app->enqueueMessage(Text::_('COM_JOOMLEAGUE_CONTEXT_PROJECT_REQUIRED'), 'warning');
			$app->redirect(Route::_('index.php?option=com_joomleague&view=projects', false));
			return;
		}

		try {
			$this->project = $this->getModel()->getProject($projectId);
			$this->overrideCounts = $this->getModel()->getOverrideCounts($projectId);
			$this->aggregateCounts = $this->getModel()->getAggregateCounts($projectId);
		} catch (\Throwable $exception) {
			throw new GenericDataException($exception->getMessage(), 500);
		}

		$this->canEdit = Factory::getApplication()->getIdentity()->authorise('core.edit', 'com_joomleague.project.' . $projectId);
		ToolbarHelper::title(Text::_('COM_JOOMLEAGUE_PROJECTPANEL_TOOLBAR_TITLE'), 'folder-open');
		ToolbarHelper::link('index.php?option=com_joomleague&view=projects', 'JTOOLBAR_CLOSE', 'cancel');
		parent::display($tpl);
	}
}
