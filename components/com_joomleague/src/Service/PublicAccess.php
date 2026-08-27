<?php

declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Site\Service;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;

/** Builds SQL conditions from the current identity's authorised Joomla view levels. */
final class PublicAccess
{
	public static function condition(DatabaseInterface $database, string $alias): string
	{
		$levels = array_values(array_unique(array_filter(
			array_map('intval', Factory::getApplication()->getIdentity()->getAuthorisedViewLevels()),
			static fn (int $level): bool => $level > 0
		)));

		if ($levels === []) {
			$levels = [1];
		}

		return $database->quoteName($alias . '.access') . ' IN (' . implode(',', $levels) . ')';
	}

	public static function projectAllowed(DatabaseInterface $database, int $projectId): bool
	{
		$query = $database->getQuery(true)
			->select('COUNT(*)')
			->from($database->quoteName('#__joomleague_project', 'project'))
			->innerJoin($database->quoteName('#__joomleague_competition', 'competition') . ' ON competition.id = project.competition_id AND competition.published = 1')
			->innerJoin($database->quoteName('#__joomleague_season', 'season') . ' ON season.id = project.season_id AND season.published = 1')
			->where('project.id = :projectId')->where('project.published = 1')
			->where(self::condition($database, 'project'))
			->where(self::condition($database, 'competition'))
			->where(self::condition($database, 'season'))
			->bind(':projectId', $projectId, ParameterType::INTEGER);

		return (int) $database->setQuery($query)->loadResult() === 1;
	}
}
