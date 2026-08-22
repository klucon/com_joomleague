<?php

declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Administrator\View\Matchresult;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\Database\DatabaseInterface;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Log\Log;
use Joomla\CMS\MVC\View\GenericDataException;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Joomleague\Component\Joomleague\Administrator\Service\MatchProjectResolver;

final class HtmlView extends BaseHtmlView
{
	/** @var array<string,mixed> */
	public array $context = [];

	public function display($tpl = null): void
	{
		$app = Factory::getApplication();
		$matchId = $app->getInput()->getInt('match_id');
		if ($matchId < 1) {
			$app->enqueueMessage(Text::_('COM_JOOMLEAGUE_CONTEXT_MATCH_REQUIRED'), 'warning');
			$app->redirect(Route::_('index.php?option=com_joomleague&view=projects', false));
			return;
		}

		$projectId = (new MatchProjectResolver(Factory::getContainer()->get(DatabaseInterface::class)))->resolveProjectId($matchId);
		$asset = $projectId > 0 ? 'com_joomleague.project.' . $projectId : 'com_joomleague';
		if (!$app->getIdentity()->authorise('joomleague.project.edit.results', $asset)) {
			throw new GenericDataException(Text::_('JERROR_ALERTNOAUTHOR'), 403);
		}

		try {
			$this->context = $this->getModel()->getContext($matchId);
		} catch (\Throwable $exception) {
			Log::add($exception->getMessage(), Log::ERROR, 'com_joomleague');
			throw new GenericDataException(Text::_('COM_JOOMLEAGUE_MATCHRESULT_LOAD_FAILED'), 500);
		}

		ToolbarHelper::title(Text::sprintf('COM_JOOMLEAGUE_MATCHRESULT_TITLE', $this->context['project']['name']), 'trophy');
		ToolbarHelper::apply('matchresult.apply'); ToolbarHelper::save('matchresult.save');
		ToolbarHelper::cancel('matchresult.cancel', 'JTOOLBAR_CLOSE');
		parent::display($tpl);
	}
}
