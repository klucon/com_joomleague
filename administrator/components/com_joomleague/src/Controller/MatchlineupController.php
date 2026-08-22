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

final class MatchlineupController extends BaseController
{
	public function assign(): void
	{
		$this->assertAuthorized();
		$matchId = $this->input->getInt('match_id'); $participantId = $this->input->getInt('participant_id');
		$memberIds = array_map('intval', $this->input->get('cid', [], 'array'));
		if ($memberIds === []) { $this->app->enqueueMessage(Text::_('COM_JOOMLEAGUE_MATCHLINEUP_SELECT_AVAILABLE'), 'warning'); $this->redirectBack($matchId, $participantId); return; }
		try {
			$this->getModel('Matchlineup')->assign($matchId, $participantId, $memberIds, $this->input->get('lineup_status', [], 'array'), $this->input->get('captain', [], 'array'));
			$this->app->enqueueMessage(Text::_('COM_JOOMLEAGUE_MATCHLINEUP_ASSIGN_SUCCESS'), 'success');
		} catch (\Throwable $exception) {
			Log::add($exception->getMessage(), Log::ERROR, 'com_joomleague');
			$this->app->enqueueMessage(Text::_('COM_JOOMLEAGUE_MATCHLINEUP_ASSIGN_FAILED'), 'error');
		}
		$this->redirectBack($matchId, $participantId);
	}

	public function remove(): void
	{
		$this->assertAuthorized();
		$matchId = $this->input->getInt('match_id'); $participantId = $this->input->getInt('participant_id');
		$lineupIds = array_map('intval', $this->input->get('rid', [], 'array'));
		if ($lineupIds === []) { $this->app->enqueueMessage(Text::_('COM_JOOMLEAGUE_MATCHLINEUP_SELECT_ASSIGNED'), 'warning'); $this->redirectBack($matchId, $participantId); return; }
		try {
			$this->getModel('Matchlineup')->remove($matchId, $participantId, $lineupIds);
			$this->app->enqueueMessage(Text::_('COM_JOOMLEAGUE_MATCHLINEUP_REMOVE_SUCCESS'), 'success');
		} catch (\Throwable $exception) {
			Log::add($exception->getMessage(), Log::ERROR, 'com_joomleague');
			$this->app->enqueueMessage(Text::_('COM_JOOMLEAGUE_MATCHLINEUP_REMOVE_FAILED'), 'error');
		}
		$this->redirectBack($matchId, $participantId);
	}

	public function addSubstitution(): void
	{
		$this->assertAuthorized();
		$matchId = $this->input->getInt('match_id'); $participantId = $this->input->getInt('participant_id');
		try {
			$this->getModel('Matchlineup')->addSubstitution($matchId, $participantId, $this->input->get('substitution', [], 'array'));
			$this->app->enqueueMessage(Text::_('COM_JOOMLEAGUE_MATCHLINEUP_SUBSTITUTION_ADD_SUCCESS'), 'success');
		} catch (\Throwable $exception) {
			Log::add($exception->getMessage(), Log::ERROR, 'com_joomleague');
			$this->app->enqueueMessage(Text::_('COM_JOOMLEAGUE_MATCHLINEUP_SUBSTITUTION_ADD_FAILED'), 'error');
		}
		$this->redirectBack($matchId, $participantId);
	}

	public function removeSubstitution(): void
	{
		$this->assertAuthorized();
		$matchId = $this->input->getInt('match_id'); $participantId = $this->input->getInt('participant_id');
		$ids = array_map('intval', $this->input->get('sid', [], 'array'));
		if ($ids === []) { $this->app->enqueueMessage(Text::_('COM_JOOMLEAGUE_MATCHLINEUP_SUBSTITUTION_SELECT'), 'warning'); $this->redirectBack($matchId, $participantId); return; }
		try {
			$this->getModel('Matchlineup')->removeSubstitutions($matchId, $participantId, $ids);
			$this->app->enqueueMessage(Text::_('COM_JOOMLEAGUE_MATCHLINEUP_SUBSTITUTION_REMOVE_SUCCESS'), 'success');
		} catch (\Throwable $exception) {
			Log::add($exception->getMessage(), Log::ERROR, 'com_joomleague');
			$this->app->enqueueMessage(Text::_('COM_JOOMLEAGUE_MATCHLINEUP_SUBSTITUTION_REMOVE_FAILED'), 'error');
		}
		$this->redirectBack($matchId, $participantId);
	}

	private function assertAuthorized(): void
	{
		if (!Session::checkToken()) throw new \RuntimeException(Text::_('JINVALID_TOKEN'), 403);
		$matchId = $this->input->getInt('match_id');
		$projectId = (new MatchProjectResolver(Factory::getContainer()->get(DatabaseInterface::class)))->resolveProjectId($matchId);
		$asset = $projectId > 0 ? 'com_joomleague.project.' . $projectId : 'com_joomleague';
		if (!$this->app->getIdentity()->authorise('joomleague.project.edit.lineup', $asset)) throw new \RuntimeException(Text::_('JERROR_ALERTNOAUTHOR'), 403);
	}

	private function redirectBack(int $matchId, int $participantId): void
	{
		$this->setRedirect(Route::_('index.php?option=com_joomleague&view=matchlineup&match_id=' . $matchId . '&participant_id=' . $participantId, false));
	}
}
