<?php

declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Administrator\Service;

defined('_JEXEC') or die;

use Joomla\Database\DatabaseInterface;
use Joomleague\Component\Joomleague\Domain\Service\CanonicalJson;

final class ProjectTemplateConfigRepository
{
	private const MAX_PAYLOAD_BYTES = 65535;

	public function __construct(
		private readonly DatabaseInterface $database,
		private readonly TemplateDefinitionRegistry $registry = new TemplateDefinitionRegistry()
	) {
	}

	/** @return array<string,array<string,mixed>> */
	public function getAll(int $projectId): array
	{
		$profile = $this->loadProjectProfile($projectId);
		$supported = $this->supportedTemplateCodes($profile);
		$query = $this->database->getQuery(true)
			->select(['template_code', 'schema_version', 'params_json', 'params_checksum'])
			->from($this->database->quoteName('#__joomleague_project_template_config'))
			->where('project_id = :projectId')->bind(':projectId', $projectId);
		$result = [];
		foreach ($this->database->setQuery($query)->loadObjectList() as $row) {
			$code = (string) $row->template_code;
			if (!in_array($code, $supported, true) || !hash_equals(TemplateDefinitionRegistry::SCHEMA_VERSION, (string) $row->schema_version)) {
					throw new \UnexpectedValueException('COM_JOOMLEAGUE_TEMPLATE_ERROR_PROJECT_CONFIG_UNSUPPORTED');
			}
			$params = $this->decodeObject((string) $row->params_json);
			$this->registry->validateValues($code, $params);
			if (!hash_equals(CanonicalJson::checksum($params), (string) $row->params_checksum)) {
					throw new \UnexpectedValueException('COM_JOOMLEAGUE_TEMPLATE_ERROR_PROJECT_CHECKSUM');
			}
			$result[$code] = $params;
		}
		return $result;
	}

	/** @param array<string,mixed> $params */
	public function save(int $projectId, string $templateCode, array $params, int $actorId): void
	{
		$this->saveAll($projectId, [$templateCode => $params], $actorId);
	}

