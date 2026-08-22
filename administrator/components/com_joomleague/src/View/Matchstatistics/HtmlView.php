<?php

declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Administrator\View\Matchstatistics;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\GenericDataException;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Toolbar\ToolbarHelper;

final class HtmlView extends BaseHtmlView
{
	public object $match; public object $project; public array $statistics = []; public array $participants = [];
	public array $lineup = []; public array $segments = []; public array $values = []; public bool $canEdit = false;

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
			$context = $this->getModel()->getContext($matchId); $this->match = $context['match']; $this->project = $context['project'];
			$this->statistics = $context['statistics']; $this->participants = $context['participants']; $this->lineup = $context['lineup'];
			$this->segments = $context['segments']; $this->values = $this->getModel()->getValues($matchId);
		} catch (\Throwable $error) { throw new GenericDataException($error->getMessage(), 500); }
		$this->canEdit = Factory::getApplication()->getIdentity()->authorise('core.edit', 'com_joomleague');
		ToolbarHelper::title(Text::_('COM_JOOMLEAGUE_MATCHSTATISTICS_TITLE'), 'chart');
		ToolbarHelper::link('index.php?option=com_joomleague&view=matches&round_id=' . (int) $this->match->round_id, 'JTOOLBAR_CLOSE', 'cancel');
		parent::display($tpl);
	}
}
