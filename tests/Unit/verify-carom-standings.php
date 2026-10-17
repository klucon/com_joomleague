<?php
declare(strict_types=1);
define('_JEXEC', 1);
$root = __DIR__ . '/../../administrator/components/com_joomleague/src/Service/';
foreach (['StandingsDecimal', 'StandingsContractValidator', 'StandingsCalculator', 'CaromStandingsFacts', 'CaromStandingsContract', 'ExactDecimal', 'ProjectRuleValidator'] as $class) require_once $root . $class . '.php';
use Joomleague\Component\Joomleague\Domain\Service\CaromStandingsFacts;
use Joomleague\Component\Joomleague\Domain\Service\StandingsCalculator;
$entries = [['id' => 1, 'name' => 'A'], ['id' => 2, 'name' => 'B']];
$game = static fn (string $status, int $a, int $b, ?int $innings): array => ['code' => 'duel', 'status' => $status, 'values' => [1 => (string) $a, 2 => (string) $b], 'metadata' => [1 => ['innings' => $innings, 'high_run' => 7], 2 => ['innings' => $innings, 'high_run' => 4]]];
$match = ['status' => 'final', 'participants' => [['entry_id' => 1, 'root_value' => '6'], ['entry_id' => 2, 'root_value' => '2']], 'segments' => [$game('completed', 40, 30, 20), $game('completed', 20, 25, 40), $game('walkover', 40, 0, 1), $game('cancelled', 40, 0, 1)]];
$facts = new CaromStandingsFacts();
$match['statistics'] = $facts->forMatch($match);
if ($match['statistics'][1] !== ['caroms' => '60', 'innings' => '60', 'high_run' => '7']) throw new RuntimeException('Awards-only / cancelled boards entered playing statistics.');
$metric = static fn (string $code, string $operation, array $extra = []): array => ['code' => $code, 'operation' => $operation, 'value_type' => 'decimal', ...$extra];
$contract = ['mode' => 'head_to_head', 'included_result_statuses' => ['final'], 'scopes' => [['code' => 'total', 'filter' => ['type' => 'always']]], 'metrics' => [$metric('caroms', 'sum_statistic', ['statistic_code' => 'caroms']), $metric('innings', 'sum_statistic', ['statistic_code' => 'innings']), $metric('high_run', 'max_statistic', ['statistic_code' => 'high_run']), $metric('average', 'ratio', ['left_metric' => 'caroms', 'right_metric' => 'innings'])], 'awards' => ['mode' => 'none', 'rules' => []], 'ordering' => [['metric' => 'average', 'direction' => 'desc', 'nulls' => 'last']]];
$calculator = new StandingsCalculator();
foreach ([['1', '3000000000', '1', '4000000000', 1], ['1', '3', '2', '6', 0], ['-1', '3', '-1', '4', -1], ['0', '-1', '0', '1', 0], ['1', '-3', '1', '4', -1]] as [$a, $b, $c, $d, $expected]) {
	$comparison = \Joomleague\Component\Joomleague\Domain\Service\StandingsDecimal::compareRatios($a, $b, $c, $d);
	if (($comparison <=> 0) !== $expected) throw new RuntimeException('Exact ratio comparison is incorrect.');
}
$rows = $calculator->calculate($contract, $entries, [$match, $match]);
if ($rows[0]['metrics'] !== ['caroms' => '120', 'innings' => '120', 'high_run' => '7', 'average' => '1']) throw new RuntimeException('Weighted totals or maximum run incorrect.');
$incomplete = $match;
$incomplete['segments'][0]['metadata'][1]['innings'] = null;
$incomplete['statistics'] = $facts->forMatch($incomplete);
foreach ([[$match, $incomplete], [$incomplete, $match]] as $matches) {
	$rows = $calculator->calculate($contract, $entries, $matches);
	$byId = array_column($rows, null, 'id');
	if ($byId[1]['metrics']['average'] !== null || $byId[1]['metrics']['innings'] !== null || $rows[0]['id'] !== 2) throw new RuntimeException('Missing innings were silently replaced by zero.');
}
$noGames = $match;
$noGames['segments'] = [$game('walkover', 40, 0, 1)];
$noGames['statistics'] = $facts->forMatch($noGames);
if ($calculator->calculate($contract, $entries, [$noGames])[0]['metrics']['average'] !== null) throw new RuntimeException('No playing statistics must not imply an average.');
$profile = json_decode(file_get_contents($root . '../../resources/sport-profiles/carom.json'), true, 512, JSON_THROW_ON_ERROR);
$rules = new \Joomleague\Component\Joomleague\Administrator\Service\ProjectRuleValidator();
$changed = $rules->resolve($profile, ['standings' => ['tie_breaks' => ['first' => 'caroms', 'second' => 'average', 'third' => 'none', 'fourth' => 'none', 'fifth' => 'none'], 'team_points' => ['win' => 2, 'draw' => 1, 'loss' => 0]]]);
$resolved = (new \Joomleague\Component\Joomleague\Domain\Service\CaromStandingsContract())->resolve($changed);
if (array_column($resolved['ordering'], 'metric') !== ['points', 'caroms', 'average'] || $resolved['awards']['rules'][0]['values'] !== ['winner' => 2, 'draw' => 1, 'loser' => 0]) throw new RuntimeException('Project criteria or team awards ignored.');
if ($profile['standings']['team_points']['win'] !== 3) throw new RuntimeException('Pinned profile was modified.');
$headToHead = $rules->resolve($profile, ['standings' => ['tie_breaks' => ['first' => 'head_to_head_points', 'second' => 'head_to_head_average', 'third' => 'head_to_head_caroms', 'fourth' => 'none', 'fifth' => 'none']]]);
$headContract = (new \Joomleague\Component\Joomleague\Domain\Service\CaromStandingsContract())->resolve($headToHead);
if (array_column($headContract['ordering'], 'metric') !== ['points', 'points', 'average', 'caroms'] || array_column($headContract['ordering'], 'scope') !== ['head_to_head', 'head_to_head', 'head_to_head']) throw new RuntimeException('Head-to-head criteria compiled incorrectly.');
try { $rules->resolve($profile, ['standings' => ['tie_breaks' => ['first' => 'unsupported']]]); throw new RuntimeException('Unknown criterion accepted.'); } catch (InvalidArgumentException) {}
echo "Carom standings facts, weighted averages, missing data, highest run, configurable criteria/awards and forfeit exclusion OK\n";
