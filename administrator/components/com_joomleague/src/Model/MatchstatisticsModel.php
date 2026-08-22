<?php

declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Administrator\Model;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomleague\Component\Joomleague\Administrator\Service\MatchResultDuration;
use Joomleague\Component\Joomleague\Administrator\Service\MatchStatisticRepository;

final class MatchstatisticsModel extends BaseDatabaseModel
{
	private function repository(): MatchStatisticRepository { return new MatchStatisticRepository($this->getDatabase()); }
	public function getContext(int $matchId): array { return $this->repository()->getContext($matchId); }
	public function getValues(int $matchId): array { return $this->repository()->getValues($matchId); }

	/** @param array<string,mixed> $data */
	public function saveValue(int $matchId, array $data): void
	{
		$db = $this->getDatabase(); $db->transactionStart();
		try { $this->repository()->save($matchId, $data, (int) Factory::getApplication()->getIdentity()->id); $db->transactionCommit(); }
		catch (\Throwable $error) { $db->transactionRollback(); throw $error; }
	}

	/** @param list<int> $ids */
	public function remove(int $matchId, array $ids): void
	{
		$db = $this->getDatabase(); $db->transactionStart();
		try { $repository = $this->repository(); foreach (array_unique($ids) as $id) $repository->remove($matchId, (int) $id); $db->transactionCommit(); }
		catch (\Throwable $error) { $db->transactionRollback(); throw $error; }
	}

	public function displayValue(object $value): string
	{
		if ($value->value_type === 'duration') return MatchResultDuration::format($value->numeric_value);
		if ($value->text_value !== null) return (string) $value->text_value;
		$numeric = rtrim(rtrim((string) $value->numeric_value, '0'), '.');
		if ($numeric === '' || $numeric === '-') $numeric = '0';
		return $numeric . ($value->value_type === 'percentage' ? ' %' : '');
	}
}
