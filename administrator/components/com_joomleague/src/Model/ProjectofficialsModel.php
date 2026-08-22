<?php

declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Administrator\Model;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomleague\Component\Joomleague\Administrator\Service\MatchActorRoleRepository;

final class ProjectofficialsModel extends BaseDatabaseModel
{
	private function repository(): MatchActorRoleRepository { return new MatchActorRoleRepository($this->getDatabase(), (string) Factory::getApplication()->get('offset', 'UTC')); }
	public function getContext(int $projectId): array { return $this->repository()->getProjectContext($projectId); }
	public function getActors(): array { return $this->repository()->getActors(); }
	public function getAssignments(int $projectId): array { return $this->repository()->getProjectAssignments($projectId); }
	/** @param array<string,mixed> $data */
	public function add(int $projectId, array $data): void
	{
		$db = $this->getDatabase(); $db->transactionStart();
		try {
			$this->repository()->addProjectAssignment($projectId, (string) ($data['actor'] ?? ''), (string) ($data['role_code'] ?? ''), isset($data['valid_from']) ? (string) $data['valid_from'] : null, isset($data['valid_until']) ? (string) $data['valid_until'] : null, isset($data['notes']) ? (string) $data['notes'] : null, (int) Factory::getApplication()->getIdentity()->id);
			$db->transactionCommit();
		} catch (\Throwable $error) { $db->transactionRollback(); throw $error; }
	}
	/** @param list<int> $ids */
	public function remove(int $projectId, array $ids): void
	{
		$db = $this->getDatabase(); $db->transactionStart();
		try { $repository = $this->repository(); foreach (array_unique($ids) as $id) $repository->removeProjectAssignment($projectId, (int) $id); $db->transactionCommit(); }
		catch (\Throwable $error) { $db->transactionRollback(); throw $error; }
	}
}
