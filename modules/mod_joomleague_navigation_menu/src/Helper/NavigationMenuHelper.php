<?php

/**
 * @package     Klucon.Site
 * @subpackage  mod_joomleague_navigation_menu
 *
 * @author      Ondřej Klučka <info@klucon.cz>
 * @copyright   (C) 2026 Ondřej Klučka (https://klucon.cz). All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomleague\Module\NavigationMenu\Site\Helper;

use Joomla\CMS\Application\SiteApplication;
use Joomla\Registry\Registry;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Helper for mod_joomleague_navigation_menu.
 *
 * @since  1.0.0
 */
class NavigationMenuHelper
{
    /**
     * Build the navigation data (competitions and the teams of the active one).
     *
     * @param   Registry         $params  Module parameters.
     * @param   SiteApplication  $app     Application.
     *
     * @return  array  ['projects' => [], 'active' => int, 'teams' => []]
     */
    public function getNavigation(Registry $params, SiteApplication $app): array
    {
        $model = $app->bootComponent('com_joomleague')
            ->getMVCFactory()
            ->createModel('Projects', 'Site', ['ignore_request' => true]);

        $projects = $model->getProjects();

        $active = $this->resolveProjectId($params, $app);

        if ($active === 0 && $projects !== []) {
            $active = (int) $projects[0]->id;
        }

        $teams = $active > 0 ? $model->getProjectTeams($active) : [];

        return ['projects' => $projects, 'active' => $active, 'teams' => $teams];
    }

    private function resolveProjectId(Registry $params, SiteApplication $app): int
    {
        return $this->firstPositiveInt(
            $params->get('project_id')
            ?: $params->get('default_project_id')
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
