<?php

/**
 * @package     Klucon.Site
 * @subpackage  mod_joomleague_ticker
 *
 * @author      Ondřej Klučka <info@klucon.cz>
 * @copyright   (C) 2026 Ondřej Klučka (https://klucon.cz). All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomleague\Module\Ticker\Site\Helper;

use Joomla\CMS\Application\SiteApplication;
use Joomla\Registry\Registry;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Helper for mod_joomleague_ticker.
 *
 * @since  1.0.0
 */
class TickerHelper
{
    /**
     * Get a ticker list: most recent results followed by upcoming matches.
     *
     * @param   Registry         $params  Module parameters.
     * @param   SiteApplication  $app     Application.
     *
     * @return  array
     */
    public function getTicker(Registry $params, SiteApplication $app): array
    {
        $projectId = $this->resolveProjectId($params, $app);

        if ($projectId === 0) {
            return [];
        }

        $count = $this->firstPositiveInt($params->get('count') ?: $params->get('limit') ?: $params->get('results'));
        $half  = $count > 0 ? max(1, (int) ceil($count / 2)) : 0;

        $model = $app->bootComponent('com_joomleague')
            ->getMVCFactory()
            ->createModel('Results', 'Site', ['ignore_request' => true]);

        $all = $model->getMatches($projectId);

        $played   = [];
        $upcoming = [];

        foreach ($all as $m) {
            if ($m->team1_result !== null && $m->team2_result !== null && (int) $m->count_result === 1) {
                $played[] = $m;
            } else {
                $upcoming[] = $m;
            }
        }

        usort($played, static fn ($a, $b): int => strcmp((string) $b->match_date, (string) $a->match_date));
        usort($upcoming, static fn ($a, $b): int => strcmp((string) $a->match_date, (string) $b->match_date));

        if ($half > 0) {
            $played   = \array_slice($played, 0, $half);
            $upcoming = \array_slice($upcoming, 0, $half);
        }

        return array_merge(array_reverse($played), $upcoming);
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
