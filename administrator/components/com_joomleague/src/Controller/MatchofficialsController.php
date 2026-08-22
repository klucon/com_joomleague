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

final class MatchofficialsController extends BaseController
{
	public function assign(): void
	{
		$matchId = $this->input->getInt('match_id');
		$this->authorize($matchId); $sourceId = $this->input->getInt('project_actor_role_id');
		try { $this->getModel('Matchofficials')->assign($matchId, $sourceId, $this->input->getString('notes')); $this->app->enqueueMessage(Text::_('COM_JOOMLEAGUE_MATCHOFFICIALS_ASSIGN_SUCCESS'), 'success'); }
		catch (\Throwable $error) { Log::add($error->getMessage(), Log::ERROR, 'com_joomleague.matchofficials'); $this->app->enqueueMessage(Text::_('COM_JOOMLEAGUE_MATCHOFFICIALS_ASSIGN_FAILED'), 'error'); }
		$this->redirectBack($matchId);
	}

	public function remove(): void
	{
		$matchId = $this->input->getInt('match_id');
		$this->authorize($matchId); $ids = array_map('intval', $this->input->get('cid', [], 'array'));
		if ($ids === []) { $this->app->enqueueMessage(Text::_('COM_JOOMLEAGUE_MATCHOFFICIALS_SELECT_REMOVE'), 'warning'); $this->redirectBack($matchId); return; }
		try { $this->getModel('Matchofficials')->remove($matchId, $ids); $this->app->enqueueMessage(Text::_('COM_JOOMLEAGUE_MATCHOFFICIALS_REMOVE_SUCCESS'), 'success'); }
		catch (\Throwable $error) { Log::add($error->getMessage(), Log::ERROR, 'com_joomleague.matchofficials'); $this->app->enqueueMessage(Text::_('COM_JOOMLEAGUE_MATCHOFFICIALS_REMOVE_FAILED'), 'error'); }
		$this->redirectBack($matchId);
	}

	private function authorize(int $matchId): void
	{
		if (!Session::checkToken()) throw new \RuntimeException(Text::_('JINVALID_TOKEN'), 403);
		$projectId = (new MatchProjectResolver(Factory::getContainer()->get(DatabaseInterface::class)))->resolveProjectId($matchId);
		$asset = $projectId > 0 ? 'com_joomleague.project.' . $projectId : 'com_joomleague';
		if (!$this->app->getIdentity()->authorise('joomleague.project.manage.officials', $asset)) throw new \RuntimeException(Text::_('JERROR_ALERTNOAUTHOR'), 403);
	}
	private function redirectBack(int $matchId): void { $this->setRedirect(Route::_('index.php?option=com_joomleague&view=matchofficials&match_id=' . $matchId, false)); }
}
