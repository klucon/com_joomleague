<?php

declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Domain\Service;

defined('_JEXEC') or die;

use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;

/** Read-only sport-neutral statistic ranking shared by site views and modules. */
final class StatisticRankingReader
{
	public function __construct(private readonly DatabaseInterface $database) {}

	/** @param list<int> $viewLevels @return array<string,mixed> */
	public function forProject(int $projectId, ?string $statisticCode, int $limit, array $viewLevels): array
	{
		if ($projectId < 1 || ($statisticCode !== null && preg_match('/^[a-z][a-z0-9_]*$/', $statisticCode) !== 1)) {
			throw new \InvalidArgumentException('Statistic ranking context is invalid.');
		}
		$db = $this->database;
		$viewLevels = array_values(array_unique(array_filter(array_map('intval', $viewLevels), static fn (int $id): bool => $id > 0))) ?: [1];
		$access = implode(',', $viewLevels);
		$project = $db->setQuery($db->getQuery(true)
			->select(['project.id', 'project.name'])
			->from($db->quoteName('#__joomleague_project', 'project'))
			->innerJoin($db->quoteName('#__joomleague_competition', 'competition') . ' ON competition.id = project.competition_id AND competition.published = 1 AND competition.access IN (' . $access . ')')
			->innerJoin($db->quoteName('#__joomleague_season', 'season') . ' ON season.id = project.season_id AND season.published = 1 AND season.access IN (' . $access . ')')
			->where('project.id = :project')->where('project.published = 1')->where('project.access IN (' . $access . ')')
			->bind(':project', $projectId, ParameterType::INTEGER))->loadObject();
		if (!$project) throw new \RuntimeException('Statistic ranking project is unavailable.');

		$definitions = $db->setQuery($db->getQuery(true)
			->select(['value.statistic_code', 'MAX(value.statistic_name_key) AS name_key', 'MAX(value.abbreviation_key) AS abbreviation_key', 'MAX(value.value_type) AS value_type', 'MAX(value.scope_code) AS scope_code', 'COUNT(value.id) AS value_count'])
			->from($db->quoteName('#__joomleague_match_statistic_value', 'value'))
			->innerJoin($db->quoteName('#__joomleague_project_match', 'match') . ' ON match.id = value.match_id AND match.published = 1')
			->innerJoin($db->quoteName('#__joomleague_match_result', 'result') . " ON result.match_id = match.id AND result.status_code = 'final'")
			->where('value.project_id = :project')->where('value.published = 1')->where('value.numeric_value IS NOT NULL')->where('value.segment_key = 0')
			->group('value.statistic_code')->order('value.statistic_code ASC')
			->bind(':project', $projectId, ParameterType::INTEGER))->loadObjectList();
		if ($definitions === []) return compact('project', 'definitions') + ['selected_code' => null, 'rows' => []];
		$codes = array_map(static fn (object $row): string => (string) $row->statistic_code, $definitions);
		if ($statisticCode === null || !in_array($statisticCode, $codes, true)) $statisticCode = $codes[0];

		$code = $statisticCode;
		$query = $db->getQuery(true)
			->select([
				'value.target_kind', "CASE WHEN value.target_kind = 'person' THEN value.person_id ELSE participant.project_entry_id END AS target_id",
				'MAX(value.target_name_snapshot) AS display_name', 'SUM(value.numeric_value) AS total_value',
				'COUNT(DISTINCT value.match_id) AS appearances', 'MAX(value.value_type) AS value_type',
			])
			->from($db->quoteName('#__joomleague_match_statistic_value', 'value'))
			->innerJoin($db->quoteName('#__joomleague_project_match', 'match') . ' ON match.id = value.match_id AND match.published = 1')
			->innerJoin($db->quoteName('#__joomleague_match_result', 'result') . " ON result.match_id = match.id AND result.status_code = 'final'")
			->innerJoin($db->quoteName('#__joomleague_match_participant', 'participant') . ' ON participant.id = value.match_participant_id AND participant.published = 1')
			->innerJoin($db->quoteName('#__joomleague_project_entry', 'entry') . ' ON entry.id = participant.project_entry_id AND entry.published = 1')
			->leftJoin($db->quoteName('#__joomleague_team', 'team') . ' ON team.id = entry.team_id AND team.published = 1 AND team.access IN (' . $access . ')')
			->leftJoin($db->quoteName('#__joomleague_person', 'entry_person') . ' ON entry_person.id = entry.person_id AND entry_person.published = 1 AND entry_person.access IN (' . $access . ')')
			->leftJoin($db->quoteName('#__joomleague_person', 'target_person') . ' ON target_person.id = value.person_id AND target_person.published = 1 AND target_person.access IN (' . $access . ')')
			->where('value.project_id = :project')->where('value.statistic_code = :code')->where('value.published = 1')
			->where('value.numeric_value IS NOT NULL')->where('value.segment_key = 0')
			->where("((value.target_kind = 'person' AND target_person.id IS NOT NULL) OR (value.target_kind = 'participant' AND ((entry.entry_kind = 'team' AND team.id IS NOT NULL) OR (entry.entry_kind = 'person' AND entry_person.id IS NOT NULL) OR entry.entry_kind = 'group')))")
			->group(['value.target_kind', "CASE WHEN value.target_kind = 'person' THEN value.person_id ELSE participant.project_entry_id END"])
			->order(['total_value DESC', 'display_name ASC'])
			->bind(':project', $projectId, ParameterType::INTEGER)->bind(':code', $code);
		$rows = $db->setQuery($query, 0, min(500, max(1, $limit)))->loadObjectList();
		foreach ($rows as $index => $row) $row->rank = $index + 1;

		return compact('project', 'definitions', 'rows') + ['selected_code' => $statisticCode];
	}
}
