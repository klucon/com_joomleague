<?php

declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Administrator\Service;

defined('_JEXEC') or die;

use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;
use Joomleague\Component\Joomleague\Domain\Service\UuidFactory;

final class MatchActorRoleRepository
{
	public function __construct(
		private readonly DatabaseInterface $database,
		private readonly string $systemTimezone = 'UTC',
		private readonly EntryModelValidator $profileValidator = new EntryModelValidator()
	) {
	}

	/** @return array{project:object,profile:array<string,mixed>,roles:array<string,array<string,string>>} */
	public function getProjectContext(int $projectId): array
	{
		if ($projectId < 1) throw new \InvalidArgumentException('A positive project ID is required.');
		$boundId = $projectId;
		$query = $this->database->getQuery(true)
			->select(['project.id', 'project.name', 'project.timezone', 'version.payload_json'])
			->from($this->database->quoteName('#__joomleague_project', 'project'))
			->innerJoin($this->database->quoteName('#__joomleague_sport_profile_version', 'version') . ' ON version.id = project.profile_version_id')
			->where('project.id = :projectId')->bind(':projectId', $boundId, ParameterType::INTEGER);
		$project = $this->database->setQuery($query)->loadObject();
		if (!$project) throw new \RuntimeException('The project does not exist.');
		$profile = json_decode((string) $project->payload_json, true, 512, JSON_THROW_ON_ERROR);
		$this->profileValidator->validate($profile);
		unset($project->payload_json);
		return ['project' => $project, 'profile' => $profile, 'roles' => $this->officialRoles($profile)];
	}

	/** @return array{match:object,project:object,profile:array<string,mixed>,roles:array<string,array<string,string>>} */
	public function getMatchContext(int $matchId): array
	{
		if ($matchId < 1) throw new \InvalidArgumentException('A positive match ID is required.');
		$boundId = $matchId;
		$query = $this->database->getQuery(true)
			->select(['match.id', 'match.project_id', 'match.round_id', 'match.match_number', 'match.scheduled_start', 'match.timezone', 'project.name AS project_name', 'project.timezone AS project_timezone', 'version.payload_json'])
			->from($this->database->quoteName('#__joomleague_project_match', 'match'))
			->innerJoin($this->database->quoteName('#__joomleague_project', 'project') . ' ON project.id = match.project_id')
			->innerJoin($this->database->quoteName('#__joomleague_sport_profile_version', 'version') . ' ON version.id = project.profile_version_id')
			->where('match.id = :matchId')->bind(':matchId', $boundId, ParameterType::INTEGER);
		$match = $this->database->setQuery($query)->loadObject();
		if (!$match) throw new \RuntimeException('The match does not exist.');
		$profile = json_decode((string) $match->payload_json, true, 512, JSON_THROW_ON_ERROR);
		$this->profileValidator->validate($profile);
		unset($match->payload_json);
		$project = (object) ['id' => (int) $match->project_id, 'name' => (string) $match->project_name, 'timezone' => (string) $match->project_timezone];
		return ['match' => $match, 'project' => $project, 'profile' => $profile, 'roles' => $this->officialRoles($profile)];
	}

	/** @return array{persons:list<object>,teams:list<object>} */
	public function getActors(): array
	{
		$persons = $this->database->setQuery($this->database->getQuery(true)
			->select(['id', 'first_name', 'last_name', 'nickname'])->from($this->database->quoteName('#__joomleague_person'))
			->where('published = 1')->order('last_name ASC, first_name ASC, id ASC'))->loadObjectList();
		$teams = $this->database->setQuery($this->database->getQuery(true)
			->select(['id', 'name'])->from($this->database->quoteName('#__joomleague_team'))
			->where('published = 1')->order('name ASC, id ASC'))->loadObjectList();
		return compact('persons', 'teams');
	}

	/** @return list<object> */
	public function getProjectAssignments(int $projectId): array
	{
		$this->getProjectContext($projectId);
		$boundId = $projectId;
		$query = $this->database->getQuery(true)
			->select(['assignment.*', 'person.first_name', 'person.last_name', 'person.nickname', 'team.name AS team_name'])
			->from($this->database->quoteName('#__joomleague_project_actor_role', 'assignment'))
			->leftJoin($this->database->quoteName('#__joomleague_person', 'person') . ' ON person.id = assignment.person_id')
			->leftJoin($this->database->quoteName('#__joomleague_team', 'team') . ' ON team.id = assignment.team_id')
			->where('assignment.project_id = :projectId')->bind(':projectId', $boundId, ParameterType::INTEGER)
			->order('assignment.ordering ASC, assignment.id ASC');
		return $this->database->setQuery($query)->loadObjectList();
	}

