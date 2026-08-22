<?php

declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Administrator\Service;

defined('_JEXEC') or die;

final class LegacyVersionParser
{
	/**
	 * Rows must be ordered from the newest source record to the oldest.
	 *
	 * @param list<array<string, mixed>> $rows
	 *
	 * @return array{status: string, version: ?string, family: ?string, evidence: list<string>}
	 */
	public function parse(array $rows): array
	{
		foreach ($rows as $row) {
			$major = $this->integerPart($row['major'] ?? null);
			$minor = $this->integerPart($row['minor'] ?? null);
			$build = $this->integerPart($row['build'] ?? null);

			if ($major === null || $minor === null || $build === null) {
				continue;
			}

			$version = $major . '.' . $minor . '.' . $build;
			$revision = $this->safeSuffix($row['revision'] ?? null);
			$channel = $this->safeSuffix($row['version'] ?? null);

			if ($revision !== '') {
				$version .= '.' . $revision;
			}

			if ($channel !== '') {
				$version .= '-' . $channel;
			}

			return [
				'status' => 'detected',
				'version' => $version,
				'family' => $this->family($major, $minor),
				'evidence' => [
					'major=' . $major,
					'minor=' . $minor,
					'build=' . $build,
					'revision=' . ($revision !== '' ? $revision : '(empty)'),
					'channel=' . ($channel !== '' ? $channel : '(empty)'),
				],
			];
		}

		return [
			'status' => $rows === [] ? 'missing' : 'invalid',
			'version' => null,
			'family' => null,
			'evidence' => [],
		];
	}

	private function integerPart(mixed $value): ?int
	{
		if (is_int($value)) {
			$integer = $value;
		} elseif (is_string($value) && preg_match('/^\d{1,6}$/', $value) === 1) {
			$integer = (int) $value;
		} else {
			return null;
		}

		return $integer >= 0 && $integer <= 999999 ? $integer : null;
	}

	private function safeSuffix(mixed $value): string
	{
		$value = trim((string) $value);

		return preg_match('/^[0-9A-Za-z][0-9A-Za-z._-]{0,63}$/', $value) === 1 ? $value : '';
	}

	private function family(int $major, int $minor): string
	{
		return match (true) {
			$major === 0 => 'joomleague_0_x',
			$major === 1 && $minor <= 5 => 'joomleague_1_5_or_older',
			$major === 1 => 'joomleague_1_6',
			$major === 2 => 'joomleague_2_x',
			$major === 3 => 'joomleague_3_x',
			$major >= 4 && $major <= 6 => 'joomleague_precanonical_modern',
			default => 'joomleague_unknown_family',
		};
	}
}
