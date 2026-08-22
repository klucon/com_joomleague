<?php

declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Administrator\Service;

defined('_JEXEC') or die;

final class TemplateConfigResolver
{
	public function __construct(private readonly TemplateDefinitionRegistry $registry)
	{
	}

	/**
	 * Later layers take precedence. Lists are replaced as complete values; only
	 * associative objects are merged recursively.
	 *
	 * @param array<string, mixed> ...$layers
	 * @return array<string, mixed>
	 */
	public function resolve(string $templateCode, array ...$layers): array
	{
		$definition = $this->registry->get($templateCode);
		$result = $definition['defaults'];

		foreach ($layers as $layer) {
			$this->registry->validateValues($templateCode, $layer);
			$result = $this->merge($result, $layer);
		}

		return $result;
	}

	/**
	 * @param array<string, mixed> $profilePayload
	 * @param array<string, mixed> $profileOverrides
	 * @param array<string, mixed> $projectOverrides
	 * @param array<string, mixed> $presentationOverrides
	 * @return array<string, mixed>
	 */
	public function resolveProfileTemplate(
		string $templateCode,
		array $profilePayload,
		array $profileOverrides = [],
		array $projectOverrides = [],
		array $presentationOverrides = []
	): array {
		$profileDefaults = $profilePayload['template_defaults'][$templateCode] ?? [];

		if (!is_array($profileDefaults)) {
			throw new \UnexpectedValueException(sprintf('Profile defaults for "%s" must be an object.', $templateCode));
		}

		return $this->resolve(
			$templateCode,
			$profileDefaults,
			$profileOverrides,
			$projectOverrides,
			$presentationOverrides
		);
	}

	/** @param array<string, mixed> $base @param array<string, mixed> $override @return array<string, mixed> */
	private function merge(array $base, array $override): array
	{
		foreach ($override as $key => $value) {
			if (is_array($value) && is_array($base[$key] ?? null) && !array_is_list($value) && !array_is_list($base[$key])) {
				$base[$key] = $this->merge($base[$key], $value);
				continue;
			}

			$base[$key] = $value;
		}

		return $base;
	}
}
