<?php

declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Administrator\View\Matchparticipants;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\Database\DatabaseInterface;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\GenericDataException;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Joomleague\Component\Joomleague\Administrator\Service\MatchProjectResolver;

final class HtmlView extends BaseHtmlView
{
	public object $match;
	public string $contestType = 'head_to_head';
	public bool $locked = false;
	/** @var list<array{id:int,entry_id:int,name:string}> */
	public array $assigned = [];
	/** @var list<object> */
	public array $available = [];

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
			$context = $this->getModel()->getContext($matchId);
			$this->match = $context['match'];
			$this->contestType = $context['contestType'];
			$this->locked = $context['locked'];
			$this->assigned = $context['assigned'];
			$this->available = $context['available'];
		} catch (\Throwable $exception) {
			throw new GenericDataException($exception->getMessage(), 500);
		}

		$projectId = (new MatchProjectResolver(Factory::getContainer()->get(DatabaseInterface::class)))->resolveProjectId($matchId);
		$asset = $projectId > 0 ? 'com_joomleague.project.' . $projectId : 'com_joomleague';
		ToolbarHelper::title(Text::_('COM_JOOMLEAGUE_MATCHPARTICIPANTS_TITLE'), 'users');
		if (!$this->locked && Factory::getApplication()->getIdentity()->authorise('core.edit', $asset)) {
			ToolbarHelper::custom('matchparticipants.add', 'plus', 'plus', 'COM_JOOMLEAGUE_MATCHPARTICIPANTS_ADD', false);
			ToolbarHelper::custom('matchparticipants.remove', 'minus', 'minus', 'COM_JOOMLEAGUE_MATCHPARTICIPANTS_REMOVE', false);
		}
		ToolbarHelper::link('index.php?option=com_joomleague&view=matches&round_id=' . (int) ($this->match->round_id ?? 0), 'JTOOLBAR_CLOSE', 'cancel');
		parent::display($tpl);
	}
}
