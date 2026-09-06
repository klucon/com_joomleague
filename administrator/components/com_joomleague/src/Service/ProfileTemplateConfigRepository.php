<?php

declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Administrator\Service;

defined('_JEXEC') or die;

use Joomla\Database\DatabaseInterface;
use Joomleague\Component\Joomleague\Domain\Service\CanonicalJson;

final class ProfileTemplateConfigRepository
{
	private const MAX_PAYLOAD_BYTES = 65535;

	public function __construct(
		private readonly DatabaseInterface $database,
		private readonly TemplateDefinitionRegistry $registry = new TemplateDefinitionRegistry()
	) {
	}

	/** @return array<string,array<string,mixed>> */
	public function getAll(int $profileVersionId): array
	{
		$profile = $this->loadProfile($profileVersionId);
		$supported = $this->supportedTemplateCodes($profile);
		$query = $this->database->getQuery(true)
			->select(['template_code', 'schema_version', 'params_json', 'params_checksum'])
			->from($this->database->quoteName('#__joomleague_profile_template_config'))
			->where($this->database->quoteName('profile_version_id') . ' = :versionId')
			->where($this->database->quoteName('published') . ' = 1')
			->bind(':versionId', $profileVersionId);
		$result = [];

		foreach ($this->database->setQuery($query)->loadObjectList() as $row) {
			$code = (string) $row->template_code;

			if (!in_array($code, $supported, true) || !hash_equals(TemplateDefinitionRegistry::SCHEMA_VERSION, (string) $row->schema_version)) {
				throw new \UnexpectedValueException('COM_JOOMLEAGUE_TEMPLATE_ERROR_PROFILE_CONFIG_UNSUPPORTED');
			}

			$params = $this->decodeObject((string) $row->params_json);
			$this->registry->validateValues($code, $params);

			if (!hash_equals(CanonicalJson::checksum($params), (string) $row->params_checksum)) {
				throw new \UnexpectedValueException('COM_JOOMLEAGUE_TEMPLATE_ERROR_PROFILE_CHECKSUM');
			}

			$result[$code] = $params;
		}

		return $result;
	}

	/** @param array<string,array<string,mixed>> $configs */
	public function saveAll(int $profileVersionId, array $configs, int $actorId): void
	{
		if ($actorId < 0) {
			throw new \InvalidArgumentException('COM_JOOMLEAGUE_TEMPLATE_ERROR_ACTOR_INVALID');
		}

		$this->database->transactionStart();

		try {
			$this->lockProfileVersion($profileVersionId);
			$profile = $this->loadProfile($profileVersionId);
			$supported = $this->supportedTemplateCodes($profile);
			$records = [];

			foreach ($configs as $templateCode => $params) {
				if (!is_string($templateCode) || !in_array($templateCode, $supported, true)) {
					throw new \InvalidArgumentException('COM_JOOMLEAGUE_TEMPLATE_ERROR_PROFILE_TEMPLATE_UNSUPPORTED');
				}

				$this->registry->validateValues($templateCode, $params);

				if ($params === []) {
					$records[$templateCode] = null;
					continue;
				}

				$json = CanonicalJson::encodeObject($params);

				if (strlen($json) > self::MAX_PAYLOAD_BYTES) {
					throw new \LengthException('COM_JOOMLEAGUE_TEMPLATE_ERROR_PROFILE_SIZE');
				}

				$records[$templateCode] = ['json' => $json, 'checksum' => hash('sha256', $json)];
			}

			foreach ($records as $templateCode => $record) {
				$id = $this->findId($profileVersionId, $templateCode);

				if ($record === null) {
					$this->delete($profileVersionId, $templateCode);
				} elseif ($id === null) {
					$this->insert($profileVersionId, $templateCode, $record['json'], $record['checksum'], $actorId);
				} else {
					$this->update($id, $record['json'], $record['checksum'], $actorId);
				}
			}

			$this->database->transactionCommit();
		} catch (\Throwable $exception) {
			$this->database->transactionRollback();
			throw $exception;
		}
	}

	/** @return array<string,mixed> */
	private function loadProfile(int $profileVersionId): array
	{
		if ($profileVersionId < 1) {
			throw new \InvalidArgumentException('COM_JOOMLEAGUE_PROFILETEMPLATES_PROFILE_REQUIRED');
		}

		$query = $this->database->getQuery(true)
			->select($this->database->quoteName('payload_json'))
			->from($this->database->quoteName('#__joomleague_sport_profile_version'))
			->where($this->database->quoteName('id') . ' = :versionId')
			->bind(':versionId', $profileVersionId);
		$json = $this->database->setQuery($query)->loadResult();

		if ($json === null) {
			throw new \RuntimeException('COM_JOOMLEAGUE_PROFILETEMPLATES_PROFILE_NOT_FOUND');
		}

		return $this->decodeObject((string) $json);
	}

