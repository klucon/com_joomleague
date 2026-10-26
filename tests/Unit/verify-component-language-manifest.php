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
        if ($tag !== 'en-GB' || !str_starts_with($path, 'language/' . $tag . '/')) throw new RuntimeException('Only English may be declared in the core manifest.');
        if (isset($declared[$path])) throw new RuntimeException('Duplicate language declaration: ' . $path);
        if (!is_file($directory . '/' . $path)) throw new RuntimeException('Missing language source: ' . $path);
        $declared[$path] = true;
    }
    foreach (glob($directory . '/language/*/*.ini') ?: [] as $file) {
        $relative = substr($file, strlen($directory) + 1);
        $sourceTag = basename(dirname($file));
        if ($sourceTag !== 'en-GB') {
            if (preg_match('/^[a-z]{2,3}-[A-Z]{2}$/D', $sourceTag) !== 1) throw new RuntimeException('Invalid separate translation tag.');
            continue;
        }
        if (!isset($declared[$relative])) throw new RuntimeException('Language file would not be updated globally: ' . $relative);
    }
    if (count($declared) !== ($folder === 'admin' ? 6 : 1)) throw new RuntimeException('Language inventory changed unexpectedly.');
}
echo "Component language manifest OK: bundled runtime/menu/tour languages registered; separate translation sources are not implicitly bundled.\n";
