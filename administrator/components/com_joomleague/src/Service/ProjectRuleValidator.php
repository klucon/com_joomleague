<?php

declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Administrator\Service;

defined('_JEXEC') or die;

use Joomleague\Component\Joomleague\Domain\Service\CanonicalJson;

final class ProjectRuleValidator
{
	public const SCHEMA_VERSION = '1.0.0';

	/** @param array<string, mixed> $profile */
	public function validateProfileSchema(array $profile): void
	{
		$schema = $profile['project_rule_schema'] ?? null;

		if (!is_array($schema) || ($schema['schema_version'] ?? null) !== self::SCHEMA_VERSION || !is_array($schema['fields'] ?? null)) {
			throw new \UnexpectedValueException('Profile project-rule schema is missing or unsupported.');
		}

		foreach ($schema['fields'] as $pointer => $definition) {
			if (!is_string($pointer) || !$this->isPointer($pointer) || !is_array($definition)) {
				throw new \UnexpectedValueException('Project-rule schema contains an invalid field definition.');
			}

			$type = $definition['type'] ?? null;

			if (!in_array($type, ['boolean', 'integer', 'number', 'string', 'array'], true)) {
				throw new \UnexpectedValueException(sprintf('Project-rule field "%s" has an unsupported type.', $pointer));
			}

			foreach (['minimum', 'maximum'] as $bound) {
				if (isset($definition[$bound]) && !is_int($definition[$bound]) && !is_float($definition[$bound])) {
					throw new \UnexpectedValueException(sprintf('Project-rule field "%s" has an invalid %s.', $pointer, $bound));
				}
			}

			if (isset($definition['minimum'], $definition['maximum']) && $definition['minimum'] > $definition['maximum']) {
				throw new \UnexpectedValueException(sprintf('Project-rule field "%s" has an inverted range.', $pointer));
			}

			if (isset($definition['enum']) && (!is_array($definition['enum']) || $definition['enum'] === [] || !array_is_list($definition['enum']))) {
				throw new \UnexpectedValueException(sprintf('Project-rule field "%s" has an invalid enum.', $pointer));
			}

			if (isset($definition['enum'])) {
				foreach ($definition['enum'] as $enumValue) {
					$enumTypeValid = match ($type) {
						'integer' => is_int($enumValue),
						'number' => is_int($enumValue) || is_float($enumValue),
						'string' => is_string($enumValue),
						'boolean' => is_bool($enumValue),
						default => false,
					};

					if (!$enumTypeValid) {
						throw new \UnexpectedValueException(sprintf('Project-rule field "%s" has an enum value of the wrong type.', $pointer));
					}
				}
			}

			if (isset($definition['pattern']) && (!is_string($definition['pattern']) || @preg_match($definition['pattern'], '') === false)) {
				throw new \UnexpectedValueException(sprintf('Project-rule field "%s" has an invalid pattern.', $pointer));
			}

			if (isset($definition['max_length']) && (!is_int($definition['max_length']) || $definition['max_length'] < 1)) {
				throw new \UnexpectedValueException(sprintf('Project-rule field "%s" has an invalid maximum length.', $pointer));
			}

			if ($type === 'array') {
				$itemType = $definition['items']['type'] ?? null;

				if (!in_array($itemType, ['integer', 'number', 'string'], true)) {
					throw new \UnexpectedValueException(sprintf('Project-rule array "%s" has an unsupported item type.', $pointer));
				}

				if (isset($definition['items']['max_length']) && (!is_int($definition['items']['max_length']) || $definition['items']['max_length'] < 1)) {
					throw new \UnexpectedValueException(sprintf('Project-rule array "%s" has an invalid item length.', $pointer));
				}

				foreach (['min_items', 'max_items'] as $bound) {
					if (isset($definition[$bound]) && (!is_int($definition[$bound]) || $definition[$bound] < 0)) {
						throw new \UnexpectedValueException(sprintf('Project-rule array "%s" has an invalid %s.', $pointer, $bound));
					}
				}

				if (isset($definition['min_items'], $definition['max_items']) && $definition['min_items'] > $definition['max_items']) {
					throw new \UnexpectedValueException(sprintf('Project-rule array "%s" has an inverted item range.', $pointer));
				}
			}

			$default = $this->readPointer($profile, $pointer);
			$this->validateValue($pointer, $definition, $default, \UnexpectedValueException::class);
		}

		$constraints = $schema['constraints'] ?? [];

		if (!is_array($constraints) || !array_is_list($constraints)) {
			throw new \UnexpectedValueException('Project-rule constraints must be a list.');
		}

		foreach ($constraints as $constraint) {
			$this->validateConstraintDefinition($constraint, $schema['fields']);
		}

		$this->validateConstraints($profile, $constraints, \UnexpectedValueException::class);
	}

