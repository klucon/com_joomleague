<?php

declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Domain\Service;

defined('_JEXEC') or die;

/** Formats canonical database decimals without losing significant integer zeroes. */
final class DecimalDisplayFormatter
{
	public static function format(mixed $value): string
	{
		$value = trim((string) $value);

		if (preg_match('/^-?\d+(?:\.\d+)?$/', $value) !== 1) {
			return '';
		}

		if (str_contains($value, '.')) {
			$value = rtrim(rtrim($value, '0'), '.');
		}

		return $value === '' || preg_match('/^-?0$/', $value) === 1 ? '0' : $value;
	}
}
