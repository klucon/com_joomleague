<?php

/**
 * @package     Klucon.Site
 * @subpackage  mod_joomleague_calendar
 *
 * @author      Ondřej Klučka <info@klucon.cz>
 * @copyright   (C) 2026 Ondřej Klučka (https://klucon.cz). All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomleague\Module\Calendar\Site\Helper;

use Joomla\CMS\Application\SiteApplication;
use Joomla\Registry\Registry;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Helper for mod_joomleague_calendar.
 *
 * @since  1.0.0
 */
class CalendarHelper
{
    /**
     * Get matches grouped by calendar day.
     *
     * @param   Registry         $params  Module parameters.
     * @param   SiteApplication  $app     Application.
     *
     * @return  array  Map of 'Y-m-d' => array of matches.
     */
    public function getCalendar(Registry $params, SiteApplication $app): array
    {
        $projectId = $this->resolveProjectId($params, $app);

        if ($projectId === 0) {
            return [];
        }

        $model = $app->bootComponent('com_joomleague')
            ->getMVCFactory()
            ->createModel('Results', 'Site', ['ignore_request' => true]);

        $teamIds = $this->positiveInts($params->get('team_ids') ?: $params->get('teams') ?: $params->get('team'));
        $matches = $model->getMatches($projectId);

        if ($teamIds !== []) {
            $availableTeamIds = [];

            foreach ($matches as $match) {
                $availableTeamIds[] = (int) ($match->home_team_id ?? 0);
                $availableTeamIds[] = (int) ($match->away_team_id ?? 0);
            }

            $teamIds = array_values(array_intersect($teamIds, array_unique(array_filter($availableTeamIds))));
        }

        $grouped = [];

        foreach ($matches as $m) {
            if ($teamIds !== [] && !in_array((int) ($m->home_team_id ?? 0), $teamIds, true) && !in_array((int) ($m->away_team_id ?? 0), $teamIds, true)) {
                continue;
            }

            $day = substr((string) $m->match_date, 0, 10);
            $grouped[$day][] = $m;
        }

        ksort($grouped);

        $count = $this->firstPositiveInt($params->get('count') ?: $params->get('limit') ?: $params->get('results'));

        return $count > 0 ? \array_slice($grouped, 0, $count, true) : $grouped;
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

    /**
     * @return  int[]
     */
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
