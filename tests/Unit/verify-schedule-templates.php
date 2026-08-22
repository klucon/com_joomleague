<?php

declare(strict_types=1);

$directory = dirname(__DIR__, 2) . '/administrator/components/com_joomleague/resources/schedule-templates';
$first = json_decode((string) file_get_contents($directory . '/round-robin-first-half.json'), true, 512, JSON_THROW_ON_ERROR);
$second = json_decode((string) file_get_contents($directory . '/round-robin-second-half.json'), true, 512, JSON_THROW_ON_ERROR);

foreach (glob($directory . '/*.json') ?: [] as $file) {
	json_decode((string) file_get_contents($file), true, 512, JSON_THROW_ON_ERROR);
}

for ($count = 2; $count <= 30; $count++) {
	$key = (string) $count;
	foreach ([$first, $second] as $half) {
		$schedule = $half['tables'][$key]['schedule'] ?? null;
		if (!is_array($schedule) || count($schedule) !== ($count % 2 === 0 ? $count - 1 : $count)) {
			throw new RuntimeException("Invalid round count for {$count} participants.");
		}
		$pairs = [];
		foreach ($schedule as $round) {
			$used = [];
			foreach ($round['matches'] as $match) {
				$home = (int) $match['home']['seed'];
				$away = (int) $match['away']['seed'];
				if ($home === $away || isset($used[$home]) || isset($used[$away])) {
					throw new RuntimeException("Duplicate participant in a {$count}-participant round.");
				}
				$used[$home] = $used[$away] = true;
				$pair = min($home, $away) . ':' . max($home, $away);
				$pairs[$pair] = ($pairs[$pair] ?? 0) + 1;
			}
			if ($count % 2 === 1) {
				$bye = (int) ($round['bye']['seed'] ?? 0);
				if ($bye < 1 || $bye > $count || isset($used[$bye]) || count($used) !== $count - 1) {
					throw new RuntimeException("Invalid bye in a {$count}-participant round.");
				}
			} elseif (count($used) !== $count) {
				throw new RuntimeException("Incomplete {$count}-participant round.");
			}
		}
		if (count($pairs) !== ($count * ($count - 1)) / 2 || max($pairs) !== 1) {
			throw new RuntimeException("Pairs are not unique for {$count} participants.");
		}
	}

	$firstFixtures = [];
	foreach ($first['tables'][$key]['schedule'] as $round) foreach ($round['matches'] as $match) {
		$firstFixtures[(int) $match['home']['seed'] . ':' . (int) $match['away']['seed']] = true;
	}
	foreach ($second['tables'][$key]['schedule'] as $round) foreach ($round['matches'] as $match) {
		if (!isset($firstFixtures[(int) $match['away']['seed'] . ':' . (int) $match['home']['seed']])) {
			throw new RuntimeException("Return fixture is not mirrored for {$count} participants.");
		}
	}
}

echo "Schedule template verification passed (2-30 participants).\n";
