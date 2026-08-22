<?php

declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Administrator\View\Projectpreflight;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\GenericDataException;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Toolbar\ToolbarHelper;

final class HtmlView extends BaseHtmlView
{
	public array $report = [];

	public function display($tpl = null): void
	{
		$app = Factory::getApplication();
		$projectId = $app->getInput()->getInt('project_id');
		if ($projectId < 1) {
			$app->enqueueMessage(Text::_('COM_JOOMLEAGUE_CONTEXT_PROJECT_REQUIRED'), 'warning');
			$app->redirect(Route::_('index.php?option=com_joomleague&view=projects', false));
			return;
		}
		if (!$app->getIdentity()->authorise('core.edit', 'com_joomleague.project.' . $projectId)) {
			throw new GenericDataException(Text::_('JERROR_ALERTNOAUTHOR'), 403);
		}
		try { $this->report = $this->getModel()->inspect($projectId); }
		catch (\Throwable $error) { throw new GenericDataException($error->getMessage(), 500); }
		ToolbarHelper::title(Text::_('COM_JOOMLEAGUE_PREFLIGHT_TOOLBAR_TITLE'), 'check-circle');
		ToolbarHelper::link('index.php?option=com_joomleague&view=projectpreflight&project_id=' . $projectId, 'COM_JOOMLEAGUE_PREFLIGHT_REFRESH', 'refresh');
		ToolbarHelper::link('index.php?option=com_joomleague&view=projectpanel&project_id=' . $projectId, 'JTOOLBAR_CLOSE', 'cancel');
		parent::display($tpl);
	}
}
