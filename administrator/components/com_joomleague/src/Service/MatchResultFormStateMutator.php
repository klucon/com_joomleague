<?php

declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Administrator\Service;

defined('_JEXEC') or die;

final class MatchResultFormStateMutator
{
	/** @param array<string,mixed> $payload @param array<string,mixed> $schema @return array<string,mixed> */
	public function add(array $payload, array $schema, string $parentLocator, string $segmentCode): array
	{
		$type = $this->segmentType($schema, $segmentCode);

		if (($type['repeatable'] ?? false) !== true || isset($type['expected_count'])) {
			throw new \InvalidArgumentException('The score segment cannot be added manually.');
		}

		$parent =& $this->locate($payload, $parentLocator);
		$expectedParent = (string) (($type['parent_code'] ?? null) ?: 'result');

		if (($parent['level_code'] ?? null) !== $expectedParent) {
			throw new \InvalidArgumentException('The score segment parent does not match the sport profile.');
		}

		$sequences = [];

		foreach ($parent['children'] ?? [] as $child) {
			if (is_array($child) && ($child['level_code'] ?? null) === $segmentCode) {
				$sequences[] = (int) ($child['sequence_number'] ?? 0);
			}
		}

		if (isset($type['maximum_count']) && count($sequences) >= (int) $type['maximum_count']) {
			throw new \LengthException('The score segment maximum has been reached.');
		}

		$parent['children'][] = [
			'level_code' => $segmentCode,
			'sequence_number' => $sequences === [] ? 1 : max($sequences) + 1,
			'status_code' => 'completed',
			'metadata' => [],
			'values' => [],
			'children' => [],
		];

		return $payload;
	}

	/** @param array<string,mixed> $payload @param array<string,mixed> $schema @return array<string,mixed> */
	public function remove(array $payload, array $schema, string $locator): array
	{
		$parts = $this->locatorParts($locator);

		if (count($parts) < 2) throw new \InvalidArgumentException('The root score segment cannot be removed.');

		[$segmentCode, $sequence] = array_pop($parts);
		$type = $this->segmentType($schema, $segmentCode);

		if (($type['repeatable'] ?? false) !== true || isset($type['expected_count'])) {
			throw new \InvalidArgumentException('The score segment cannot be removed manually.');
		}

		$parentLocator = implode('/', array_map(static fn (array $part): string => $part[0] . ':' . $part[1], $parts));
		$parent =& $this->locate($payload, $parentLocator);
		$removed = false;

		foreach ($parent['children'] ?? [] as $index => $child) {
			if (is_array($child) && ($child['level_code'] ?? null) === $segmentCode && (int) ($child['sequence_number'] ?? 0) === $sequence) {
				unset($parent['children'][$index]);
				$removed = true;
				break;
			}
		}

		if (!$removed) throw new \InvalidArgumentException('The selected score segment does not exist.');

		$parent['children'] = array_values($parent['children']);

		return $payload;
	}

	/** @param array<string,mixed> $payload @return array<string,mixed> */
	private function &locate(array &$payload, string $locator): array
	{
		$parts = $this->locatorParts($locator);
		$segment =& $payload['segments'][0];
		$root = array_shift($parts);

		if (!is_array($segment) || $root !== ['result', 1] || ($segment['level_code'] ?? null) !== 'result') {
			throw new \InvalidArgumentException('The score segment locator has an invalid root.');
		}

		foreach ($parts as [$code, $sequence]) {
			$found = null;

			foreach ($segment['children'] ?? [] as $index => $child) {
				if (is_array($child) && ($child['level_code'] ?? null) === $code && (int) ($child['sequence_number'] ?? 0) === $sequence) {
					$found = $index;
					break;
				}
			}

			if ($found === null) throw new \InvalidArgumentException('The selected score segment does not exist.');

			$segment =& $segment['children'][$found];
		}

		return $segment;
	}

	/** @return list<array{0:string,1:int}> */
	private function locatorParts(string $locator): array
	{
		if ($locator === '' || strlen($locator) > 1000) throw new \InvalidArgumentException('The score segment locator is invalid.');
		$parts = [];

		foreach (explode('/', $locator) as $part) {
			if (preg_match('/^([a-z][a-z0-9_]{0,99}):([1-9][0-9]{0,8})$/', $part, $match) !== 1) {
				throw new \InvalidArgumentException('The score segment locator is invalid.');
			}

			$parts[] = [$match[1], (int) $match[2]];
		}

		return $parts;
	}

	/** @param array<string,mixed> $schema @return array<string,mixed> */
	private function segmentType(array $schema, string $code): array
	{
		foreach ($schema['segment_types'] ?? [] as $type) {
			if (is_array($type) && ($type['code'] ?? null) === $code) return $type;
		}

		throw new \InvalidArgumentException('The score segment is not defined by the sport profile.');
	}
}
