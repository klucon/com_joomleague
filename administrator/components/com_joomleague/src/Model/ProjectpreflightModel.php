<?php

declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Administrator\Model;

defined('_JEXEC') or die;

use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomleague\Component\Joomleague\Administrator\Service\ProjectPreflightService;

final class ProjectpreflightModel extends BaseDatabaseModel
{
	public function inspect(int $projectId): array
	{
		return (new ProjectPreflightService($this->getDatabase()))->inspect($projectId);
	}
}
