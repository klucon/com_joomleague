<?php

declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Domain\Service;

defined('_JEXEC') or die;

use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;

final class ParticipantSummaryReader
{
	public function __construct(private readonly DatabaseInterface $db)
	{
	}

	/** @param list<int> $viewLevels
	 *  @return array<string,mixed>
	 */
	public function read(int $projectId, int $entryId, array $viewLevels, string $today): array
	{
		if ($projectId < 1 || $entryId < 1) {
			return ['error' => 'participant_required'];
		}
		$levels = array_values(array_unique(array_filter(array_map('intval', $viewLevels), static fn (int $id): bool => $id > 0)));
		$access = implode(',', $levels === [] ? [1] : $levels);
		$db = $this->db;
		$query = $db->getQuery(true)->select([
			'entry.id', 'entry.entry_kind', 'entry.entry_code', 'entry.seed_number', 'entry.bib_number', 'entry.notes', 'entry.person_id',
			$db->quoteName('project.id', 'project_id'), $db->quoteName('project.name', 'project_name'),
			$db->quoteName('sport_type.name', 'sport_type_name'),
			$db->quoteName('team.logo', 'team_logo'), $db->quoteName('team.picture', 'team_picture'),
			$db->quoteName('team.description', 'team_description'), $db->quoteName('team.website', 'team_website'),
			$db->quoteName('person.picture', 'person_picture'), $db->quoteName('person.description', 'person_description'),
			$db->quoteName('person.country_code', 'person_country_code'), $db->quoteName('club.id', 'club_id'), $db->quoteName('club.name', 'club_name'),
			'COALESCE(NULLIF(' . $db->quoteName('entry.display_name') . ", ''), " . $db->quoteName('team.name')
				. ', NULLIF(TRIM(CONCAT(' . $db->quoteName('person.first_name') . ", ' ', " . $db->quoteName('person.last_name')
				. ")), ''), CONCAT('ID ', " . $db->quoteName('entry.id') . ')) AS ' . $db->quoteName('display_name'),
		])->from($db->quoteName('#__joomleague_project_entry', 'entry'))
			->innerJoin($db->quoteName('#__joomleague_project', 'project') . ' ON project.id = entry.project_id AND project.published = 1')
			->innerJoin($db->quoteName('#__joomleague_competition', 'competition') . ' ON competition.id = project.competition_id AND competition.published = 1')
			->innerJoin($db->quoteName('#__joomleague_season', 'season') . ' ON season.id = project.season_id AND season.published = 1')
			->innerJoin($db->quoteName('#__joomleague_sport_type', 'sport_type') . ' ON sport_type.id = project.sport_type_id AND sport_type.published = 1')
			->leftJoin($db->quoteName('#__joomleague_team', 'team') . ' ON team.id = entry.team_id AND team.published = 1 AND team.access IN (' . $access . ')')
			->leftJoin($db->quoteName('#__joomleague_club', 'club') . ' ON club.id = team.club_id AND club.published = 1 AND club.access IN (' . $access . ')')
			->leftJoin($db->quoteName('#__joomleague_person', 'person') . ' ON person.id = entry.person_id AND person.published = 1 AND person.access IN (' . $access . ')')
			->where('entry.id = :entryId')->where('entry.project_id = :projectId')->where('entry.published = 1')
			->where('project.access IN (' . $access . ')')->where('competition.access IN (' . $access . ')')->where('season.access IN (' . $access . ')')
			->where("(entry.entry_kind = 'group' OR (entry.entry_kind = 'team' AND team.id IS NOT NULL) OR (entry.entry_kind = 'person' AND person.id IS NOT NULL))")
			->bind(':entryId', $entryId, ParameterType::INTEGER)->bind(':projectId', $projectId, ParameterType::INTEGER);
		$participant = $db->setQuery($query)->loadObject();
		if (!$participant) {
			return ['error' => 'participant_unavailable'];
		}

		$memberQuery = $db->getQuery(true)->select([
			'member.id', 'member.member_person_type', 'member.shirt_number', 'member.is_captain',
			$db->quoteName('person.id', 'person_id'), 'person.first_name', 'person.last_name', 'person.nickname', 'person.picture',
		])->from($db->quoteName('#__joomleague_project_entry_member', 'member'))
			->innerJoin($db->quoteName('#__joomleague_person', 'person') . ' ON person.id = member.person_id AND person.published = 1 AND person.access IN (' . $access . ')')
			->where('member.entry_id = :entryId')->where('member.published = 1')
			->where('(member.valid_from IS NULL OR member.valid_from <= :todayFrom)')
			->where('(member.valid_until IS NULL OR member.valid_until >= :todayUntil)')
			->bind(':entryId', $entryId, ParameterType::INTEGER)->bind(':todayFrom', $today)->bind(':todayUntil', $today)
			->order('member.ordering ASC, person.last_name ASC, person.first_name ASC, member.id ASC');

		$statPersonId = (int) ($participant->person_id ?? 0);
		$statisticCount = $db->getQuery(true)->select('COUNT(*)')->from($db->quoteName('#__joomleague_match_statistic_value', 'value'))
			->innerJoin($db->quoteName('#__joomleague_match_participant', 'match_participant') . ' ON match_participant.id = value.match_participant_id AND match_participant.published = 1')
			->innerJoin($db->quoteName('#__joomleague_project_match', 'item') . ' ON item.id = value.match_id AND item.published = 1')
			->innerJoin($db->quoteName('#__joomleague_match_result', 'result') . " ON result.match_id = item.id AND result.status_code = 'final'")
			->where('match_participant.project_entry_id = :statEntryId')->where('value.published = 1')->where('value.numeric_value IS NOT NULL')->where('value.segment_key = 0')
			->where("(value.target_kind = 'participant' OR (value.target_kind = 'person' AND :statEntryKind = 'person' AND value.person_id = :statPersonId))")
			->bind(':statEntryId', $entryId, ParameterType::INTEGER)->bind(':statEntryKind', $participant->entry_kind)
			->bind(':statPersonId', $statPersonId, ParameterType::INTEGER);

		return ['participant' => $participant, 'members' => $db->setQuery($memberQuery)->loadObjectList(), 'statistic_count' => (int) $db->setQuery($statisticCount)->loadResult()];
	}
}
