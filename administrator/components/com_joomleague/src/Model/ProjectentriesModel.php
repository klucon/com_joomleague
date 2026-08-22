<?php

declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Administrator\Model;

defined('_JEXEC') or die;

use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomleague\Component\Joomleague\Administrator\Service\ProjectContextRepository;
use Joomleague\Component\Joomleague\Administrator\Service\ProjectEntryRepository;

final class ProjectentriesModel extends BaseDatabaseModel
{
	public function getProject(int $projectId): object
	{
		return (new ProjectContextRepository($this->getDatabase()))->get($projectId);
	}

	/** @return list<object> */
	public function getEntries(int $projectId, string $search = '', string $lifecycleState = ''): array
	{
		return (new ProjectEntryRepository($this->getDatabase()))->getEntries($projectId, $search, $lifecycleState);
	}
}
