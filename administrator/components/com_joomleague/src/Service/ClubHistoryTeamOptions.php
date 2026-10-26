<?php
declare(strict_types=1);
namespace Joomleague\Component\Joomleague\Domain\Service;
defined('_JEXEC') or die;
use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;

final class ClubHistoryTeamOptions
{
    public static function load(DatabaseInterface $db, int $clubId, array $levels): array
    {
        $access = implode(',', array_values(array_unique(array_filter(array_map('intval', $levels), static fn (int $id): bool => $id > 0))) ?: [1]);
        $query = $db->getQuery(true)->select(['DISTINCT team.id', 'team.name'])
            ->from($db->quoteName('#__joomleague_team', 'team'))
            ->innerJoin($db->quoteName('#__joomleague_club', 'club') . ' ON club.id=team.club_id AND club.published=1 AND club.access IN (' . $access . ')')
            ->innerJoin($db->quoteName('#__joomleague_project_entry', 'entry') . ' ON entry.team_id=team.id AND entry.published=1')
            ->innerJoin($db->quoteName('#__joomleague_project', 'project') . ' ON project.id=entry.project_id AND project.published=1 AND project.access IN (' . $access . ')')
            ->innerJoin($db->quoteName('#__joomleague_competition', 'competition') . ' ON competition.id=project.competition_id AND competition.published=1 AND competition.access IN (' . $access . ')')
            ->innerJoin($db->quoteName('#__joomleague_season', 'season') . ' ON season.id=project.season_id AND season.published=1 AND season.access IN (' . $access . ')')
            ->where('team.club_id=:club')->where('team.published=1')->where('team.access IN (' . $access . ')')
            ->order(['team.name', 'team.id'])->bind(':club', $clubId, ParameterType::INTEGER);
        return $db->setQuery($query)->loadAssocList('id', 'name');
    }
}
