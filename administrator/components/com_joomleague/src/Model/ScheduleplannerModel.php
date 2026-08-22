<?php

declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Administrator\Model;

defined('_JEXEC') or die;

use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomleague\Component\Joomleague\Administrator\Service\SchedulePlannerService;
use Joomleague\Component\Joomleague\Administrator\Service\ScheduleTemplateService;

final class ScheduleplannerModel extends BaseDatabaseModel
{
	public function defaults(int $stageId): array
	{
		return (new SchedulePlannerService($this->getDatabase()))->defaults($stageId);
	}

	public function preview(int $stageId, array $options): array
	{
		return (new SchedulePlannerService($this->getDatabase()))->preview($stageId, $options);
	}

	public function applySchedule(int $stageId, array $options, int $actorId): array
	{
		return (new SchedulePlannerService($this->getDatabase()))->apply($stageId, $options, $actorId);
	}

	public function templates(): array
	{
		return (new ScheduleTemplateService())->all();
	}
}
