<?php

/**
 * @package     Klucon.Site
 * @subpackage  mod_joomleague_eventsranking
 *
 * @author      Ondřej Klučka <info@klucon.cz>
 * @copyright   (C) 2026 Ondřej Klučka (https://klucon.cz). All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomleague\Module\Eventsranking\Site\Helper;

use Joomla\CMS\Application\SiteApplication;
use Joomla\Database\DatabaseAwareInterface;
use Joomla\Database\DatabaseAwareTrait;
use Joomla\Database\ParameterType;
use Joomla\Registry\Registry;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Helper for mod_joomleague_eventsranking.
 *
 * Aggregates match events (goals, cards, ...) per player for a competition.
 *
 * TODO: once the component core exposes an events-ranking model method
 *       (e.g. SiteModel::getEventsRanking()), switch this helper to consume it
 *       instead of querying the tables directly.
 *
 * @since  1.0.0
 */
class EventsrankingHelper implements DatabaseAwareInterface
{
    use DatabaseAwareTrait;

    /**
     * Get the per-player ranking for the configured event type.
     *
     * @param   Registry         $params  Module parameters.
     * @param   SiteApplication  $app     Application.
     *
     * @return  array
     */
    public function getRanking(Registry $params, SiteApplication $app): array
    {
        $projectId = $this->resolveProjectId($params, $app);

        if ($projectId === 0) {
            return [];
        }

        $eventIds = $this->resolveEventTypeIds($params, $projectId);
        $projectTeamId = $this->resolveProjectTeamId($params, $projectId);
        $matchId = $this->resolveMatchId($params, $projectId);

        // Treat regular goals (1) together with penalty goals (4) as "goals".
        $types = $eventIds === [] || in_array(1, $eventIds, true) ? [1, 4] : $eventIds;

        $db    = $this->getDatabase();
        $query = $db->createQuery();

        $query->select([
                'CONCAT_WS(' . $db->quote(' ') . ', ' . $db->quoteName('p.firstname') . ', ' . $db->quoteName('p.lastname') . ') AS person_name',
                $db->quoteName('p.id', 'person_id'),
                $db->quoteName('t.name', 'team_name'),
                'COUNT(*) AS total',
            ])
            ->from($db->quoteName('#__joomleague_match_event', 'ev'))
            ->join('INNER', $db->quoteName('#__joomleague_match', 'm') . ' ON ' . $db->quoteName('m.id') . ' = ' . $db->quoteName('ev.match_id'))
            ->join('INNER', $db->quoteName('#__joomleague_round', 'r') . ' ON ' . $db->quoteName('r.id') . ' = ' . $db->quoteName('m.round_id'))
            ->join('INNER', $db->quoteName('#__joomleague_team_player', 'tp') . ' ON ' . $db->quoteName('tp.id') . ' = ' . $db->quoteName('ev.teamplayer_id'))
            ->join('INNER', $db->quoteName('#__joomleague_person', 'p') . ' ON ' . $db->quoteName('p.id') . ' = ' . $db->quoteName('tp.person_id'))
            ->join('LEFT', $db->quoteName('#__joomleague_project_team', 'pt') . ' ON ' . $db->quoteName('pt.id') . ' = ' . $db->quoteName('ev.projectteam_id'))
            ->join('LEFT', $db->quoteName('#__joomleague_team', 't') . ' ON ' . $db->quoteName('t.id') . ' = ' . $db->quoteName('pt.team_id'))
            ->where($db->quoteName('r.project_id') . ' = :pid')
            ->whereIn($db->quoteName('ev.event_type_id'), $types)
            ->bind(':pid', $projectId, ParameterType::INTEGER)
            ->group([$db->quoteName('tp.id'), $db->quoteName('p.id'), $db->quoteName('p.firstname'), $db->quoteName('p.lastname'), $db->quoteName('t.name')])
            ->order('total DESC, person_name ASC');

        if ($projectTeamId > 0) {
            $query->where($db->quoteName('ev.projectteam_id') . ' = :projectteam_id')
                ->bind(':projectteam_id', $projectTeamId, ParameterType::INTEGER);
        }

        if ($matchId > 0) {
            $query->where($db->quoteName('ev.match_id') . ' = :match_id')
                ->bind(':match_id', $matchId, ParameterType::INTEGER);
        }

        $count = $this->firstPositiveInt($params->get('count') ?: $params->get('limit'));

        return $db->setQuery($query, 0, $count > 0 ? $count : 0)->loadObjectList();
    }

