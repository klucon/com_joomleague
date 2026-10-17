<?php
declare(strict_types=1);

// Run only on the backed-up local fixture, before and after package installation.
if (getenv('JL_LANGUAGE_UPGRADE_TEST') !== '1') throw new RuntimeException('Local fixture opt-in required.');
$root = '/var/www/html';
$statePath = '/tmp/jl-language-upgrade-state.json';
$phase = $argv[1] ?? '';
$sourceRoot = getenv('JL_LANGUAGE_SOURCE');
if (!$sourceRoot || !is_dir($sourceRoot)) throw new RuntimeException('Package source directory required.');
$manifest = simplexml_load_file($sourceRoot . '/administrator/components/com_joomleague/joomleague.xml');
$files = [];
foreach (['site' => $manifest->languages, 'admin' => $manifest->administration->languages] as $client => $languages) {
    $base = $root . ($client === 'admin' ? '/administrator' : '');
    foreach ($languages->language ?? [] as $language) {
        $files[$base . '/language/' . (string) $language['tag'] . '/' . basename((string) $language)] = $sourceRoot . ($client === 'admin' ? '/administrator' : '') . '/components/com_joomleague/' . (string) $language;
    }
}
if ($phase === 'seed') {
    if (is_file($statePath)) throw new RuntimeException('Existing fixture state must be restored first.');
    $overrides = [];
    foreach ([$root, $root . '/administrator'] as $base) {
        $path = $base . '/language/overrides/en-GB.override.ini';
        $overrides[$path] = is_file($path) ? file_get_contents($path) : null;
    }
    file_put_contents($statePath, json_encode($overrides, JSON_THROW_ON_ERROR));
    foreach ($files as $target => $source) {
        if (!is_dir(dirname($target))) mkdir(dirname($target), 0775, true);
        file_put_contents($target, "COM_JOOMLEAGUE=\"Stale fixture\"\n");
    }
    foreach ($overrides as $path => $original) {
        if (!is_dir(dirname($path))) mkdir(dirname($path), 0775, true);
        file_put_contents($path, ($original ?? '') . "\nCOM_JOOMLEAGUE_LANGUAGE_UPGRADE_TEST=\"Preserve user override\"\n");
    }
    echo "Seeded stale site/admin translations and user overrides.\n";
} elseif ($phase === 'verify') {
    $overrides = json_decode(file_get_contents($statePath), true, 512, JSON_THROW_ON_ERROR);
    try {
        foreach ($files as $target => $source) {
            if (!is_file($target) || hash_file('sha256', $source) !== hash_file('sha256', $target)) throw new RuntimeException('Language not replaced: ' . $target);
        }
        foreach ($overrides as $path => $original) if (file_get_contents($path) !== ($original ?? '') . "\nCOM_JOOMLEAGUE_LANGUAGE_UPGRADE_TEST=\"Preserve user override\"\n") throw new RuntimeException('User override modified: ' . $path);
        echo 'PASS: ' . count($files) . " site/admin runtime, menu and tour files replaced; user overrides preserved.\n";
    } finally {
        foreach ($overrides as $path => $original) {
            if ($original === null) unlink($path); else file_put_contents($path, $original);
        }
        unlink($statePath);
    }
} else throw new RuntimeException('Use seed or verify.');
