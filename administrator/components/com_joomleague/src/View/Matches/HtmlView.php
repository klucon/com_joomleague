<?php

declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Administrator\View\Matches;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\GenericDataException;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Joomla\CMS\Uri\Uri;

final class HtmlView extends BaseHtmlView
{
	public array $items = []; public $pagination; public $state; public $filterForm; public array $activeFilters = []; public object $round; public array $entryOptions = []; public string $contestType = 'head_to_head'; public array $venueOptions = [];
	public function display($tpl = null): void
	{
		$app = Factory::getApplication(); $input = $app->getInput();
		if ($input->getInt('round_id') < 1) {
			$app->enqueueMessage(Text::_('COM_JOOMLEAGUE_CONTEXT_ROUND_REQUIRED'), 'warning');
			$stageId = $input->getInt('stage_id'); $projectId = $input->getInt('project_id');
			$url = $stageId > 0 ? 'index.php?option=com_joomleague&view=rounds&stage_id=' . $stageId : ($projectId > 0 ? 'index.php?option=com_joomleague&view=stages&project_id=' . $projectId : 'index.php?option=com_joomleague&view=projects');
			$app->redirect(Route::_($url, false));
			return;
		}
		$this->items = $this->get('Items'); $this->pagination = $this->get('Pagination'); $this->state = $this->get('State'); $this->filterForm = $this->get('FilterForm'); $this->activeFilters = $this->get('ActiveFilters'); $this->round = $this->getModel()->getRound(); $this->entryOptions = $this->getModel()->getEntryOptions(); $this->contestType = $this->getModel()->getContestType(); $this->venueOptions = $this->getModel()->getVenueOptions();
		$this->getDocument()->addScript(Uri::root(true) . '/media/com_joomleague/js/matches-autosave.js', ['version' => 'auto'], ['defer' => true]);
		$this->getDocument()->addScript(Uri::root(true) . '/media/com_joomleague/js/matches-batch.js', ['version' => 'auto'], ['defer' => true]);
		if ($errors = $this->get('Errors')) throw new GenericDataException(implode("\n", $errors), 500);
		$user = Factory::getApplication()->getIdentity(); ToolbarHelper::title(Text::sprintf('COM_JOOMLEAGUE_MATCHES_TITLE_ROUND', $this->round->name), 'play');
		$asset = 'com_joomleague.project.' . (int) ($this->round->project_id ?? 0);
		if ($user->authorise('core.create', $asset)) ToolbarHelper::addNew('match.add');
		if ($user->authorise('joomleague.project.edit.schedule', $asset)) ToolbarHelper::editList('match.edit');
		if ($user->authorise('joomleague.project.edit.schedule', $asset)) ToolbarHelper::modal('matches-batch-modal', 'icon-copy', 'COM_JOOMLEAGUE_MATCHES_BATCH_BUTTON');
		if ($user->authorise('core.edit.state', $asset)) { ToolbarHelper::publish('matches.publish', 'JTOOLBAR_PUBLISH', true); ToolbarHelper::unpublish('matches.unpublish', 'JTOOLBAR_UNPUBLISH', true); ToolbarHelper::checkin('matches.checkin'); }
		if ($user->authorise('core.delete', $asset)) ToolbarHelper::deleteList('JGLOBAL_CONFIRM_DELETE', 'matches.delete');
		ToolbarHelper::link('index.php?option=com_joomleague&view=rounds&stage_id=' . (int) $this->round->stage_id, 'JTOOLBAR_CLOSE', 'cancel'); parent::display($tpl);
	}
}