	public function addProjectAssignment(int $projectId, string $actorReference, string $roleCode, ?string $validFrom, ?string $validUntil, ?string $notes, int $actorId): int
	{
		$context = $this->getProjectContext($projectId);
		$role = $this->role($context['roles'], $roleCode);
		[$actorKind, $targetId, $displayName] = $this->actor($actorReference);
		[$validFrom, $validUntil] = $this->dateRange($validFrom, $validUntil);
		foreach ($this->getProjectAssignments($projectId) as $existing) {
			$existingTarget = $actorKind === 'person' ? $existing->person_id : $existing->team_id;
			if ($existing->actor_kind === $actorKind && (int) $existingTarget === $targetId && $existing->role_code === $roleCode
				&& $this->rangesOverlap($validFrom, $validUntil, $existing->valid_from, $existing->valid_until)) {
				throw new \InvalidArgumentException('This actor already has an overlapping project role.');
			}
		}
		$record = (object) [
			'uuid' => UuidFactory::v4(), 'project_id' => $projectId, 'actor_kind' => $actorKind,
			'person_id' => $actorKind === 'person' ? $targetId : null, 'team_id' => $actorKind === 'team' ? $targetId : null,
			'role_code' => $roleCode, 'person_type' => $role['person_type'], 'valid_from' => $validFrom, 'valid_until' => $validUntil,
			'notes' => $this->notes($notes), 'created' => gmdate('Y-m-d H:i:s'), 'created_by' => $actorId,
		];
		$this->database->insertObject('#__joomleague_project_actor_role', $record, 'id');
		return (int) $record->id;
	}

	public function removeProjectAssignment(int $projectId, int $assignmentId): void
	{
		$this->getProjectContext($projectId);
		$boundId = $assignmentId; $boundProjectId = $projectId;
		$query = $this->database->getQuery(true)->delete($this->database->quoteName('#__joomleague_project_actor_role'))
			->where('id = :id')->where('project_id = :projectId')
			->bind(':id', $boundId, ParameterType::INTEGER)->bind(':projectId', $boundProjectId, ParameterType::INTEGER);
		$this->database->setQuery($query)->execute();
		if ($this->database->getAffectedRows() !== 1) throw new \InvalidArgumentException('The project role assignment does not belong to this project.');
	}

	/** @return list<object> */
	public function getAvailableForMatch(int $matchId): array
	{
		$context = $this->getMatchContext($matchId);
		$matchDate = $this->matchDate($context['match']);
		$assigned = array_fill_keys(array_map(static fn (object $row): int => (int) $row->source_project_actor_role_id, $this->getMatchAssignments($matchId)), true);
		return array_values(array_filter($this->getProjectAssignments((int) $context['project']->id), static function (object $row) use ($matchDate, $assigned): bool {
			return (int) $row->published === 1 && $row->lifecycle_state === 'active' && !isset($assigned[(int) $row->id])
				&& ($matchDate === null || ((!$row->valid_from || $row->valid_from <= $matchDate) && (!$row->valid_until || $row->valid_until >= $matchDate)));
		}));
	}

	/** @return list<object> */
	public function getMatchAssignments(int $matchId): array
	{
		$this->getMatchContext($matchId);
		$boundId = $matchId;
		$query = $this->database->getQuery(true)->select('assignment.*')
			->from($this->database->quoteName('#__joomleague_match_actor_role', 'assignment'))
			->where('assignment.match_id = :matchId')->bind(':matchId', $boundId, ParameterType::INTEGER)
			->order('assignment.ordering ASC, assignment.id ASC');
		return $this->database->setQuery($query)->loadObjectList();
	}

	public function assignToMatch(int $matchId, int $projectAssignmentId, ?string $notes, int $actorId): int
	{
		$context = $this->getMatchContext($matchId);
		$source = null;
		foreach ($this->getProjectAssignments((int) $context['project']->id) as $candidate) if ((int) $candidate->id === $projectAssignmentId) $source = $candidate;
		if (!$source || (int) $source->published !== 1 || $source->lifecycle_state !== 'active') throw new \InvalidArgumentException('The project role is not available for this match.');
		$this->role($context['roles'], (string) $source->role_code, (string) $source->person_type);
		$matchDate = $this->matchDate($context['match']);
		if ($matchDate !== null && (($source->valid_from && $source->valid_from > $matchDate) || ($source->valid_until && $source->valid_until < $matchDate))) {
			throw new \InvalidArgumentException('The project role is not valid on the match date.');
		}
		$displayName = $source->actor_kind === 'person' ? trim((string) $source->first_name . ' ' . (string) $source->last_name) : (string) $source->team_name;
		if ($displayName === '') throw new \InvalidArgumentException('The assigned actor does not have a display name.');
		$record = (object) [
			'uuid' => UuidFactory::v4(), 'match_id' => $matchId, 'project_id' => (int) $context['project']->id,
			'source_project_actor_role_id' => (int) $source->id, 'actor_kind' => (string) $source->actor_kind,
			'person_id' => $source->person_id ? (int) $source->person_id : null, 'team_id' => $source->team_id ? (int) $source->team_id : null,
			'role_code' => (string) $source->role_code, 'person_type' => (string) $source->person_type,
			'display_name_snapshot' => $displayName, 'notes' => $this->notes($notes),
			'created' => gmdate('Y-m-d H:i:s'), 'created_by' => $actorId,
		];
		$this->database->insertObject('#__joomleague_match_actor_role', $record, 'id');
		return (int) $record->id;
	}

