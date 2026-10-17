<?php
declare(strict_types=1);
$root=dirname(__DIR__,2);
$reader=file_get_contents($root.'/administrator/components/com_joomleague/src/Service/ClubPersonHistoryReader.php');
foreach(['COUNT(DISTINCT match_id)','COUNT(*) OVER (PARTITION BY lineup.person_id, match.id)','min(1000, $total)',"\$start, \$limit)->loadObjectList()","\$detailStart, 25)->loadObjectList()"]as$required) {
    if(!str_contains($reader,$required))throw new RuntimeException('Missing bounded history contract: '.$required);
}
if(substr_count($reader,'->loadObjectList()')!==2 || str_contains($reader,'profile.payload_json AS profile_json'))throw new RuntimeException('Unbounded row hydration returned');
foreach(['ClubpeopleModel','ClubstaffhistoryModel']as$model) {
    $source=file_get_contents($root.'/components/com_joomleague/src/Model/'.$model.'.php');
    if(str_contains($source,'array_slice(') || str_contains($source,'usort(') || !str_contains($source,"\$data['pagination_state']"))throw new RuntimeException('Model paginates a preloaded directory: '.$model);
}
$template=file_get_contents($root.'/components/com_joomleague/tmpl/clubstaffhistory/default.php');
if(!str_contains($template,"\$data['detail_pagination']->getPagesLinks()"))throw new RuntimeException('Bounded match details lack pagination');
echo "PASS: SQL person aggregation, bounded page/detail hydration and model pagination contracts\n";
