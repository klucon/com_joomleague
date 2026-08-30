<?php

declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Domain\Service;

defined('_JEXEC') or die;

use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;

/** Read-only sport-neutral statistics summary for one project entry. */
final class ParticipantStatisticReader
{
	public function __construct(private readonly DatabaseInterface $database) {}

	/** @param list<int> $viewLevels @return array{entry:object,rows:list<object>} */
	public function forEntry(int $projectId, int $entryId, array $viewLevels): array
	{
		if ($projectId < 1 || $entryId < 1) throw new \InvalidArgumentException('Positive project and entry IDs are required.');
		$db = $this->database; $levels = array_values(array_unique(array_filter(array_map('intval', $viewLevels), static fn (int $id): bool => $id > 0))) ?: [1]; $access = implode(',', $levels);
		$entry = $db->setQuery($db->getQuery(true)
			->select(['entry.id', 'entry.entry_kind', 'entry.person_id', 'project.id AS project_id', 'project.name AS project_name', "COALESCE(NULLIF(entry.display_name, ''), team.name, NULLIF(TRIM(CONCAT(person.first_name, ' ', person.last_name)), ''), CONCAT('ID ', entry.id)) AS display_name"])
			->from($db->quoteName('#__joomleague_project_entry', 'entry'))
			->innerJoin($db->quoteName('#__joomleague_project', 'project') . ' ON project.id=entry.project_id AND project.published=1 AND project.access IN (' . $access . ')')
			->innerJoin($db->quoteName('#__joomleague_competition', 'competition') . ' ON competition.id=project.competition_id AND competition.published=1 AND competition.access IN (' . $access . ')')
			->innerJoin($db->quoteName('#__joomleague_season', 'season') . ' ON season.id=project.season_id AND season.published=1 AND season.access IN (' . $access . ')')
			->leftJoin($db->quoteName('#__joomleague_team', 'team') . ' ON team.id=entry.team_id AND team.published=1 AND team.access IN (' . $access . ')')
			->leftJoin($db->quoteName('#__joomleague_person', 'person') . ' ON person.id=entry.person_id AND person.published=1 AND person.access IN (' . $access . ')')
			->where('entry.id=:entryId')->where('entry.project_id=:projectId')->where('entry.published=1')
			->where("(entry.entry_kind='group' OR (entry.entry_kind='team' AND team.id IS NOT NULL) OR (entry.entry_kind='person' AND person.id IS NOT NULL))")
			->bind(':entryId', $entryId, ParameterType::INTEGER)->bind(':projectId', $projectId, ParameterType::INTEGER))->loadObject();
		if (!$entry) throw new \RuntimeException('Participant statistics are unavailable.');
		$personId = (int) ($entry->person_id ?? 0);
		$query = $db->getQuery(true)->select(['value.statistic_code', 'MAX(value.statistic_name_key) AS name_key', 'MAX(value.abbreviation_key) AS abbreviation_key', 'MAX(value.value_type) AS value_type', 'MAX(value.scope_code) AS scope_code', 'COUNT(DISTINCT value.match_id) AS appearances', 'SUM(value.numeric_value) AS total_value', 'AVG(value.numeric_value) AS average_value', 'MIN(value.numeric_value) AS minimum_value', 'MAX(value.numeric_value) AS maximum_value'])
			->from($db->quoteName('#__joomleague_match_statistic_value', 'value'))->innerJoin($db->quoteName('#__joomleague_project_match', 'item') . ' ON item.id=value.match_id AND item.published=1')
			->innerJoin($db->quoteName('#__joomleague_match_result', 'result') . " ON result.match_id=item.id AND result.status_code='final'")
			->innerJoin($db->quoteName('#__joomleague_match_participant', 'participant') . ' ON participant.id=value.match_participant_id AND participant.published=1')
			->where('value.project_id=:projectId')->where('participant.project_entry_id=:entryId')->where('value.published=1')->where('value.numeric_value IS NOT NULL')->where('value.segment_key=0')
			->where("(value.target_kind='participant' OR (value.target_kind='person' AND :entryKind='person' AND value.person_id=:personId))")
			->group('value.statistic_code')->order('value.statistic_code ASC')->bind(':projectId', $projectId, ParameterType::INTEGER)->bind(':entryId', $entryId, ParameterType::INTEGER)->bind(':entryKind', $entry->entry_kind)->bind(':personId', $personId, ParameterType::INTEGER);
		return ['entry' => $entry, 'rows' => $db->setQuery($query)->loadObjectList()];
	}
}
