<?php
declare(strict_types=1);
namespace Joomla\CMS\Language {
    final class Text {
        public static function _(string $key): string { return $key; }
        public static function sprintf(string $key, mixed ...$args): string { return 'Set ' . $args[0]; }
    }
}
namespace Joomla\CMS\Router {
    final class Route { public static function _(string $url): string { return $url; } }
}
namespace {
    define('_JEXEC', 1);
    $participants = [(object)['id'=>1,'name'=>'A <team>'], (object)['id'=>2,'name'=>'B']];
    $data = ['segments'=>[], 'valuesBySegment'=>[], 'encounterMembers'=>[]];
    foreach ([1=>5,2=>2] as $duelNumber=>$count) {
        $id = $duelNumber * 10;
        $data['segments'][] = (object)['id'=>$id,'level_code'=>'duel','sequence_number'=>$duelNumber,'parent_id'=>1];
        foreach (range(1,$count) as $sequence) {
            $setId = $id + $sequence;
            $data['segments'][] = (object)['id'=>$setId,'level_code'=>'set','sequence_number'=>$sequence,'parent_id'=>$id];
            $data['valuesBySegment'][$setId] = [1=>(object)['numeric_value'=>'6'],2=>(object)['numeric_value'=>'4']];
        }
        $data['valuesBySegment'][$id] = [1=>(object)['numeric_value'=>'3'],2=>(object)['numeric_value'=>'2']];
    }
    $data['encounterMembers'][1][1] = [(object)['person_id'=>7,'first_name'=>'<Alex>','last_name'=>'River']];
    $data['encounterMembers'][1][2] = [(object)['person_id'=>null]];
    $formatValue = static fn(object $value):string => $value->numeric_value;
    $segmentLabel = static fn(object $segment):string => 'Duel';
    ob_start();
    require dirname(__DIR__,2) . '/components/com_joomleague/tmpl/eventreport/encounter.php';
    $html = ob_get_clean();
    foreach (['Set 5','A &lt;team&gt;','&lt;Alex&gt; River','COM_JOOMLEAGUE_ENCOUNTER_PRIVATE_PLAYER','table-responsive'] as $expected) {
        if (!str_contains($html,$expected)) throw new \RuntimeException('Missing: ' . $expected);
    }
    if (substr_count($html,'&mdash;') !== 3 || substr_count($html,'<tr>') !== 3 || str_contains($html,'Set 6')) throw new \RuntimeException('Wrong column or row count.');
    echo "Encounter set columns OK: five sets, shorter match placeholders, player privacy and escaping.\n";
    $data['valuesBySegment'][10][1] = (object) ['numeric_value' => '40', 'metadata' => ['innings' => 43, 'high_run' => 9]];
    $data['valuesBySegment'][10][2] = (object) ['numeric_value' => '32', 'metadata' => ['innings' => 43, 'high_run' => 7]];
    ob_start();
    require dirname(__DIR__,2) . '/components/com_joomleague/tmpl/eventreport/encounter.php';
    $html = ob_get_clean();
    foreach (['COM_JOOMLEAGUE_ENCOUNTER_INNINGS: 43', 'COM_JOOMLEAGUE_ENCOUNTER_AVERAGE: 0.930', 'COM_JOOMLEAGUE_ENCOUNTER_AVERAGE: 0.744', 'COM_JOOMLEAGUE_ENCOUNTER_HIGH_RUN: 9'] as $expected) {
        if (!str_contains($html, $expected)) throw new \RuntimeException('Missing carom statistic: ' . $expected);
    }
    echo "Carom public statistics OK: innings, high runs and three-decimal averages.\n";
    $data['caromSummary'] = [1 => ['caroms' => 60, 'innings' => 60, 'complete' => true, 'average' => 1.0, 'high_run' => 9], 2 => ['caroms' => 70, 'innings' => 0, 'complete' => false, 'average' => null, 'high_run' => null]];
    ob_start();
    require dirname(__DIR__,2) . '/components/com_joomleague/tmpl/eventreport/encounter.php';
    $html = ob_get_clean();
    if (!str_contains($html, 'COM_JOOMLEAGUE_CAROM_SUMMARY_CAPTION') || !str_contains($html, '<td>1.000</td>') || !str_contains($html, '<td>70</td><td>&mdash;</td><td>&mdash;</td>')) throw new \RuntimeException('Carom summary rendering is incorrect.');
    echo "Carom summary rendering OK: totals and unavailable-statistic placeholders.\n";
}