	public function removeFromMatch(int $matchId, int $assignmentId): void
	{
		$this->getMatchContext($matchId);
		$boundId = $assignmentId; $boundMatchId = $matchId;
		$query = $this->database->getQuery(true)->delete($this->database->quoteName('#__joomleague_match_actor_role'))
			->where('id = :id')->where('match_id = :matchId')
			->bind(':id', $boundId, ParameterType::INTEGER)->bind(':matchId', $boundMatchId, ParameterType::INTEGER);
		$this->database->setQuery($query)->execute();
		if ($this->database->getAffectedRows() !== 1) throw new \InvalidArgumentException('The match role assignment does not belong to this match.');
	}

	/** @param array<string,mixed> $profile @return array<string,array<string,string>> */
	private function officialRoles(array $profile): array
	{
		$roles = [];
		foreach ($profile['positions'] ?? [] as $position) {
			if (($position['person_type'] ?? null) !== 'official' || !is_string($position['code'] ?? null) || !is_string($position['name_key'] ?? null)) continue;
			$roles[$position['code']] = ['name_key' => $position['name_key'], 'person_type' => $position['person_type']];
		}
		return $roles;
	}

	/** @param array<string,array<string,string>> $roles @return array<string,string> */
	private function role(array $roles, string $roleCode, ?string $personType = null): array
	{
		$roleCode = trim($roleCode);
		if (!isset($roles[$roleCode]) || ($personType !== null && $roles[$roleCode]['person_type'] !== $personType)) throw new \InvalidArgumentException('The role is not an official position in the active profile.');
		return $roles[$roleCode];
	}

	/** @return array{0:string,1:int,2:string} */
	private function actor(string $reference): array
	{
		if (preg_match('/^(person|team):(\d+)$/', $reference, $matches) !== 1 || (int) $matches[2] < 1) throw new \InvalidArgumentException('The actor reference is invalid.');
		$kind = $matches[1]; $id = (int) $matches[2]; $boundId = $id;
		if ($kind === 'person') {
			$query = $this->database->getQuery(true)->select(['first_name', 'last_name'])->from($this->database->quoteName('#__joomleague_person'))->where('id = :id')->where('published = 1')->bind(':id', $boundId, ParameterType::INTEGER);
			$row = $this->database->setQuery($query)->loadObject(); $name = $row ? trim((string) $row->first_name . ' ' . (string) $row->last_name) : '';
		} else {
			$query = $this->database->getQuery(true)->select('name')->from($this->database->quoteName('#__joomleague_team'))->where('id = :id')->where('published = 1')->bind(':id', $boundId, ParameterType::INTEGER);
			$name = (string) $this->database->setQuery($query)->loadResult();
		}
		if ($name === '') throw new \InvalidArgumentException('The actor does not exist or is not published.');
		return [$kind, $id, $name];
	}

	/** @return array{0:?string,1:?string} */
	private function dateRange(?string $from, ?string $until): array
	{
		$from = $this->date($from); $until = $this->date($until);
		if ($from !== null && $until !== null && $until < $from) throw new \InvalidArgumentException('The validity end must not precede the start.');
		return [$from, $until];
	}

	private function date(?string $value): ?string
	{
		$value = trim((string) $value); if ($value === '') return null;
		$date = \DateTimeImmutable::createFromFormat('!Y-m-d', $value);
		if (!$date || $date->format('Y-m-d') !== $value) throw new \InvalidArgumentException('The validity date is invalid.');
		return $value;
	}

	private function rangesOverlap(?string $leftFrom, ?string $leftUntil, ?string $rightFrom, ?string $rightUntil): bool
	{
		return ($leftUntil === null || $rightFrom === null || $rightFrom <= $leftUntil)
			&& ($rightUntil === null || $leftFrom === null || $leftFrom <= $rightUntil);
	}

	private function matchDate(object $match): ?string
	{
		if (!$match->scheduled_start) return null;
		$timezone = (string) ($match->timezone ?: ($match->project_timezone ?: $this->systemTimezone));
		return (new \DateTimeImmutable((string) $match->scheduled_start, new \DateTimeZone('UTC')))->setTimezone(new \DateTimeZone($timezone))->format('Y-m-d');
	}

	private function notes(?string $notes): ?string
	{
		$notes = trim((string) $notes); if ($notes === '') return null;
		if (strlen($notes) > 65535) throw new \LengthException('The notes are too long.');
		return $notes;
	}
}
