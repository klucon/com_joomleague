<?php

declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Administrator\Controller;

defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;
use Joomla\CMS\Log\Log;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Session\Session;

final class StandingsController extends BaseController
{
	public function recalculate(): void
	{
		if (!Session::checkToken()) throw new \RuntimeException(Text::_('JINVALID_TOKEN'), 403);
		$projectId = $this->input->getInt('project_id'); $stageId = $this->input->getInt('stage_id') ?: null; $scope = $this->input->getCmd('scope');
		$asset = $projectId > 0 ? 'com_joomleague.project.' . $projectId : 'com_joomleague';
		if (!$this->app->getIdentity()->authorise('joomleague.project.edit.results', $asset)) throw new \RuntimeException(Text::_('JERROR_ALERTNOAUTHOR'), 403);
		try { $this->getModel('Standings')->recalculate($projectId, $stageId, $scope); $this->app->enqueueMessage(Text::_('COM_JOOMLEAGUE_STANDINGS_RECALCULATE_SUCCESS'), 'success'); }
		catch (\Throwable $error) { Log::add($error->getMessage(), Log::ERROR, 'com_joomleague.standings'); $this->app->enqueueMessage(Text::_('COM_JOOMLEAGUE_STANDINGS_RECALCULATE_FAILED'), 'error'); }
		$url = 'index.php?option=com_joomleague&view=standings&project_id=' . $projectId . '&scope=' . rawurlencode($scope); if ($stageId !== null) $url .= '&stage_id=' . $stageId;
		$this->setRedirect(Route::_($url, false));
	}
}
