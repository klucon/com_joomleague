<?php

/**
 * @package     Klucon.Site
 * @subpackage  mod_joomleague_teamstaffs
 *
 * @author      Ondřej Klučka <info@klucon.cz>
 * @copyright   (C) 2026 Ondřej Klučka (https://klucon.cz). All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomleague\Module\Teamstaffs\Site\Helper;

use Joomla\CMS\Application\SiteApplication;
use Joomla\Database\DatabaseAwareInterface;
use Joomla\Database\DatabaseAwareTrait;
use Joomla\Database\ParameterType;
use Joomla\Registry\Registry;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Helper for mod_joomleague_teamstaffs.
 *
 * TODO: once the component core exposes a staff-roster model method
 *       (e.g. SiteModel::getStaff()), switch this helper to consume it
 *       instead of querying the tables directly.
 *
 * @since  1.0.0
 */
class TeamstaffsHelper implements DatabaseAwareInterface
{
    use DatabaseAwareTrait;

    /**
     * Get the staff of the configured project team.
     *
     * @param   Registry         $params  Module parameters.
     * @param   SiteApplication  $app     Application.
     *
     * @return  array
     */
    public function getStaff(Registry $params, SiteApplication $app): array
    {
        $projectTeamId = $this->resolveProjectTeamId($params, $app);

        if ($projectTeamId === 0) {
            return [];
        }

        $db    = $this->getDatabase();
        $query = $db->createQuery();

        $query->select([
                'ts.*',
                'CONCAT_WS(' . $db->quote(', ') . ', ' . $db->quoteName('p.lastname') . ', ' . $db->quoteName('p.firstname') . ') AS person_name',
                $db->quoteName('p.id', 'person_id'),
                $db->quoteName('p.picture', 'person_picture'),
                $db->quoteName('pos.name', 'position_name'),
            ])
            ->from($db->quoteName('#__joomleague_team_staff', 'ts'))
            ->join('INNER', $db->quoteName('#__joomleague_person', 'p') . ' ON ' . $db->quoteName('p.id') . ' = ' . $db->quoteName('ts.person_id'))
            ->join('LEFT', $db->quoteName('#__joomleague_project_position', 'pp') . ' ON ' . $db->quoteName('pp.id') . ' = ' . $db->quoteName('ts.project_position_id'))
            ->join('LEFT', $db->quoteName('#__joomleague_position', 'pos') . ' ON ' . $db->quoteName('pos.id') . ' = ' . $db->quoteName('pp.position_id'))
            ->where($db->quoteName('ts.projectteam_id') . ' = :ptid')
            ->where($db->quoteName('ts.published') . ' = 1')
            ->bind(':ptid', $projectTeamId, ParameterType::INTEGER)
            ->order($db->quoteName('pos.ordering') . ' ASC, ' . $db->quoteName('p.lastname') . ' ASC');

        $count = $this->firstPositiveInt($params->get('count') ?: $params->get('limit'));

        return $db->setQuery($query, 0, $count > 0 ? $count : 0)->loadObjectList();
    }

    private function resolveProjectTeamId(Registry $params, SiteApplication $app): int
    {
        $projectTeamId = $this->firstPositiveInt($params->get('projectteam_id') ?: $params->get('project_team_id') ?: $params->get('ttid'));

        if ($projectTeamId > 0) {
            return $projectTeamId;
        }

        $teamIds = $this->positiveInts($params->get('team') ?: $params->get('teams') ?: $params->get('tid'));
        $projectId = $this->firstPositiveInt($params->get('project_id') ?: $params->get('p') ?: $params->get('project') ?: $params->get('projects') ?: $params->get('project_ids') ?: $app->getInput()->getInt('project_id', 0) ?: $app->getInput()->getInt('p', 0));

        if ($teamIds === []) {
            $teamId = $app->getInput()->getCmd('view') === 'team' ? $app->getInput()->getInt('id', 0) : 0;
            $teamIds = $teamId > 0 ? [$teamId] : [];
        }

        if ($teamIds === [] || $projectId === 0) {
            return $app->getInput()->getCmd('view') === 'roster' ? $app->getInput()->getInt('id', 0) : 0;
        }

        $db = $this->getDatabase();
        $query = $db->getQuery(true)
            ->select($db->quoteName('id'))
            ->from($db->quoteName('#__joomleague_project_team'))
            ->where($db->quoteName('project_id') . ' = :project_id')
            ->whereIn($db->quoteName('team_id'), $teamIds)
            ->order($db->quoteName('ordering') . ' ASC, ' . $db->quoteName('id') . ' ASC')
            ->bind(':project_id', $projectId, ParameterType::INTEGER);

        $db->setQuery($query, 0, 1);

        return (int) $db->loadResult();
    }

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
