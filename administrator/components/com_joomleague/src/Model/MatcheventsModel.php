<?php

declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Administrator\Model;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomleague\Component\Joomleague\Administrator\Service\MatchEventRepository;

final class MatcheventsModel extends BaseDatabaseModel
{
	private function repository(): MatchEventRepository { return new MatchEventRepository($this->getDatabase()); }
	public function getContext(int $matchId): array { return $this->repository()->getContext($matchId); }
	public function getEvents(int $matchId): array { return $this->repository()->getEvents($matchId); }

	/** @param array<string,mixed> $data */
	public function add(int $matchId, array $data): void
	{
		$db = $this->getDatabase(); $db->transactionStart();
		try { $this->repository()->add($matchId, $data, (int) Factory::getApplication()->getIdentity()->id); $db->transactionCommit(); }
		catch (\Throwable $error) { $db->transactionRollback(); throw $error; }
	}

	/** @param list<int> $ids */
	public function remove(int $matchId, array $ids): void
	{
		$db = $this->getDatabase(); $db->transactionStart();
		try { $repository = $this->repository(); foreach (array_unique($ids) as $id) $repository->remove($matchId, (int) $id); $db->transactionCommit(); }
		catch (\Throwable $error) { $db->transactionRollback(); throw $error; }
	}
}
