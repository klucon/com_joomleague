<?php

declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Administrator\Controller;

\defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Session\Session;
use Joomleague\Component\Joomleague\Administrator\Service\ScheduleGeneratorService;
use RuntimeException;
use Throwable;

final class ScheduleController extends BaseController
{
	private ScheduleGeneratorService $service;

	public function setScheduleGeneratorService(ScheduleGeneratorService $service): void
	{
		$this->service = $service;
	}

	public function generate(): void
	{
		Session::checkToken() or jexit(Text::_('JINVALID_TOKEN'));

		$projectId = $this->input->getInt('project_id');

		try {
			if (!$this->app->getIdentity()->authorise('core.edit', 'com_joomleague.project.' . $projectId)) {
				throw new RuntimeException(Text::_('JLIB_APPLICATION_ERROR_EDIT_NOT_PERMITTED'), 403);
			}

			$interval = max(1, $this->input->getInt('interval', 7));
			$pattern = Text::_('COM_JOOMLEAGUE_SCHEDULE_ROUND_NAME_PATTERN');
			$includeReturnLegs = (bool) $this->input->getInt('double_round', 0);

			if ($this->input->getCmd('mode') === 'empty') {
				$result = $this->service->createEmptyRounds(
					$projectId,
					$this->input->getString('start_date'),
					$interval,
					max(1, $this->input->getInt('round_count', 1)),
					$pattern
				);
			} else {
				$result = $this->service->generateRoundRobin(
					$projectId,
					$this->input->getString('start_date'),
					$this->input->getString('start_time'),
					$interval,
					$includeReturnLegs,
					$this->input->getInt('match_number'),
					$pattern,
					$this->input->getCmd('template_id', ScheduleGeneratorService::ROUND_ROBIN_FIRST_HALF)
				);
			}

			$this->setRedirect(
				Route::_('index.php?option=com_joomleague&view=rounds&project_id=' . $projectId, false),
				Text::sprintf('COM_JOOMLEAGUE_SCHEDULE_SUCCESS', $result['rounds'], $result['matches'])
			);
		} catch (Throwable $exception) {
			$message = $exception->getMessage();
			$this->setRedirect(
				Route::_('index.php?option=com_joomleague&view=schedule&project_id=' . $projectId, false),
				str_starts_with($message, 'COM_') ? Text::_($message) : $message,
				'error'
			);
		}
	}
}
