<?php

declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Administrator\Model;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomleague\Component\Joomleague\Administrator\Service\MatchActorRoleRepository;

final class MatchofficialsModel extends BaseDatabaseModel
{
	private function repository(): MatchActorRoleRepository { return new MatchActorRoleRepository($this->getDatabase(), (string) Factory::getApplication()->get('offset', 'UTC')); }
	public function getContext(int $matchId): array { return $this->repository()->getMatchContext($matchId); }
	public function getAvailable(int $matchId): array { return $this->repository()->getAvailableForMatch($matchId); }
	public function getAssignments(int $matchId): array { return $this->repository()->getMatchAssignments($matchId); }
	public function assign(int $matchId, int $projectAssignmentId, ?string $notes): void
	{
		$db = $this->getDatabase(); $db->transactionStart();
		try { $this->repository()->assignToMatch($matchId, $projectAssignmentId, $notes, (int) Factory::getApplication()->getIdentity()->id); $db->transactionCommit(); }
		catch (\Throwable $error) { $db->transactionRollback(); throw $error; }
	}
	/** @param list<int> $ids */
	public function remove(int $matchId, array $ids): void
	{
		$db = $this->getDatabase(); $db->transactionStart();
		try { $repository = $this->repository(); foreach (array_unique($ids) as $id) $repository->removeFromMatch($matchId, (int) $id); $db->transactionCommit(); }
		catch (\Throwable $error) { $db->transactionRollback(); throw $error; }
	}
}
