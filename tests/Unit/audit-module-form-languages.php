<?php
declare(strict_types=1);
$root = dirname(__DIR__, 2);
$issues = [];
$counts = [];
foreach (glob($root . '/modules/mod_joomleague_*', GLOB_ONLYDIR) as $directory) {
    $module = basename($directory);
    $xml = simplexml_load_file($directory . '/' . $module . '.xml', SimpleXMLElement::class, LIBXML_NONET);
    if (!$xml) throw new RuntimeException('Invalid module manifest: ' . $module);
    $keys = [];
    $names = [];
    foreach ($xml->xpath('//config/fields/fieldset/field') as $field) {
        $name = (string) $field['name'];
        if (isset($names[$name])) $issues[] = $module . ': duplicate parameter ' . $name;
        $names[$name] = true;
    }
    foreach ($xml->xpath('//config//@label | //config//@description | //config//option') as $value) {
        $key = trim((string) $value);
        if (str_starts_with($key, 'MOD_JOOMLEAGUE_')) $keys[$key] = true;
    }
    foreach (['en-GB', 'cs-CZ'] as $language) {
        $strings = [];
        foreach (glob($directory . '/language/' . $language . '/*.ini') as $file) {
            $parsed = parse_ini_file($file, false, INI_SCANNER_RAW);
            if ($parsed === false) throw new RuntimeException('Invalid language INI: ' . $file);
            $strings += $parsed;
        }
        foreach ($keys as $key => $_) if (!isset($strings[$key]) || trim($strings[$key]) === '') $issues[] = $module . ': ' . $language . ' missing ' . $key;
    }
    $counts[$module] = ['parameters' => count($names), 'languageKeys' => count($keys)];
}
echo json_encode(['scope' => 'source manifest fields and referenced language labels; not save/render acceptance', 'modules' => $counts, 'issues' => $issues], JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR) . "\n";
exit($issues ? 1 : 0);
