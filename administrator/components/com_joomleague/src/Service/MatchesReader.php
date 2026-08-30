<?php

declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Domain\Service;

defined('_JEXEC') or die;

use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;

/**
 * Read-only access to a project entry's own fixture list (past + future
 * matches), from that entry's point of view (opponent, home/away, own
 * score first). Safe to call from admin, site views and modules alike —
 * mirrors StandingsReader's role for the "teamplan"/"clubplan"/"nextmatch"
 * family of views (see docs/FRONTEND_MODULE_ROADMAP.md), which are meant to
 * share this one read pattern instead of each re-querying the schema.
 */
final class MatchesReader
{
    public function __construct(private readonly DatabaseInterface $database) {}

    /** @return array<string,mixed> */
	public function forEntry(int $projectId, int $entryId): array
    {
        if ($projectId < 1 || $entryId < 1) {
            throw new \InvalidArgumentException('Matches request is invalid.');
        }

        $db = $this->database;

        $entry = $db->setQuery(
            $db->getQuery(true)
                ->select([
                    $db->quoteName('entry.id'),
                    $db->quoteName('entry.project_id'),
                    'COALESCE(NULLIF(' . $db->quoteName('entry.display_name') . ", ''), "
                        . $db->quoteName('team.name') . ', NULLIF(TRIM(CONCAT('
                        . $db->quoteName('person.first_name') . ", ' ', " . $db->quoteName('person.last_name')
                        . ")), ''), CONCAT('ID ', " . $db->quoteName('entry.id') . ')) AS ' . $db->quoteName('display_name'),
                ])
                ->from($db->quoteName('#__joomleague_project_entry', 'entry'))
                ->leftJoin($db->quoteName('#__joomleague_team', 'team') . ' ON team.id = entry.team_id')
                ->leftJoin($db->quoteName('#__joomleague_person', 'person') . ' ON person.id = entry.person_id')
                ->where($db->quoteName('entry.id') . ' = :entry')
                ->bind(':entry', $entryId, ParameterType::INTEGER)
        )->loadObject();

        if (!$entry || (int) $entry->project_id !== $projectId) {
            return ['entry' => null, 'matches' => []];
        }

		return ['entry' => $entry, 'matches' => $this->matchesForEntryIds([$entryId])[$entryId] ?? []];
	}

	/**
	 * Reads several entries in one project without repeating the match query.
	 *
	 * @param list<int> $entryIds
	 * @return array{entries:array<int,object>,matches:array<int,list<array<string,mixed>>>}
	 */
	public function forEntries(int $projectId, array $entryIds): array
	{
		$entryIds = array_values(array_unique(array_filter(array_map('intval', $entryIds), static fn (int $id): bool => $id > 0)));

		if ($projectId < 1 || $entryIds === []) {
			return ['entries' => [], 'matches' => []];
		}

		$db = $this->database;
		$entries = $db->setQuery(
			$db->getQuery(true)
				->select([
					$db->quoteName('entry.id'),
					$db->quoteName('entry.project_id'),
					'COALESCE(NULLIF(' . $db->quoteName('entry.display_name') . ", ''), "
						. $db->quoteName('team.name') . ', NULLIF(TRIM(CONCAT('
						. $db->quoteName('person.first_name') . ", ' ', " . $db->quoteName('person.last_name')
						. ")), ''), CONCAT('ID ', " . $db->quoteName('entry.id') . ')) AS ' . $db->quoteName('display_name'),
				])
				->from($db->quoteName('#__joomleague_project_entry', 'entry'))
				->leftJoin($db->quoteName('#__joomleague_team', 'team') . ' ON team.id = entry.team_id')
				->leftJoin($db->quoteName('#__joomleague_person', 'person') . ' ON person.id = entry.person_id')
				->where($db->quoteName('entry.project_id') . ' = :project')
				->whereIn($db->quoteName('entry.id'), $entryIds, ParameterType::INTEGER)
				->bind(':project', $projectId, ParameterType::INTEGER)
		)->loadObjectList('id');

		$validIds = array_map('intval', array_keys($entries));

		return [
			'entries' => $entries,
			'matches' => $this->matchesForEntryIds($validIds),
		];
	}

