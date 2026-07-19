<?php

/**
 * @package     Klucon.Site
 * @subpackage  mod_joomleague_results
 *
 * @author      Ondřej Klučka <info@klucon.cz>
 * @copyright   (C) 2026 Ondřej Klučka (https://klucon.cz). All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomleague\Module\Results\Site\Helper;

use Joomla\CMS\Application\SiteApplication;
use Joomla\Registry\Registry;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Helper for mod_joomleague_results.
 *
 * @since  1.0.0
 */
class ResultsHelper
{
    /**
     * Get the most recent played matches of the configured project.
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

        $model = $app->bootComponent('com_joomleague')
            ->getMVCFactory()
            ->createModel('Results', 'Site', ['ignore_request' => true]);

        $roundId = $this->firstPositiveInt($params->get('round_id') ?: $params->get('round') ?: $params->get('r'));
        $matches = array_filter(
            $model->getMatches($projectId),
            static fn ($m): bool => $m->team1_result !== null && $m->team2_result !== null && (int) $m->count_result === 1
        );

        if ($roundId > 0) {
            $roundIds = array_values(array_unique(array_map(static fn ($match): int => (int) ($match->round_id ?? 0), $matches)));

            if (in_array($roundId, $roundIds, true)) {
                $matches = array_filter($matches, static fn ($match): bool => (int) ($match->round_id ?? 0) === $roundId);
            }
        }

        usort($matches, static fn ($a, $b): int => strcmp((string) $b->match_date, (string) $a->match_date));

        $count = $this->firstPositiveInt($params->get('count') ?: $params->get('limit') ?: $params->get('results'));

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
