<?php

/**
 * @package     Klucon.Site
 * @subpackage  mod_joomleague_ranking
 *
 * @author      Ondřej Klučka <info@klucon.cz>
 * @copyright   (C) 2026 Ondřej Klučka (https://klucon.cz). All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomleague\Module\Ranking\Site\Helper;

use Joomla\CMS\Application\SiteApplication;
use Joomla\Registry\Registry;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Helper for mod_joomleague_ranking.
 *
 * @since  1.0.0
 */
class RankingHelper
{
    /**
     * Get the standings rows for the configured project.
     *
     * @param   Registry         $params  The module parameters.
     * @param   SiteApplication  $app     The application.
     *
     * @return  array
     */
    public function getStandings(Registry $params, SiteApplication $app): array
    {
        $projectId = $this->resolveProjectId($params, $app);

        if ($projectId === 0) {
            return [];
        }

        $model = $app->bootComponent('com_joomleague')
            ->getMVCFactory()
            ->createModel('Ranking', 'Site', ['ignore_request' => true]);

        $list = $model->getStandings($projectId);

        $count = $this->firstPositiveInt($params->get('count') ?: $params->get('limit'));

        if ($count > 0) {
            $list = \array_slice($list, 0, $count);
        }

        return $list;
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
