<?php

declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Administrator\Model;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomleague\Component\Joomleague\Administrator\Service\MatchLineupRepository;

final class MatchlineupModel extends BaseDatabaseModel
{
	private function repository(): MatchLineupRepository
	{
		return new MatchLineupRepository($this->getDatabase(), (string) Factory::getApplication()->get('offset', 'UTC'));
	}

	/** @return array{match:object,profile:array<string,mixed>,participants:list<object>} */
	public function getContext(int $matchId): array { return $this->repository()->getContext($matchId); }
	/** @return list<object> */
	public function getAvailableMembers(int $matchId, int $participantId): array { return $this->repository()->getAvailableMembers($matchId, $participantId); }
	/** @return list<object> */
	public function getAssignedMembers(int $matchId, int $participantId): array { return $this->repository()->getAssignedMembers($matchId, $participantId); }
	/** @return list<object> */
	public function getSubstitutions(int $matchId, int $participantId): array { return $this->repository()->getSubstitutions($matchId, $participantId); }

	/** @param list<int> $memberIds @param array<int,string> $statuses @param array<int,int|string> $captains */
	public function assign(int $matchId, int $participantId, array $memberIds, array $statuses, array $captains): void
	{
		$database = $this->getDatabase();
		$database->transactionStart();
		try {
			$repository = $this->repository();
			foreach (array_values(array_unique($memberIds)) as $memberId) {
				$memberId = (int) $memberId;
				$repository->assign($matchId, $participantId, $memberId, (string) ($statuses[$memberId] ?? 'available'), !empty($captains[$memberId]), (int) Factory::getApplication()->getIdentity()->id);
			}
			$database->transactionCommit();
		} catch (\Throwable $exception) {
			$database->transactionRollback();
			throw $exception;
		}
	}

	/** @param list<int> $lineupIds */
	public function remove(int $matchId, int $participantId, array $lineupIds): void
	{
		$database = $this->getDatabase();
		$database->transactionStart();
		try {
			$repository = $this->repository();
			foreach (array_values(array_unique($lineupIds)) as $lineupId) $repository->remove($matchId, $participantId, (int) $lineupId);
			$database->transactionCommit();
		} catch (\Throwable $exception) {
			$database->transactionRollback();
			throw $exception;
		}
	}

	/** @param array<string,mixed> $data */
	public function addSubstitution(int $matchId, int $participantId, array $data): void
	{
		$database = $this->getDatabase(); $database->transactionStart();
		try {
			$this->repository()->addSubstitution(
				$matchId, $participantId, (int) ($data['outgoing_id'] ?? 0), (int) ($data['incoming_id'] ?? 0),
				isset($data['phase_code']) ? (string) $data['phase_code'] : null,
				isset($data['phase_sequence']) && $data['phase_sequence'] !== '' ? (int) $data['phase_sequence'] : null,
				isset($data['clock_value']) ? (string) $data['clock_value'] : null,
				isset($data['clock_unit']) ? (string) $data['clock_unit'] : null,
				isset($data['notes']) ? (string) $data['notes'] : null,
				(int) Factory::getApplication()->getIdentity()->id
			);
			$database->transactionCommit();
		} catch (\Throwable $exception) { $database->transactionRollback(); throw $exception; }
	}

	/** @param list<int> $changeIds */
	public function removeSubstitutions(int $matchId, int $participantId, array $changeIds): void
	{
		$database = $this->getDatabase(); $database->transactionStart();
		try {
			$repository = $this->repository();
			foreach (array_values(array_unique($changeIds)) as $changeId) $repository->removeSubstitution($matchId, $participantId, (int) $changeId);
			$database->transactionCommit();
		} catch (\Throwable $exception) { $database->transactionRollback(); throw $exception; }
	}
}
