<?php
declare(strict_types=1);
define('_JEXEC', 1);
$root = dirname(__DIR__, 2) . '/administrator/components/com_joomleague/';
require $root . 'src/Service/MatchResultValidationException.php';
require $root . 'src/Service/StageEncounterRules.php';
use Joomleague\Component\Joomleague\Administrator\Service\StageEncounterRules;
use Joomleague\Component\Joomleague\Administrator\Service\MatchResultValidationException;
$profile = json_decode(file_get_contents($root . 'resources/sport-profiles/carom.json'), true, 512, JSON_THROW_ON_ERROR);
if (StageEncounterRules::apply($profile, []) !== $profile) throw new RuntimeException('Default must preserve pinned profile.');
foreach ([false, true] as $required) {
    $p = $profile; $p['match']['encounter']['penalty_series']['required'] = $required;
    if (StageEncounterRules::apply($p, ['encounter_decision_policy' => 'inherit']) !== $p) throw new RuntimeException('Inheritance changed project contract.');
    foreach (['allow_draw' => false, 'require_penalties' => true] as $policy => $expected) {
        $resolved = StageEncounterRules::apply($p, ['encounter_decision_policy' => $policy]);
        if ($resolved['match']['encounter']['penalty_series']['required'] !== $expected) throw new RuntimeException('Stage override ignored.');
        $resolved['match']['encounter']['penalty_series']['required'] = $required;
        if ($resolved !== $p) throw new RuntimeException('Stage changed unrelated profile data.');
    }
}
foreach ([null, true, [], 'knockout', 'require_penalties<script>'] as $invalid) {
    try { StageEncounterRules::policy($invalid); throw new RuntimeException('Invalid stage policy accepted.'); }
    catch (MatchResultValidationException $error) { if ($error->getLanguageKey() !== 'COM_JOOMLEAGUE_STAGE_ERROR_DECISION_POLICY') throw $error; }
}
try { StageEncounterRules::apply([], ['encounter_decision_policy' => 'require_penalties']); throw new RuntimeException('Unsupported profile accepted.'); }
catch (MatchResultValidationException $error) { if ($error->getLanguageKey() !== 'COM_JOOMLEAGUE_STAGE_ERROR_DECISION_UNSUPPORTED') throw $error; }
echo "Stage encounter rules OK: inheritance, override, immutability, unsupported profiles and strict policies.\n";
