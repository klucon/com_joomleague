<?php

declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Administrator\Model;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomleague\Component\Joomleague\Domain\Service\StandingsReader;
use Joomleague\Component\Joomleague\Domain\Service\StandingsRecalculator;

final class StandingsModel extends BaseDatabaseModel
{
	private function reader(): StandingsReader { return new StandingsReader($this->getDatabase()); }
	private function recalculator(): StandingsRecalculator { return new StandingsRecalculator($this->getDatabase(), $this->reader()); }
	public function getContext(int $projectId, ?int $stageId): array { return $this->reader()->describe($projectId, $stageId); }
	public function getCurrent(int $projectId, ?int $stageId, string $scope): array
	{
		$this->recalculator()->recalculate($projectId, $stageId, $scope, (int) Factory::getApplication()->getIdentity()->id);
		return $this->reader()->current($projectId, $stageId, $scope);
	}
	public function recalculate(int $projectId, ?int $stageId, string $scope): int { return $this->recalculator()->recalculate($projectId, $stageId, $scope, (int) Factory::getApplication()->getIdentity()->id); }
}
