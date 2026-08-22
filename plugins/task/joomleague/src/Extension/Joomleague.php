<?php

declare(strict_types=1);

namespace Joomleague\Plugin\Task\Joomleague\Extension;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\Component\Scheduler\Administrator\Event\ExecuteTaskEvent;
use Joomla\Component\Scheduler\Administrator\Task\Status;
use Joomla\Component\Scheduler\Administrator\Traits\TaskPluginTrait;
use Joomla\Database\DatabaseInterface;
use Joomla\Event\SubscriberInterface;
use Joomleague\Component\Joomleague\Administrator\Service\TelemetryService;

final class Joomleague extends CMSPlugin implements SubscriberInterface
{
	use TaskPluginTrait;

	private const TASKS_MAP = [
		'joomleague.telemetry' => [
			'langConstPrefix' => 'PLG_TASK_JOOMLEAGUE_TELEMETRY',
			'method' => 'sendTelemetry',
		],
	];

	protected $autoloadLanguage = true;

	public static function getSubscribedEvents(): array
	{
		return [
			'onTaskOptionsList' => 'advertiseRoutines',
			'onExecuteTask' => 'standardRoutineHandler',
		];
	}

	private function sendTelemetry(ExecuteTaskEvent $event): int
	{
		$container = Factory::getContainer();
		$service = new TelemetryService(
			$container->get(DatabaseInterface::class),
			$this->getApplication(),
		);
		$service->maybeSend();

		return Status::OK;
	}
}
