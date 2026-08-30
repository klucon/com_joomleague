<?php

declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Site\Model;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;
use Joomleague\Component\Joomleague\Site\Service\PublicAccess;

final class PersonnelModel extends BaseDatabaseModel
{
	protected function populateState($ordering = null, $direction = null): void
	{
		$this->setState('project_id', Factory::getApplication()->getInput()->getInt('project_id', 0));
	}

	/** @return array<string,mixed> */
	public function getPersonnel(): array
	{
		$projectId = (int) $this->getState('project_id');

		if ($projectId < 1) {
			return ['error' => 'COM_JOOMLEAGUE_PERSONNEL_NO_PROJECT'];
		}

		$db = Factory::getContainer()->get(DatabaseInterface::class);
		$project = $this->getProject($db, $projectId);

		if (!$project) {
			return ['error' => 'COM_JOOMLEAGUE_PERSONNEL_UNAVAILABLE'];
		}

		$roleLabels = [];
		$profile = json_decode((string) $project->profile_json, true);

		foreach (is_array($profile) ? ($profile['positions'] ?? []) : [] as $position) {
			if (is_array($position) && is_string($position['code'] ?? null) && is_string($position['name_key'] ?? null)) {
				$roleLabels[$position['code']] = $position['name_key'];
			}
		}

		unset($project->profile_json);
		$groups = ['staff' => [], 'official' => []];

		foreach ($this->getEntryStaff($db, $projectId) as $row) {
			$this->append($groups['staff'], $row, $roleLabels, 'staff');
		}

		foreach ($this->getOfficials($db, $projectId) as $row) {
			$this->append($groups['official'], $row, $roleLabels, 'official');
		}

		return ['project' => $project, 'groups' => $groups];
	}

	private function getProject(DatabaseInterface $db, int $projectId): ?object
	{
		$query = $db->getQuery(true)
			->select(['project.id', 'project.name', 'sport_type.name AS sport_type_name', 'version.payload_json AS profile_json'])
			->from($db->quoteName('#__joomleague_project', 'project'))
			->innerJoin($db->quoteName('#__joomleague_competition', 'competition') . ' ON competition.id = project.competition_id AND competition.published = 1')
			->innerJoin($db->quoteName('#__joomleague_season', 'season') . ' ON season.id = project.season_id AND season.published = 1')
			->innerJoin($db->quoteName('#__joomleague_sport_type', 'sport_type') . ' ON sport_type.id = project.sport_type_id AND sport_type.published = 1')
			->innerJoin($db->quoteName('#__joomleague_sport_profile_version', 'version') . ' ON version.id = project.profile_version_id')
			->where('project.id = :projectId')->where('project.published = 1')
			->where(PublicAccess::condition($db, 'project'))->where(PublicAccess::condition($db, 'competition'))->where(PublicAccess::condition($db, 'season'))
			->bind(':projectId', $projectId, ParameterType::INTEGER);

		return $db->setQuery($query)->loadObject() ?: null;
	}

	/** @return list<object> */
	private function getEntryStaff(DatabaseInterface $db, int $projectId): array
	{
		$type = 'staff';
		$query = $db->getQuery(true)
			->select(['member.person_id', 'member.role_code', 'person.first_name', 'person.last_name', 'person.nickname', 'person.picture', 'entry.id AS entry_id'])
			->from($db->quoteName('#__joomleague_project_entry_member', 'member'))
			->innerJoin($db->quoteName('#__joomleague_project_entry', 'entry') . ' ON entry.id = member.entry_id AND entry.published = 1')
			->innerJoin($db->quoteName('#__joomleague_person', 'person') . ' ON person.id = member.person_id AND person.published = 1 AND ' . PublicAccess::condition($db, 'person'))
			->where('entry.project_id = :projectId')->where('member.published = 1')->where('member.member_person_type = :personType')
			->bind(':projectId', $projectId, ParameterType::INTEGER)->bind(':personType', $type)
			->order('member.ordering ASC, person.last_name ASC, person.first_name ASC, member.id ASC');

		return $db->setQuery($query)->loadObjectList();
	}

	/** @return list<object> */
	private function getOfficials(DatabaseInterface $db, int $projectId): array
	{
		$kind = 'person';
		$assignmentCount = $db->getQuery(true)->select('COUNT(*)')->from($db->quoteName('#__joomleague_match_actor_role', 'match_role'))
			->innerJoin($db->quoteName('#__joomleague_project_match', 'item') . ' ON item.id = match_role.match_id AND item.published = 1')
			->where('match_role.project_id = role.project_id')->where('match_role.person_id = role.person_id')->where('match_role.role_code = role.role_code')->where('match_role.published = 1');
		$query = $db->getQuery(true)
			->select(['role.person_id', 'role.role_code', 'person.first_name', 'person.last_name', 'person.nickname', 'person.picture', '(' . $assignmentCount . ') AS assignment_count'])
			->from($db->quoteName('#__joomleague_project_actor_role', 'role'))
			->innerJoin($db->quoteName('#__joomleague_person', 'person') . ' ON person.id = role.person_id AND person.published = 1 AND ' . PublicAccess::condition($db, 'person'))
			->where('role.project_id = :projectId')->where('role.actor_kind = :actorKind')->where('role.published = 1')
			->bind(':projectId', $projectId, ParameterType::INTEGER)->bind(':actorKind', $kind)
			->order('role.ordering ASC, person.last_name ASC, person.first_name ASC, role.id ASC');

		return $db->setQuery($query)->loadObjectList();
	}

	/** @param array<string,object> $target @param array<string,string> $roleLabels */
	private function append(array &$target, object $row, array $roleLabels, string $personType): void
	{
		$key = (int) $row->person_id . ':' . (string) $row->role_code;

		if (!isset($target[$key])) {
			$target[$key] = (object) [
				'person_id' => (int) $row->person_id,
				'name' => trim((string) $row->first_name . ' ' . (string) $row->last_name),
				'nickname' => (string) $row->nickname,
				'picture' => (string) $row->picture,
				'role_code' => (string) $row->role_code,
				'role_label' => $roleLabels[(string) $row->role_code] ?? '',
				'person_type' => $personType,
				'entry_count' => 0,
				'assignment_count' => (int) ($row->assignment_count ?? 0),
			];
		}

		if (isset($row->entry_id)) {
			$target[$key]->entry_count++;
		}
	}
}
