<?php

declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Administrator\Service;

defined('_JEXEC') or die;

use Joomla\Database\DatabaseInterface;
use Joomleague\Component\Joomleague\Domain\Service\CanonicalJson;

final class ProjectRuleConfigRepository
{
	private const MAX_PAYLOAD_BYTES = 65535;

	public function __construct(
		private readonly DatabaseInterface $database,
		private readonly ProjectRuleValidator $validator = new ProjectRuleValidator()
	) {
	}

	/**
	 * Missing configuration means full profile inheritance and returns an empty object.
	 *
	 * @return array<string, mixed>
	 */
	public function get(int $projectId): array
	{
		$this->assertProjectId($projectId);
		$query = $this->database->getQuery(true)
			->select($this->database->quoteName('version.payload_json'))
			->select([
				$this->database->quoteName('config.schema_version'),
				$this->database->quoteName('config.overrides_json'),
				$this->database->quoteName('config.overrides_checksum'),
			])
			->from($this->database->quoteName('#__joomleague_project', 'project'))
			->innerJoin($this->database->quoteName('#__joomleague_sport_profile_version', 'version') . ' ON version.id = project.profile_version_id')
			->leftJoin($this->database->quoteName('#__joomleague_project_rule_config', 'config') . ' ON config.project_id = project.id')
			->where($this->database->quoteName('project.id') . ' = :project_id')
			->bind(':project_id', $projectId);
		$row = $this->database->setQuery($query)->loadObject();

		if ($row === null) {
			throw new \RuntimeException(sprintf('Project %d does not exist.', $projectId));
		}

		$profile = $this->decodeObject((string) $row->payload_json, 'sport profile');
		$this->validator->validateProfileSchema($profile);

		if ($row->overrides_json === null) {
			return [];
		}

		$schemaVersion = (string) $profile['project_rule_schema']['schema_version'];

		if (!hash_equals($schemaVersion, (string) $row->schema_version)) {
			throw new \UnexpectedValueException(sprintf('Project %d rule schema version does not match its profile.', $projectId));
		}

		$overrides = $this->decodeObject((string) $row->overrides_json, 'project rule overrides');
		$checksum = $this->validator->checksum($overrides);

		if (!hash_equals($checksum, (string) $row->overrides_checksum)) {
			throw new \UnexpectedValueException(sprintf('Project %d rule checksum is invalid.', $projectId));
		}

		$this->validator->validateOverrides($profile, $overrides);

		return $overrides;
	}

	/**
	 * An empty object deletes the sparse row and restores full inheritance.
	 *
	 * @param array<string, mixed> $overrides
	 * @return array{schema_version: string, overrides_json: string, overrides_checksum: string}|null
	 */
	public function save(int $projectId, array $overrides, int $actorId): ?array
	{
		$this->assertProjectId($projectId);

		if ($actorId < 0) {
			throw new \InvalidArgumentException('Actor ID cannot be negative.');
		}

		$this->database->transactionStart();

		try {
			$this->lockProject($projectId);
			$profile = $this->loadProjectProfile($projectId);
			$this->validator->validateOverrides($profile, $overrides);

			if ($overrides === []) {
				$this->delete($projectId);
				$this->database->transactionCommit();

				return null;
			}

			$payload = CanonicalJson::encodeObject($overrides);

			if (strlen($payload) > self::MAX_PAYLOAD_BYTES) {
				throw new \LengthException('Project rule overrides exceed the storage limit.');
			}

			$record = [
				'schema_version' => (string) $profile['project_rule_schema']['schema_version'],
				'overrides_json' => $payload,
				'overrides_checksum' => hash('sha256', $payload),
			];
			$existingId = $this->findConfigId($projectId);

			if ($existingId === null) {
				$this->insert($projectId, $actorId, $record);
			} else {
				$this->update($existingId, $actorId, $record);
			}

			$this->database->transactionCommit();

			return $record;
		} catch (\Throwable $exception) {
			$this->database->transactionRollback();

			throw $exception;
		}
	}

	private function lockProject(int $projectId): void
	{
		$query = $this->database->getQuery(true)
			->update($this->database->quoteName('#__joomleague_project'))
			->set($this->database->quoteName('id') . ' = ' . $this->database->quoteName('id'))
			->where($this->database->quoteName('id') . ' = :project_id')
			->bind(':project_id', $projectId);
		$this->database->setQuery($query)->execute();
	}