    /**
     * Shared core query, keyed by entry id, so a future clubplan reader
     * (several entries at once, one per club member team) can call this
     * with more than one id without duplicating the joins.
     *
     * @param list<int> $entryIds
     * @return array<int, list<array<string,mixed>>>
     */
    private function matchesForEntryIds(array $entryIds): array
    {
        if ($entryIds === []) {
            return [];
        }

        $db = $this->database;

        $own = $db->setQuery(
            $db->getQuery(true)
                ->select(['participant.match_id', 'participant.project_entry_id', 'participant.slot_number'])
                ->from($db->quoteName('#__joomleague_match_participant', 'participant'))
                ->whereIn($db->quoteName('participant.project_entry_id'), $entryIds, ParameterType::INTEGER)
        )->loadObjectList();

        if ($own === []) {
            return [];
        }

        $matchIds = array_values(array_unique(array_map(static fn (object $row): int => (int) $row->match_id, $own)));

        $matches = $db->setQuery(
            $db->getQuery(true)
                ->select([
                    'match.id', 'match.round_id', 'match.scheduled_start', 'match.attendance',
                    'round.name AS round_name', 'venue.name AS venue_name',
                ])
                ->from($db->quoteName('#__joomleague_project_match', 'match'))
                ->innerJoin($db->quoteName('#__joomleague_project_round', 'round') . ' ON round.id = match.round_id')
                ->leftJoin($db->quoteName('#__joomleague_venue', 'venue') . ' ON venue.id = match.venue_id')
                ->whereIn($db->quoteName('match.id'), $matchIds, ParameterType::INTEGER)
                ->order($db->quoteName('match.scheduled_start') . ' ASC, match.id ASC')
        )->loadObjectList();

        // entry.display_name is frequently left blank (it only overrides the
        // linked team/person's own name) — same COALESCE fallback as the
        // own-entry lookup above, or opponents show up nameless.
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

        $scoreValues = $db->setQuery(
            $db->getQuery(true)
                ->select(['segment.match_id', 'segment.level_code', 'participant.slot_number', 'value.numeric_value'])
                ->from($db->quoteName('#__joomleague_match_score_segment', 'segment'))
                ->innerJoin($db->quoteName('#__joomleague_match_score_value', 'value') . ' ON value.segment_id = segment.id')
                ->innerJoin($db->quoteName('#__joomleague_match_participant', 'participant') . ' ON participant.id = value.participant_id')
                ->whereIn($db->quoteName('segment.match_id'), $matchIds, ParameterType::INTEGER)
                ->where($db->quoteName('segment.level_code') . " = 'result'")
        )->loadObjectList();

        $scoreByMatch = [];
        foreach ($scoreValues as $value) {
            $scoreByMatch[(int) $value->match_id][(int) $value->slot_number] = $value->numeric_value;
        }

        $matchById = [];
        foreach ($matches as $match) {
            $matchById[(int) $match->id] = $match;
        }

        $ownByMatchEntry = [];
        foreach ($own as $row) {
            $ownByMatchEntry[(int) $row->match_id][(int) $row->project_entry_id] = (int) $row->slot_number;
        }

        $result = [];
        foreach ($entryIds as $entryId) {
            $rows = [];
            foreach ($ownByMatchEntry as $matchId => $slotsByEntry) {
                if (!isset($slotsByEntry[$entryId]) || !isset($matchById[$matchId])) {
                    continue;
                }

                $match = $matchById[$matchId];
                $ownSlot = $slotsByEntry[$entryId];
                $opponentSlot = $ownSlot === 1 ? 2 : 1;

                $ownScore = $scoreByMatch[$matchId][$ownSlot] ?? null;
                $opponentScore = $scoreByMatch[$matchId][$opponentSlot] ?? null;

                $rows[] = [
                    'match_id' => $matchId,
                    'round_name' => (string) $match->round_name,
                    'scheduled_start' => $match->scheduled_start,
                    'venue' => $match->venue_name,
                    'attendance' => $match->attendance,
                    'is_home' => $ownSlot === 1,
                    'opponent' => $entryNameByMatchSlot[$matchId][$opponentSlot] ?? '',
                    'own_score' => $ownScore,
                    'opponent_score' => $opponentScore,
                    'played' => $ownScore !== null && $opponentScore !== null,
                ];
            }

            usort($rows, static fn (array $a, array $b): int => ($a['scheduled_start'] ?? '') <=> ($b['scheduled_start'] ?? ''));

            $result[$entryId] = $rows;
        }

        return $result;
    }
}
