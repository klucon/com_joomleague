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
 * Reads every stage of a project up to and including the requested one, as a
 * single graphical bracket — site "bracket" view, alongside "standings" and
 * "results" (see docs/FRONTEND_MODULE_ROADMAP.md).
 *
 * A real cup draw is not a clean binary tree end to end: lower-tier clubs
 * enter in early qualifying rounds whose match counts don't halve, and the
 * source data for those rounds is itself a curated sample (not every
 * qualifying match is recorded), so many early matches have no traceable
 * link to the round before or after them. Rather than special-case "the
 * clean part" vs "the messy part", this model uses one algorithm for the
 * whole thing, working FORWARD from the very first round:
 *
 *   - round 0's matches are stacked in their stored order, one row height
 *     apart — there is nothing earlier to align them to.
 *   - for round R>0, each match looks up its two team names in round R-1's
 *     "team name -> vertical position" map. A team keeps the SAME vertical
 *     position from the round it enters until the round it's eliminated
 *     (verified true for every team in this dataset — nobody skips a round
 *     once they've entered), so:
 *       - both teams found -> position = the average of their two positions
 *         (this is what produces the familiar symmetric bracket shape
 *         whenever a round is a clean 2-into-1 elimination step);
 *       - one team found -> position = that team's position (a continuing
 *         team paired against a fresh entrant this round);
 *       - neither found -> no source position to derive from; placed after
 *         all positioned matches of the round, in stored order.
 *     Positions are then resolved top-to-bottom with a minimum row-height
 *     gap so nothing overlaps.
 *
 * Connector lines are drawn by the template as straight lines between a
 * match and each of its two source matches (when known) — genuinely absent
 * links (no source found) simply draw no line, which is the honest
 * reflection of what the data does and doesn't record.
 */
final class BracketModel extends BaseDatabaseModel
{
    private const ROW_HEIGHT = 64;

    protected function populateState($ordering = null, $direction = null)
    {
        $input = Factory::getApplication()->getInput();
        $this->setState('project_id', $input->getInt('project_id', 0));
        $this->setState('stage_id', $input->getInt('stage_id', 0));
    }

