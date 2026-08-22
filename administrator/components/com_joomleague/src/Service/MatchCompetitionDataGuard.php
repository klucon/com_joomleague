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
	public function __construct(private readonly DatabaseInterface $database)
	{
	}

	public function hasCompetitionData(int $matchId): bool
	{
		foreach (['#__joomleague_match_result', '#__joomleague_match_lineup_member', '#__joomleague_match_event', '#__joomleague_match_statistic_value'] as $table) {
			$query = $this->database->getQuery(true)->select('COUNT(*)')->from($this->database->quoteName($table))
				->where($this->database->quoteName('match_id') . ' = :matchId')->bind(':matchId', $matchId, ParameterType::INTEGER);
			if ((int) $this->database->setQuery($query)->loadResult() > 0) return true;
		}
		return false;
	}
}