	/**
	 * Saves all submitted template configurations atomically. Empty values remove
	 * the sparse project row and restore inheritance for that template.
	 *
	 * @param array<string,array<string,mixed>> $configs
	 */
	public function saveAll(int $projectId, array $configs, int $actorId): void
	{
		if ($actorId < 0) {
			throw new \InvalidArgumentException('COM_JOOMLEAGUE_TEMPLATE_ERROR_ACTOR_INVALID');
		}

		$this->database->transactionStart();

		try {
			$this->lockProject($projectId);
			$profile = $this->loadProjectProfile($projectId);
			$supported = $this->supportedTemplateCodes($profile);
			$records = [];

			foreach ($configs as $templateCode => $params) {
				if (!is_string($templateCode) || !in_array($templateCode, $supported, true)) {
						throw new \InvalidArgumentException('COM_JOOMLEAGUE_TEMPLATE_ERROR_PROJECT_TEMPLATE_UNSUPPORTED');
				}

				$this->registry->validateValues($templateCode, $params);

				if ($params === []) {
					$records[$templateCode] = null;
					continue;
				}

				$json = CanonicalJson::encodeObject($params);

				if (strlen($json) > self::MAX_PAYLOAD_BYTES) {
						throw new \LengthException('COM_JOOMLEAGUE_TEMPLATE_ERROR_PROJECT_SIZE');
				}

				$records[$templateCode] = [
					'json' => $json,
					'checksum' => hash('sha256', $json),
				];
			}

			foreach ($records as $templateCode => $record) {
				if ($record === null) {
					$this->delete($projectId, $templateCode);
					continue;
				}

				$id = $this->findId($projectId, $templateCode);

				if ($id === null) {
					$this->insert($projectId, $templateCode, $record['json'], $record['checksum'], $actorId);
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
	private function loadProjectProfile(int $projectId): array
	{
		if ($projectId < 1) throw new \InvalidArgumentException('COM_JOOMLEAGUE_CONTEXT_PROJECT_REQUIRED');
		$query = $this->database->getQuery(true)
			->select('version.payload_json')
			->from($this->database->quoteName('#__joomleague_project', 'project'))
			->innerJoin($this->database->quoteName('#__joomleague_sport_profile_version', 'version') . ' ON version.id = project.profile_version_id')
			->where('project.id = :projectId')->bind(':projectId', $projectId);
		$json = $this->database->setQuery($query)->loadResult();
		if ($json === null) throw new \RuntimeException('COM_JOOMLEAGUE_TEMPLATE_ERROR_PROJECT_NOT_FOUND');
		return $this->decodeObject((string) $json);
	}

	/** @param array<string,mixed> $profile @return list<string> */
	private function supportedTemplateCodes(array $profile): array
	{
		$defaults = $profile['template_defaults'] ?? null;
		if (!is_array($defaults) || array_is_list($defaults)) throw new \UnexpectedValueException('COM_JOOMLEAGUE_TEMPLATE_ERROR_PROFILE_DEFAULTS_INVALID');
		$known = $this->registry->all();
		foreach ($defaults as $code => $values) {
			if (!isset($known[$code]) || !is_array($values) || array_is_list($values)) throw new \UnexpectedValueException('COM_JOOMLEAGUE_TEMPLATE_ERROR_PROFILE_DEFAULTS_INVALID');
			$this->registry->validateValues((string) $code, $values);
		}
		return array_keys($defaults);
	}

	private function lockProject(int $projectId): void
	{
		$query = $this->database->getQuery(true)->update($this->database->quoteName('#__joomleague_project'))->set($this->database->quoteName('id') . ' = ' . $this->database->quoteName('id'))->where('id = :projectId')->bind(':projectId', $projectId);
		$this->database->setQuery($query)->execute();
	}

	private function findId(int $projectId, string $code): ?int
	{
		$query = $this->database->getQuery(true)->select('id')->from($this->database->quoteName('#__joomleague_project_template_config'))->where('project_id = :projectId')->where('template_code = :templateCode')->bind(':projectId', $projectId)->bind(':templateCode', $code);
		$id = $this->database->setQuery($query)->loadResult();
		return $id === null ? null : (int) $id;
	}

	private function insert(int $projectId, string $code, string $json, string $checksum, int $actorId): void
	{
		$schema = TemplateDefinitionRegistry::SCHEMA_VERSION;
		$query = $this->database->getQuery(true)->insert($this->database->quoteName('#__joomleague_project_template_config'))->columns($this->database->quoteName(['project_id','template_code','schema_version','params_json','params_checksum','created_by']))->values(':projectId,:templateCode,:schemaVersion,:paramsJson,:paramsChecksum,:createdBy')->bind(':projectId', $projectId)->bind(':templateCode', $code)->bind(':schemaVersion', $schema)->bind(':paramsJson', $json)->bind(':paramsChecksum', $checksum)->bind(':createdBy', $actorId);
		$this->database->setQuery($query)->execute();
	}

	private function update(int $id, string $json, string $checksum, int $actorId): void
	{
		$modified = gmdate('Y-m-d H:i:s');
		$query = $this->database->getQuery(true)
			->update($this->database->quoteName('#__joomleague_project_template_config'))
			->set($this->database->quoteName('params_json') . ' = :paramsJson')
			->set($this->database->quoteName('params_checksum') . ' = :paramsChecksum')
			->set($this->database->quoteName('modified') . ' = :modified')
			->set($this->database->quoteName('modified_by') . ' = :modifiedBy')
			->where($this->database->quoteName('id') . ' = :id')
			->bind(':paramsJson', $json)
			->bind(':paramsChecksum', $checksum)
			->bind(':modified', $modified)
			->bind(':modifiedBy', $actorId)
			->bind(':id', $id);
		$this->database->setQuery($query)->execute();
	}

	private function delete(int $projectId, string $code): void
	{
		$query = $this->database->getQuery(true)->delete($this->database->quoteName('#__joomleague_project_template_config'))->where('project_id = :projectId')->where('template_code = :templateCode')->bind(':projectId', $projectId)->bind(':templateCode', $code);
		$this->database->setQuery($query)->execute();
	}

	/** @return array<string,mixed> */
	private function decodeObject(string $json): array
	{
		$value = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
		if (!is_array($value) || (array_is_list($value) && $value !== [])) throw new \UnexpectedValueException('COM_JOOMLEAGUE_TEMPLATE_ERROR_CONFIG_INVALID');
		return $value;
	}
}
