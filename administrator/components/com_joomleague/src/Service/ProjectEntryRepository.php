<?php

declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Administrator\Service;

defined('_JEXEC') or die;

use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;
use Joomleague\Component\Joomleague\Domain\Service\UuidFactory;

final class ProjectEntryRepository
{
	public function __construct(
		private readonly DatabaseInterface $database,
		private readonly EntryModelValidator $validator = new EntryModelValidator()
	) {
	}

	/** @return list<object> */
	public function getEntries(int $projectId, string $search = '', string $lifecycleState = ''): array
	{
		$this->loadProjectProfile($projectId);
		$memberCount = $this->database->getQuery(true)
			->select('COUNT(*)')
			->from($this->database->quoteName('#__joomleague_project_entry_member', 'member'))
			->where('member.entry_id = entry.id')
			->where('member.published = 1');
		$query = $this->database->getQuery(true)
			->select('entry.*')
			->select([
				$this->database->quoteName('team.name', 'team_name'),
				$this->database->quoteName('person.first_name', 'person_first_name'),
				$this->database->quoteName('person.last_name', 'person_last_name'),
			])
			->select('(' . $memberCount . ') AS member_count')
			->from($this->database->quoteName('#__joomleague_project_entry', 'entry'))
			->leftJoin($this->database->quoteName('#__joomleague_team', 'team') . ' ON team.id = entry.team_id')
			->leftJoin($this->database->quoteName('#__joomleague_person', 'person') . ' ON person.id = entry.person_id')
			->where('entry.project_id = :projectId')
			->order('entry.ordering ASC, entry.id ASC')
			->bind(':projectId', $projectId, ParameterType::INTEGER);

		$lifecycleState = trim($lifecycleState);
		if ($lifecycleState !== '') {
			$query->where('entry.lifecycle_state = :lifecycleState')->bind(':lifecycleState', $lifecycleState);
		}

		$search = trim($search);
		if ($search !== '') {
			$like = '%' . $search . '%';
			$query->where('(team.name LIKE :searchTeam OR person.first_name LIKE :searchFirst OR person.last_name LIKE :searchLast OR entry.display_name LIKE :searchDisplay OR entry.entry_code LIKE :searchCode)')
				->bind(':searchTeam', $like)
				->bind(':searchFirst', $like)
				->bind(':searchLast', $like)
				->bind(':searchDisplay', $like)
				->bind(':searchCode', $like);
		}

		$entries = $this->database->setQuery($query)->loadObjectList();

		foreach ($entries as $entry) {
			$personName = trim((string) $entry->person_first_name . ' ' . (string) $entry->person_last_name);
			$entry->resolved_name = match ((string) $entry->entry_kind) {
				'team' => (string) $entry->team_name,
				'person' => $personName,
				default => (string) $entry->display_name,
			};
		}

		return $entries;
	}

	/** @param array<string, mixed> $data */
	public function createEntry(int $projectId, array $data, int $actorId): int
	{
		$this->assertActor($actorId);
		$profile = $this->loadProjectProfile($projectId);
		$model = $profile['entry_model'];
		$kind = trim((string) ($data['entry_kind'] ?? $model['default_kind']));

		if (!in_array($kind, $model['allowed_kinds'], true)) {
			throw new \InvalidArgumentException(sprintf('Entry kind %s is not allowed by the project sport profile.', $kind));
		}

		$teamId = $this->positiveOrNull($data['team_id'] ?? null);
		$personId = $this->positiveOrNull($data['person_id'] ?? null);
		$displayName = trim((string) ($data['display_name'] ?? ''));

		if (
			($kind === 'team' && ($teamId === null || $personId !== null))
			|| ($kind === 'person' && ($personId === null || $teamId !== null))
			|| ($kind === 'group' && ($teamId !== null || $personId !== null || $displayName === ''))
		) {
			throw new \InvalidArgumentException('Entry target does not match its entry kind.');
		}

		$uuid = UuidFactory::v4();
		$entryCode = $this->nullableText($data['entry_code'] ?? null, 100);
		$bibNumber = $this->nullableText($data['bib_number'] ?? null, 50);
		$seedNumber = $this->positiveOrNull($data['seed_number'] ?? null);
		$included = !array_key_exists('included_in_standings', $data) || (bool) $data['included_in_standings'] ? 1 : 0;
		$query = $this->database->getQuery(true)
			->insert($this->database->quoteName('#__joomleague_project_entry'))
			->columns($this->database->quoteName([
				'uuid', 'project_id', 'entry_kind', 'team_id', 'person_id', 'display_name', 'entry_code',
				'seed_number', 'bib_number', 'included_in_standings', 'created_by',
			]))
			->values(':uuid, :projectId, :entryKind, :teamId, :personId, :displayName, :entryCode, :seedNumber, :bibNumber, :included, :actorId')
			->bind(':uuid', $uuid)
			->bind(':projectId', $projectId, ParameterType::INTEGER)
			->bind(':entryKind', $kind)
			->bind(':teamId', $teamId, ParameterType::INTEGER)
			->bind(':personId', $personId, ParameterType::INTEGER)
			->bind(':displayName', $displayName)
			->bind(':entryCode', $entryCode)
			->bind(':seedNumber', $seedNumber, ParameterType::INTEGER)
			->bind(':bibNumber', $bibNumber)
			->bind(':included', $included, ParameterType::INTEGER)
			->bind(':actorId', $actorId, ParameterType::INTEGER);
		$this->database->setQuery($query)->execute();

		return (int) $this->database->insertid();
	}

