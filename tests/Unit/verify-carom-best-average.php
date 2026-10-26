<?php
declare(strict_types=1);
define('_JEXEC', 1);
$root = dirname(__DIR__, 2) . '/administrator/components/com_joomleague/';
foreach (['StandingsDecimal', 'StandingsContractValidator', 'StandingsCalculator', 'CaromStandingsFacts', 'CaromStandingsContract', 'ExactDecimal', 'ProjectRuleValidator'] as $class) require_once $root . 'src/Service/' . $class . '.php';
use Joomleague\Component\Joomleague\Domain\Service\StandingsCalculator;
use Joomleague\Component\Joomleague\Domain\Service\CaromStandingsFacts;
use Joomleague\Component\Joomleague\Domain\Service\CaromStandingsContract;
use Joomleague\Component\Joomleague\Domain\Service\StandingsContractValidator;
use Joomleague\Component\Joomleague\Administrator\Service\ProjectRuleValidator;
$profile = json_decode(file_get_contents($root . 'resources/sport-profiles/carom.json'), true, 512, JSON_THROW_ON_ERROR);
$original = $profile;
$profile = (new ProjectRuleValidator())->resolve($profile, ['standings' => ['tie_breaks' => ['first' => 'best_average', 'second' => 'none', 'third' => 'none', 'fourth' => 'none', 'fifth' => 'none']]]);
$contract = (new CaromStandingsContract())->resolve($profile);
if (array_column($contract['ordering'], 'metric') !== ['points', 'best_average'] || $original['standings']['tie_breaks']['first'] !== 'score_for') throw new RuntimeException('Criterion compilation changed inherited defaults.');
$entries = [['id' => 1, 'name' => 'Zulu'], ['id' => 2, 'name' => 'Alpha']];
$game = static fn (int $a, int $b, ?int $innings, string $code = 'duel', string $status = 'completed'): array => ['code' => $code, 'status' => $status, 'values' => [1 => (string) $a, 2 => (string) $b], 'metadata' => [1 => ['innings' => $innings, 'high_run' => min(9, $a)], 2 => ['innings' => $innings, 'high_run' => min(9, $b)]]];
$match = ['status' => 'final', 'participants' => [['entry_id' => 1, 'root_value' => '4'], ['entry_id' => 2, 'root_value' => '4']], 'segments' => [$game(40, 30, 30), $game(40, 30, 20), $game(40, 30, 40), $game(999, 1, 1, 'penalty_round'), $game(999, 0, 1, 'duel', 'walkover'), $game(999, 0, 1, 'duel', 'cancelled')]];
$facts = new CaromStandingsFacts(); $calculator = new StandingsCalculator();
$match['statistics'] = $facts->forMatch($match);
$rows = $calculator->calculate($contract, $entries, [$match]);
if ($rows[0]['id'] !== 1 || $rows[0]['metrics']['best_average'] !== '2' || $rows[0]['metrics']['average'] !== '1.333333333' || $rows[0]['metrics']['caroms'] !== '120') throw new RuntimeException('Best individual average confused with general/team average or penalties.');
if (array_keys($rows[0]) !== ['id', 'name', 'rank', 'metrics']) throw new RuntimeException('Internal ratio operands leaked into the public row contract.');
$precise = $match;
$precise['statistics'][1]['best_average_caroms'] = '1'; $precise['statistics'][1]['best_average_innings'] = '99999';
$precise['statistics'][2]['best_average_caroms'] = '1'; $precise['statistics'][2]['best_average_innings'] = '100000';
foreach ([$entries, array_reverse($entries)] as $input) {
    $rows = $calculator->calculate($contract, $input, [$precise]);
    if ($rows[0]['metrics']['best_average'] !== $rows[1]['metrics']['best_average'] || $rows[0]['id'] !== 1 || $rows[1]['rank'] !== 2) throw new RuntimeException('Rounded decimal created a false tie.');
}
$headContract = $contract; $headContract['ordering'][1]['scope'] = 'head_to_head';
if ($calculator->calculate($headContract, $entries, [$precise])[0]['id'] !== 1) throw new RuntimeException('Mini-table lost exact ratio operands.');
$equal = $precise;
$equal['statistics'][1]['best_average_caroms'] = '1'; $equal['statistics'][1]['best_average_innings'] = '3';
$equal['statistics'][2]['best_average_caroms'] = '2'; $equal['statistics'][2]['best_average_innings'] = '6';
if (array_column($calculator->calculate($contract, $entries, [$equal]), 'rank') !== [1, 1]) throw new RuntimeException('Equivalent fractions do not share rank.');
$better = $match; $better['segments'] = [$game(40, 20, 10)]; $better['statistics'] = $facts->forMatch($better);
foreach ([[$match, $better], [$better, $match]] as $matches) if ($calculator->calculate($contract, $entries, $matches)[0]['metrics']['best_average'] !== '4') throw new RuntimeException('Maximum across encounters depends on input order.');
$missing = $match; $missing['segments'][0]['metadata'][1]['innings'] = null; $missing['statistics'] = $facts->forMatch($missing);
foreach ([[$match, $missing], [$missing, $match]] as $matches) {
    $rows = array_column($calculator->calculate($contract, $entries, $matches), null, 'id');
    if ($rows[1]['metrics']['best_average'] !== null || $rows[2]['metrics']['best_average'] === null) throw new RuntimeException('Missing playing data produced an invented maximum.');
}
$none = $match; $none['segments'] = [$game(40, 0, 1, 'duel', 'walkover')]; $none['statistics'] = $facts->forMatch($none);
if ($calculator->calculate($contract, $entries, [$none])[0]['metrics']['best_average'] !== null) throw new RuntimeException('No played game implied a zero average.');
$zero = $match; $zero['segments'] = [$game(0, 0, 1)]; $zero['statistics'] = $facts->forMatch($zero);
if ($calculator->calculate($contract, $entries, [$zero])[0]['metrics']['best_average'] !== '0') throw new RuntimeException('A played zero score was confused with missing data.');
$draft = $better; $draft['status'] = 'in_progress';
if ($calculator->calculate($contract, $entries, [$match, $draft])[0]['metrics']['best_average'] !== '2') throw new RuntimeException('Draft result entered the maximum.');
foreach (['0', '-1', 'invalid'] as $bad) {
    $invalid = $precise; $invalid['statistics'][1]['best_average_innings'] = $bad;
    try { $calculator->calculate($contract, $entries, [$invalid]); throw new RuntimeException('Invalid ratio accepted.'); } catch (InvalidArgumentException|UnexpectedValueException) {}
}
try { $calculator->calculate($contract, $entries, [$match], null, [['entry_id' => 1, 'metric' => 'best_average', 'value' => '999']]); throw new RuntimeException('Derived metric adjustment accepted.'); } catch (InvalidArgumentException) {}
foreach (['numerator_statistic', 'denominator_statistic'] as $field) {
    $invalid = $contract;
    foreach ($invalid['metrics'] as &$metric) if ($metric['operation'] === 'max_ratio') unset($metric[$field]);
    unset($metric);
    try { (new StandingsContractValidator())->validate($invalid); throw new RuntimeException('Missing ratio source accepted.'); } catch (UnexpectedValueException) {}
}
$legacy = $original;
$legacy['version'] = '1.6.0';
$legacy['standings']['calculation']['metrics'] = array_values(array_filter($legacy['standings']['calculation']['metrics'], static fn (array $metric): bool => $metric['code'] !== 'best_average'));
$legacy['standings']['columns'] = array_values(array_diff($legacy['standings']['columns'], ['best_average']));
foreach ($legacy['project_rule_schema']['fields'] as &$field) if (isset($field['enum'])) $field['enum'] = array_values(array_diff($field['enum'], ['best_average']));
unset($field);
$checksum = hash('sha256', serialize($legacy));
$legacyContract = (new CaromStandingsContract())->resolve($legacy);
if (array_key_exists('best_average', $calculator->calculate($legacyContract, $entries, [$match])[0]['metrics']) || hash('sha256', serialize($legacy)) !== $checksum) throw new RuntimeException('Existing profile received a silent new metric or mutation.');
try { (new ProjectRuleValidator())->resolve($legacy, ['standings' => ['tie_breaks' => ['first' => 'best_average']]]); throw new RuntimeException('New criterion silently enabled on an old pinned schema.'); } catch (InvalidArgumentException) {}
echo "Carom best average OK: Hugo example, exact ratios, mini-tables, equal fractions, nulls, zero, drafts, penalties, walkovers, corrections and adjustment/input validation.\n";