	/** @param array<string, mixed> $profile @param array<string, mixed> $overrides */
	public function validateOverrides(array $profile, array $overrides): void
	{
		$this->validateProfileSchema($profile);
		$fields = $profile['project_rule_schema']['fields'];

		foreach ($this->flatten($overrides) as $pointer => $value) {
			if (!isset($fields[$pointer])) {
				throw new \InvalidArgumentException(sprintf('Project-rule field "%s" is not overridable.', $pointer));
			}

			$this->validateValue($pointer, $fields[$pointer], $value, \InvalidArgumentException::class);
		}

		$this->validateConstraints(
			$this->merge($profile, $overrides),
			$profile['project_rule_schema']['constraints'] ?? [],
			\InvalidArgumentException::class
		);
	}

	/** @param array<string, mixed> $profile @param array<string, mixed> $overrides @return array<string, mixed> */
	public function resolve(array $profile, array $overrides): array
	{
		$this->validateOverrides($profile, $overrides);

		return $this->merge($profile, $overrides);
	}

	/** @param array<string, mixed> $overrides */
	public function checksum(array $overrides): string
	{
		return CanonicalJson::checksum($overrides);
	}

	/** @param array<string, mixed> $value @return array<string, mixed> */
	private function flatten(array $value, string $pointer = ''): array
	{
		$flat = [];

		foreach ($value as $key => $child) {
			if (!is_string($key)) {
				throw new \InvalidArgumentException('Project-rule override objects require string keys.');
			}

			$childPointer = $pointer . '/' . str_replace(['~', '/'], ['~0', '~1'], $key);

			if (is_array($child) && !array_is_list($child) && $child !== []) {
				$flat += $this->flatten($child, $childPointer);
				continue;
			}

			$flat[$childPointer] = $child;
		}

		return $flat;
	}

	/** @param array<string, mixed> $source */
	private function readPointer(array $source, string $pointer): mixed
	{
		$value = $source;

		foreach (explode('/', substr($pointer, 1)) as $segment) {
			$key = str_replace(['~1', '~0'], ['/', '~'], $segment);

			if (!is_array($value) || !array_key_exists($key, $value)) {
				throw new \UnexpectedValueException(sprintf('Project-rule field "%s" does not exist in the profile.', $pointer));
			}

			$value = $value[$key];
		}

		return $value;
	}

	/** @param array<string, mixed> $definition @param class-string<\Throwable> $exception */
	private function validateValue(string $pointer, array $definition, mixed $value, string $exception): void
	{
		$type = $definition['type'];
		$valid = match ($type) {
			'boolean' => is_bool($value),
			'integer' => is_int($value),
			'number' => is_int($value) || is_float($value),
			'string' => is_string($value),
			'array' => is_array($value) && array_is_list($value),
			default => false,
		};

		if (!$valid) {
			throw new $exception(sprintf('Project-rule field "%s" must be %s.', $pointer, $type));
		}

		if (isset($definition['enum']) && (!is_array($definition['enum']) || !in_array($value, $definition['enum'], true))) {
			throw new $exception(sprintf('Project-rule field "%s" contains an unsupported value.', $pointer));
		}

		if ((is_int($value) || is_float($value)) && (
			(isset($definition['minimum']) && $value < $definition['minimum'])
			|| (isset($definition['maximum']) && $value > $definition['maximum'])
		)) {
			throw new $exception(sprintf('Project-rule field "%s" is outside its allowed range.', $pointer));
		}

		if (is_string($value) && isset($definition['pattern']) && preg_match($definition['pattern'], $value) !== 1) {
			throw new $exception(sprintf('Project-rule field "%s" has an invalid format.', $pointer));
		}

		if (is_string($value) && strlen($value) > ($definition['max_length'] ?? 255)) {
			throw new $exception(sprintf('Project-rule field "%s" exceeds its maximum length.', $pointer));
		}

		if ($type === 'array') {
			$count = count($value);

			if ((isset($definition['min_items']) && $count < $definition['min_items']) || (isset($definition['max_items']) && $count > $definition['max_items'])) {
				throw new $exception(sprintf('Project-rule field "%s" has an invalid item count.', $pointer));
			}

			foreach ($value as $item) {
				$itemType = $definition['items']['type'] ?? null;
				$itemValid = match ($itemType) {
					'integer' => is_int($item),
					'number' => is_int($item) || is_float($item),
					'string' => is_string($item),
					default => false,
				};

				if (!$itemValid) {
					throw new $exception(sprintf('Project-rule field "%s" contains an invalid item.', $pointer));
				}

				if (is_string($item) && strlen($item) > ($definition['items']['max_length'] ?? 191)) {
					throw new $exception(sprintf('Project-rule field "%s" contains an oversized item.', $pointer));
				}
			}
		}
	}

