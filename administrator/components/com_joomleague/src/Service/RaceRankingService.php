<?php
/**
 * @package     JoomLeague
 * @copyright   Copyright (C) 2026 Ondřej Klučka (https://klucon.cz). All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */


declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Administrator\Service;

\defined('_JEXEC') or die;

final class RaceRankingService
{
	/**
	 * @param array<int, array<string, mixed>> $rows
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public function rank(array $rows): array
	{
		usort(
			$rows,
			static function (array $a, array $b): int {
				$statusA = (string) ($a['status'] ?? 'FINISHED');
				$statusB = (string) ($b['status'] ?? 'FINISHED');

				if ($statusA !== $statusB) {
					return $statusA === 'FINISHED' ? -1 : ($statusB === 'FINISHED' ? 1 : strcmp($statusA, $statusB));
				}

				return ((int) ($a['duration_ms'] ?? PHP_INT_MAX)) <=> ((int) ($b['duration_ms'] ?? PHP_INT_MAX));
			}
		);

		$categoryPlaces = [];
		$sexPlaces = [];
		$overall = 0;

		foreach ($rows as &$row) {
			if (($row['status'] ?? 'FINISHED') !== 'FINISHED') {
				$row['overall_place'] = 0;
				$row['category_place'] = 0;
				$row['sex_place'] = 0;
				continue;
			}

			$overall++;
			$category = (string) ($row['category_id'] ?? '0');
			$sex = (string) ($row['sex'] ?? '');
			$categoryPlaces[$category] = ($categoryPlaces[$category] ?? 0) + 1;
			$sexPlaces[$sex] = ($sexPlaces[$sex] ?? 0) + 1;

			$row['overall_place'] = $overall;
			$row['category_place'] = $categoryPlaces[$category];
			$row['sex_place'] = $sexPlaces[$sex];
		}
		unset($row);

		return $rows;
	}
}
