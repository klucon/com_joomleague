<?php
declare(strict_types=1);
if (PHP_SAPI!=='cli' || !str_starts_with((string)getenv('JL_R1_DB_HOST'),'jl-r1-')) throw new RuntimeException('Disposable database required');
require __DIR__.'/AuditReport.php';
$required=['bootstrap','fixtures','participant-template','programme-nonempty','programme-scope','programme-filters','stage-preview','stage-apply','stage-replay','birthdays-nonempty','birthdays-empty','birthdays-failure-log','participant-failure-log','legacy-programme','legacy-participant','teamplan','legacy-calendar','legacy-program-template','legacy-standings-template','legacy-ownership','match-lineup','match-events','match-statistics','match-officials'];
$required[]='match-workflow-cleanup';
$report=new AuditReport('/tmp/jl-r6-native.json',$required);
define('_JEXEC',1);define('JPATH_BASE','/var/www/html');
$_SERVER['HTTP_HOST']='localhost';$_SERVER['REQUEST_URI']='/index.php';$_SERVER['SCRIPT_NAME']='/index.php';
require JPATH_BASE.'/includes/defines.php';require JPATH_BASE.'/includes/framework.php';
$container=Joomla\CMS\Factory::getContainer();
$container->alias('session','session.cli')->alias(Joomla\Session\SessionInterface::class,'session.cli');
$app=$container->get(Joomla\CMS\Application\SiteApplication::class);Joomla\CMS\Factory::$application=$app;
$app->input->set('option','com_joomleague');
if ($app->get('host')!==getenv('JL_R1_DB_HOST') || $app->get('db')!=='r1_test' || $app->get('dbprefix')!=='r1_') throw new RuntimeException('Unsafe target');
$app->createExtensionNamespaceMap();$app->loadLanguage(Joomla\CMS\Language\Language::getInstance('en-GB'));
$app->bootComponent('com_joomleague');
$db=$container->get(Joomla\Database\DatabaseInterface::class);
$fixture=json_decode(file_get_contents('/tmp/jl-r1-fixture.json'),true,512,JSON_THROW_ON_ERROR);$a=$fixture['projects']['a'];$b=$fixture['projects']['b'];
$check=static function(bool $ok,string $label):void{if(!$ok)throw new AuditAssertion($label);};
$participant=Joomleague\Module\Participant\Site\Helper\ParticipantHelper::class;
$birthdays=Joomleague\Module\Birthdays\Site\Helper\BirthdaysHelper::class;
$logPath=(string)$app->get('log_path');
Joomla\CMS\Log\Log::addLogger(['text_file'=>'r6-modules.php','text_file_path'=>$logPath,'text_entry_format'=>'{MESSAGE}'],Joomla\CMS\Log\Log::ALL,['com_joomleague.modules']);
$logs=static fn():string=>is_file($logPath.'/r6-modules.php')?file_get_contents($logPath.'/r6-modules.php'):'';
$report->run('bootstrap',static function()use($check,$participant,$birthdays):int{
    $check(class_exists($participant)&&class_exists($birthdays),'Installed module namespaces missing');return 2;
});
$report->run('fixtures',function()use($db,$app,$a,$fixture,$check):int{
    $old=(int)$db->setQuery('SELECT profile_version_id FROM #__joomleague_project WHERE id='.(int)$a['id'])->loadResult();
    $version=$db->setQuery('SELECT * FROM #__joomleague_sport_profile_version WHERE id='.$old)->loadObject();
    unset($version->id);$payload=json_decode($version->payload_json,true,512,JSON_THROW_ON_ERROR);
    $payload['template_defaults']['participant']=['show_personal_data'=>true,'show_results'=>true];
    $payload['audit_fixture']='r6';$version->profile_version='1.0.0-r6-fixture';$version->source='custom';
    $version->payload_json=json_encode($payload,JSON_THROW_ON_ERROR);$version->payload_checksum=hash('sha256',$version->payload_json);
    $db->insertObject('#__joomleague_sport_profile_version',$version,'id');
    $row=(object)['id'=>$a['id'],'profile_version_id'=>(int)$version->id];$db->updateObject('#__joomleague_project',$row,'id');
    $date=Joomla\CMS\Factory::getDate();
    $row=(object)['id'=>$fixture['person'],'birth_date'=>'2000-'.$date->format('m-d')];$db->updateObject('#__joomleague_person',$row,'id');
    $row=(object)['id'=>$a['records']['Match'],'scheduled_start'=>Joomla\CMS\Factory::getDate('+1 day')->toSql()];$db->updateObject('#__joomleague_project_match',$row,'id');
    foreach(['mod_joomleague_participant'=>['project_id'=>$a['id'],'entry_id'=>$a['entries'][0],'template_show_results'=>'0','template_show_personal_data'=>'0'],'mod_joomleague_birthdays'=>['project_id'=>$a['id'],'days'=>7,'limit'=>5]]as$element=>$params){
        $row=(object)['title'=>'R6 '.$element,'module'=>$element,'position'=>'sidebar-right','published'=>1,'access'=>1,'showtitle'=>1,'client_id'=>0,'language'=>'*','params'=>json_encode($params+['cache'=>0]),'assignment'=>0];
        unset($row->assignment);$db->insertObject('#__modules',$row,'id');
        $mapping=(object)['moduleid'=>$row->id,'menuid'=>0];$db->insertObject('#__modules_menu',$mapping);
    }
    $check((int)$version->id>0,'Immutable profile fixture missing');return 4;
});
$report->run('participant-template',function()use($participant,$a,$check):int{
    $result=(new $participant())->getSummary(new Joomla\Registry\Registry(['project_id'=>$a['id'],'entry_id'=>$a['entries'][0],'template_show_personal_data'=>'0','template_show_results'=>'0']));
    $check(!isset($result['error']),'Participant fixture unavailable');
    $check(($result['template_config']['show_personal_data']??true)===false && ($result['template_config']['show_results']??true)===false,'Presentation overrides were ignored');return 3;
});
$reader=new Joomleague\Component\Joomleague\Domain\Service\ProgrammeReader($db);
$report->run('programme-nonempty',function()use($reader,$a,$check):int{
    $events=$reader->forProject($a['id'],null,[1]);
    $check(count($events)===1 && count($events[0]['participants'])===2,'Required programme content absent');return count($events)+count($events[0]['participants']);
});
$report->run('programme-scope',function()use($reader,$a,$b,$check):int{
    $events=$reader->forProject($a['id'],[$a['entries'][0]],[1]);
    $check(count($events)===1 && in_array($a['entries'][0],array_column($events[0]['participants'],'entry_id'),true),'Valid entry scope missing');
    $check($reader->forProject($a['id'],[$b['entries'][0]],[1])===[],'Foreign entry scope leaked');return 2;
});
$report->run('programme-filters',function()use($reader,$a,$b,$check):int{
    $check(count($reader->forProject($a['id'],null,[1],null,$a['stage'],$a['round']))===1,'Valid programme filters empty');
    $check($reader->forProject($a['id'],null,[1],null,$b['stage'],$b['round'])===[],'Foreign filter leaked');return 2;
});
$progression=new Joomleague\Component\Joomleague\Administrator\Service\StageProgressionService($db);
$report->run('stage-preview',function()use($progression,$a,$check):int{
    $preview=$progression->preview($a['records']['Stagetransition']);
    $check($preview['executable'] && count($preview['entries'])===2,'Required progression fixture is empty');return 2;
});
$report->run('stage-apply',function()use($progression,$a,$fixture,$db,$check):int{
    $result=$progression->apply($a['records']['Stagetransition'],$fixture['users']['a']);
    $check($result['resolved_count']===2 && !$result['reused'],'Progression did not apply');
    $count=(int)$db->setQuery('SELECT COUNT(*) FROM #__joomleague_stage_entry WHERE stage_id='.(int)$a['target'])->loadResult();
    $check($count===2,'Target stage entries missing');return $count;
});
$report->run('stage-replay',function()use($progression,$a,$fixture,$db,$check):int{
    $result=$progression->apply($a['records']['Stagetransition'],$fixture['users']['a']);
    $check($result['resolved_count']===2 && $result['reused'],'Progression repeat was not idempotent');
    $check((int)$db->setQuery('SELECT COUNT(*) FROM #__joomleague_stage_transition_run WHERE transition_id='.(int)$a['records']['Stagetransition'])->loadResult()===1,'Duplicate progression run');return 2;
});
$report->run('birthdays-nonempty',function()use($birthdays,$a,$check):int{
    $result=(new $birthdays())->getBirthdays(new Joomla\Registry\Registry(['project_id'=>$a['id'],'days'=>7]));
    $check(!isset($result['error']) && count($result['items'])===1,'Birthday fixture missing');return count($result['items']);
});
$report->run('birthdays-empty',function()use($birthdays,$b,$check,$logs):int{
    $before=$logs();$result=(new $birthdays())->getBirthdays(new Joomla\Registry\Registry(['project_id'=>$b['id'],'days'=>7,'role_codes'=>['r6_missing_role']]));
    $check(($result['error']??'')==='MOD_JOOMLEAGUE_BIRTHDAYS_EMPTY','Legitimate empty result not distinguished');
    $check($logs()===$before,'Legitimate empty result logged as a failure');return 2;
});
$secret='r6-private-email@example.invalid SQL-password-secret';
$report->run('birthdays-failure-log',function()use($birthdays,$a,$app,$secret,$logs,$check):int{
    $offset=$app->get('offset');$app->set('offset',$secret);
    try{$result=(new $birthdays())->getBirthdays(new Joomla\Registry\Registry(['project_id'=>$a['id']]));}finally{$app->set('offset',$offset);}
    $check(($result['error']??'')==='MOD_JOOMLEAGUE_BIRTHDAYS_UNAVAILABLE','Thrown failure masked as empty/success');
    $log=$logs();$check(str_contains($log,'mod_joomleague_birthdays failure:'),'Failure diagnostic missing');
    $check(!str_contains($log,$secret) && !str_contains(json_encode($result),$secret),'Sensitive error payload leaked');return 3;
});
$report->run('participant-failure-log',function()use($participant,$a,$db,$secret,$logs,$check):int{
    $row=(object)['project_id'=>$a['id'],'template_code'=>'participant','schema_version'=>'1.0.0','params_json'=>$secret,'params_checksum'=>hash('sha256',$secret),'modified_by'=>0];
    $db->insertObject('#__joomleague_project_template_config',$row,'id');
    try{$result=(new $participant())->getSummary(new Joomla\Registry\Registry(['project_id'=>$a['id'],'entry_id'=>$a['entries'][0]]));}
    finally{$db->setQuery('DELETE FROM #__joomleague_project_template_config WHERE id='.(int)$row->id)->execute();}
    $check(($result['error']??'')==='MOD_JOOMLEAGUE_PARTICIPANT_UNAVAILABLE','Template failure silently accepted');
    $log=$logs();$check(str_contains($log,'mod_joomleague_participant failure:'),'Participant diagnostic missing');
    $check(!str_contains($log,$secret) && !str_contains(json_encode($result),$secret),'Sensitive template payload leaked');return 3;
});
$workflowCounts=static function()use($db):array{
    $counts=[];
    foreach($db->getTableList()as$table)if(str_starts_with($table,$db->getPrefix().'joomleague_')) {
        $counts[$table]=(int)$db->setQuery('SELECT COUNT(*) FROM '.$db->quoteName($table))->loadResult();
    }
    ksort($counts);return $counts;
};
$workflowBefore=null;
foreach(['legacy-programme'=>'programme-reader.php','legacy-participant'=>'verify-participant-module-template.php','legacy-calendar'=>'verify-calendar-module.php','legacy-program-template'=>'verify-program-module-template.php','legacy-standings-template'=>'verify-standings-module-template.php','legacy-ownership'=>'audit-controller-ownership.php','match-lineup'=>'match-lineup.php','match-events'=>'match-events.php','match-statistics'=>'match-statistics.php','match-officials'=>'match-officials.php']as$id=>$script){
    if($id==='match-lineup')$workflowBefore=$workflowCounts();
    $report->run($id,function()use($script,$a,$check,$db):int{
        if($script==='verify-standings-module-template.php') {
            $reader=new Joomleague\Component\Joomleague\Domain\Service\StandingsReader($db);
            $snapshot=(new Joomleague\Component\Joomleague\Domain\Service\StandingsRecalculator($db,$reader))->recalculate($a['id'],null,'total',0);
            $check((int)$db->setQuery('SELECT COUNT(*) FROM #__joomleague_standing_snapshot_row WHERE snapshot_id='.(int)$snapshot)->loadResult()===2,'Required standings fixture rows missing');
        }
        $command=str_starts_with($script,'match-')?[PHP_BINARY,__DIR__.'/audit-workflow-child.php',$script]:[PHP_BINARY,__DIR__.'/'.$script];
        $process=proc_open($command,[1=>['pipe','w'],2=>['pipe','w']],$pipes,null,array_merge(getenv(),['JOOMLEAGUE_TEST_PROJECT_ID'=>(string)$a['id']]));
        $check(is_resource($process),'Cannot launch legacy integration check');
        $out=stream_get_contents($pipes[1]);$err=stream_get_contents($pipes[2]);fclose($pipes[1]);fclose($pipes[2]);
        $status=proc_close($process);
        if(preg_match('/WORKFLOW_FAILURE (\{[^\n]+\})/',$err,$diagnostic)) {
            $failure=json_decode($diagnostic[1],true,512,JSON_THROW_ON_ERROR);
            if(preg_match('/^[A-Za-z0-9_-]+\.php$/D',(string)($failure['file']??'')) && is_int($failure['line']??null)) {
                throw new AuditAssertion('Workflow failed at '.$failure['file'].':'.$failure['line']);
            }
        }
        $check($status===0 && !preg_match('/SKIP|Fatal error|Allowed memory size|Warning:|Notice:/i',$out.$err),'Legacy check failed or skipped');
        if($script==='audit-controller-ownership.php') {
            $observations=json_decode($out,true,512,JSON_THROW_ON_ERROR)['observations']??[];
            $check(count($observations)===6,'Required ownership observations missing');
            foreach($observations as$observation)$check(($observation['stored_owner_denied']??false)===true && ($observation['forged_owner_accepted']??true)===false,'Controller ownership denial failed');
            return count($observations);
        }
        $check(str_contains($out,'OK'),'Legacy success evidence missing');return 1;
    });
}
$report->run('match-workflow-cleanup',function()use($workflowCounts,$workflowBefore,$check):int{
    $check(is_array($workflowBefore) && count($workflowBefore)>=50,'Workflow baseline coverage missing');
    $check($workflowCounts()===$workflowBefore,'Match workflows left changed component table counts');
    return count($workflowBefore);
});
$report->run('teamplan',function()use($app,$a,$check,$db):int{
    $app->loadIdentity(Joomla\CMS\Factory::getContainer()->get(Joomla\CMS\User\UserFactoryInterface::class)->loadUserById(0));
    $model=$app->bootComponent('com_joomleague')->getMVCFactory()->createModel('Teamplan','Site',['ignore_request'=>true]);
    $model->setState('project_id',$a['id']);$model->setState('entry_id',$a['entries'][0]);
    $plan=$model->getPlan();
    $levels=$app->getIdentity()->getAuthorisedViewLevels();
    $entries=(new Joomleague\Component\Joomleague\Domain\Service\ProgrammeScopeResolver($db))->resolve($a['id'],'entry',$a['entries'][0],$levels);
    $check(count($entries??[])===1,'Teamplan fixture scope is empty');
    $events=(new Joomleague\Component\Joomleague\Domain\Service\ProgrammeReader($db))->forProject($a['id'],$entries,$levels);
    $check(count($events)===1,'Teamplan scoped reader is empty after model query');
    $check(count($events[0]['participants'])===2,'Teamplan scoped reader lost participants');
    $check(in_array($a['entries'][0],array_column($events[0]['participants'],'entry_id'),true),'Teamplan participant identity mismatch');
    $check(count($plan['matches']??[])===1,'Teamplan model lost the required programme fixture');return count($plan['matches']);
});
exit($report->finish()?0:1);
