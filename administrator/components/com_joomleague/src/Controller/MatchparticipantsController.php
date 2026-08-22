<?php

declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Administrator\Controller;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\Database\DatabaseInterface;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Log\Log;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Session\Session;
use Joomleague\Component\Joomleague\Administrator\Service\MatchProjectResolver;

final class MatchparticipantsController extends BaseController
{
	public function add(): void
	{
		$matchId = $this->input->getInt('match_id');
		$this->assertAuthorized($matchId);
		$entryIds = array_map('intval', $this->input->get('cid', [], 'array'));
		if ($entryIds === []) {
			$this->app->enqueueMessage(Text::_('COM_JOOMLEAGUE_MATCHPARTICIPANTS_SELECT_ADD'), 'warning');
			$this->redirectBack($matchId);
			return;
		}
		try {
			$this->getModel('Matchparticipants')->add($matchId, $entryIds, (int) $this->app->getIdentity()->id);
			$this->app->enqueueMessage(Text::_('COM_JOOMLEAGUE_MATCHPARTICIPANTS_ADD_SUCCESS'), 'success');
		} catch (\Throwable $exception) {
			Log::add($exception->getMessage(), Log::ERROR, 'com_joomleague');
			$this->app->enqueueMessage(Text::_('COM_JOOMLEAGUE_MATCHPARTICIPANTS_ADD_FAILED'), 'error');
		}
		$this->redirectBack($matchId);
	}

	public function remove(): void
	{
		$matchId = $this->input->getInt('match_id');
		$this->assertAuthorized($matchId);
		$participantIds = array_map('intval', $this->input->get('rid', [], 'array'));
		if ($participantIds === []) {
			$this->app->enqueueMessage(Text::_('COM_JOOMLEAGUE_MATCHPARTICIPANTS_SELECT_REMOVE'), 'warning');
			$this->redirectBack($matchId);
			return;
		}
		try {
			$this->getModel('Matchparticipants')->remove($matchId, $participantIds);
			$this->app->enqueueMessage(Text::_('COM_JOOMLEAGUE_MATCHPARTICIPANTS_REMOVE_SUCCESS'), 'success');
		} catch (\Throwable $exception) {
			Log::add($exception->getMessage(), Log::ERROR, 'com_joomleague');
			$this->app->enqueueMessage(Text::_('COM_JOOMLEAGUE_MATCHPARTICIPANTS_REMOVE_FAILED'), 'error');
		}
		$this->redirectBack($matchId);
	}

	private function assertAuthorized(int $matchId): void
	{
		if (!Session::checkToken()) throw new \RuntimeException(Text::_('JINVALID_TOKEN'), 403);
		$projectId = (new MatchProjectResolver(Factory::getContainer()->get(DatabaseInterface::class)))->resolveProjectId($matchId);
		$asset = $projectId > 0 ? 'com_joomleague.project.' . $projectId : 'com_joomleague';
		if (!$this->app->getIdentity()->authorise('core.edit', $asset)) throw new \RuntimeException(Text::_('JERROR_ALERTNOAUTHOR'), 403);
	}

	private function redirectBack(int $matchId): void
	{
		$this->setRedirect(Route::_('index.php?option=com_joomleague&view=matchparticipants&match_id=' . $matchId, false));
	}
}
