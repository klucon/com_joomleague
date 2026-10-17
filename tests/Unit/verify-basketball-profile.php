<?php

declare(strict_types=1);

define('_JEXEC', 1);
$root = dirname(__DIR__, 2);
foreach (['StandingsContractValidator', 'RoleDomain', 'SportProfileSchemaValidator', 'CanonicalJson', 'ProjectRuleValidator'] as $service) {
    require_once $root . '/administrator/components/com_joomleague/src/Service/' . $service . '.php';
}
$profile = json_decode(file_get_contents($root . '/administrator/components/com_joomleague/resources/sport-profiles/basketball.json'), true, 512, JSON_THROW_ON_ERROR);
(new Joomleague\Component\Joomleague\Administrator\Service\SportProfileSchemaValidator())->validate($profile);
$rules = new Joomleague\Component\Joomleague\Administrator\Service\ProjectRuleValidator();
$rules->validateProfileSchema($profile);
$rules->validateOverrides($profile, ['lineup' => ['players_on_field' => 5, 'captain_supported' => true]]);
$statistics = array_column($profile['statistics'], null, 'code');
$positions = array_column($profile['positions'], null, 'code');
$events = array_column($profile['event_types'], null, 'code');
if ($profile['version'] !== '1.7.0' || $profile['schema_version'] !== '1.5.0') throw new RuntimeException('Profile and schema versions are incorrect.');
if ($profile['lineup']['players_on_field'] !== 5 || !$profile['lineup']['captain_supported']) throw new RuntimeException('Starting five or captain support is missing.');
if ($profile['match_structure'] !== $profile['match']['structure']) throw new RuntimeException('Match structures are inconsistent.');
foreach (['one_point' => 1, 'two_points' => 2, 'three_points' => 3] as $code => $points) {
    if ($events[$code]['points'] !== $points || !$events[$code]['affects_score']) throw new RuntimeException('Historical scoring event changed: ' . $code);
}
foreach (['free_throw_missed', 'two_points_missed', 'three_points_missed', 'offensive_rebound', 'defensive_rebound', 'assist', 'steal', 'block', 'turnover', 'unsportsmanlike_foul', 'disqualifying_foul'] as $code) {
    if (!isset($events[$code]) || !$events[$code]['timeline'] || $events[$code]['affects_score']) throw new RuntimeException('Missing or incorrectly scoring event: ' . $code);
}
foreach ($events as $event) {
    if (isset($event['statistic_code']) && !isset($statistics[$event['statistic_code']])) throw new RuntimeException('Unknown event statistic.');
}
foreach ($profile['lineup']['preferred_order'] as $code) {
    if (($positions[$code]['role_domain'] ?? '') !== 'participant') throw new RuntimeException('Lineup order contains a non-player role.');
}
foreach (['plus_minus', 'efficiency', 'performance_index_rating', 'effective_field_goal_percentage', 'true_shooting_percentage'] as $code) {
    if ($statistics[$code]['source'] !== 'manual_or_import') throw new RuntimeException('An unimplemented automatic calculation was advertised.');
}
foreach (['effective_field_goal_percentage', 'true_shooting_percentage'] as $code) {
    if ($statistics[$code]['value_type'] !== 'decimal') throw new RuntimeException('Weighted shooting metrics must allow values exceeding 100.');
}
foreach (['en-GB', 'cs-CZ'] as $locale) {
    $source = file_get_contents($root . '/administrator/components/com_joomleague/language/' . $locale . '/com_joomleague.ini');
    preg_match_all('/^(COM_JOOMLEAGUE_PROFILE_BASKETBALL_[A-Z0-9_]+)=/m', $source, $matches);
    if (count($matches[1]) !== count(array_unique($matches[1]))) throw new RuntimeException('Duplicate basketball language constant: ' . $locale);
    $language = parse_ini_string($source, false, INI_SCANNER_RAW);
    foreach (array_merge($profile['positions'], $profile['event_types'], $profile['statistics']) as $item) {
        foreach (['name_key', 'abbreviation_key'] as $field) {
            if (isset($item[$field]) && empty($language[$item[$field]])) throw new RuntimeException('Missing translation: ' . $locale . ' ' . $item[$field]);
        }
    }
}
printf("Basketball profile OK: %d roles, %d events, %d statistics, English/Czech translations\n", count($positions), count($events), count($statistics));
