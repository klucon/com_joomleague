<?php

/**
 * @package     Klucon.Site
 * @subpackage  mod_joomleague_statranking
 *
 * @author      Ondřej Klučka <info@klucon.cz>
 * @copyright   (C) 2026 Ondřej Klučka (https://klucon.cz). All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomleague\Module\Statranking\Site\Helper;

use Joomla\CMS\Application\SiteApplication;
use Joomla\Registry\Registry;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Helper for mod_joomleague_statranking.
 *
 * @since  1.0.0
 */
class StatrankingHelper
{
    /**
     * Get the per-player statistics ranking grouped by statistic.
     *
     * @param   Registry         $params  Module parameters.
     * @param   SiteApplication  $app     Application.
     *
     * @return  array  Map of statistic name => array of rows.
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

        $count    = $this->firstPositiveInt($params->get('count') ?: $params->get('limit'));
        $statId   = $this->firstPositiveInt($params->get('statistic_id') ?: $params->get('sid'));
        $grouped  = [];

        foreach ($model->getStats($projectId) as $row) {
            if (empty($row->person_name)) {
                continue;
            }

            if ($statId > 0 && (int) ($row->statistic_id ?? 0) !== $statId) {
                continue;
            }

            $key = $row->statistic_name;

            if (!isset($grouped[$key])) {
                $grouped[$key] = [];
            }

            if ($count > 0 && \count($grouped[$key]) >= $count) {
                continue;
            }

            $grouped[$key][] = $row;
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
