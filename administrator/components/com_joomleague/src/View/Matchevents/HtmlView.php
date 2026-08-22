<?php

declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Administrator\View\Matchevents;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\GenericDataException;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Toolbar\ToolbarHelper;

final class HtmlView extends BaseHtmlView
{
	public object $match; public object $project; public array $profile = []; public array $eventDefinitions = [];
	public array $participants = []; public array $lineup = []; public array $officials = []; public array $segments = []; public array $events = []; public bool $canEdit = false;

	public function display($tpl = null): void
	{
		$app = Factory::getApplication();
		$matchId = $app->getInput()->getInt('match_id');
		if ($matchId < 1) {
			$app->enqueueMessage(Text::_('COM_JOOMLEAGUE_CONTEXT_MATCH_REQUIRED'), 'warning');
			$app->redirect(Route::_('index.php?option=com_joomleague&view=projects', false));
			return;
		}
		try {
			$context = $this->getModel()->getContext($matchId); $this->match = $context['match']; $this->project = $context['project']; $this->profile = $context['profile'];
			$this->eventDefinitions = $context['events']; $this->participants = $context['participants']; $this->lineup = $context['lineup'];
			$this->officials = $context['officials']; $this->segments = $context['segments']; $this->events = $this->getModel()->getEvents($matchId);
		} catch (\Throwable $error) { throw new GenericDataException($error->getMessage(), 500); }
		$this->canEdit = Factory::getApplication()->getIdentity()->authorise('core.edit', 'com_joomleague');
		ToolbarHelper::title(Text::_('COM_JOOMLEAGUE_MATCHEVENTS_TITLE'), 'bolt');
		ToolbarHelper::link('index.php?option=com_joomleague&view=matches&round_id=' . (int) $this->match->round_id, 'JTOOLBAR_CLOSE', 'cancel');
		parent::display($tpl);
	}
}
