<?php

/**
 * @package     Klucon.Site
 * @subpackage  mod_joomleague_matches
 *
 * @author      Ondřej Klučka <info@klucon.cz>
 * @copyright   (C) 2026 Ondřej Klučka (https://klucon.cz). All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomleague\Module\Matches\Site\Helper;

use Joomla\CMS\Application\SiteApplication;
use Joomla\Registry\Registry;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Helper for mod_joomleague_matches.
 *
 * @since  1.0.0
 */
class MatchesHelper
{
    /**
     * Get matches according to the configured mode (all / results / upcoming).
     *
     * @param   Registry         $params  Module parameters.
     * @param   SiteApplication  $app     Application.
     *
     * @return  array
     */
    public function getMatches(Registry $params, SiteApplication $app): array
    {
        $projectId = $this->resolveProjectId($params, $app);

        if ($projectId === 0) {
            return [];
        }

        $mode  = (string) $params->get('mode', 'all');
        $count = $this->firstPositiveInt($params->get('count') ?: $params->get('limit') ?: $params->get('results'));

        $model = $app->bootComponent('com_joomleague')
            ->getMVCFactory()
            ->createModel('Results', 'Site', ['ignore_request' => true]);

        if ($mode === 'upcoming') {
            return $model->getMatches($projectId, 0, 0, $count, true);
        }

        $matches = $model->getMatches($projectId);

        if ($mode === 'results') {
            $matches = array_filter(
                $matches,
                static fn ($m): bool => $m->team1_result !== null && $m->team2_result !== null && (int) $m->count_result === 1
            );
            usort($matches, static fn ($a, $b): int => strcmp((string) $b->match_date, (string) $a->match_date));
        }

        $matches = array_values($matches);

        return $count > 0 ? \array_slice($matches, 0, $count) : $matches;
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
