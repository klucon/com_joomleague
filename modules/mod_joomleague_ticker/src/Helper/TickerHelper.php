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
use Joomla\Database\DatabaseAwareInterface;
use Joomla\Database\DatabaseAwareTrait;
use Joomla\Database\ParameterType;
use Joomla\Registry\Registry;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Helper for mod_joomleague_ticker.
 *
 * @since  1.0.0
 */
class TickerHelper implements DatabaseAwareInterface
{
    use DatabaseAwareTrait;

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

        $model = $app->bootComponent('com_joomleague')
            ->getMVCFactory()
            ->createModel('Results', 'Site', ['ignore_request' => true]);

        $roundId = $this->resolveRoundId($params, $projectId);
        $teamId  = $this->resolveTeamId($params, $projectId);

        $all = $model->getMatches($projectId, $roundId);
        $all = $this->filterMatches($all, $params, $teamId);
        $all = $this->sortMatches($all, (string) $params->get('ordering', 'asc'));

        return $count > 0 ? array_slice($all, 0, $count) : $all;
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

    private function resolveTeamId(Registry $params, int $projectId): int
    {
        $teamId = $this->firstPositiveInt($params->get('teamid') ?: $params->get('team_id'));

        if ($teamId === 0 || $projectId === 0) {
            return 0;
        }

        $db = $this->getDatabase();
        $query = $db->createQuery()
            ->select('COUNT(*)')
            ->from($db->quoteName('#__joomleague_project_team'))
            ->where($db->quoteName('project_id') . ' = :project_id')
            ->where($db->quoteName('team_id') . ' = :team_id')
            ->bind(':project_id', $projectId, ParameterType::INTEGER)
            ->bind(':team_id', $teamId, ParameterType::INTEGER);

        return (int) $db->setQuery($query)->loadResult() > 0 ? $teamId : 0;
    }

    private function resolveRoundId(Registry $params, int $projectId): int
    {
        $roundId = $this->firstPositiveInt($params->get('round') ?: $params->get('round_id'));

        if ($roundId === 0 || $projectId === 0) {
            return 0;
        }

        $db = $this->getDatabase();
        $query = $db->createQuery()
            ->select('COUNT(*)')
            ->from($db->quoteName('#__joomleague_round'))
            ->where($db->quoteName('project_id') . ' = :project_id')
            ->where($db->quoteName('id') . ' = :round_id')
            ->bind(':project_id', $projectId, ParameterType::INTEGER)
            ->bind(':round_id', $roundId, ParameterType::INTEGER);

        return (int) $db->setQuery($query)->loadResult() > 0 ? $roundId : 0;
    }

    private function filterMatches(array $matches, Registry $params, int $teamId): array
    {
        $status = (int) $params->get('matchstatus', 4);
        $daysBack = max(0, (int) $params->get('daysback', 14));
        $cutoff = $daysBack > 0 ? strtotime('-' . $daysBack . ' days') : 0;

        return array_values(array_filter($matches, function ($match) use ($cutoff, $status, $teamId): bool {
            if ($teamId > 0 && (int) ($match->home_team_id ?? 0) !== $teamId && (int) ($match->away_team_id ?? 0) !== $teamId) {
                return false;
            }

            $played = $this->isPlayed($match);
            $matchTime = strtotime((string) ($match->match_date ?? '')) ?: 0;

            if ($played && $cutoff > 0 && $matchTime > 0 && $matchTime < $cutoff) {
                return false;
            }

            return match ($status) {
                0 => $played,
                1 => $played,
                2, 3 => !$played,
                default => true,
            };
        }));
    }

    private function sortMatches(array $matches, string $ordering): array
    {
        $direction = strtolower($ordering) === 'desc' ? -1 : 1;

        usort($matches, static function ($a, $b) use ($direction): int {
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
}
