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

final class ScheduleplannerController extends BaseController
{
	public function preview(): void
	{
		Session::checkToken() or jexit(Text::_('JINVALID_TOKEN'));
		$stageId = $this->input->getInt('stage_id');
		$this->assertPermission($stageId);
		$options = $this->input->get('schedule', [], 'array');
		$this->app->setUserState('com_joomleague.scheduleplanner.' . $stageId, $options);
		$this->setRedirect(Route::_('index.php?option=com_joomleague&view=scheduleplanner&stage_id=' . $stageId . '&preview=1', false));
	}

	public function apply(): void
	{
		Session::checkToken() or jexit(Text::_('JINVALID_TOKEN'));
		$stageId = $this->input->getInt('stage_id');
		$this->assertPermission($stageId);
		$options = (array) $this->app->getUserState('com_joomleague.scheduleplanner.' . $stageId, []);

		try {
			$result = $this->getModel('Scheduleplanner')->applySchedule($stageId, $options, (int) $this->app->getIdentity()->id);
			$key = $result['reused'] ? 'COM_JOOMLEAGUE_SCHEDULE_ALREADY_APPLIED' : 'COM_JOOMLEAGUE_SCHEDULE_APPLIED';
			$this->setMessage(Text::sprintf($key, $result['rounds'], $result['matches']));
			$this->setRedirect(Route::_('index.php?option=com_joomleague&view=rounds&stage_id=' . $stageId, false));
			return;
		} catch (\Throwable $error) {
			Log::add($error->getMessage(), Log::ERROR, 'com_joomleague.schedule');
			$this->setMessage(Text::_('COM_JOOMLEAGUE_SCHEDULE_APPLY_FAILED'), 'error');
		}

		$this->setRedirect(Route::_('index.php?option=com_joomleague&view=scheduleplanner&stage_id=' . $stageId . '&preview=1', false));
	}

	private function assertPermission(int $stageId): void
	{
		$projectId = (new MatchProjectResolver(Factory::getContainer()->get(DatabaseInterface::class)))->resolveProjectIdFromStage($stageId);
		$asset = $projectId > 0 ? 'com_joomleague.project.' . $projectId : 'com_joomleague';
		if (!$this->app->getIdentity()->authorise('joomleague.project.edit.schedule', $asset)) {
			throw new \RuntimeException(Text::_('JERROR_ALERTNOAUTHOR'), 403);
		}
	}
}
