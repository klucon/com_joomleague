<?php
declare(strict_types=1);

$root = dirname(__DIR__, 2);
$manifest = simplexml_load_file($root . '/administrator/components/com_joomleague/joomleague.xml');
if ($manifest === false || (string) $manifest['method'] !== 'upgrade') throw new RuntimeException('Component must permit replacement during upgrade.');
foreach (['site' => $manifest->languages, 'admin' => $manifest->administration->languages] as $folder => $languages) {
    if ((string) $languages['folder'] !== $folder) throw new RuntimeException('Incorrect language source folder: ' . $folder);
    $directory = $root . ($folder === 'admin' ? '/administrator' : '') . '/components/com_joomleague';
    $declared = [];
    foreach ($languages->language as $language) {
        $path = (string) $language;
        $tag = (string) $language['tag'];
        if (!in_array($tag, ['en-GB', 'cs-CZ'], true) || !str_starts_with($path, 'language/' . $tag . '/')) throw new RuntimeException('Invalid language declaration.');
        if (isset($declared[$path])) throw new RuntimeException('Duplicate language declaration: ' . $path);
        if (!is_file($directory . '/' . $path)) throw new RuntimeException('Missing language source: ' . $path);
        $declared[$path] = true;
    }
    foreach (glob($directory . '/language/*/*.ini') ?: [] as $file) {
        $relative = substr($file, strlen($directory) + 1);
        if (!isset($declared[$relative])) throw new RuntimeException('Language file would not be updated globally: ' . $relative);
    }
    if (count($declared) !== ($folder === 'admin' ? 12 : 2)) throw new RuntimeException('Language inventory changed unexpectedly.');
}
echo "Component language manifest OK: all site/admin runtime, menu and tour languages registered for upgrade.\n";
