<?php

declare(strict_types=1);

define('_JEXEC', 1);
$root = dirname(__DIR__, 2);
foreach (['StandingsContractValidator', 'RoleDomain', 'SportProfileSchemaValidator', 'CanonicalJson', 'ExactDecimal', 'ProjectRuleValidator', 'SportVariantCompiler'] as $service) {
	require_once $root . '/administrator/components/com_joomleague/src/Service/' . $service . '.php';
}
$base = json_decode(file_get_contents($root . '/administrator/components/com_joomleague/resources/sport-profiles/basketball.json'), true, 512, JSON_THROW_ON_ERROR);
$original = $base;
$compiler = new Joomleague\Component\Joomleague\Administrator\Service\SportVariantCompiler();
$rules = ['match_structure' => ['period_count' => 2, 'period_length_minutes' => 8], 'lineup' => ['players_on_field' => 4]];
$result = $compiler->compile($base, $rules);
$profile = $result['profile'];
if ($base !== $original || $profile['match']['structure'] !== $profile['match_structure']
	|| $profile['match']['structure']['period_length_minutes'] !== 8
	|| $profile['lineup']['players_on_field'] !== 4) throw new RuntimeException('Variant rules were not compiled immutably.');
$periods = array_column($profile['match']['score']['segment_types'], null, 'code');
if ($periods['period']['expected_count'] !== 2) throw new RuntimeException('Period score topology is stale.');
if ($result !== $compiler->compile($base, array_reverse($rules, true))) throw new RuntimeException('Compilation is not deterministic.');
if ($result['base_checksum'] === $result['payload_checksum']) throw new RuntimeException('Variant checksum did not change.');
if ($compiler->compile($base, [])['profile'] !== $base) throw new RuntimeException('Empty variant changed the contract.');
foreach ([['unknown' => true], ['lineup' => ['players_on_field' => 0]], ['standings' => ['points_regular' => [3, 1, 0]]]] as $invalid) {
	try {
		$compiler->compile($base, $invalid);
	} catch (InvalidArgumentException) {
		continue;
	}
	throw new RuntimeException('Unsupported variant was accepted.');
}
$inconsistent = $base;
$football = json_decode(file_get_contents($root . '/administrator/components/com_joomleague/resources/sport-profiles/football.json'), true, 512, JSON_THROW_ON_ERROR);
$short = $compiler->compile($football, ['match_structure' => ['period_count' => 1, 'extra_time' => ['enabled' => false]]])['profile'];
if ($short['match_structure']['breaks'] !== [] || in_array('extra_time', array_column($short['match']['score']['segment_types'], 'code'), true)
	|| in_array('extra_time', $short['match']['score']['aggregation']['from'], true)) throw new RuntimeException('Disabled segments or obsolete breaks survived compilation.');
$overtime = $compiler->compile($football, ['match_structure' => ['extra_time' => ['period_count' => 1]]])['profile'];
$segments = array_column($overtime['match']['score']['segment_types'], null, 'code');
if ($segments['extra_time']['expected_count'] !== 1) throw new RuntimeException('Overtime score topology is stale.');
$inconsistent['match_structure']['period_count'] = 3;
try {
	$compiler->compile($inconsistent, $rules);
} catch (InvalidArgumentException) {
	echo "Sport variant compiler OK: immutable contracts, executable structure, topology, checksums and rejected unsupported rules.\n";
	exit(0);
}
throw new RuntimeException('Inconsistent base structures were accepted.');
