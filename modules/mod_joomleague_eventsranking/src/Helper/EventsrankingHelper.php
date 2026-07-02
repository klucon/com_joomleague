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

        $eventType = $this->firstPositiveInt($params->get('event_type_id') ?: $params->get('evid')) ?: 1;

        // Treat regular goals (1) together with penalty goals (4) as "goals".
        $types = $eventType === 1 ? [1, 4] : [$eventType];

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

        $count = $this->firstPositiveInt($params->get('count') ?: $params->get('limit'));

        return $db->setQuery($query, 0, $count > 0 ? $count : 0)->loadObjectList();
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
}
