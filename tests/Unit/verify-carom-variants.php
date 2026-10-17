<?php
declare(strict_types=1);
define('_JEXEC', 1);
$root = dirname(__DIR__, 2);
foreach (['StandingsContractValidator', 'RoleDomain', 'SportProfileSchemaValidator', 'CanonicalJson', 'ExactDecimal', 'ProjectRuleValidator', 'SportVariantCompiler', 'SportVariantRules'] as $service) require_once $root . '/administrator/components/com_joomleague/src/Service/' . $service . '.php';
use Joomleague\Component\Joomleague\Administrator\Service\SportVariantCompiler;
use Joomleague\Component\Joomleague\Administrator\Service\SportVariantRules;
use Joomleague\Component\Joomleague\Administrator\Service\ProjectRuleValidator;
$base = json_decode(file_get_contents($root . '/administrator/components/com_joomleague/resources/sport-profiles/carom.json'), true, 512, JSON_THROW_ON_ERROR);
$compiler = new SportVariantCompiler();
$overrides = ['match' => ['encounter' => ['game_rules' => ['games_per_encounter' => 2, 'target_score' => 30, 'inning_limit' => 50, 'equal_innings' => false]]]];
$compiled = $compiler->compile($base, $overrides);
if ($compiled['profile']['match']['encounter']['game_rules'] !== $overrides['match']['encounter']['game_rules'] || $base['match']['encounter']['game_rules']['target_score'] !== 40) throw new RuntimeException('Carom variant failed or changed its base.');
$rules = new SportVariantRules();
$fields = $rules->fields($base);
if (count($fields) !== 8) throw new RuntimeException('Missing carom variant fields.');
$submitted = [];
foreach ($fields as $key => $field) if ($field['pointer'] === '/match/encounter/game_rules/target_score') $submitted[$key] = ['enabled' => '1', 'value' => '30'];
if (($rules->parse($base, $submitted)['match']['encounter']['game_rules']['target_score'] ?? null) !== 30) throw new RuntimeException('Variant form did not parse target score.');
foreach ([['win' => 1, 'loss' => 1], ['draw' => 3], ['loss' => 2]] as $awards) {
    try { (new ProjectRuleValidator())->resolve($base, ['match' => ['encounter' => ['duel_points' => $awards]]]); }
    catch (InvalidArgumentException) { continue; }
    throw new RuntimeException('Invalid project awards were accepted.');
}
foreach (['en-GB', 'cs-CZ'] as $locale) {
    $language = parse_ini_file($root . '/administrator/components/com_joomleague/language/' . $locale . '/com_joomleague.ini', false, INI_SCANNER_RAW);
    foreach ($fields as $field) if (!isset($language[$field['label']])) throw new RuntimeException('Missing carom variant translation: ' . $locale . ' ' . $field['label']);
}
echo "Carom variants OK: native form fields, immutable compilation, rule bounds and both languages.\n";
