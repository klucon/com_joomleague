<?php

declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Domain\Service;

defined('_JEXEC') or die;

final class CanonicalJson
{
	/** @param array<string, mixed> $value */
	public static function encodeObject(array $value): string
	{
		if (array_is_list($value) && $value !== []) {
			throw new \InvalidArgumentException('Canonical JSON root must be an object.');
		}

		return json_encode(
			self::sort($value),
			JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRESERVE_ZERO_FRACTION
		);
	}

	/** @param array<string, mixed> $value */
	public static function checksum(array $value): string
	{
		return hash('sha256', self::encodeObject($value));
	}

	private static function sort(mixed $value): mixed
	{
		if (!is_array($value)) {
			return $value;
		}

		if (array_is_list($value)) {
			return array_map(self::sort(...), $value);
		}

		ksort($value, SORT_STRING);

		foreach ($value as $key => $child) {
			$value[$key] = self::sort($child);
		}

		return $value;
	}
}