	/** @param array<string, mixed> $data */
	public function addMember(int $entryId, int $personId, array $data, int $actorId): int
	{
		if ($entryId < 1 || $personId < 1) {
			throw new \InvalidArgumentException('Positive entry and person IDs are required.');
		}

		$this->assertActor($actorId);
		$profile = $this->loadEntryProfile($entryId);
		$model = $profile['entry_model'];

		if ($model['members_supported'] !== true) {
			throw new \InvalidArgumentException('The project sport profile does not support entry members.');
		}

		$personType = trim((string) ($data['member_person_type'] ?? ''));

		if (!in_array($personType, $model['member_person_types'], true)) {
			throw new \InvalidArgumentException(sprintf('Member person type %s is not allowed by the project sport profile.', $personType));
		}

		$roleCode = $this->nullableText($data['role_code'] ?? null, 100);

		if ($roleCode !== null && !$this->profileHasRole($profile, $roleCode, $personType)) {
			throw new \InvalidArgumentException(sprintf('Role %s is not valid for person type %s.', $roleCode, $personType));
		}

		$validFrom = $this->dateOrNull($data['valid_from'] ?? null);
		$validUntil = $this->dateOrNull($data['valid_until'] ?? null);

		if ($validFrom !== null && $validUntil !== null && $validUntil < $validFrom) {
			throw new \InvalidArgumentException('Member validity end cannot be earlier than its start.');
		}

		$uuid = UuidFactory::v4();
		$shirtNumber = $this->nullableText($data['shirt_number'] ?? null, 20);
		$isCaptain = !empty($data['is_captain']) ? 1 : 0;
		$query = $this->database->getQuery(true)
			->insert($this->database->quoteName('#__joomleague_project_entry_member'))
			->columns($this->database->quoteName([
				'uuid', 'entry_id', 'person_id', 'member_person_type', 'role_code', 'shirt_number',
				'is_captain', 'valid_from', 'valid_until', 'created_by',
			]))
			->values(':uuid, :entryId, :personId, :personType, :roleCode, :shirtNumber, :isCaptain, :validFrom, :validUntil, :actorId')
			->bind(':uuid', $uuid)
			->bind(':entryId', $entryId, ParameterType::INTEGER)
			->bind(':personId', $personId, ParameterType::INTEGER)
			->bind(':personType', $personType)
			->bind(':roleCode', $roleCode)
			->bind(':shirtNumber', $shirtNumber)
			->bind(':isCaptain', $isCaptain, ParameterType::INTEGER)
			->bind(':validFrom', $validFrom)
			->bind(':validUntil', $validUntil)
			->bind(':actorId', $actorId, ParameterType::INTEGER);
		$this->database->setQuery($query)->execute();

		return (int) $this->database->insertid();
	}

	/** @return array<string, mixed> */
	private function loadProjectProfile(int $projectId): array
	{
		if ($projectId < 1) {
			throw new \InvalidArgumentException('A positive project ID is required.');
		}

		$query = $this->database->getQuery(true)
			->select($this->database->quoteName('version.payload_json'))
			->from($this->database->quoteName('#__joomleague_project', 'project'))
			->innerJoin($this->database->quoteName('#__joomleague_sport_profile_version', 'version') . ' ON version.id = project.profile_version_id')
			->where('project.id = :projectId')
			->bind(':projectId', $projectId, ParameterType::INTEGER);
		$payload = $this->database->setQuery($query)->loadResult();

		if ($payload === null) {
			throw new \RuntimeException(sprintf('Project %d does not exist.', $projectId));
		}

		$profile = json_decode((string) $payload, true, 512, JSON_THROW_ON_ERROR);
		$this->validator->validate($profile);

		return $profile;
	}

	/** @return array<string, mixed> */
	private function loadEntryProfile(int $entryId): array
	{
		$query = $this->database->getQuery(true)
			->select($this->database->quoteName('entry.project_id'))
			->from($this->database->quoteName('#__joomleague_project_entry', 'entry'))
			->where('entry.id = :entryId')
			->bind(':entryId', $entryId, ParameterType::INTEGER);
		$projectId = $this->database->setQuery($query)->loadResult();

		if ($projectId === null) {
			throw new \RuntimeException(sprintf('Project entry %d does not exist.', $entryId));
		}

		return $this->loadProjectProfile((int) $projectId);
	}

	/** @param array<string, mixed> $profile */
	private function profileHasRole(array $profile, string $roleCode, string $personType): bool
	{
		foreach ($profile['positions'] as $position) {
			if (($position['code'] ?? null) === $roleCode && ($position['person_type'] ?? null) === $personType) {
				return true;
			}
		}

		return false;
	}

	private function assertActor(int $actorId): void
	{
		if ($actorId < 0) {
			throw new \InvalidArgumentException('Actor ID cannot be negative.');
		}
	}

	private function positiveOrNull(mixed $value): ?int
	{
		if ($value === null || $value === '') {
			return null;
		}

		$value = filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

		if ($value === false) {
			throw new \InvalidArgumentException('Expected a positive integer.');
		}

		return $value;
	}

	private function nullableText(mixed $value, int $maximumLength): ?string
	{
		$value = trim((string) ($value ?? ''));

		if ($value === '') {
			return null;
		}

		if (mb_strlen($value) > $maximumLength) {
			throw new \LengthException(sprintf('Text exceeds %d characters.', $maximumLength));
		}

		return $value;
	}

	private function dateOrNull(mixed $value): ?string
	{
		$value = trim((string) ($value ?? ''));

		if ($value === '') {
			return null;
		}

		$date = \DateTimeImmutable::createFromFormat('!Y-m-d', $value);

		if ($date === false || $date->format('Y-m-d') !== $value) {
			throw new \InvalidArgumentException('Dates must use the YYYY-MM-DD format.');
		}

		return $value;
	}
}
