<?php
declare(strict_types=1);
namespace Joomleague\Component\Joomleague\Administrator\Model;
defined('_JEXEC') or die;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomleague\Component\Joomleague\Administrator\Service\StageProgressionService;
final class StageprogressionModel extends BaseDatabaseModel
{
	public function preview(int $transitionId): array { return (new StageProgressionService($this->getDatabase()))->preview($transitionId); }
	public function apply(int $transitionId, int $actorId): array { return (new StageProgressionService($this->getDatabase()))->apply($transitionId, $actorId); }
}