	/** @param array<string, array<string, mixed>> $fields */
	private function validateConstraintDefinition(mixed $constraint, array $fields): void
	{
		if (!is_array($constraint)
			|| preg_match('/^[a-z][a-z0-9_]*$/', (string) ($constraint['code'] ?? '')) !== 1
			|| !in_array($constraint['operator'] ?? null, ['eq', 'lte', 'gte'], true)
		) {
			throw new \UnexpectedValueException('Project-rule schema contains an invalid relational constraint.');
		}

		foreach (['left', 'right'] as $side) {
			$expression = $constraint[$side] ?? null;

			if (!is_array($expression) || !is_array($expression['terms'] ?? null) || !array_is_list($expression['terms']) || $expression['terms'] === [] || count($expression['terms']) > 10) {
				throw new \UnexpectedValueException(sprintf('Project-rule constraint "%s" has an invalid %s expression.', $constraint['code'], $side));
			}

			if (isset($expression['constant']) && !is_int($expression['constant']) && !is_float($expression['constant'])) {
				throw new \UnexpectedValueException(sprintf('Project-rule constraint "%s" has an invalid constant.', $constraint['code']));
			}

			foreach ($expression['terms'] as $term) {
				$pointer = is_array($term) ? ($term['path'] ?? null) : null;
				$factor = is_array($term) ? ($term['factor'] ?? 1) : null;

				if (!is_string($pointer)
					|| !isset($fields[$pointer])
					|| !in_array($fields[$pointer]['type'] ?? null, ['integer', 'number'], true)
					|| (!is_int($factor) && !is_float($factor))
				) {
					throw new \UnexpectedValueException(sprintf('Project-rule constraint "%s" contains an invalid term.', $constraint['code']));
				}
			}
		}
	}

	/** @param list<array<string, mixed>> $constraints @param class-string<\Throwable> $exception */
	private function validateConstraints(array $values, array $constraints, string $exception): void
	{
		foreach ($constraints as $constraint) {
			$left = $this->evaluateExpression($values, $constraint['left']);
			$right = $this->evaluateExpression($values, $constraint['right']);
			$valid = match ($constraint['operator']) {
				'eq' => abs($left - $right) < 0.000000001,
				'lte' => $left <= $right,
				'gte' => $left >= $right,
				default => false,
			};

			if (!$valid) {
				throw new $exception(sprintf('Project-rule constraint "%s" is not satisfied.', $constraint['code']));
			}
		}
	}

	/** @param array<string, mixed> $values @param array<string, mixed> $expression */
	private function evaluateExpression(array $values, array $expression): float
	{
		$result = (float) ($expression['constant'] ?? 0);

		foreach ($expression['terms'] as $term) {
			$result += (float) $this->readPointer($values, $term['path']) * (float) ($term['factor'] ?? 1);
		}

		return $result;
	}

	private function isPointer(string $pointer): bool
	{
		return preg_match('#^/(?:[^~/]|~[01])+(?:/(?:[^~/]|~[01])+)*$#', $pointer) === 1;
	}

	/** @param array<string, mixed> $base @param array<string, mixed> $override @return array<string, mixed> */
	private function merge(array $base, array $override): array
	{
		foreach ($override as $key => $value) {
			if (is_array($value) && !array_is_list($value) && $value !== [] && is_array($base[$key] ?? null)) {
				$base[$key] = $this->merge($base[$key], $value);
				continue;
			}

			$base[$key] = $value;
		}

		return $base;
	}
}
