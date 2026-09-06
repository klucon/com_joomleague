<?php

declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Administrator\Service;

defined('_JEXEC') or die;

final class TemplateDefinitionRegistry
{
	public const SCHEMA_VERSION = '1.0.0';

	/** @var array<string, array<string, mixed>>|null */
	private ?array $definitions = null;

	public function __construct(private readonly ?string $definitionFile = null)
	{
	}

	/** @return array<string, array<string, mixed>> */
	public function all(): array
	{
		if ($this->definitions !== null) {
			return $this->definitions;
		}

		$file = $this->definitionFile
			?? JPATH_ADMINISTRATOR . '/components/com_joomleague/resources/template-definitions/templates.json';
		$contents = file_get_contents($file);

		if ($contents === false) {
			throw new \RuntimeException('COM_JOOMLEAGUE_TEMPLATE_ERROR_DEFINITION_READ');
		}

		$data = json_decode($contents, true, 512, JSON_THROW_ON_ERROR);

		if (($data['schema_version'] ?? null) !== self::SCHEMA_VERSION || !is_array($data['definitions'] ?? null)) {
			throw new \UnexpectedValueException('COM_JOOMLEAGUE_TEMPLATE_ERROR_DEFINITION_SCHEMA');
		}

		foreach ($data['definitions'] as $code => $definition) {
			$this->validateDefinition((string) $code, $definition);
		}

		return $this->definitions = $data['definitions'];
	}

	/** @return array<string, mixed> */
	public function get(string $code): array
	{
		return $this->all()[$code]
				?? throw new \InvalidArgumentException('COM_JOOMLEAGUE_TEMPLATE_ERROR_DEFINITION_UNKNOWN');
	}

	/** @param array<string, mixed> $values */
	public function validateValues(string $code, array $values): void
	{
		$definition = $this->get($code);

		foreach ($values as $field => $value) {
			if (!isset($definition['fields'][$field])) {
				throw new \InvalidArgumentException('COM_JOOMLEAGUE_TEMPLATE_ERROR_FIELD_UNKNOWN');
			}

			$this->validateValue($code, (string) $field, $definition['fields'][$field], $value);
		}
	}

	private function validateDefinition(string $code, mixed $definition): void
	{
		if (preg_match('/^[a-z][a-z0-9_]*$/', $code) !== 1 || !is_array($definition)) {
			throw new \UnexpectedValueException('COM_JOOMLEAGUE_TEMPLATE_ERROR_DEFINITION_INVALID');
		}

		foreach (['name_key', 'description_key'] as $key) {
			if (!is_string($definition[$key] ?? null) || !str_starts_with($definition[$key], 'COM_JOOMLEAGUE_')) {
				throw new \UnexpectedValueException('COM_JOOMLEAGUE_TEMPLATE_ERROR_DEFINITION_INVALID');
			}
		}

		if (!is_array($definition['defaults'] ?? null) || !is_array($definition['fields'] ?? null)) {
			throw new \UnexpectedValueException('COM_JOOMLEAGUE_TEMPLATE_ERROR_DEFINITION_INVALID');
		}

		if (array_diff_key($definition['defaults'], $definition['fields']) !== []) {
			throw new \UnexpectedValueException('COM_JOOMLEAGUE_TEMPLATE_ERROR_DEFINITION_INVALID');
		}

		$this->validateValuesFromDefinition($code, $definition);
	}

	/** @param array<string, mixed> $definition */
	private function validateValuesFromDefinition(string $code, array $definition): void
	{
		foreach ($definition['fields'] as $field => $metadata) {
			if (!is_array($metadata) || !in_array($metadata['type'] ?? null, ['boolean', 'integer', 'string'], true)) {
				throw new \UnexpectedValueException('COM_JOOMLEAGUE_TEMPLATE_ERROR_FIELD_INVALID');
			}

			foreach (['label_key', 'description_key'] as $key) {
				if (!is_string($metadata[$key] ?? null) || !str_starts_with($metadata[$key], 'COM_JOOMLEAGUE_')) {
					throw new \UnexpectedValueException('COM_JOOMLEAGUE_TEMPLATE_ERROR_FIELD_INVALID');
				}
			}

			if (isset($metadata['enum'])) {
				if ($metadata['type'] !== 'string' || !is_array($metadata['enum']) || $metadata['enum'] === [] || !array_is_list($metadata['enum'])) {
					throw new \UnexpectedValueException('COM_JOOMLEAGUE_TEMPLATE_ERROR_FIELD_INVALID');
				}

				foreach ($metadata['enum'] as $option) {
					if (!is_string($option) || $option === '') {
						throw new \UnexpectedValueException('COM_JOOMLEAGUE_TEMPLATE_ERROR_FIELD_INVALID');
					}
				}

				if (count($metadata['enum']) !== count(array_unique($metadata['enum']))) {
					throw new \UnexpectedValueException('COM_JOOMLEAGUE_TEMPLATE_ERROR_FIELD_INVALID');
				}
			}

			if (array_key_exists($field, $definition['defaults'])) {
				$this->validateValue($code, (string) $field, $metadata, $definition['defaults'][$field]);
			}
		}
	}

	/** @param array<string, mixed> $metadata */
	private function validateValue(string $code, string $field, array $metadata, mixed $value): void
	{
		$valid = match ($metadata['type']) {
			'boolean' => is_bool($value),
			'integer' => is_int($value),
			'string' => is_string($value),
			default => false,
		};

		if (!$valid || (isset($metadata['enum']) && !in_array($value, $metadata['enum'], true))) {
			throw new \InvalidArgumentException('COM_JOOMLEAGUE_TEMPLATE_ERROR_VALUE_INVALID');
		}
	}
}
