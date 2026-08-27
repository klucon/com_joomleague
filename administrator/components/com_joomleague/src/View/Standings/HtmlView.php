<?php

declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Administrator\View\Standings;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\GenericDataException;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Toolbar\ToolbarHelper;

final class HtmlView extends BaseHtmlView
{
	public object $project; public ?object $stage = null; public ?int $stageId = null; public string $scope; public array $availableScopes = []; public array $metrics = []; public array $standingsByScope = []; public bool $canEdit = false;

	public function display($tpl = null): void
	{
		$app = Factory::getApplication(); $input = $app->getInput(); $projectId = (int) $input->getInt('project_id');
		if ($projectId < 1) {
			$app->enqueueMessage(Text::_('COM_JOOMLEAGUE_CONTEXT_PROJECT_REQUIRED'), 'warning');
			$app->redirect(Route::_('index.php?option=com_joomleague&view=projects', false));
			return;
		}
		$this->stageId = $input->getInt('stage_id') ?: null;
		try { $context = $this->getModel()->getContext($projectId, $this->stageId); $this->project = $context['project']; $this->stage = $context['stage']; $this->availableScopes = $context['available_scopes']; $this->scope = (string) $context['default_scope']; $this->metrics = $context['contract']['metrics']; $this->standingsByScope = $this->getModel()->getAllCurrent($projectId, $this->stageId, $context); }
		catch (\Throwable $error) { throw new GenericDataException($error->getMessage(), 500); }
		$this->canEdit = Factory::getApplication()->getIdentity()->authorise('core.edit', 'com_joomleague.project.' . $projectId);
		ToolbarHelper::title(Text::_('COM_JOOMLEAGUE_STANDINGS_TITLE'), 'ranking-star');
		if ($this->canEdit) { $url = 'index.php?option=com_joomleague&view=standingadjustments&project_id=' . $projectId; if ($this->stageId !== null) $url .= '&stage_id=' . $this->stageId; ToolbarHelper::link($url, 'COM_JOOMLEAGUE_STANDING_ADJUSTMENTS_MANAGE', 'plus-circle'); }
		$closeUrl = $this->stageId === null ? 'index.php?option=com_joomleague&view=projectpanel&project_id=' . $projectId : 'index.php?option=com_joomleague&view=stages&project_id=' . $projectId;
		ToolbarHelper::link($closeUrl, 'JTOOLBAR_CLOSE', 'cancel'); parent::display($tpl);
	}
}
