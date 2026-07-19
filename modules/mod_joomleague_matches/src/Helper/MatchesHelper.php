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
     * Get matches according to the configured mode and legacy module filters.
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

        $mode  = (string) $params->get('mode', '');
        $count = $this->firstPositiveInt($params->get('count') ?: $params->get('limit') ?: $params->get('results'));

        $model = $app->bootComponent('com_joomleague')
            ->getMVCFactory()
            ->createModel('Results', 'Site', ['ignore_request' => true]);

        if ($mode === 'upcoming') {
            $matches = $model->getMatches($projectId, 0, 0, 0, true);
        } else {
            $matches = $model->getMatches($projectId);
        }

        if ($mode === 'results') {
            $matches = array_filter(
                $matches,
                fn ($m): bool => $this->isPlayed($m)
            );
        } elseif ($mode === '') {
            $matches = $this->filterByLegacyPeriods($matches, $params);
        }

        $matches = $this->filterByTeams(array_values($matches), $params);
        $matches = $this->sortMatches($matches, $params, $mode);

        if ((int) $params->get('show_referee', 0) === 1 && $matches !== []) {
            $referees = $model->getMatchesReferees(array_map(static fn ($match): int => (int) $match->id, $matches));

            foreach ($matches as $match) {
                $match->referees = $referees[(int) $match->id] ?? [];
            }
        }

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

    private function filterByLegacyPeriods(array $matches, Registry $params): array
    {
        $now = time();
        $showPlayed = (int) $params->get('show_played', 0) === 1;
        $pastWindow = $this->periodSeconds($params->get('result_add_time', 0), (string) $params->get('result_add_unit', 'DAY'));
        $futureWindow = $this->periodSeconds($params->get('period_int', 0), (string) $params->get('period_string', 'DAY'));

        return array_values(array_filter($matches, function ($match) use ($futureWindow, $now, $pastWindow, $showPlayed): bool {
            $matchTime = strtotime((string) ($match->match_date ?? '')) ?: 0;

            if ($matchTime === 0) {
                return false;
            }

            if ($this->isPlayed($match)) {
                if (!$showPlayed) {
                    return false;
                }

                return $pastWindow === 0 || $matchTime >= ($now - $pastWindow);
            }

            return $futureWindow === 0 || $matchTime <= ($now + $futureWindow);
        }));
    }

    private function filterByTeams(array $matches, Registry $params): array
    {
        $teamIds = $this->positiveInts($params->get('teams'));

        if ($teamIds === []) {
            return $matches;
        }

        $availableTeamIds = [];

        foreach ($matches as $match) {
            $availableTeamIds[] = (int) ($match->home_team_id ?? 0);
            $availableTeamIds[] = (int) ($match->away_team_id ?? 0);
        }

        $teamIds = array_values(array_intersect($teamIds, array_unique(array_filter($availableTeamIds))));

        if ($teamIds === []) {
            return $matches;
        }

        return array_values(array_filter(
            $matches,
            static fn ($match): bool => in_array((int) ($match->home_team_id ?? 0), $teamIds, true)
                || in_array((int) ($match->away_team_id ?? 0), $teamIds, true)
        ));
    }

    private function sortMatches(array $matches, Registry $params, string $mode): array
    {
        $upcomingFirst = (int) $params->get('upcoming_first', 1) === 1;
        $playedOrder = strtolower((string) $params->get('lastsortorder', 'asc')) === 'desc' ? -1 : 1;

        usort($matches, function ($a, $b) use ($mode, $playedOrder, $upcomingFirst): int {
            $aPlayed = $this->isPlayed($a);
            $bPlayed = $this->isPlayed($b);

            if ($mode === '' && $aPlayed !== $bPlayed) {
                return $upcomingFirst
                    ? ($aPlayed ? 1 : -1)
                    : ($aPlayed ? -1 : 1);
            }

            $direction = $aPlayed && $bPlayed ? $playedOrder : 1;
            $dateCompare = strcmp((string) ($a->match_date ?? ''), (string) ($b->match_date ?? ''));

            if ($dateCompare !== 0) {
                return $direction * $dateCompare;
            }

            return $direction * ((int) ($a->id ?? 0) <=> (int) ($b->id ?? 0));
        });

        return $matches;
    }

    private function isPlayed(object $match): bool
    {
        return $match->team1_result !== null
            && $match->team2_result !== null
            && (int) ($match->count_result ?? 1) === 1;
    }

    private function periodSeconds(mixed $amount, string $unit): int
    {
        $amount = max(0, (int) $amount);

        if ($amount === 0) {
            return 0;
        }

        return $amount * match (strtoupper($unit)) {
            'SECOND' => 1,
            'MINUTE' => 60,
            'HOUR' => 3600,
            'WEEK' => 604800,
            'MONTH' => 2592000,
            'YEAR' => 31536000,
            default => 86400,
        };
    }

    /**
     * @return  int[]
     */
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
}