	/** @return array<string, mixed> */
	private function loadProjectProfile(int $projectId): array
	{
		$query = $this->database->getQuery(true)
			->select($this->database->quoteName('version.payload_json'))
			->from($this->database->quoteName('#__joomleague_project', 'project'))
			->innerJoin($this->database->quoteName('#__joomleague_sport_profile_version', 'version') . ' ON version.id = project.profile_version_id')
			->where($this->database->quoteName('project.id') . ' = :project_id')
			->bind(':project_id', $projectId);
		$payload = $this->database->setQuery($query)->loadResult();

		if ($payload === null) {
			throw new \RuntimeException(sprintf('Project %d does not exist.', $projectId));
		}

		return $this->decodeObject((string) $payload, 'sport profile');
	}

	private function findConfigId(int $projectId): ?int
	{
		$query = $this->database->getQuery(true)
			->select($this->database->quoteName('id'))
			->from($this->database->quoteName('#__joomleague_project_rule_config'))
			->where($this->database->quoteName('project_id') . ' = :project_id')
			->bind(':project_id', $projectId);
		$value = $this->database->setQuery($query)->loadResult();

		return $value === null ? null : (int) $value;
	}

	/** @param array{schema_version: string, overrides_json: string, overrides_checksum: string} $record */
	private function insert(int $projectId, int $actorId, array $record): void
	{
		$schemaVersion = $record['schema_version'];
		$overridesJson = $record['overrides_json'];
		$overridesChecksum = $record['overrides_checksum'];
		$query = $this->database->getQuery(true)
			->insert($this->database->quoteName('#__joomleague_project_rule_config'))
			->columns($this->database->quoteName([
				'project_id', 'schema_version', 'overrides_json', 'overrides_checksum', 'created_by',
			]))
			->values(':project_id, :schema_version, :overrides_json, :overrides_checksum, :created_by')
			->bind(':project_id', $projectId)
			->bind(':schema_version', $schemaVersion)
			->bind(':overrides_json', $overridesJson)
			->bind(':overrides_checksum', $overridesChecksum)
			->bind(':created_by', $actorId);
		$this->database->setQuery($query)->execute();
	}

	/** @param array{schema_version: string, overrides_json: string, overrides_checksum: string} $record */
	private function update(int $configId, int $actorId, array $record): void
	{
		$modified = gmdate('Y-m-d H:i:s');
		$schemaVersion = $record['schema_version'];
		$overridesJson = $record['overrides_json'];
		$overridesChecksum = $record['overrides_checksum'];
		$query = $this->database->getQuery(true)
			->update($this->database->quoteName('#__joomleague_project_rule_config'))
			->set($this->database->quoteName('schema_version') . ' = :schema_version')
			->set($this->database->quoteName('overrides_json') . ' = :overrides_json')
			->set($this->database->quoteName('overrides_checksum') . ' = :overrides_checksum')
			->set($this->database->quoteName('modified') . ' = :modified')
			->set($this->database->quoteName('modified_by') . ' = :modified_by')
			->where($this->database->quoteName('id') . ' = :config_id')
			->bind(':schema_version', $schemaVersion)
			->bind(':overrides_json', $overridesJson)
			->bind(':overrides_checksum', $overridesChecksum)
			->bind(':modified', $modified)
			->bind(':modified_by', $actorId)
			->bind(':config_id', $configId);
		$this->database->setQuery($query)->execute();
	}

	private function delete(int $projectId): void
	{
		$query = $this->database->getQuery(true)
			->delete($this->database->quoteName('#__joomleague_project_rule_config'))
			->where($this->database->quoteName('project_id') . ' = :project_id')
			->bind(':project_id', $projectId);
		$this->database->setQuery($query)->execute();
	}

	/** @return array<string, mixed> */
	private function decodeObject(string $json, string $context): array
	{
		$value = json_decode($json, true, 512, JSON_THROW_ON_ERROR);

		if (!is_array($value) || (array_is_list($value) && $value !== [])) {
			throw new \UnexpectedValueException(sprintf('The %s must be a JSON object.', $context));
		}

		return $value;
	}

	private function assertProjectId(int $projectId): void
	{
		if ($projectId < 1) {
			throw new \InvalidArgumentException('A positive project ID is required.');
		}
	}
}
