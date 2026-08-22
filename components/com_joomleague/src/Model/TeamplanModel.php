<?php

declare(strict_types=1);

/**
 * @package     Joomleague.Site
 * @subpackage  com_joomleague
 *
 * @copyright   (C) 2026 Ondřej Klučka (https://klucon.cz). All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomleague\Component\Joomleague\Site\Model;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\Database\DatabaseInterface;
use Joomleague\Component\Joomleague\Domain\Service\MatchesReader;

/**
 * Reads one project entry's own fixture list (past + future matches) for
 * the site "teamplan" view — third real site page, after "standings" and
 * "results". Read-only: reuses the same MatchesReader a future
 * mod_joomleague_matches module and the "clubplan"/"nextmatch" views will
 * share, per the shared domain layer rule (see docs/FRONTEND_MODULE_ROADMAP.md).
 */
final class TeamplanModel extends BaseDatabaseModel
{
    protected function populateState($ordering = null, $direction = null)
    {
        $input = Factory::getApplication()->getInput();

        // project_id/entry_id are "request" menu-item fields (part of the
        // menu item's own link query string), not "params".
        $this->setState('project_id', $input->getInt('project_id', 0));
        $this->setState('entry_id', $input->getInt('entry_id', 0));
    }

    /** @return array<string,mixed> */
    public function getPlan(): array
    {
        $projectId = (int) $this->getState('project_id');
        $entryId = (int) $this->getState('entry_id');

        if ($projectId < 1) {
            return ['error' => 'COM_JOOMLEAGUE_TEAMPLAN_NO_PROJECT'];
        }

        if ($entryId < 1) {
            return ['error' => 'COM_JOOMLEAGUE_TEAMPLAN_NO_ENTRY'];
        }

        Factory::getApplication()->bootComponent('com_joomleague');

        $database = Factory::getContainer()->get(DatabaseInterface::class);
        $reader = new MatchesReader($database);

        $plan = $reader->forEntry($projectId, $entryId);

        if ($plan['entry'] === null) {
            return ['error' => 'COM_JOOMLEAGUE_TEAMPLAN_UNAVAILABLE'];
        }

        $params = Factory::getApplication()->getParams();
        $scope = (string) $params->get('scope', 'all');
        $orderDesc = (int) $params->get('order_desc', 0) === 1;
        $limit = (int) $params->get('limit', 0);
        $highlightNext = (int) $params->get('highlight_next', 1) === 1;

        // "Upcoming" means an unplayed match that is actually ahead of us in
        // time — not just any match without a recorded score. Historical
        // imports can contain old matches that were simply never entered
        // (no result), which the plain !played check alone would wrongly
        // treat as "upcoming"/"next" even though their date is years in the
        // past (found live while testing: a 2021 match was picked as "the
        // next match" on a 2026 page). "Played" stays purely score-based —
        // that fact is unambiguous regardless of date.
        $now = Factory::getDate()->toSql();
        $isFuture = static fn (array $m): bool => !$m['played'] && $m['scheduled_start'] !== null && $m['scheduled_start'] >= $now;

        // Resolved against the full, chronologically-ascending list — before
        // scope filtering or the display limit slice it below — since "the
        // next match" is an absolute fact about the schedule, not something
        // that should shift depending on how the page happens to be filtered.
        $nextMatchId = null;
        foreach ($plan['matches'] as $match) {
            if ($isFuture($match)) {
                $nextMatchId = $match['match_id'];
                break;
            }
        }

        $matches = $plan['matches'];
        if ($scope === 'upcoming') {
            $matches = array_values(array_filter($matches, $isFuture));
        } elseif ($scope === 'played') {
            $matches = array_values(array_filter($matches, static fn (array $m): bool => $m['played']));
        }

        if ($orderDesc) {
            $matches = array_reverse($matches);
        }

        if ($limit > 0) {
            $matches = array_slice($matches, 0, $limit);
        }

        return [
            'entry' => $plan['entry'],
            'matches' => $matches,
            'show_venue' => (int) $params->get('show_venue', 1) === 1,
            'show_round' => (int) $params->get('show_round', 1) === 1,
            'highlight_next' => $highlightNext,
            'next_match_id' => $nextMatchId,
        ];
    }
}
