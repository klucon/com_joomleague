<?php

declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Administrator\Model;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomleague\Component\Joomleague\Domain\Service\StandingsReader;
use Joomleague\Component\Joomleague\Domain\Service\StandingsSnapshotSynchronizer;

final class StandingsModel extends BaseDatabaseModel
{
	private function reader(): StandingsReader { return new StandingsReader($this->getDatabase()); }
	public function getContext(int $projectId, ?int $stageId): array { return $this->reader()->describe($projectId, $stageId); }
	/** @param array<string,mixed>|null $context */
	public function getAllCurrent(int $projectId, ?int $stageId, ?array $context = null): array
	{
		$context ??= $this->getContext($projectId, $stageId);
		(new StandingsSnapshotSynchronizer($this->getDatabase()))->synchronize($projectId, $stageId, (int) Factory::getApplication()->getIdentity()->id, $context);
		$reader = $this->reader();
		$current = [];
		foreach ($context['available_scopes'] as $scope) $current[(string) $scope] = $reader->current($projectId, $stageId, (string) $scope);
		return $current;
	}
}
