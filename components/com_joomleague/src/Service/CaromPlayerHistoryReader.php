<?php
declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Site\Service;

defined('_JEXEC') or die;

use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;
use Joomleague\Component\Joomleague\Administrator\Service\CaromPlayerSummary;

/** Public final singles only; identities are taken from persisted duel nominations. */
final class CaromPlayerHistoryReader
{
	public function __construct(private DatabaseInterface $database) {}

	public function forPerson(int $personId, array $viewLevels): array
	{
		$db = $this->database;
		$levels = array_values(array_filter(array_unique(array_map('intval', $viewLevels)), static fn (int $id): bool => $id > 0));
		if ($personId < 1 || $levels === []) return ['history' => [], 'projects' => []];
		$access = static fn (string $alias): string => $db->quoteName($alias . '.access') . ' IN (' . implode(',', $levels) . ')';
		$query = $db->getQuery(true)->select([
			'segment.id', 'segment.sequence_number', 'item.id AS match_id', 'item.scheduled_start', 'item.timezone',
			'project.id AS project_id', 'project.name AS project_name', 'season.id AS season_id', 'season.name AS season_name',
			'competition.name AS competition_name', 'version.payload_json',
		])->from($db->quoteName('#__joomleague_match_score_segment', 'segment'))
			->innerJoin($db->quoteName('#__joomleague_project_match', 'item') . ' ON item.id=segment.match_id AND item.published=1')
			->innerJoin($db->quoteName('#__joomleague_project', 'project') . ' ON project.id=item.project_id AND project.published=1')
			->innerJoin($db->quoteName('#__joomleague_competition', 'competition') . ' ON competition.id=project.competition_id AND competition.published=1')
			->innerJoin($db->quoteName('#__joomleague_season', 'season') . ' ON season.id=project.season_id AND season.published=1')
			->innerJoin($db->quoteName('#__joomleague_sport_type', 'sport') . ' ON sport.id=project.sport_type_id AND sport.published=1')
			->innerJoin($db->quoteName('#__joomleague_sport_profile_version', 'version') . ' ON version.id=project.profile_version_id')
			->innerJoin($db->quoteName('#__joomleague_project_stage', 'stage') . ' ON stage.id=item.stage_id AND stage.project_id=project.id AND stage.published=1')
			->innerJoin($db->quoteName('#__joomleague_project_round', 'round') . ' ON round.id=item.round_id AND round.project_id=project.id AND round.published=1')
			->innerJoin($db->quoteName('#__joomleague_match_result', 'result') . " ON result.match_id=item.id AND result.status_code='final' AND item.status_code <> 'cancelled'")
			->innerJoin($db->quoteName('#__joomleague_match_duel', 'duel') . " ON duel.match_id=item.id AND duel.sequence_number=segment.sequence_number AND duel.format_code='singles'")
			->innerJoin($db->quoteName('#__joomleague_match_duel_member', 'nomination') . ' ON nomination.duel_id=duel.id')
			->innerJoin($db->quoteName('#__joomleague_match_lineup_member', 'member') . ' ON member.id=nomination.lineup_member_id AND member.match_id=item.id AND member.match_participant_id=nomination.match_participant_id AND member.published=1')
			->innerJoin($db->quoteName('#__joomleague_person', 'person') . ' ON person.id=member.person_id AND person.published=1')
			->where('person.id=:personId')->where("segment.level_code='duel'")->where("segment.status_code='completed'")
			->where($access('person'))->where($access('project'))->where($access('competition'))->where($access('season'))
			->bind(':personId', $personId, ParameterType::INTEGER)->order('item.scheduled_start DESC, item.id DESC, segment.sequence_number ASC');
		$games = [];
		foreach ($db->setQuery($query)->loadObjectList() as $game) {
			$profile = json_decode((string) $game->payload_json, true);
			if (($profile['code'] ?? '') !== 'carom') continue;
			unset($game->payload_json);
			$games[(int) $game->id] = $game;
		}
		if ($games === []) return ['history' => [], 'projects' => []];
		$rows = [];
		// Bound IN lists keep the second pass portable and avoid one query per duel.
		foreach (array_chunk(array_keys($games), 500) as $ids) {
			$query = $db->getQuery(true)->select([
				'segment.id AS segment_id', 'participant.id AS participant_id', 'member.id AS member_id',
				'member.person_id AS nominated_person_id', 'person.id AS person_id', 'person.first_name', 'person.last_name',
				'value.numeric_value', 'value.metadata_json',
			])->from($db->quoteName('#__joomleague_match_score_segment', 'segment'))
				->innerJoin($db->quoteName('#__joomleague_match_duel', 'duel') . ' ON duel.match_id=segment.match_id AND duel.sequence_number=segment.sequence_number')
				->innerJoin($db->quoteName('#__joomleague_match_duel_member', 'nomination') . ' ON nomination.duel_id=duel.id')
				->innerJoin($db->quoteName('#__joomleague_match_participant', 'participant') . ' ON participant.id=nomination.match_participant_id AND participant.match_id=segment.match_id AND participant.published=1')
				->innerJoin($db->quoteName('#__joomleague_project_entry', 'entry') . ' ON entry.id=participant.project_entry_id AND entry.project_id=participant.project_id AND entry.published=1')
				->innerJoin($db->quoteName('#__joomleague_team', 'team') . " ON team.id=entry.team_id AND entry.entry_kind='team' AND team.published=1 AND " . $access('team'))
				->innerJoin($db->quoteName('#__joomleague_match_lineup_member', 'member') . ' ON member.id=nomination.lineup_member_id AND member.match_id=segment.match_id AND member.match_participant_id=participant.id')
				->leftJoin($db->quoteName('#__joomleague_person', 'person') . ' ON person.id=member.person_id AND member.published=1 AND person.published=1 AND ' . $access('person'))
				->leftJoin($db->quoteName('#__joomleague_match_score_value', 'value') . ' ON value.segment_id=segment.id AND value.match_id=segment.match_id AND value.participant_id=participant.id')
				->whereIn('segment.id', $ids)->order('segment.id, participant.id, member.id');
			foreach ($db->setQuery($query)->loadObjectList() as $row) $rows[(int) $row->segment_id][] = $row;
		}
		return (new CaromPlayerSummary())->build((int) $personId, $games, $rows);
	}
}
