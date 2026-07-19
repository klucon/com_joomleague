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
 * @since  1.0.0
 */
class BirthdayHelper implements DatabaseAwareInterface
{
    use DatabaseAwareTrait;

    /**
     * Get upcoming birthday/death anniversaries of people involved in the configured project.
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

        $days      = $this->resolveDays($params);
        $teamIds   = $this->resolveTeamIds($params, $projectId);
        $personSet = (string) $params->get('use_which', '0');
        $rows      = [];

        if ($personSet === '0' || $personSet === '1') {
            $rows = array_merge($rows, $this->getRows($projectId, $teamIds, 'player'));
        }

        if ($personSet === '0' || $personSet === '2') {
            $rows = array_merge($rows, $this->getRows($projectId, $teamIds, 'staff'));
        }

        $timezone = $this->resolveTimezone($projectId, $app);

        $today       = new \DateTimeImmutable('today', $timezone);
        $nameFormat  = (string) ($params->get('name_format', '0') ?: '0');
        $anniversary = (string) $params->get('anniversary_type', 'birthday');
        $out         = [];
        $seen        = [];

        $includeBirthdays = $anniversary === 'birthday' || $anniversary === 'both' || $anniversary === '';
        $includeDeathdays = $anniversary === 'deathday' || $anniversary === 'both';

        foreach ($rows as $row) {
            if ($includeBirthdays) {
                $item = $this->buildAnniversary($row, 'birthday', $today, $timezone, $days, $nameFormat);

                if ($item !== null) {
                    $key = (int) $item->person_id . ':birthday';

                    if (!isset($seen[$key])) {
                        $seen[$key] = true;
                        $out[]      = $item;
                    }
                }
            }

            if ($includeDeathdays) {
                $item = $this->buildAnniversary($row, 'deathday', $today, $timezone, $days, $nameFormat);

                if ($item !== null) {
                    $key = (int) $item->person_id . ':deathday';

                    if (!isset($seen[$key])) {
                        $seen[$key] = true;
                        $out[]      = $item;
                    }
                }
            }
        }

        $sortOrder = (string) $params->get('sort_order', '-');
        usort(
            $out,
            static function ($a, $b) use ($sortOrder): int {
                $diff = $a->days_until <=> $b->days_until;

                if ($diff !== 0) {
                    return $diff;
                }

                $ageDiff = (int) $b->years <=> (int) $a->years;

                if ($sortOrder === '+') {
                    $ageDiff *= -1;
                }

                $typeDiff = strcmp((string) $a->anniversary_type, (string) $b->anniversary_type);

                return $ageDiff ?: ($typeDiff ?: strcasecmp((string) $a->person_name, (string) $b->person_name));
            }
        );

        $count = $this->firstPositiveInt($params->get('count') ?: $params->get('limit'));

        return $count > 0 ? \array_slice($out, 0, $count) : $out;
    }

    private function getRows(int $projectId, array $teamIds, string $type): array
    {
        $db = $this->getDatabase();

        $relationTable = $type === 'staff' ? '#__joomleague_team_staff' : '#__joomleague_team_player';
        $alias         = $type === 'staff' ? 'ts' : 'tp';

        $query = $db->createQuery()
            ->select(
                [
                    $db->quoteName('p.id', 'person_id'),
                    $db->quoteName('p.firstname'),
                    $db->quoteName('p.lastname'),
                    $db->quoteName('p.nickname'),
                    $db->quoteName('p.birthday'),
                    $db->quoteName('p.deathday'),
                    $db->quoteName('p.country', 'person_country'),
                    'COALESCE(NULLIF(' . $db->quoteName($alias . '.picture') . ', ' . $db->quote('') . '), NULLIF(' . $db->quoteName('p.picture') . ', ' . $db->quote('') . ')) AS person_picture',
                    $db->quoteName('pt.id', 'projectteam_id'),
                    $db->quoteName('t.id', 'team_id'),
                    $db->quoteName('t.name', 'team_name'),
                ]
            )
            ->from($db->quoteName('#__joomleague_person', 'p'))
            ->join('INNER', $db->quoteName($relationTable, $alias) . ' ON ' . $db->quoteName($alias . '.person_id') . ' = ' . $db->quoteName('p.id'))
            ->join('INNER', $db->quoteName('#__joomleague_project_team', 'pt') . ' ON ' . $db->quoteName('pt.id') . ' = ' . $db->quoteName($alias . '.projectteam_id'))
            ->join('LEFT', $db->quoteName('#__joomleague_team', 't') . ' ON ' . $db->quoteName('t.id') . ' = ' . $db->quoteName('pt.team_id'))
            ->where($db->quoteName('pt.project_id') . ' = :pid')
            ->where('((' . $db->quoteName('p.birthday') . ' IS NOT NULL AND ' . $db->quoteName('p.birthday') . ' <> ' . $db->quote('0000-00-00') . ')'
                . ' OR (' . $db->quoteName('p.deathday') . ' IS NOT NULL AND ' . $db->quoteName('p.deathday') . ' <> ' . $db->quote('0000-00-00') . '))')
            ->where($db->quoteName($alias . '.published') . ' = 1')
            ->where($db->quoteName('p.published') . ' = 1')
            ->bind(':pid', $projectId, ParameterType::INTEGER);

        if ($teamIds !== []) {
            $query->whereIn($db->quoteName('t.id'), $teamIds);
        }

        return $db->setQuery($query)->loadObjectList();
    }

    private function buildAnniversary(
        object $row,
        string $type,
        \DateTimeImmutable $today,
        \DateTimeZone $timezone,
        int $days,
        string $nameFormat
    ): ?object {
        $field = $type === 'deathday' ? 'deathday' : 'birthday';
        $raw   = trim((string) ($row->{$field} ?? ''));

        if ($raw === '' || $raw === '0000-00-00') {
            return null;
        }

        try {
            $date = new \DateTimeImmutable($raw, $timezone);
        } catch (\Exception $e) {
            return null;
        }

        $next = $date->setDate((int) $today->format('Y'), (int) $date->format('n'), (int) $date->format('j'));

        if ($next < $today) {
            $next = $next->modify('+1 year');
        }

        $diff = (int) $today->diff($next)->format('%a');

        if ($days >= 0 && $diff > $days) {
            return null;
        }

        $item                   = clone $row;
        $item->person_name      = $this->formatName($row, $nameFormat);
        $item->anniversary_type = $type;
        $item->days_until       = $diff;
        $item->next_date        = $next->format('Y-m-d');
        $item->source_date      = $date->format('Y-m-d');
        $item->birthday_date    = $type === 'birthday' ? $date->format('Y-m-d') : (string) ($row->birthday ?? '');
        $item->deathday_date    = $type === 'deathday' ? $date->format('Y-m-d') : (string) ($row->deathday ?? '');
        $item->years            = (int) $next->format('Y') - (int) $date->format('Y');
        $item->age              = $type === 'birthday' ? $item->years : null;

        return $item;
    }

    private function resolveDays(Registry $params): int
    {
        $value = $params->get('days');

        if ($value === null || $value === '') {
            $value = $params->get('maxdays');
        }

        if ($value === null || $value === '') {
            return PHP_INT_MAX;
        }

        return max(0, (int) $value);
    }

    private function resolveTimezone(int $projectId, SiteApplication $app): \DateTimeZone
    {
        $db = $this->getDatabase();
        $query = $db->createQuery()
            ->select($db->quoteName('timezone'))
            ->from($db->quoteName('#__joomleague_project'))
            ->where($db->quoteName('id') . ' = :pid')
            ->bind(':pid', $projectId, ParameterType::INTEGER);

        $timezoneName = trim((string) $db->setQuery($query)->loadResult());

        if ($timezoneName === '') {
            $timezoneName = (string) ($app->get('offset', 'UTC') ?: 'UTC');
        }

        try {
            return new \DateTimeZone($timezoneName);
        } catch (\Exception $e) {
            return new \DateTimeZone('UTC');
        }
    }

    private function resolveTeamIds(Registry $params, int $projectId): array
    {
        $teams = $this->positiveIntList($params->get('teams'));

        if ($teams !== []) {
            return $this->filterProjectTeamIds($teams, $projectId);
        }

        if ((int) $params->get('use_fav', 1) === 0) {
            return [];
        }

        $db = $this->getDatabase();
        $query = $db->createQuery()
            ->select($db->quoteName('fav_team'))
            ->from($db->quoteName('#__joomleague_project'))
            ->where($db->quoteName('id') . ' = :pid')
            ->bind(':pid', $projectId, ParameterType::INTEGER);

        $favorite = trim((string) $db->setQuery($query)->loadResult());
        $favoriteIds = $this->positiveIntList($favorite);

        if ($favoriteIds === []) {
            return [];
        }

        $query = $db->createQuery()
            ->select($db->quoteName('team_id'))
            ->from($db->quoteName('#__joomleague_project_team'))
            ->where($db->quoteName('project_id') . ' = :pid')
            ->whereIn($db->quoteName('id'), $favoriteIds)
            ->bind(':pid', $projectId, ParameterType::INTEGER);

        return array_values(array_filter(array_map('intval', $db->setQuery($query)->loadColumn())));
    }

    private function filterProjectTeamIds(array $teamIds, int $projectId): array
    {
        $teamIds = array_values(array_unique(array_filter(array_map('intval', $teamIds))));

        if ($teamIds === [] || $projectId === 0) {
            return [];
        }

        $db = $this->getDatabase();
        $query = $db->createQuery()
            ->select($db->quoteName('team_id'))
            ->from($db->quoteName('#__joomleague_project_team'))
            ->where($db->quoteName('project_id') . ' = :pid')
            ->whereIn($db->quoteName('team_id'), $teamIds)
            ->bind(':pid', $projectId, ParameterType::INTEGER);

        return array_values(array_filter(array_map('intval', $db->setQuery($query)->loadColumn())));
    }

    private function formatName(object $row, string $format): string
    {
        $firstname = trim((string) $row->firstname);
        $lastname  = trim((string) $row->lastname);
        $nickname  = trim((string) $row->nickname);

        $name = match ($format) {
            '1' => trim($lastname . ', ' . $firstname, ' ,'),
            '2' => $nickname !== '' ? $nickname : trim($firstname . ' ' . $lastname),
            default => trim($firstname . ' ' . $lastname),
        };

        return $name !== '' ? $name : ('ID ' . (int) $row->person_id);
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

    private function positiveIntList(mixed $value): array
    {
        if ($value === null || $value === '') {
            return [];
        }

        $items = is_array($value) ? $value : explode(',', (string) $value);
        $out   = [];

        foreach ($items as $item) {
            $number = $this->firstPositiveInt($item);

            if ($number > 0) {
                $out[] = $number;
            }
        }

        return array_values(array_unique($out));
    }
}
