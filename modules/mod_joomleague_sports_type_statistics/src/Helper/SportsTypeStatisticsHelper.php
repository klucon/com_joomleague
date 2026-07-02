<?php

/**
 * @package     Klucon.Site
 * @subpackage  mod_joomleague_sports_type_statistics
 *
 * @author      Ondřej Klučka <info@klucon.cz>
 * @copyright   (C) 2026 Ondřej Klučka (https://klucon.cz). All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomleague\Module\SportsTypeStatistics\Site\Helper;

use Joomla\CMS\Application\SiteApplication;
use Joomla\CMS\Language\Text;
use Joomla\Database\DatabaseAwareInterface;
use Joomla\Database\DatabaseAwareTrait;
use Joomla\Registry\Registry;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Helper for mod_joomleague_sports_type_statistics.
 *
 * @since  1.0.0
 */
class SportsTypeStatisticsHelper implements DatabaseAwareInterface
{
    use DatabaseAwareTrait;

    /**
     * Get aggregate counters for the configured sports type.
     *
     * @param   Registry         $params  Module parameters.
     * @param   SiteApplication  $app     Application.
     *
     * @return  array  List of {statistic_name, statistic_short, value}.
     */
    public function getStatistics(Registry $params, SiteApplication $app): array
    {
        $db          = $this->getDatabase();
        $sportTypeId = $this->firstPositiveInt(
            $params->get('sport_type_id')
            ?: $params->get('sportstypes')
            ?: $params->get('sports_type_id')
        );

        if ($sportTypeId === 0) {
            $projectId = $this->resolveProjectId($params, $app);

            if ($projectId > 0) {
                $query = $db->getQuery(true)
                    ->select($db->quoteName('sports_type_id'))
                    ->from($db->quoteName('#__joomleague_project'))
                    ->where($db->quoteName('id') . ' = :project_id')
                    ->bind(':project_id', $projectId);

                $db->setQuery($query);
                $sportTypeId = (int) $db->loadResult();
            }
        }

        if ($sportTypeId === 0) {
            return [];
        }

        return [
            $this->row('MOD_JOOMLEAGUE_SPORTS_TYPE_STATISTICS_PROJECTS', 'projects', $this->countProjects($sportTypeId)),
            $this->row('MOD_JOOMLEAGUE_SPORTS_TYPE_STATISTICS_LEAGUES', 'leagues', $this->countDistinctProjectColumn($sportTypeId, 'league_id')),
            $this->row('MOD_JOOMLEAGUE_SPORTS_TYPE_STATISTICS_SEASONS', 'seasons', $this->countDistinctProjectColumn($sportTypeId, 'season_id')),
            $this->row('MOD_JOOMLEAGUE_SPORTS_TYPE_STATISTICS_TEAMS', 'teams', $this->countProjectTeams($sportTypeId)),
            $this->row('MOD_JOOMLEAGUE_SPORTS_TYPE_STATISTICS_PLAYERS', 'players', $this->countTeamPersons($sportTypeId, '#__joomleague_team_player', 'person_id')),
            $this->row('MOD_JOOMLEAGUE_SPORTS_TYPE_STATISTICS_STAFF', 'staff', $this->countTeamPersons($sportTypeId, '#__joomleague_team_staff', 'person_id')),
            $this->row('MOD_JOOMLEAGUE_SPORTS_TYPE_STATISTICS_ROUNDS', 'rounds', $this->countRounds($sportTypeId)),
            $this->row('MOD_JOOMLEAGUE_SPORTS_TYPE_STATISTICS_MATCHES', 'matches', $this->countMatches($sportTypeId)),
            $this->row('MOD_JOOMLEAGUE_SPORTS_TYPE_STATISTICS_EVENTS', 'events', $this->countEvents($sportTypeId)),
            $this->row('MOD_JOOMLEAGUE_SPORTS_TYPE_STATISTICS_STATISTICS', 'statistics', $this->countStatistics($sportTypeId)),
        ];
    }

    private function resolveProjectId(Registry $params, SiteApplication $app): int
    {
        return $this->firstPositiveInt(
            $params->get('project_id')
            ?: $params->get('p')
            ?: $params->get('project')
            ?: $params->get('projects')
            ?: $params->get('project_ids')
            ?: $app->getInput()->getInt('project_id', 0)
            ?: $app->getInput()->getInt('p', 0)
        );
    }

    private function firstPositiveInt(mixed $value): int
    {
        if (is_array($value)) {
            foreach ($value as $item) {
                $number = $this->firstPositiveInt($item);

                if ($number > 0) {
                    return $number;
                }
            }

            return 0;
        }

        if (is_string($value) && str_contains($value, ',')) {
            foreach (explode(',', $value) as $item) {
                $number = $this->firstPositiveInt($item);

                if ($number > 0) {
                    return $number;
                }
            }

            return 0;
        }

        $number = (int) $value;

        return $number > 0 ? $number : 0;
    }

    private function row(string $languageKey, string $short, int $value): object
    {
        return (object) [
            'statistic_name'  => Text::_($languageKey),
            'statistic_short' => $short,
            'value'           => $value,
        ];
    }

    private function countProjects(int $sportTypeId): int
    {
        $db = $this->getDatabase();
        $query = $db->getQuery(true)
            ->select('COUNT(*)')
            ->from($db->quoteName('#__joomleague_project'))
            ->where($db->quoteName('sports_type_id') . ' = :sport_type_id')
            ->bind(':sport_type_id', $sportTypeId);

        $db->setQuery($query);

        return (int) $db->loadResult();
    }

