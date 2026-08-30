<?php

declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Domain\Service;

defined('_JEXEC') or die;

use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;

final class ProjectNavigationReader
{
	public function __construct(private readonly DatabaseInterface $db)
	{
	}

	/** @param list<int> $viewLevels
	 *  @return array<string,mixed>
	 */
	public function forProject(int $projectId, array $viewLevels): array
	{
		if ($projectId < 1) {
			return ['error' => 'project_required'];
		}

		$viewLevels = array_values(array_unique(array_filter(array_map('intval', $viewLevels), static fn (int $id): bool => $id > 0)));
		$viewLevels = $viewLevels === [] ? [1] : $viewLevels;
		$access = implode(',', $viewLevels);
		$db = $this->db;
		$project = $db->setQuery(
			$db->getQuery(true)
				->select([
					'project.id', 'project.name', 'project.description', 'project.picture', 'project.project_type',
					'project.start_date', 'project.end_date',
					$db->quoteName('competition.name', 'competition_name'),
					$db->quoteName('season.name', 'season_name'),
					$db->quoteName('sport_type.name', 'sport_type_name'),
					$db->quoteName('profile_version.payload_json', 'profile_json'),
				])
				->from($db->quoteName('#__joomleague_project', 'project'))
				->innerJoin($db->quoteName('#__joomleague_competition', 'competition') . ' ON competition.id = project.competition_id AND competition.published = 1')
				->innerJoin($db->quoteName('#__joomleague_season', 'season') . ' ON season.id = project.season_id AND season.published = 1')
				->innerJoin($db->quoteName('#__joomleague_sport_type', 'sport_type') . ' ON sport_type.id = project.sport_type_id AND sport_type.published = 1')
				->leftJoin($db->quoteName('#__joomleague_sport_profile_version', 'profile_version') . ' ON profile_version.id = project.profile_version_id')
				->where('project.id = :projectId')
				->where('project.published = 1')
				->where('project.access IN (' . $access . ')')
				->where('competition.access IN (' . $access . ')')
				->where('season.access IN (' . $access . ')')
				->bind(':projectId', $projectId, ParameterType::INTEGER)
		)->loadObject();

		if (!$project) {
			return ['error' => 'project_unavailable'];
		}

		$entryCountQuery = $db->getQuery(true)
			->select('COUNT(*)')
			->from($db->quoteName('#__joomleague_project_entry', 'entry'))
			->leftJoin($db->quoteName('#__joomleague_team', 'entry_team') . ' ON entry_team.id = entry.team_id AND entry_team.published = 1 AND entry_team.access IN (' . $access . ')')
			->leftJoin($db->quoteName('#__joomleague_person', 'entry_person') . ' ON entry_person.id = entry.person_id AND entry_person.published = 1 AND entry_person.access IN (' . $access . ')')
			->where('entry.project_id = :projectId')
			->where('entry.published = 1')
			->where("(entry.entry_kind = 'group' OR (entry.entry_kind = 'team' AND entry_team.id IS NOT NULL) OR (entry.entry_kind = 'person' AND entry_person.id IS NOT NULL))")
			->bind(':projectId', $projectId, ParameterType::INTEGER);
		$counts = ['entries' => (int) $db->setQuery($entryCountQuery)->loadResult()];

		foreach (['stages' => '#__joomleague_project_stage', 'rounds' => '#__joomleague_project_round', 'program' => '#__joomleague_project_match'] as $key => $table) {
			$query = $db->getQuery(true)->select('COUNT(*)')->from($db->quoteName($table))
				->where('project_id = :projectId')->where('published = 1')->bind(':projectId', $projectId, ParameterType::INTEGER);
			$counts[$key] = (int) $db->setQuery($query)->loadResult();
		}

		$counts['results'] = (int) $db->setQuery(
			$db->getQuery(true)->select('COUNT(*)')->from($db->quoteName('#__joomleague_match_result', 'result'))
				->innerJoin($db->quoteName('#__joomleague_project_match', 'item') . ' ON item.id = result.match_id AND item.published = 1')
				->where('item.project_id = :projectId')->where("result.status_code = 'final'")
				->bind(':projectId', $projectId, ParameterType::INTEGER)
		)->loadResult();
		$counts['statistics'] = (int) $db->setQuery(
			$db->getQuery(true)->select('COUNT(*)')->from($db->quoteName('#__joomleague_match_statistic_value'))
				->where('project_id = :projectId')->where('published = 1')->where('numeric_value IS NOT NULL')->where('segment_key = 0')
				->bind(':projectId', $projectId, ParameterType::INTEGER)
		)->loadResult();
		$counts['events'] = (int) $db->setQuery(
			$db->getQuery(true)->select('COUNT(*)')->from($db->quoteName('#__joomleague_match_event'))
				->where('project_id = :projectId')->where('published = 1')->bind(':projectId', $projectId, ParameterType::INTEGER)
		)->loadResult();

		$staffCount = $db->getQuery(true)->select('COUNT(*)')->from($db->quoteName('#__joomleague_project_entry_member', 'member'))
			->innerJoin($db->quoteName('#__joomleague_project_entry', 'entry') . ' ON entry.id = member.entry_id AND entry.published = 1')
			->innerJoin($db->quoteName('#__joomleague_person', 'person') . ' ON person.id = member.person_id AND person.published = 1 AND person.access IN (' . $access . ')')
			->where('entry.project_id = :projectId')->where('member.published = 1')->where("member.member_person_type = 'staff'")
			->bind(':projectId', $projectId, ParameterType::INTEGER);
		$officialCount = $db->getQuery(true)->select('COUNT(*)')->from($db->quoteName('#__joomleague_project_actor_role', 'role'))
			->innerJoin($db->quoteName('#__joomleague_person', 'person') . ' ON person.id = role.person_id AND person.published = 1 AND person.access IN (' . $access . ')')
			->where('role.project_id = :projectId')->where('role.published = 1')->where("role.actor_kind = 'person'")
			->bind(':projectId', $projectId, ParameterType::INTEGER);
		$counts['personnel'] = (int) $db->setQuery($staffCount)->loadResult() + (int) $db->setQuery($officialCount)->loadResult();

		$profile = json_decode((string) ($project->profile_json ?? ''), true);
		$capabilities = [
			'participants' => $counts['entries'] > 0,
			'personnel' => $counts['personnel'] > 0,
			'program' => $counts['program'] > 0,
			'standings' => is_array($profile) && isset($profile['standings']) && is_array($profile['standings']),
			'bracket_stage_id' => $this->findBracketStage($projectId),
			'statistics_overview' => $counts['statistics'] > 0 || $counts['events'] > 0,
			'result_matrix' => $counts['results'] > 0 && is_array($profile) && (string) ($profile['contest']['type'] ?? '') === 'head_to_head',
			'comparison' => $counts['entries'] > 1 && $counts['results'] > 0,
			'progression' => $counts['rounds'] > 1 && $counts['results'] > 0 && is_array($profile) && isset($profile['standings']),
		];
		unset($project->profile_json);

		return ['project' => $project, 'counts' => $counts, 'capabilities' => $capabilities];
	}

	private function findBracketStage(int $projectId): int
	{
		$stageType = 'knockout';
		$query = $this->db->getQuery(true)->select('stage.id')->from($this->db->quoteName('#__joomleague_project_stage', 'stage'))
			->innerJoin($this->db->quoteName('#__joomleague_project_round', 'round') . ' ON round.stage_id = stage.id AND round.published = 1')
			->where('stage.project_id = :projectId')->where('stage.published = 1')->where('stage.stage_type = :stageType')
			->group('stage.id')->having('COUNT(round.id) >= 2')->order('stage.sequence_number ASC, stage.id ASC')
			->bind(':projectId', $projectId, ParameterType::INTEGER)->bind(':stageType', $stageType);

		return (int) $this->db->setQuery($query, 0, 1)->loadResult();
	}
}
