<?php

declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Site\Service;

defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;
use Joomla\Database\DatabaseInterface;

/** Resolves the canonical five-layer frontend presentation contract. */
final class ProjectTemplateProvider
{
	/** @var array<string,array<string,mixed>>|null */
	private ?array $definitions = null;

	public function __construct(private readonly DatabaseInterface $database)
	{
	}

	/** @param array<string,mixed> $presentationOverrides @return array<string,mixed> */
	public function resolve(int $projectId, string $templateCode, array $presentationOverrides = []): array
	{
		if ($projectId < 1) {
			throw new \InvalidArgumentException(Text::_('COM_JOOMLEAGUE_TEMPLATE_ERROR_PROJECT_REQUIRED'));
		}

		$code = $templateCode;
		$query = $this->database->getQuery(true)
			->select(['version.payload_json', 'profile_config.params_json AS profile_params_json', 'project_config.params_json AS project_params_json'])
			->from($this->database->quoteName('#__joomleague_project', 'project'))
			->innerJoin($this->database->quoteName('#__joomleague_sport_profile_version', 'version') . ' ON version.id = project.profile_version_id')
			->leftJoin($this->database->quoteName('#__joomleague_profile_template_config', 'profile_config') . ' ON profile_config.profile_version_id = project.profile_version_id AND profile_config.template_code = :profileCode AND profile_config.published = 1')
			->leftJoin($this->database->quoteName('#__joomleague_project_template_config', 'project_config') . ' ON project_config.project_id = project.id AND project_config.template_code = :projectCode')
			->where('project.id = :projectId')
			->bind(':profileCode', $code)
			->bind(':projectCode', $code)
			->bind(':projectId', $projectId, \Joomla\Database\ParameterType::INTEGER);
		$row = $this->database->setQuery($query)->loadObject();
		if ($row === null) throw new \RuntimeException(Text::_('COM_JOOMLEAGUE_TEMPLATE_ERROR_PROJECT_NOT_FOUND'));

		$payload = $this->object((string) $row->payload_json);
		$profileDefaults = $payload['template_defaults'][$templateCode] ?? null;
		if (!is_array($profileDefaults) || array_is_list($profileDefaults)) {
			throw new \InvalidArgumentException(Text::_('COM_JOOMLEAGUE_TEMPLATE_ERROR_PROJECT_TEMPLATE_UNSUPPORTED'));
		}

		$definition = $this->definition($templateCode);
		$result = $definition['defaults'];
		foreach ([$profileDefaults, $this->nullableObject($row->profile_params_json), $this->nullableObject($row->project_params_json), $presentationOverrides] as $layer) {
			$this->validate($templateCode, $definition, $layer);
			$result = array_replace($result, $layer);
		}

		return $result;
	}

	public function supports(int $projectId, string $templateCode): bool
	{
		try {
			$this->resolve($projectId, $templateCode);
			return true;
		} catch (\InvalidArgumentException) {
			return false;
		}
	}

	/** @return array<string,mixed> */
	private function definition(string $code): array
	{
		if ($this->definitions === null) {
			$file = JPATH_ADMINISTRATOR . '/components/com_joomleague/resources/template-definitions/templates.json';
			$data = json_decode((string) file_get_contents($file), true, 512, JSON_THROW_ON_ERROR);
				if (($data['schema_version'] ?? null) !== '1.0.0' || !is_array($data['definitions'] ?? null)) throw new \UnexpectedValueException(Text::_('COM_JOOMLEAGUE_TEMPLATE_ERROR_DEFINITION_SCHEMA'));
			$this->definitions = $data['definitions'];
		}
		return $this->definitions[$code] ?? throw new \InvalidArgumentException(Text::_('COM_JOOMLEAGUE_TEMPLATE_ERROR_DEFINITION_UNKNOWN'));
	}

	/** @param array<string,mixed> $definition @param array<string,mixed> $values */
	private function validate(string $code, array $definition, array $values): void
	{
		foreach ($values as $name => $value) {
			$field = $definition['fields'][$name] ?? throw new \InvalidArgumentException(Text::_('COM_JOOMLEAGUE_TEMPLATE_ERROR_FIELD_UNKNOWN'));
			$valid = match ($field['type'] ?? null) { 'boolean' => is_bool($value), 'integer' => is_int($value), 'string' => is_string($value), default => false };
			if (!$valid || (isset($field['enum']) && !in_array($value, $field['enum'], true))) throw new \InvalidArgumentException(Text::_('COM_JOOMLEAGUE_TEMPLATE_ERROR_VALUE_INVALID'));
		}
	}

	/** @return array<string,mixed> */
	private function object(string $json): array
	{
		$value = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
		if (!is_array($value) || array_is_list($value)) throw new \UnexpectedValueException(Text::_('COM_JOOMLEAGUE_TEMPLATE_ERROR_CONFIG_INVALID'));
		return $value;
	}

	/** @return array<string,mixed> */
	private function nullableObject(mixed $json): array
	{
		return $json === null ? [] : $this->object((string) $json);
	}
}