    private function countDistinctProjectColumn(int $sportTypeId, string $column): int
    {
        $db = $this->getDatabase();
        $query = $db->getQuery(true)
            ->select('COUNT(DISTINCT ' . $db->quoteName($column) . ')')
            ->from($db->quoteName('#__joomleague_project'))
            ->where($db->quoteName('sports_type_id') . ' = :sport_type_id')
            ->where($db->quoteName($column) . ' IS NOT NULL')
            ->where($db->quoteName($column) . ' > 0')
            ->bind(':sport_type_id', $sportTypeId);

        $db->setQuery($query);

        return (int) $db->loadResult();
    }

    private function countProjectTeams(int $sportTypeId): int
    {
        $db = $this->getDatabase();
        $query = $db->getQuery(true)
            ->select('COUNT(*)')
            ->from($db->quoteName('#__joomleague_project_team', 'pt'))
            ->innerJoin($db->quoteName('#__joomleague_project', 'p') . ' ON ' . $db->quoteName('p.id') . ' = ' . $db->quoteName('pt.project_id'))
            ->where($db->quoteName('p.sports_type_id') . ' = :sport_type_id')
            ->bind(':sport_type_id', $sportTypeId);

        $db->setQuery($query);

        return (int) $db->loadResult();
    }

    private function countTeamPersons(int $sportTypeId, string $table, string $personColumn): int
    {
        $db = $this->getDatabase();
        $query = $db->getQuery(true)
            ->select('COUNT(DISTINCT ' . $db->quoteName('tp.' . $personColumn) . ')')
            ->from($db->quoteName($table, 'tp'))
            ->innerJoin($db->quoteName('#__joomleague_project_team', 'pt') . ' ON ' . $db->quoteName('pt.id') . ' = ' . $db->quoteName('tp.projectteam_id'))
            ->innerJoin($db->quoteName('#__joomleague_project', 'p') . ' ON ' . $db->quoteName('p.id') . ' = ' . $db->quoteName('pt.project_id'))
            ->where($db->quoteName('p.sports_type_id') . ' = :sport_type_id')
            ->where($db->quoteName('tp.' . $personColumn) . ' IS NOT NULL')
            ->where($db->quoteName('tp.' . $personColumn) . ' > 0')
            ->bind(':sport_type_id', $sportTypeId);

        $db->setQuery($query);

        return (int) $db->loadResult();
    }

    private function countRounds(int $sportTypeId): int
    {
        $db = $this->getDatabase();
        $query = $db->getQuery(true)
            ->select('COUNT(*)')
            ->from($db->quoteName('#__joomleague_round', 'r'))
            ->innerJoin($db->quoteName('#__joomleague_project', 'p') . ' ON ' . $db->quoteName('p.id') . ' = ' . $db->quoteName('r.project_id'))
            ->where($db->quoteName('p.sports_type_id') . ' = :sport_type_id')
            ->bind(':sport_type_id', $sportTypeId);

        $db->setQuery($query);

        return (int) $db->loadResult();
    }

    private function countMatches(int $sportTypeId): int
    {
        $db = $this->getDatabase();
        $query = $db->getQuery(true)
            ->select('COUNT(*)')
            ->from($db->quoteName('#__joomleague_match', 'm'))
            ->innerJoin($db->quoteName('#__joomleague_round', 'r') . ' ON ' . $db->quoteName('r.id') . ' = ' . $db->quoteName('m.round_id'))
            ->innerJoin($db->quoteName('#__joomleague_project', 'p') . ' ON ' . $db->quoteName('p.id') . ' = ' . $db->quoteName('r.project_id'))
            ->where($db->quoteName('p.sports_type_id') . ' = :sport_type_id')
            ->bind(':sport_type_id', $sportTypeId);

        $db->setQuery($query);

        return (int) $db->loadResult();
    }

    private function countEvents(int $sportTypeId): int
    {
        $db = $this->getDatabase();
        $query = $db->getQuery(true)
            ->select('COUNT(*)')
            ->from($db->quoteName('#__joomleague_match_event', 'e'))
            ->innerJoin($db->quoteName('#__joomleague_match', 'm') . ' ON ' . $db->quoteName('m.id') . ' = ' . $db->quoteName('e.match_id'))
            ->innerJoin($db->quoteName('#__joomleague_round', 'r') . ' ON ' . $db->quoteName('r.id') . ' = ' . $db->quoteName('m.round_id'))
            ->innerJoin($db->quoteName('#__joomleague_project', 'p') . ' ON ' . $db->quoteName('p.id') . ' = ' . $db->quoteName('r.project_id'))
            ->where($db->quoteName('p.sports_type_id') . ' = :sport_type_id')
            ->bind(':sport_type_id', $sportTypeId);

        $db->setQuery($query);

        return (int) $db->loadResult();
    }

    private function countStatistics(int $sportTypeId): int
    {
        $db = $this->getDatabase();
        $query = $db->getQuery(true)
            ->select('COUNT(*)')
            ->from($db->quoteName('#__joomleague_match_statistic', 's'))
            ->innerJoin($db->quoteName('#__joomleague_match', 'm') . ' ON ' . $db->quoteName('m.id') . ' = ' . $db->quoteName('s.match_id'))
            ->innerJoin($db->quoteName('#__joomleague_round', 'r') . ' ON ' . $db->quoteName('r.id') . ' = ' . $db->quoteName('m.round_id'))
            ->innerJoin($db->quoteName('#__joomleague_project', 'p') . ' ON ' . $db->quoteName('p.id') . ' = ' . $db->quoteName('r.project_id'))
            ->where($db->quoteName('p.sports_type_id') . ' = :sport_type_id')
            ->bind(':sport_type_id', $sportTypeId);

        $db->setQuery($query);

        return (int) $db->loadResult();
    }
}
