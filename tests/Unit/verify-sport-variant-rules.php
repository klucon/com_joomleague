<?php
declare(strict_types=1);
define('_JEXEC', 1);
$root = dirname(__DIR__, 2);
foreach (['StandingsContractValidator', 'RoleDomain', 'SportProfileSchemaValidator', 'CanonicalJson', 'ExactDecimal', 'ProjectRuleValidator', 'SportVariantCompiler', 'SportVariantRules'] as $service) require_once $root . '/administrator/components/com_joomleague/src/Service/' . $service . '.php';
$rules = new Joomleague\Component\Joomleague\Administrator\Service\SportVariantRules();
$profile = json_decode(file_get_contents($root . '/administrator/components/com_joomleague/resources/sport-profiles/basketball.json'), true, 512, JSON_THROW_ON_ERROR);
$fields = $rules->fields($profile);
$keys = array_column($fields, 'pointer');
if (in_array('/standings/points_regular', $keys, true)) throw new RuntimeException('Unsupported standings fields are exposed.');
$key = array_search('/lineup/players_on_field', array_map(static fn ($field) => $field['pointer'], $fields), true);
if ($rules->parse($profile, [$key => ['enabled' => '1', 'value' => '4']]) !== ['lineup' => ['players_on_field' => 4]]) throw new RuntimeException('Native values were not parsed.');
foreach ([['unknown' => []], [$key => ['enabled' => '1', 'value' => ['4']]], [$key => ['enabled' => '1', 'value' => '0']], [$key => ['enabled' => '1', 'value' => '4.5']]] as $invalid) {
	try { $rules->parse($profile, $invalid); } catch (InvalidArgumentException) { continue; }
	throw new RuntimeException('Invalid native input was accepted.');
}
foreach (['en-GB', 'cs-CZ'] as $locale) {
	$language = parse_ini_file($root . '/administrator/components/com_joomleague/language/' . $locale . '/com_joomleague.ini', false, INI_SCANNER_RAW);
	foreach (glob($root . '/administrator/components/com_joomleague/resources/sport-profiles/*.json') as $path) {
		$base = json_decode(file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
		foreach ($rules->fields($base) as $field) if (empty($language[$field['label']])) throw new RuntimeException('Missing rule translation: ' . $locale . ' ' . $field['label']);
	}
}
echo "Sport variant forms OK: supported fields, strict parsing and English/Czech rule labels.\n";
