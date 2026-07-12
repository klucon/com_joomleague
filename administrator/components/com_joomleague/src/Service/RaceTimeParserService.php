<?php
/**
 * @package     JoomLeague
 * @copyright   Copyright (C) 2026 Ondřej Klučka (https://klucon.cz). All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */


declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Administrator\Service;

\defined('_JEXEC') or die;

final class RaceTimeParserService
{
	public function parseToMilliseconds(?string $value): ?int
	{
		$value = trim((string) $value);

		if ($value === '') {
			return null;
		}

		if (preg_match('/^\d+$/', $value) === 1) {
			return (int) $value;
		}

		if (preg_match('/^(?:(\d+):)?(\d{1,2}):(\d{2})(?:[.,](\d{1,3}))?$/', $value, $matches) !== 1) {
			return null;
		}

		$hours = isset($matches[1]) && $matches[1] !== '' ? (int) $matches[1] : 0;
		$minutes = (int) $matches[2];
		$seconds = (int) $matches[3];
		$milliseconds = isset($matches[4]) && $matches[4] !== '' ? (int) str_pad(substr($matches[4], 0, 3), 3, '0') : 0;

		return (($hours * 3600) + ($minutes * 60) + $seconds) * 1000 + $milliseconds;
	}

	public function formatMilliseconds(?int $milliseconds): string
	{
		if ($milliseconds === null || $milliseconds < 0) {
			return '';
		}

		$totalSeconds = intdiv($milliseconds, 1000);
		$ms = $milliseconds % 1000;
		$hours = intdiv($totalSeconds, 3600);
		$minutes = intdiv($totalSeconds % 3600, 60);
		$seconds = $totalSeconds % 60;

		$time = $hours > 0
			? sprintf('%d:%02d:%02d', $hours, $minutes, $seconds)
			: sprintf('%d:%02d', $minutes, $seconds);

		return $ms > 0 ? $time . '.' . str_pad((string) $ms, 3, '0', STR_PAD_LEFT) : $time;
	}
}
