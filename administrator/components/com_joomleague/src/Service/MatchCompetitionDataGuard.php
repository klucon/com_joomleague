<?php

declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Administrator\Service;

defined('_JEXEC') or die;

use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;

/**
 * A match's participants must stay fixed once any competition data has been recorded against
 * it - changing who took part after a result, lineup, event or statistic already references
 * them would silently orphan or misattribute that data.
 */
final class MatchCompetitionDataGuard
{
	private const TABLES = ['#__joomleague_match_result', '#__joomleague_match_lineup_member', '#__joomleague_match_event', '#__joomleague_match_statistic_value'];

	public function __construct(private readonly DatabaseInterface $database)
	{
	}

	public function hasCompetitionData(int $matchId, bool $lock = false): bool
	{
		foreach (self::TABLES as $table) {
			if ($lock) {
				// Current read: do not miss results committed after a caller's snapshot.
				$found = $this->database->setQuery('SELECT id FROM ' . $this->database->quoteName($table)
					. ' WHERE match_id = ' . $matchId . ' LIMIT 1 FOR UPDATE')->loadResult();
				if ($found !== null) return true;
				continue;
			}
			$query = $this->database->getQuery(true)->select('COUNT(*)')->from($this->database->quoteName($table))
				->where($this->database->quoteName('match_id') . ' = :matchId')->bind(':matchId', $matchId, ParameterType::INTEGER);
			if ((int) $this->database->setQuery($query)->loadResult() > 0) return true;
		}
		return false;
	}

	/** @return array<int, true> Match IDs with recorded competition data. */
	public function lockedMatchIds(array $matchIds): array
	{
		$ids = array_values(array_unique(array_filter(array_map('intval', $matchIds), static fn (int $id): bool => $id > 0)));
		if ($ids === []) return [];

		$locked = [];
		foreach (self::TABLES as $table) {
			$query = $this->database->getQuery(true)->select('DISTINCT ' . $this->database->quoteName('match_id'))
				->from($this->database->quoteName($table))
				->where($this->database->quoteName('match_id') . ' IN (' . implode(',', $ids) . ')');
			foreach ($this->database->setQuery($query)->loadColumn() as $id) $locked[(int) $id] = true;
		}
		return $locked;
	}
}
