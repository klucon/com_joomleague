<?php

declare(strict_types=1);

$directory = dirname(__DIR__) . '/administrator/components/com_joomleague/resources/sport-profiles';

foreach (glob($directory . '/*.json') ?: [] as $file) {
	$profile = json_decode((string) file_get_contents($file), true, 512, JSON_THROW_ON_ERROR);
	$calculation = $profile['standings']['calculation'] ?? null;

	if (!is_array($calculation)) {
		throw new RuntimeException('Missing standings calculation contract in ' . basename($file));
	}

	if (($calculation['mode'] ?? null) === 'classification') {
		$scopes = [['code' => 'overall', 'filter' => ['type' => 'always']]];
	} else {
		$scopes = [['code' => 'total', 'filter' => ['type' => 'always']]];
		$declared = array_fill_keys(is_array($profile['standings']['scopes'] ?? null) ? $profile['standings']['scopes'] : [], true);

		if (isset($declared['home'])) {
			$scopes[] = ['code' => 'home', 'filter' => ['type' => 'participant_slot', 'value' => 1]];
		}

		if (isset($declared['away'])) {
			$scopes[] = ['code' => 'away', 'filter' => ['type' => 'participant_slot', 'value' => 2]];
		}
	}

	$profile['schema_version'] = '1.4.0';
	$profile['version'] = '1.5.0';
	$profile['standings']['calculation']['scopes'] = $scopes;
	file_put_contents($file, json_encode($profile, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR) . "\n");
}
