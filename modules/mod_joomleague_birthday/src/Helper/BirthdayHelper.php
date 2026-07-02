<?php

/**
 * @package     Klucon.Site
 * @subpackage  mod_joomleague_birthday
 *
 * @author      Ondřej Klučka <info@klucon.cz>
 * @copyright   (C) 2026 Ondřej Klučka (https://klucon.cz). All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomleague\Module\Birthday\Site\Helper;

use Joomla\CMS\Application\SiteApplication;
use Joomla\Database\DatabaseAwareInterface;
use Joomla\Database\DatabaseAwareTrait;
use Joomla\Database\ParameterType;
use Joomla\Registry\Registry;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Helper for mod_joomleague_birthday.
 *
 * TODO: once the component core exposes a birthdays model method
 *       (e.g. SiteModel::getBirthdays()), switch this helper to consume it
 *       instead of querying the tables directly.
 *
 * @since  1.0.0
 */
class BirthdayHelper implements DatabaseAwareInterface
{
    use DatabaseAwareTrait;

    /**
     * Get upcoming birthdays of people involved in the configured project.
     *
     * @param   Registry         $params  Module parameters.
     * @param   SiteApplication  $app     Application.
     *
     * @return  array
     */
    public function getBirthdays(Registry $params, SiteApplication $app): array
    {
        $projectId = $this->resolveProjectId($params, $app);

        if ($projectId === 0) {
            return [];
        }

        $days = $this->firstPositiveInt($params->get('days') ?: $params->get('maxdays')) ?: 30;
        $db   = $this->getDatabase();

        $query = $db->createQuery();
        $query->select('DISTINCT ' . $db->quoteName('p.id', 'person_id'))
            ->select('CONCAT_WS(' . $db->quote(' ') . ', ' . $db->quoteName('p.firstname') . ', ' . $db->quoteName('p.lastname') . ') AS person_name')
            ->select($db->quoteName('p.birthday'))
            ->from($db->quoteName('#__joomleague_person', 'p'))
            ->join('INNER', $db->quoteName('#__joomleague_team_player', 'tp') . ' ON ' . $db->quoteName('tp.person_id') . ' = ' . $db->quoteName('p.id'))
            ->join('INNER', $db->quoteName('#__joomleague_project_team', 'pt') . ' ON ' . $db->quoteName('pt.id') . ' = ' . $db->quoteName('tp.projectteam_id'))
            ->where($db->quoteName('pt.project_id') . ' = :pid')
            ->where($db->quoteName('p.birthday') . ' IS NOT NULL')
            ->where($db->quoteName('p.birthday') . ' <> ' . $db->quote('0000-00-00'))
            ->bind(':pid', $projectId, ParameterType::INTEGER);

        $rows  = $db->setQuery($query)->loadObjectList();
        $today = new \DateTimeImmutable('today');
        $out   = [];

        foreach ($rows as $row) {
            try {
                $bd = new \DateTimeImmutable((string) $row->birthday);
            } catch (\Exception $e) {
                continue;
            }

            $next = $bd->setDate((int) $today->format('Y'), (int) $bd->format('n'), (int) $bd->format('j'));

            if ($next < $today) {
                $next = $next->modify('+1 year');
            }

            $diff = (int) $today->diff($next)->format('%a');

            if ($diff <= $days) {
                $row->days_until = $diff;
                $row->next_date  = $next->format('Y-m-d');
                $out[]           = $row;
            }
        }

        usort($out, static fn ($a, $b): int => $a->days_until <=> $b->days_until);

        $count = $this->firstPositiveInt($params->get('count') ?: $params->get('limit'));

        return $count > 0 ? \array_slice($out, 0, $count) : $out;
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