    /** @return array<string,mixed> */
    public function getBracket(): array
    {
        $projectId = (int) $this->getState('project_id');
        $stageId = (int) $this->getState('stage_id');

        if ($projectId < 1 || $stageId < 1) {
            return ['error' => 'COM_JOOMLEAGUE_BRACKET_NO_STAGE'];
        }

        $db = Factory::getContainer()->get(DatabaseInterface::class);

        $project = $db->setQuery(
            $db->getQuery(true)->select(['id', 'name'])
                ->from($db->quoteName('#__joomleague_project'))
                ->where($db->quoteName('id') . ' = :projectId')
                ->bind(':projectId', $projectId, ParameterType::INTEGER)
        )->loadObject();

        $targetStage = $db->setQuery(
            $db->getQuery(true)->select(['id', 'name', 'sequence_number'])
                ->from($db->quoteName('#__joomleague_project_stage'))
                ->where($db->quoteName('id') . ' = :stageId')
                ->where($db->quoteName('project_id') . ' = :projectId')
                ->bind(':stageId', $stageId, ParameterType::INTEGER)
                ->bind(':projectId', $projectId, ParameterType::INTEGER)
        )->loadObject();

        if (!$project || !$targetStage) {
            return ['error' => 'COM_JOOMLEAGUE_BRACKET_UNAVAILABLE'];
        }

        // Every stage of the project up to and including the requested one, in
        // tournament order — a project's stages are itself the ordered
        // structure (see the stage/round split decided for this project), so
        // "the bracket up to stage X" is simply "every earlier stage plus X".
        $stages = $db->setQuery(
            $db->getQuery(true)->select(['id', 'name'])
                ->from($db->quoteName('#__joomleague_project_stage'))
                ->where($db->quoteName('project_id') . ' = :projectId')
                ->where($db->quoteName('sequence_number') . ' <= :maxSequence')
                ->bind(':projectId', $projectId, ParameterType::INTEGER)
                ->bind(':maxSequence', $targetStage->sequence_number, ParameterType::INTEGER)
                ->order($db->quoteName('sequence_number') . ' ASC')
        )->loadObjectList();

        $stageIds = array_map(static fn (object $stage): int => (int) $stage->id, $stages);

        $rounds = $db->setQuery(
            $db->getQuery(true)->select(['round.id', 'round.name', 'round.stage_id'])
                ->from($db->quoteName('#__joomleague_project_round', 'round'))
                ->innerJoin($db->quoteName('#__joomleague_project_stage', 'stage') . ' ON stage.id = round.stage_id')
                ->whereIn($db->quoteName('round.stage_id'), $stageIds, ParameterType::INTEGER)
                ->order($db->quoteName('stage.sequence_number') . ' ASC, ' . $db->quoteName('round.sequence_number') . ' ASC')
        )->loadObjectList();

        if (count($rounds) < 2) {
            return ['error' => 'COM_JOOMLEAGUE_BRACKET_VIEW_EMPTY', 'project' => $project];
        }

        $roundIds = array_map(static fn (object $round): int => (int) $round->id, $rounds);

        $matches = $db->setQuery(
            $db->getQuery(true)->select(['id', 'round_id'])
                ->from($db->quoteName('#__joomleague_project_match'))
                ->whereIn($db->quoteName('round_id'), $roundIds, ParameterType::INTEGER)
        )->loadObjectList();

        $matchIds = array_map(static fn (object $match): int => (int) $match->id, $matches);

        if ($matchIds === []) {
            return ['error' => 'COM_JOOMLEAGUE_BRACKET_VIEW_EMPTY', 'project' => $project];
        }

        $participants = $db->setQuery(
            $db->getQuery(true)
                ->select(['participant.match_id', 'participant.slot_number', 'entry.display_name'])
                ->from($db->quoteName('#__joomleague_match_participant', 'participant'))
                ->innerJoin($db->quoteName('#__joomleague_project_entry', 'entry') . ' ON entry.id = participant.project_entry_id')
                ->whereIn($db->quoteName('participant.match_id'), $matchIds, ParameterType::INTEGER)
        )->loadObjectList();

        $nameByMatchSlot = [];
        foreach ($participants as $participant) {
            $nameByMatchSlot[(int) $participant->match_id][(int) $participant->slot_number] = (string) $participant->display_name;
        }

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
                $scoreByMatch[$matchId][$slot] = (float) $value->numeric_value;
            } else {
                $shootoutByMatch[$matchId][$slot] = (float) $value->numeric_value;
            }
        }

        $matchesByRound = [];
        foreach ($matches as $match) {
            $matchId = (int) $match->id;
            $home = $nameByMatchSlot[$matchId][1] ?? '';
            $away = $nameByMatchSlot[$matchId][2] ?? '';
            $homeScore = $scoreByMatch[$matchId][1] ?? null;
            $awayScore = $scoreByMatch[$matchId][2] ?? null;

            $winner = null;
            if ($homeScore !== null && $awayScore !== null) {
                if ($homeScore > $awayScore) {
                    $winner = 'home';
                } elseif ($awayScore > $homeScore) {
                    $winner = 'away';
                } else {
                    $homePens = $shootoutByMatch[$matchId][1] ?? null;
                    $awayPens = $shootoutByMatch[$matchId][2] ?? null;
                    if ($homePens !== null && $awayPens !== null && $homePens !== $awayPens) {
                        $winner = $homePens > $awayPens ? 'home' : 'away';
                    }
                }
            }

            $matchesByRound[(int) $match->round_id][$matchId] = [
                'id' => $matchId,
                'home' => $home,
                'away' => $away,
                'home_score' => $homeScore,
                'away_score' => $awayScore,
                'home_shootout' => $shootoutByMatch[$matchId][1] ?? null,
                'away_shootout' => $shootoutByMatch[$matchId][2] ?? null,
                'winner' => $winner,
            ];
        }

        // --- Forward layout pass (see class docblock) ---
        $y = [];            // matchId => y position (px)
        $feedersOf = [];    // matchId => list<matchId> (0, 1 or 2 source matches)
        $teamMatch = [];    // team display_name => matchId, carried from the previous round

        foreach ($rounds as $roundIndex => $round) {
            $roundId = (int) $round->id;
            $roundMatches = $matchesByRound[$roundId] ?? [];
            $nextTeamMatch = [];

            if ($roundIndex === 0) {
                $cursor = 0;
                foreach ($roundMatches as $matchId => $match) {
                    $y[$matchId] = $cursor;
                    $feedersOf[$matchId] = [];
                    $cursor += self::ROW_HEIGHT;
                    if ($match['home'] !== '') {
                        $nextTeamMatch[$match['home']] = $matchId;
                    }
                    if ($match['away'] !== '') {
                        $nextTeamMatch[$match['away']] = $matchId;
                    }
                }
                $teamMatch = $nextTeamMatch;
                continue;
            }

            $desired = [];    // matchId => float|null
            $feeders = [];    // matchId => list<matchId>
            $unpositioned = [];

            foreach ($roundMatches as $matchId => $match) {
                $positions = [];
                $matchFeeders = [];

                foreach (['home', 'away'] as $side) {
                    $team = $match[$side];
                    if ($team !== '' && isset($teamMatch[$team])) {
                        $feederId = $teamMatch[$team];
                        $positions[] = $y[$feederId];
                        $matchFeeders[] = $feederId;
                    }
                }

                $feeders[$matchId] = $matchFeeders;

                if ($positions !== []) {
                    $desired[$matchId] = array_sum($positions) / count($positions);
                } else {
                    $unpositioned[] = $matchId;
                }
            }

            // Resolve top-to-bottom with a minimum gap, positioned matches first
            // (in ascending desired order), unpositioned ones appended after.
            $orderedIds = array_keys($desired);
            usort($orderedIds, static fn (int $a, int $b) => $desired[$a] <=> $desired[$b]);
            $orderedIds = [...$orderedIds, ...$unpositioned];

            $cursor = null;
            foreach ($orderedIds as $matchId) {
                $wanted = $desired[$matchId] ?? ($cursor === null ? 0.0 : $cursor + self::ROW_HEIGHT);
                $placed = $cursor === null ? $wanted : max($wanted, $cursor + self::ROW_HEIGHT);
                $y[$matchId] = $placed;
                $feedersOf[$matchId] = $feeders[$matchId];
                $cursor = $placed;

                $match = $roundMatches[$matchId];
                if ($match['home'] !== '') {
                    $nextTeamMatch[$match['home']] = $matchId;
                }
                if ($match['away'] !== '') {
                    $nextTeamMatch[$match['away']] = $matchId;
                }
            }

            $teamMatch = $nextTeamMatch;
        }

        $roundsOut = [];
        foreach ($rounds as $roundIndex => $round) {
            $roundId = (int) $round->id;
            $roundMatches = $matchesByRound[$roundId] ?? [];
            $matchesOut = [];
            foreach ($roundMatches as $matchId => $match) {
                $match['y'] = $y[$matchId] ?? 0.0;
                $match['feeder_ids'] = $feedersOf[$matchId] ?? [];
                $match['feeder_ys'] = array_map(static fn (int $feederId): float => $y[$feederId], $match['feeder_ids']);
                $matchesOut[] = $match;
            }
            $roundsOut[] = [
                'name' => (string) $round->name,
                'in_target_stage' => (int) $round->stage_id === (int) $targetStage->id,
                'matches' => $matchesOut,
            ];
        }

        $maxY = 0.0;
        foreach ($y as $value) {
            $maxY = max($maxY, $value);
        }

        // Index of the first round belonging to the requested stage — the page
        // scrolls here on load (see tmpl/bracket/default.php), since the
        // "business end" the visitor asked for can otherwise land far outside
        // the initial viewport once earlier, larger qualifying rounds pull its
        // vertical position away from the top of the canvas.
        $focusRoundIndex = 0;
        foreach ($rounds as $roundIndex => $round) {
            if ((int) $round->stage_id === (int) $targetStage->id) {
                $focusRoundIndex = $roundIndex;
                break;
            }
        }

        return [
            'project' => $project,
            'stage' => $targetStage,
            'rounds' => $roundsOut,
            'row_height' => self::ROW_HEIGHT,
            'canvas_height' => $maxY + self::ROW_HEIGHT,
            'focus_round_index' => $focusRoundIndex,
        ];
    }
}