	/** @param array<string,mixed> $profile @return list<string> */
	private function supportedTemplateCodes(array $profile): array
	{
		$defaults = $profile['template_defaults'] ?? null;

		if (!is_array($defaults) || array_is_list($defaults)) {
			throw new \UnexpectedValueException('COM_JOOMLEAGUE_TEMPLATE_ERROR_PROFILE_DEFAULTS_INVALID');
		}

		foreach ($defaults as $code => $values) {
			if (!is_string($code) || !is_array($values) || array_is_list($values)) {
				throw new \UnexpectedValueException('COM_JOOMLEAGUE_TEMPLATE_ERROR_PROFILE_DEFAULTS_INVALID');
			}

			$this->registry->validateValues($code, $values);
		}

		return array_keys($defaults);
	}

	private function lockProfileVersion(int $profileVersionId): void
	{
		$query = $this->database->getQuery(true)
			->update($this->database->quoteName('#__joomleague_sport_profile_version'))
			->set($this->database->quoteName('id') . ' = ' . $this->database->quoteName('id'))
			->where($this->database->quoteName('id') . ' = :versionId')
			->bind(':versionId', $profileVersionId);
		$this->database->setQuery($query)->execute();
	}

	private function findId(int $profileVersionId, string $code): ?int
	{
		$query = $this->database->getQuery(true)
			->select($this->database->quoteName('id'))
			->from($this->database->quoteName('#__joomleague_profile_template_config'))
			->where($this->database->quoteName('profile_version_id') . ' = :versionId')
			->where($this->database->quoteName('template_code') . ' = :templateCode')
			->bind(':versionId', $profileVersionId)
			->bind(':templateCode', $code);
		$value = $this->database->setQuery($query)->loadResult();

		return $value === null ? null : (int) $value;
	}

	private function insert(int $profileVersionId, string $code, string $json, string $checksum, int $actorId): void
	{
		$schema = TemplateDefinitionRegistry::SCHEMA_VERSION;
		$query = $this->database->getQuery(true)
			->insert($this->database->quoteName('#__joomleague_profile_template_config'))
			->columns($this->database->quoteName(['profile_version_id', 'template_code', 'schema_version', 'params_json', 'params_checksum', 'published', 'created_by']))
			->values(':versionId, :templateCode, :schemaVersion, :paramsJson, :paramsChecksum, 1, :createdBy')
			->bind(':versionId', $profileVersionId)
			->bind(':templateCode', $code)
			->bind(':schemaVersion', $schema)
			->bind(':paramsJson', $json)
			->bind(':paramsChecksum', $checksum)
			->bind(':createdBy', $actorId);
		$this->database->setQuery($query)->execute();
	}

	private function update(int $id, string $json, string $checksum, int $actorId): void
	{
		$modified = gmdate('Y-m-d H:i:s');
		$schema = TemplateDefinitionRegistry::SCHEMA_VERSION;
		$query = $this->database->getQuery(true)
			->update($this->database->quoteName('#__joomleague_profile_template_config'))
			->set($this->database->quoteName('schema_version') . ' = :schemaVersion')
			->set($this->database->quoteName('params_json') . ' = :paramsJson')
			->set($this->database->quoteName('params_checksum') . ' = :paramsChecksum')
			->set($this->database->quoteName('published') . ' = 1')
			->set($this->database->quoteName('modified') . ' = :modified')
			->set($this->database->quoteName('modified_by') . ' = :modifiedBy')
			->where($this->database->quoteName('id') . ' = :id')
			->bind(':schemaVersion', $schema)
			->bind(':paramsJson', $json)
			->bind(':paramsChecksum', $checksum)
			->bind(':modified', $modified)
			->bind(':modifiedBy', $actorId)
			->bind(':id', $id);
		$this->database->setQuery($query)->execute();
	}

	private function delete(int $profileVersionId, string $code): void
	{
		$query = $this->database->getQuery(true)
			->delete($this->database->quoteName('#__joomleague_profile_template_config'))
			->where($this->database->quoteName('profile_version_id') . ' = :versionId')
			->where($this->database->quoteName('template_code') . ' = :templateCode')
			->bind(':versionId', $profileVersionId)
			->bind(':templateCode', $code);
		$this->database->setQuery($query)->execute();
	}

	/** @return array<string,mixed> */
	private function decodeObject(string $json): array
	{
		$value = json_decode($json, true, 512, JSON_THROW_ON_ERROR);

		if (!is_array($value) || (array_is_list($value) && $value !== [])) {
			throw new \UnexpectedValueException('COM_JOOMLEAGUE_TEMPLATE_ERROR_CONFIG_INVALID');
		}

		return $value;
	}
}
