<?php

declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Administrator\Controller;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Log\Log;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Session\Session;
use Joomla\Database\DatabaseInterface;
use Joomleague\Component\Joomleague\Administrator\Service\MatchProjectResolver;

final class StageentriesController extends BaseController
{
	public function save(): void
	{
		Session::checkToken() or jexit(Text::_('JINVALID_TOKEN'));

		$stageId = $this->input->getInt('stage_id');
		$projectId = (new MatchProjectResolver(Factory::getContainer()->get(DatabaseInterface::class)))->resolveProjectIdFromStage($stageId);
		$asset = $projectId > 0 ? 'com_joomleague.project.' . $projectId : 'com_joomleague';
		if (!$this->app->getIdentity()->authorise('core.edit', $asset)) {
			throw new \RuntimeException(Text::_('JERROR_ALERTNOAUTHOR'), 403);
		}
		$mode = $this->input->getCmd('entry_selection_mode', 'inherit_project');
		$entryIds = $this->input->get('entry_ids', [], 'array');

		try {
			$this->getModel('Stageentries')->saveAssignments($stageId, $mode, $entryIds);
			$this->setMessage(Text::_('COM_JOOMLEAGUE_STAGE_ENTRIES_SAVED'));
		} catch (\Throwable $error) {
			Log::add($error->getMessage(), Log::ERROR, 'com_joomleague.stageentries');
			$this->setMessage(Text::_('COM_JOOMLEAGUE_STAGE_ENTRIES_SAVE_FAILED'), 'error');
		}

		$this->setRedirect(Route::_('index.php?option=com_joomleague&view=stageentries&stage_id=' . $stageId, false));
	}
}
