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
use Joomla\Database\ParameterType;

/**
 * Reads a project's rounds and match results, grouped by round, for the
 * site "results" view — the second site page this component has, alongside
 * "standings" (see docs/FRONTEND_MODULE_ROADMAP.md). Deliberately
 * self-contained (plain SQL, no shared Domain\Service reader) since its
 * purpose right now is to be a small, readable second example of a menu
 * item view rather than to introduce new shared read infrastructure.
 */
final class ResultsModel extends BaseDatabaseModel
{
    protected function populateState($ordering = null, $direction = null)
    {
        $input = Factory::getApplication()->getInput();

        // project_id/stage_id are "request" menu-item fields, same as the
        // standings view — part of the menu item's own link query string.
        $this->setState('project_id', $input->getInt('project_id', 0));

        $stageId = $input->getInt('stage_id', 0);
        $this->setState('stage_id', $stageId > 0 ? $stageId : null);
    }

    /** @return array<string,mixed> */
    public function getResults(): array
    {
        $projectId = (int) $this->getState('project_id');

        if ($projectId < 1) {
            return ['error' => 'COM_JOOMLEAGUE_RESULTS_NO_PROJECT'];
        }

        $db = Factory::getContainer()->get(DatabaseInterface::class);

        $project = $db->setQuery(
            $db->getQuery(true)->select(['id', 'name'])
                ->from($db->quoteName('#__joomleague_project'))
                ->where($db->quoteName('id') . ' = :projectId')
                ->bind(':projectId', $projectId, ParameterType::INTEGER)
        )->loadObject();

        if (!$project) {
            return ['error' => 'COM_JOOMLEAGUE_RESULTS_UNAVAILABLE'];
        }

        $stageId = $this->getState('stage_id');

        // round.sequence_number is only unique WITHIN its stage, so ordering by it alone
        // interleaves rounds from different stages once a project has more than one
        // (e.g. "Kvalifikace" round 1 and "Vyřazovací fáze" round 1 both = 1) — order by
        // the parent stage's own sequence first to keep whole stages together.
        $roundQuery = $db->getQuery(true)
            ->select(['round.id', 'round.name', 'round.sequence_number'])
            ->from($db->quoteName('#__joomleague_project_round', 'round'))
            ->innerJoin($db->quoteName('#__joomleague_project_stage', 'stage') . ' ON stage.id = round.stage_id')
            ->where($db->quoteName('round.project_id') . ' = :projectId')
            ->bind(':projectId', $projectId, ParameterType::INTEGER)
            ->order($db->quoteName('stage.sequence_number') . ' ASC, ' . $db->quoteName('round.sequence_number') . ' ASC');

        if ($stageId !== null) {
            $roundQuery->where($db->quoteName('round.stage_id') . ' = :stageId')->bind(':stageId', $stageId, ParameterType::INTEGER);
        }

        $rounds = $db->setQuery($roundQuery)->loadObjectList();

        if ($rounds === []) {
            return ['error' => 'COM_JOOMLEAGUE_RESULTS_VIEW_EMPTY', 'project' => $project];
        }

        $roundIds = array_map(static fn (object $round): int => (int) $round->id, $rounds);

        $matches = $db->setQuery(
            $db->getQuery(true)
                ->select(['match.id', 'match.round_id', 'match.scheduled_start', 'match.attendance', 'venue.name AS venue_name'])
                ->from($db->quoteName('#__joomleague_project_match', 'match'))
                ->leftJoin($db->quoteName('#__joomleague_venue', 'venue') . ' ON venue.id = match.venue_id')
                ->whereIn($db->quoteName('match.round_id'), $roundIds, ParameterType::INTEGER)
                ->order($db->quoteName('match.scheduled_start') . ' ASC, match.id ASC')
        )->loadObjectList();

        if ($matches === []) {
            return ['error' => 'COM_JOOMLEAGUE_RESULTS_VIEW_EMPTY', 'project' => $project];
        }

        $matchIds = array_map(static fn (object $match): int => (int) $match->id, $matches);

        // entry.display_name is frequently left blank (it only overrides the
        // linked team/person's own name) — fall back to team.name / person's
        // full name, same as the "entry" menu-field and MatchesReader do, or
        // every match shows blank team names instead of falling back.
        $participants = $db->setQuery(
            $db->getQuery(true)
                ->select([
                    'participant.match_id', 'participant.slot_number',
                    'COALESCE(NULLIF(' . $db->quoteName('entry.display_name') . ", ''), "
                        . $db->quoteName('team.name') . ', NULLIF(TRIM(CONCAT('
                        . $db->quoteName('person.first_name') . ", ' ', " . $db->quoteName('person.last_name')
                        . ")), ''), CONCAT('ID ', " . $db->quoteName('entry.id') . ')) AS ' . $db->quoteName('display_name'),
                ])
                ->from($db->quoteName('#__joomleague_match_participant', 'participant'))
                ->innerJoin($db->quoteName('#__joomleague_project_entry', 'entry') . ' ON entry.id = participant.project_entry_id')
                ->leftJoin($db->quoteName('#__joomleague_team', 'team') . ' ON team.id = entry.team_id')
                ->leftJoin($db->quoteName('#__joomleague_person', 'person') . ' ON person.id = entry.person_id')
                ->whereIn($db->quoteName('participant.match_id'), $matchIds, ParameterType::INTEGER)
        )->loadObjectList();

        $entryNameByMatchSlot = [];
        foreach ($participants as $participant) {
            $entryNameByMatchSlot[(int) $participant->match_id][(int) $participant->slot_number] = (string) $participant->display_name;
        }

        $notes = $db->setQuery(
            $db->getQuery(true)->select(['match_id', 'notes'])
                ->from($db->quoteName('#__joomleague_match_result'))
                ->whereIn($db->quoteName('match_id'), $matchIds, ParameterType::INTEGER)
        )->loadAssocList('match_id');

        $scoreValues = $db->setQuery(
            $db->getQuery(true)
                ->select(['segment.match_id', 'segment.level_code', 'participant.slot_number', 'value.numeric_value'])
                ->from($db->quoteName('#__joomleague_match_score_segment', 'segment'))
                ->innerJoin($db->quoteName('#__joomleague_match_score_value', 'value') . ' ON value.segment_id = segment.id')
                ->innerJoin($db->quoteName('#__joomleague_match_participant', 'participant') . ' ON participant.id = value.participant_id')
                ->whereIn($db->quoteName('segment.match_id'), $matchIds, ParameterType::INTEGER)
                ->where($db->quoteName('segment.level_code') . " IN ('result', 'shootout')")
        )->loadObjectList();

        $scoreByMatch = [];
        $shootoutByMatch = [];
        foreach ($scoreValues as $value) {
            $matchId = (int) $value->match_id;
            $slot = (int) $value->slot_number;
            if ($value->level_code === 'result') {
                $scoreByMatch[$matchId][$slot] = $value->numeric_value;
            } else {
                $shootoutByMatch[$matchId][$slot] = $value->numeric_value;
            }
        }

        $params = Factory::getApplication()->getParams();
        $showScorers = (int) $params->get('show_scorers', 1) === 1;
        $showVenue = (int) $params->get('show_venue', 1) === 1;

        $goalsByMatch = [];
        if ($showScorers) {
            $events = $db->setQuery(
                $db->getQuery(true)
                    ->select(['event.match_id', 'event.event_code', 'event.primary_name_snapshot', 'event.clock_value', 'participant.slot_number'])
                    ->from($db->quoteName('#__joomleague_match_event', 'event'))
                    ->innerJoin($db->quoteName('#__joomleague_match_participant', 'participant') . ' ON participant.id = event.match_participant_id')
                    ->whereIn($db->quoteName('event.match_id'), $matchIds, ParameterType::INTEGER)
                    ->whereIn($db->quoteName('event.event_code'), ['goal', 'own_goal', 'penalty_goal'])
                    ->order($db->quoteName('event.match_id') . ' ASC, event.clock_value ASC')
            )->loadObjectList();

            foreach ($events as $event) {
                $goalsByMatch[(int) $event->match_id][] = [
                    'slot' => (int) $event->slot_number,
                    'player' => (string) $event->primary_name_snapshot,
                    'minute' => rtrim(rtrim((string) $event->clock_value, '0'), '.'),
                    'type' => (string) $event->event_code,
                ];
            }
        }

        $matchesByRound = [];
        foreach ($matches as $match) {
            $matchId = (int) $match->id;
            $matchesByRound[(int) $match->round_id][] = [
				'id' => $matchId,
                'home' => $entryNameByMatchSlot[$matchId][1] ?? '',
                'away' => $entryNameByMatchSlot[$matchId][2] ?? '',
                'home_score' => $scoreByMatch[$matchId][1] ?? null,
                'away_score' => $scoreByMatch[$matchId][2] ?? null,
                'home_shootout' => $shootoutByMatch[$matchId][1] ?? null,
                'away_shootout' => $shootoutByMatch[$matchId][2] ?? null,
                'scheduled_start' => $match->scheduled_start,
                'venue' => $showVenue ? $match->venue_name : null,
                'attendance' => $showVenue ? $match->attendance : null,
                'notes' => $notes[$matchId]['notes'] ?? null,
                'goals' => $goalsByMatch[$matchId] ?? [],
            ];
        }

        $roundsOut = [];
        foreach ($rounds as $round) {
            $roundsOut[] = [
                'name' => (string) $round->name,
                'matches' => $matchesByRound[(int) $round->id] ?? [],
            ];
        }

        return [
            'project' => $project,
            'rounds' => $roundsOut,
            'show_scorers' => $showScorers,
            'show_venue' => $showVenue,
        ];
    }
}
