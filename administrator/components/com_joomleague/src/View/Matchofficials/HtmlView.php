<?php

declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Administrator\View\Matchofficials;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\GenericDataException;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Toolbar\ToolbarHelper;

final class HtmlView extends BaseHtmlView
{
	public object $match; public object $project; public array $roles = []; public array $available = []; public array $assignments = []; public bool $canEdit = false;
	public function display($tpl = null): void
	{
		$app = Factory::getApplication();
		$matchId = $app->getInput()->getInt('match_id');
		if ($matchId < 1) {
			$app->enqueueMessage(Text::_('COM_JOOMLEAGUE_CONTEXT_MATCH_REQUIRED'), 'warning');
			$app->redirect(Route::_('index.php?option=com_joomleague&view=projects', false));
			return;
		}
		try { $context = $this->getModel()->getContext($matchId); $this->match = $context['match']; $this->project = $context['project']; $this->roles = $context['roles']; $this->available = $this->getModel()->getAvailable($matchId); $this->assignments = $this->getModel()->getAssignments($matchId); }
		catch (\Throwable $error) { throw new GenericDataException($error->getMessage(), 500); }
		$projectAsset = 'com_joomleague.project.' . (int) ($this->project->id ?? 0);
		$this->canEdit = Factory::getApplication()->getIdentity()->authorise('joomleague.project.manage.officials', $projectAsset);
		ToolbarHelper::title(Text::_('COM_JOOMLEAGUE_MATCHOFFICIALS_TITLE'), 'user-secret');
		ToolbarHelper::link('index.php?option=com_joomleague&view=matches&round_id=' . (int) $this->match->round_id, 'JTOOLBAR_CLOSE', 'cancel');
		parent::display($tpl);
	}
}
