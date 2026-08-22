<?php

declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Administrator\Service;

defined('_JEXEC') or die;

use Joomla\CMS\Application\CMSApplicationInterface;
use Joomla\Component\Scheduler\Administrator\Task\Status;
use Throwable;

final class SchedulerStatusService
{
	private const TELEMETRY_TASK_TYPE = 'joomleague.telemetry';

	public function __construct(private readonly CMSApplicationInterface $application)
	{
	}

	/**
	 * @return array{available: bool, exists: bool, enabled: bool, healthy: bool, last_execution: ?string, next_execution: ?string, last_exit_code: ?int, times_executed: int, times_failed: int}
	 */
	public function getTelemetryTaskStatus(): array
	{
		$status = [
			'available' => false,
			'exists' => false,
			'enabled' => false,
			'healthy' => false,
			'last_execution' => null,
			'next_execution' => null,
			'last_exit_code' => null,
			'times_executed' => 0,
			'times_failed' => 0,
		];

		try {
			$model = $this->application->bootComponent('com_scheduler')
				->getMVCFactory()
				->createModel('Tasks', 'Administrator', ['ignore_request' => true]);

			if ($model === null) {
				return $status;
			}

			$model->setState('filter.type', self::TELEMETRY_TASK_TYPE);
			$model->setState('filter.state', '*');
			$model->setState('filter.orphaned', 0);
			$model->setState('list.limit', 1);
			$model->setState('list.select', 'a.*');
			$items = $model->getItems() ?: [];
			$status['available'] = true;

			if ($items === []) {
				return $status;
			}

			$task = reset($items);
			$lastExitCode = $task->last_exit_code === null ? null : (int) $task->last_exit_code;

			$status['exists'] = true;
			$status['enabled'] = (int) $task->state === 1;
			$status['healthy'] = $lastExitCode === null || in_array($lastExitCode, [Status::OK, Status::WILL_RESUME], true);
			$status['last_execution'] = $task->last_execution ?: null;
			$status['next_execution'] = $task->next_execution ?: null;
			$status['last_exit_code'] = $lastExitCode;
			$status['times_executed'] = (int) ($task->times_executed ?? 0);
			$status['times_failed'] = (int) ($task->times_failed ?? 0);
		} catch (Throwable) {
			// Diagnostics must remain available when com_scheduler is unavailable.
		}

		return $status;
	}
}