    private function resolveEventTypeIds(Registry $params, int $projectId): array
    {
        $eventIds = $this->positiveInts($params->get('event_type_id') ?: $params->get('evid'));

        if ($eventIds === []) {
            return [];
        }

        $db = $this->getDatabase();
        $query = $db->createQuery()
            ->select('DISTINCT ' . $db->quoteName('ev.event_type_id'))
            ->from($db->quoteName('#__joomleague_match_event', 'ev'))
            ->join('INNER', $db->quoteName('#__joomleague_match', 'm') . ' ON ' . $db->quoteName('m.id') . ' = ' . $db->quoteName('ev.match_id'))
            ->join('INNER', $db->quoteName('#__joomleague_round', 'r') . ' ON ' . $db->quoteName('r.id') . ' = ' . $db->quoteName('m.round_id'))
            ->where($db->quoteName('r.project_id') . ' = :project_id')
            ->whereIn($db->quoteName('ev.event_type_id'), $eventIds)
            ->bind(':project_id', $projectId, ParameterType::INTEGER);

        return array_values(array_filter(array_map('intval', $db->setQuery($query)->loadColumn())));
    }

    private function resolveProjectTeamId(Registry $params, int $projectId): int
    {
        $teamId = $this->firstPositiveInt($params->get('team_id') ?: $params->get('team') ?: $params->get('teams') ?: $params->get('tid'));

        if ($teamId === 0 || $projectId === 0) {
            return 0;
        }

        $db = $this->getDatabase();
        $query = $db->createQuery()
            ->select($db->quoteName('id'))
            ->from($db->quoteName('#__joomleague_project_team'))
            ->where($db->quoteName('project_id') . ' = :project_id')
            ->where($db->quoteName('team_id') . ' = :team_id')
            ->bind(':project_id', $projectId, ParameterType::INTEGER)
            ->bind(':team_id', $teamId, ParameterType::INTEGER);

        return (int) $db->setQuery($query, 0, 1)->loadResult();
    }

    private function resolveMatchId(Registry $params, int $projectId): int
    {
        $matchId = $this->firstPositiveInt($params->get('match_id') ?: $params->get('mid'));

        if ($matchId === 0 || $projectId === 0) {
            return 0;
        }

        $db = $this->getDatabase();
        $query = $db->createQuery()
            ->select('COUNT(*)')
            ->from($db->quoteName('#__joomleague_match', 'm'))
            ->join('INNER', $db->quoteName('#__joomleague_round', 'r') . ' ON ' . $db->quoteName('r.id') . ' = ' . $db->quoteName('m.round_id'))
            ->where($db->quoteName('r.project_id') . ' = :project_id')
            ->where($db->quoteName('m.id') . ' = :match_id')
            ->bind(':project_id', $projectId, ParameterType::INTEGER)
            ->bind(':match_id', $matchId, ParameterType::INTEGER);

        return (int) $db->setQuery($query)->loadResult() > 0 ? $matchId : 0;
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

    private function positiveInts(mixed $value): array
    {
        if (is_array($value)) {
            $numbers = [];

            foreach ($value as $item) {
                array_push($numbers, ...$this->positiveInts($item));
            }

            return array_values(array_unique($numbers));
        }

        if (is_string($value) && str_contains($value, ',')) {
            return $this->positiveInts(explode(',', $value));
        }

        $number = (int) $value;

        return $number > 0 ? [$number] : [];
    }
}
