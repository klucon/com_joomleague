<?php

declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Administrator\Service;

defined('_JEXEC') or die;

final class MatchResultDuration
{
	public static function parse(string $value): ?string
	{
		$value = trim($value);

		if ($value === '') return null;

		$hours = 0;
		$minutes = 0;
		$seconds = 0;
		$fraction = '';

		if (preg_match('/^(\d{1,6}):([0-5]\d):([0-5]\d)(?:\.(\d{1,3}))?$/', $value, $match) === 1) {
			$hours = (int) $match[1];
			$minutes = (int) $match[2];
			$seconds = (int) $match[3];
			$fraction = $match[4] ?? '';
		} elseif (preg_match('/^(\d{1,6}):([0-5]\d)(?:\.(\d{1,3}))?$/', $value, $match) === 1) {
			$minutes = (int) $match[1];
			$seconds = (int) $match[2];
			$fraction = $match[3] ?? '';
		} elseif (preg_match('/^([0-5]?\d)(?:\.(\d{1,3}))?$/', $value, $match) === 1) {
			$seconds = (int) $match[1];
			$fraction = $match[2] ?? '';
		} else {
			throw new \InvalidArgumentException('Duration must use SS.mmm, MM:SS.mmm or H:MM:SS.mmm format.');
		}

		$milliseconds = (($hours * 60 + $minutes) * 60 + $seconds) * 1000;
		$milliseconds += (int) str_pad($fraction, 3, '0');

		return (string) $milliseconds;
	}

	public static function format(int|string|null $milliseconds): string
	{
		if ($milliseconds === null || $milliseconds === '') return '';

		$value = (string) $milliseconds;

		if (preg_match('/^(\d+)(?:\.0+)?$/', $value, $match) !== 1) {
			throw new \InvalidArgumentException('Stored duration must contain whole milliseconds.');
		}

		$integer = ltrim($match[1], '0');
		$integer = $integer === '' ? '0' : $integer;

		if (strlen($integer) > strlen((string) PHP_INT_MAX)
			|| (strlen($integer) === strlen((string) PHP_INT_MAX) && strcmp($integer, (string) PHP_INT_MAX) > 0)) {
			throw new \LengthException('Stored duration exceeds the supported editor range.');
		}

		$total = (int) $integer;
		$hours = intdiv($total, 3600000);
		$minutes = intdiv($total % 3600000, 60000);
		$seconds = intdiv($total % 60000, 1000);
		$fraction = $total % 1000;

		return $hours > 0
			? sprintf('%d:%02d:%02d.%03d', $hours, $minutes, $seconds, $fraction)
			: sprintf('%d:%02d.%03d', $minutes, $seconds, $fraction);
	}
}
