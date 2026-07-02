<?php

/**
 * @package     Klucon.Site
 * @subpackage  mod_joomleague_logo
 *
 * @author      Ondřej Klučka <info@klucon.cz>
 * @copyright   (C) 2026 Ondřej Klučka (https://klucon.cz). All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomleague\Module\Logo\Site\Helper;

use Joomla\CMS\Application\SiteApplication;
use Joomla\Database\DatabaseAwareInterface;
use Joomla\Database\DatabaseAwareTrait;
use Joomla\Database\ParameterType;
use Joomla\Registry\Registry;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Helper for mod_joomleague_logo.
 *
 * @since  1.0.0
 */
class LogoHelper implements DatabaseAwareInterface
{
    use DatabaseAwareTrait;

    /**
     * Get the logo data for the configured project team.
     *
     * @param   Registry         $params  Module parameters.
     * @param   SiteApplication  $app     Application.
     *
     * @return  array  Single-row list (so the layout can iterate uniformly).
     */
    public function getLogos(Registry $params, SiteApplication $app): array
    {
        $projectTeamId = $this->resolveProjectTeamId($params, $app);

        if ($projectTeamId === 0) {
            return [];
        }

        $db    = $this->getDatabase();
        $query = $db->createQuery();

        $query->select([
                $db->quoteName('pt.id', 'projectteam_id'),
                $db->quoteName('t.name', 'team_name'),
                $db->quoteName('t.picture', 'team_picture'),
                $db->quoteName('c.logo_small'),
                $db->quoteName('c.logo_middle'),
                $db->quoteName('c.logo_big'),
            ])
            ->from($db->quoteName('#__joomleague_project_team', 'pt'))
            ->join('INNER', $db->quoteName('#__joomleague_team', 't') . ' ON ' . $db->quoteName('t.id') . ' = ' . $db->quoteName('pt.team_id'))
            ->join('LEFT', $db->quoteName('#__joomleague_club', 'c') . ' ON ' . $db->quoteName('c.id') . ' = ' . $db->quoteName('t.club_id'))
            ->where($db->quoteName('pt.id') . ' = :ptid')
            ->bind(':ptid', $projectTeamId, ParameterType::INTEGER);

        $row = $db->setQuery($query)->loadObject();

        return $row ? [$row] : [];
    }

    private function resolveProjectTeamId(Registry $params, SiteApplication $app): int
    {
        $projectTeamId = $this->firstPositiveInt($params->get('projectteam_id') ?: $params->get('project_team_id') ?: $params->get('ttid'));

        if ($projectTeamId > 0) {
            return $projectTeamId;
        }

        $teamId = $this->firstPositiveInt($params->get('team') ?: $params->get('teams') ?: $params->get('tid'));
        $projectId = $this->firstPositiveInt($params->get('project_id') ?: $params->get('p') ?: $params->get('project') ?: $params->get('projects') ?: $params->get('project_ids') ?: $app->getInput()->getInt('project_id', 0) ?: $app->getInput()->getInt('p', 0));

        if ($teamId === 0) {
            $teamId = $app->getInput()->getCmd('view') === 'team' ? $app->getInput()->getInt('id', 0) : 0;
        }

        if ($teamId === 0 || $projectId === 0) {
            return $app->getInput()->getCmd('view') === 'roster' ? $app->getInput()->getInt('id', 0) : 0;
        }

        $db = $this->getDatabase();
        $query = $db->getQuery(true)
            ->select($db->quoteName('id'))
            ->from($db->quoteName('#__joomleague_project_team'))
            ->where($db->quoteName('project_id') . ' = :project_id')
            ->where($db->quoteName('team_id') . ' = :team_id')
            ->bind(':project_id', $projectId, ParameterType::INTEGER)
            ->bind(':team_id', $teamId, ParameterType::INTEGER);

        $db->setQuery($query);

        return (int) $db->loadResult();
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
