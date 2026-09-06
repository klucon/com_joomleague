<?php

declare(strict_types=1);

$root = dirname(__DIR__, 2);
$site = $root . '/components/com_joomleague';
$viewRoot = $site . '/src/View';
$modelRoot = $site . '/src/Model';
$templateRoot = $site . '/tmpl';
$languageFile = $site . '/language/en-GB/com_joomleague.ini';

$languageContents = (string) file_get_contents($languageFile);
preg_match_all('/^([A-Z][A-Z0-9_]+)=/m', $languageContents, $languageMatches);
$languageKeys = array_fill_keys($languageMatches[1] ?? [], true);
$views = glob($viewRoot . '/*', GLOB_ONLYDIR) ?: [];

if ($views === []) {
    throw new RuntimeException('No public component views were found.');
}

$verified = [];

foreach ($views as $viewDirectory) {
    $viewName = basename($viewDirectory);
    $layoutName = strtolower($viewName);
    $requiredFiles = [
        $viewDirectory . '/HtmlView.php',
        $modelRoot . '/' . $viewName . 'Model.php',
        $templateRoot . '/' . $layoutName . '/default.php',
        $templateRoot . '/' . $layoutName . '/default.xml',
    ];

    foreach ($requiredFiles as $requiredFile) {
        if (!is_file($requiredFile)) {
            throw new RuntimeException(sprintf('Public view %s is missing %s.', $viewName, $requiredFile));
        }
    }

    $xmlContents = (string) file_get_contents($requiredFiles[3]);

    if (!str_contains($xmlContents, '<metadata>') || !preg_match('/<layout\s+title="COM_JOOMLEAGUE_[A-Z0-9_]+"/', $xmlContents)) {
        throw new RuntimeException(sprintf('Public view %s has an invalid Joomla menu metadata contract.', $viewName));
    }

    preg_match_all('/COM_JOOMLEAGUE_[A-Z0-9_]+/', $xmlContents, $xmlLanguageMatches);

    foreach (array_unique($xmlLanguageMatches[0] ?? []) as $languageKey) {
        if (!isset($languageKeys[$languageKey])) {
            throw new RuntimeException(sprintf(
                'Public menu XML %s uses undefined site language key %s.',
                $requiredFiles[3],
                $languageKey,
            ));
        }
    }

    $verified[] = $layoutName;
}

$templateDirectories = glob($templateRoot . '/*', GLOB_ONLYDIR) ?: [];
$templateNames = array_map('basename', $templateDirectories);
sort($templateNames);
sort($verified);

if ($templateNames !== $verified) {
    throw new RuntimeException('Public view and template directory inventories differ.');
}

printf("Frontend contract OK: %d public views and menu types.\n", count($verified));

$packageManifest = (string) file_get_contents($root . '/build/pkg_joomleague.xml');
$moduleDirectories = glob($root . '/modules/mod_joomleague_*', GLOB_ONLYDIR) ?: [];

if ($moduleDirectories === []) {
    throw new RuntimeException('No JoomLeague site modules were found.');
}

foreach ($moduleDirectories as $moduleDirectory) {
    $moduleName = basename($moduleDirectory);
    foreach ([
        $moduleDirectory . '/' . $moduleName . '.xml',
        $moduleDirectory . '/services/provider.php',
        $moduleDirectory . '/src/Dispatcher/Dispatcher.php',
        $moduleDirectory . '/tmpl/default.php',
        $moduleDirectory . '/language/en-GB/' . $moduleName . '.ini',
        $moduleDirectory . '/language/cs-CZ/' . $moduleName . '.ini',
    ] as $requiredFile) {
        if (!is_file($requiredFile)) {
            throw new RuntimeException(sprintf('Site module %s is missing %s.', $moduleName, $requiredFile));
        }
    }

    if (!str_contains($packageManifest, sprintf('id="%s"', $moduleName))) {
        throw new RuntimeException(sprintf('Site module %s is missing from the package manifest.', $moduleName));
    }
}

printf("Module contract OK: %d packaged site modules.\n", count($moduleDirectories));
