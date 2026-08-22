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

final class MatcheventsController extends BaseController
{
	public function add(): void
	{
		$matchId = $this->input->getInt('match_id');
		$this->authorize($matchId);
		$data = [];
		foreach (['event_code', 'match_participant_id', 'primary_lineup_member_id', 'secondary_lineup_member_id', 'source_match_actor_role_id', 'score_segment_id', 'phase_code', 'phase_sequence', 'clock_value', 'clock_unit', 'occurred_at', 'numeric_value', 'text_value', 'notes'] as $key) $data[$key] = $this->input->getString($key);
		try { $this->getModel('Matchevents')->add($matchId, $data); $this->app->enqueueMessage(Text::_('COM_JOOMLEAGUE_MATCHEVENTS_ADD_SUCCESS'), 'success'); }
		catch (\Throwable $error) { Log::add($error->getMessage(), Log::ERROR, 'com_joomleague.matchevents'); $this->app->enqueueMessage(Text::_('COM_JOOMLEAGUE_MATCHEVENTS_ADD_FAILED'), 'error'); }
		$this->redirectBack($matchId);
	}

	public function remove(): void
	{
		$matchId = $this->input->getInt('match_id');
		$this->authorize($matchId); $ids = array_map('intval', $this->input->get('cid', [], 'array'));
		if ($ids === []) { $this->app->enqueueMessage(Text::_('COM_JOOMLEAGUE_MATCHEVENTS_SELECT_REMOVE'), 'warning'); $this->redirectBack($matchId); return; }
		try { $this->getModel('Matchevents')->remove($matchId, $ids); $this->app->enqueueMessage(Text::_('COM_JOOMLEAGUE_MATCHEVENTS_REMOVE_SUCCESS'), 'success'); }
		catch (\Throwable $error) { Log::add($error->getMessage(), Log::ERROR, 'com_joomleague.matchevents'); $this->app->enqueueMessage(Text::_('COM_JOOMLEAGUE_MATCHEVENTS_REMOVE_FAILED'), 'error'); }
		$this->redirectBack($matchId);
	}

	private function authorize(int $matchId): void
	{
		if (!Session::checkToken()) throw new \RuntimeException(Text::_('JINVALID_TOKEN'), 403);
		$projectId = (new MatchProjectResolver(Factory::getContainer()->get(DatabaseInterface::class)))->resolveProjectId($matchId);
		$asset = $projectId > 0 ? 'com_joomleague.project.' . $projectId : 'com_joomleague';
		if (!$this->app->getIdentity()->authorise('joomleague.project.edit.results', $asset)) throw new \RuntimeException(Text::_('JERROR_ALERTNOAUTHOR'), 403);
	}

	private function redirectBack(int $matchId): void { $this->setRedirect(Route::_('index.php?option=com_joomleague&view=matchevents&match_id=' . $matchId, false)); }
}
