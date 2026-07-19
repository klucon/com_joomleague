<?php

/**
 * @package     Klucon.Site
 * @subpackage  mod_joomleague_teamstats_ranking
 *
 * @author      Ondřej Klučka <info@klucon.cz>
 * @copyright   (C) 2026 Ondřej Klučka (https://klucon.cz). All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomleague\Module\TeamstatsRanking\Site\Helper;

use Joomla\CMS\Application\SiteApplication;
use Joomla\Registry\Registry;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Helper for mod_joomleague_teamstats_ranking.
 *
 * @since  1.0.0
 */
class TeamstatsRankingHelper
{
    /**
     * Get team statistics totals grouped by statistic.
     *
     * @param   Registry         $params  Module parameters.
     * @param   SiteApplication  $app     Application.
     *
     * @return  array  Map of statistic name => array of {team_name, value}.
     */
    public function getRanking(Registry $params, SiteApplication $app): array
    {
        $projectId = $this->resolveProjectId($params, $app);

        if ($projectId === 0) {
            return [];
        }

        $model = $app->bootComponent('com_joomleague')
            ->getMVCFactory()
            ->createModel('Stats', 'Site', ['ignore_request' => true]);

        $statId = $this->firstPositiveInt($params->get('statistic_id') ?: $params->get('sid'));
        $rows   = $model->getStats($projectId);

        if ($statId > 0) {
            $availableStatIds = array_values(array_unique(array_map(static fn ($row): int => (int) ($row->statistic_id ?? 0), $rows)));

            if (!in_array($statId, $availableStatIds, true)) {
                $statId = 0;
            }
        }

        $agg = [];

        foreach ($rows as $row) {
            if (empty($row->team_name)) {
                continue;
            }

            if ($statId > 0 && (int) ($row->statistic_id ?? 0) !== $statId) {
                continue;
            }

            $agg[$row->statistic_name][$row->team_name] = ($agg[$row->statistic_name][$row->team_name] ?? 0) + (float) $row->value;
        }

        $count   = $this->firstPositiveInt($params->get('count') ?: $params->get('limit'));
        $grouped = [];

        foreach ($agg as $statName => $teams) {
            arsort($teams);

            foreach ($teams as $teamName => $value) {
                $grouped[$statName][] = (object) ['team_name' => $teamName, 'value' => $value];
            }

            if ($count > 0) {
                $grouped[$statName] = \array_slice($grouped[$statName], 0, $count);
            }
        }

        return $grouped;
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
