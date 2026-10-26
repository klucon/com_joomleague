<?php
declare(strict_types=1);
if(PHP_SAPI!=='cli'||!str_starts_with((string)getenv('JL_R1_DB_HOST'),'jl-r1-'))throw new RuntimeException('Disposable migration database required');
define('_JEXEC',1);define('JPATH_BASE','/var/www/html');
$_SERVER['HTTP_HOST']='localhost';$_SERVER['REQUEST_URI']='/administrator/index.php';$_SERVER['SCRIPT_NAME']='/administrator/index.php';
require JPATH_BASE.'/includes/defines.php';require JPATH_BASE.'/includes/framework.php';
$c=Joomla\CMS\Factory::getContainer();$c->alias('session','session.cli')->alias(Joomla\Session\SessionInterface::class,'session.cli');
$app=$c->get(Joomla\CMS\Application\AdministratorApplication::class);Joomla\CMS\Factory::$application=$app;
$app->loadLanguage(Joomla\CMS\Language\Language::getInstance('en-GB'));
if($app->get('host')!==getenv('JL_R1_DB_HOST')||$app->get('db')!=='r1_test'||$app->get('dbprefix')!=='r1_')throw new RuntimeException('Unsafe migration target');
$app->createExtensionNamespaceMap();$app->bootComponent('com_joomleague');
$db=$c->get(Joomla\Database\DatabaseInterface::class);
$zip=new ZipArchive();if($zip->open('/opt/migration.zip')!==true)throw new RuntimeException('Migration ZIP missing');
$manifest=json_decode($zip->getFromName('manifest.json'),true,512,JSON_THROW_ON_ERROR);$sql=$zip->getFromName('migration.sql');$zip->close();
if($manifest['format']!=='joomleague-canonical-migration'||$manifest['reconciliation']['all_source_rows_accounted']!==true)throw new RuntimeException('Incomplete reconciliation');
$service=new Joomleague\Component\Joomleague\Administrator\Service\SqlDataExchangeService($db,JPATH_ADMINISTRATOR.'/components/com_joomleague');
$result=$service->import($sql);
$observations=[];
$check=static function(string $id,bool $ok)use(&$observations):void{if(!$ok)throw new RuntimeException('Failed migration case: '.$id);$observations[]=['id'=>$id,'status'=>200,'outcome'=>'PASS','evidenceCount'=>1];echo 'PASS: '.$id."\n";};
$check('import',($result['executed']??0)>0);
foreach($manifest['reconciliation']['target_row_counts'] as $table=>$expected){
 if(!preg_match('/^[a-z_]+$/D',$table))throw new RuntimeException('Invalid manifest table');
 if($table==='sport_position')continue;
 $actual=(int)$db->setQuery('SELECT COUNT(*) FROM '.$db->quoteName('#__joomleague_'.$table))->loadResult();
 if($actual!==$expected)throw new RuntimeException('Target row mismatch: '.$table.' expected '.$expected.' got '.$actual);
}
$check('rows',true);
$sports=$db->setQuery('SELECT id,profile_version_id FROM #__joomleague_sport_type ORDER BY id')->loadObjectList();
$check('profiles',count($sports)===17);
foreach($sports as $sport){
 $profile=json_decode($db->setQuery('SELECT payload_json FROM #__joomleague_sport_profile_version WHERE id='.(int)$sport->profile_version_id)->loadResult(),true,512,JSON_THROW_ON_ERROR);
 foreach(['sport_position'=>'positions','event_type'=>'event_types','statistic'=>'statistics']as$table=>$section){
  $expected=count($profile[$section]??[]);
  $actual=(int)$db->setQuery('SELECT COUNT(*) FROM #__joomleague_'.$table.' WHERE sport_type_id='.(int)$sport->id." AND source='profile'")->loadResult();
  if($actual!==$expected)throw new RuntimeException('Incomplete profile materialization: '.$profile['code'].' '.$table);
 }
}
$check('catalogues',true);
foreach(['project_actor_role','match_actor_role']as$table){
 $roles=$db->setQuery('SELECT role_code,role_domain FROM #__joomleague_'.$table.' ORDER BY id')->loadAssocList();
 if($roles!==[['role_code'=>'referee','role_domain'=>'adjudicator'],['role_code'=>'delegate','role_domain'=>'competition_official']])throw new RuntimeException('Incorrect official domains: '.$table);
}
$custom=$db->setQuery("SELECT role_domain FROM #__joomleague_sport_position WHERE code='custom_analyst' AND source='local'")->loadResult();
$check('roles',$custom==='team_staff');
$logos=$db->setQuery('SELECT logo FROM #__joomleague_club ORDER BY id')->loadColumn();
$check('logos',$logos===['images/com_joomleague/database/clubs/middle/crest.png','images/com_joomleague/database/clubs/small/crest.png']);
$scores=$db->setQuery('SELECT numeric_value FROM #__joomleague_match_score_value WHERE match_id=1 ORDER BY participant_id')->loadColumn();
$check('results',array_map('floatval',$scores)===[2.0,1.0]);
$counts=static function()use($db):array{$r=[];foreach(Joomleague\Component\Joomleague\Administrator\Service\ComponentTableCatalog::installed($db)as$table)$r[$table]=(int)$db->setQuery('SELECT COUNT(*) FROM '.$db->quoteName($table))->loadResult();return$r;};
$before=$counts();$service->import($sql);$check('retry',$counts()===$before);
$matchCount=(int)$db->setQuery('SELECT COUNT(*) FROM #__joomleague_project_match')->loadResult();
if (in_array($matchCount,[4,6],true)) {
 if($matchCount===6){
  $issue=json_decode($db->setQuery('SELECT metadata_json FROM #__joomleague_project_match WHERE id=5')->loadResult(),true);
  $check('incomplete-import-isolated',($issue['legacy_result_issue']['code']??'')==='legacy_result_requires_review' && (int)$db->setQuery('SELECT COUNT(*) FROM #__joomleague_match_result WHERE match_id=5')->loadResult()===0);
  $forfeit=(new Joomleague\Component\Joomleague\Administrator\Service\MatchResultRepository($db))->get(6);
  $check('awarded-import', $forfeit['outcome_code']==='forfeit' && array_map('floatval',array_column($forfeit['segments'][0]['values'],'numeric_value'))===[0.0,3.0] && $forfeit['metadata']['legacy_result_resolution']['played_final']===['1','0']);
 }
 foreach ([2=>['1','3'],3=>['1','1'],4=>['2','2']] as $matchId=>$expected) {
  $rows=$db->setQuery('SELECT v.* FROM #__joomleague_match_score_value v INNER JOIN #__joomleague_match_score_segment s ON s.id=v.segment_id WHERE s.level_code=\'result\' AND s.match_id='.(int)$matchId.' ORDER BY v.participant_id')->loadObjectList();
  $check('extended-root-'.$matchId,array_map(static fn($v)=>(float)$v->numeric_value,$rows)===array_map('floatval',$expected));
  $decider=$db->setQuery('SELECT v.* FROM #__joomleague_match_score_value v INNER JOIN #__joomleague_match_score_segment s ON s.id=v.segment_id WHERE s.level_code=\'shootout\' AND s.match_id='.(int)$matchId.' ORDER BY v.participant_id')->loadObjectList();
  $winners=Joomleague\Component\Joomleague\Domain\Service\MatchOutcomeResolver::resolve($rows,$decider);
  $check('extended-winner-'.$matchId,$winners===[$matchId===4?7:($matchId===3?6:4)]);
  $payload=(new Joomleague\Component\Joomleague\Administrator\Service\MatchResultRepository($db))->get($matchId);
  $profile=json_decode($db->setQuery('SELECT payload_json FROM #__joomleague_sport_profile_version WHERE id='.(int)$sports[0]->profile_version_id)->loadResult(),true,512,JSON_THROW_ON_ERROR);
  (new Joomleague\Component\Joomleague\Administrator\Service\MatchResultPayloadValidator())->validate($profile,array_map(static fn($v)=>(int)$v->participant_id,$rows),$payload);
  $check('extended-native-validation-'.$matchId,true);
 }
 $programme=(new Joomleague\Component\Joomleague\Domain\Service\ProgrammeReader($db))->forProject(1,null,[1]);
 $check('extended-programme',count($programme)===$matchCount);
 $reader=new Joomleague\Component\Joomleague\Domain\Service\StandingsReader($db);
 $context=$reader->context(1,null,null);$scope=$context['default_scope'];
 $input=(new Joomleague\Component\Joomleague\Domain\Service\StandingsRecalculator($db,$reader))->inputForCalculation($context,$scope);
 $standings=(new Joomleague\Component\Joomleague\Domain\Service\StandingsCalculator())->calculate($context['contract'],$input['entries'],$input['matches'],$scope,$input['adjustments']);
 $metrics=array_column($standings,'metrics','id');
 $check('extended-standings-goals',(float)$metrics[1]['score_for']===6.0 && (float)$metrics[2]['score_for']===($matchCount===6?10.0:7.0));
 $actor=$c->get(Joomla\CMS\User\UserFactoryInterface::class)->loadUserById((int)$db->setQuery("SELECT id FROM #__users WHERE username='admin'")->loadResult());
 $repair=new Joomleague\Component\Joomleague\Administrator\Service\LegacyFootballResultRepair($db);
 $repository=new Joomleague\Component\Joomleague\Administrator\Service\MatchResultRepository($db);
 foreach([2,3,4] as $matchId){
  $correct=$repository->get($matchId);$broken=$correct;$broken['segments'][0]['children']=[];
  foreach($broken['segments'][0]['values'] as &$value)$value['numeric_value']='1';unset($value);
  $repository->replace($matchId,$broken,0);
  $preview=$repair->preview($matchId,$actor);
  if($preview['status']!=='candidate')throw new RuntimeException('Repair preview rejected: '.($preview['reason']??$preview['status']));
  $check('repair-preview-'.$matchId,$preview['status']==='candidate');
  $segmentId=(int)$db->setQuery('SELECT id FROM #__joomleague_match_score_segment WHERE match_id='.(int)$matchId.' AND parent_id IS NULL')->loadResult();
  $db->setQuery("INSERT INTO #__joomleague_match_event (uuid,match_id,project_id,event_code,event_name_key,sequence_number,score_segment_id,profile_metadata_json) VALUES (".$db->quote(Joomleague\Component\Joomleague\Domain\Service\UuidFactory::v4()).','.(int)$matchId.",1,'goal','test',9999,".$segmentId.",'{}')")->execute();
  $eventId=(int)$db->insertid();
  $check('repair-segment-reference-'.$matchId,($repair->preview($matchId,$actor)['reason']??'')==='referenced_score_segments' && ($repair->preview($matchId,$actor,true)['reason']??'')==='referenced_score_segments');
  $db->setQuery('DELETE FROM #__joomleague_match_event WHERE id='.$eventId)->execute();
  try{$repair->apply($matchId,str_repeat('0',64),$actor);throw new RuntimeException('Stale revision accepted');}catch(RuntimeException $e){if($e->getMessage()==='Stale revision accepted')throw $e;}
  $guest=new Joomla\CMS\User\User();
  try{$repair->preview($matchId,$guest);throw new RuntimeException('Guest accepted');}catch(RuntimeException $e){if($e->getMessage()==='Guest accepted')throw $e;}
  try{$repair->preview($matchId,$guest,true);throw new RuntimeException('Overwrite guest accepted');}catch(RuntimeException $e){if($e->getMessage()==='Overwrite guest accepted')throw $e;}
  $edited=$broken;$edited['notes']='Manual correction';$repository->replace($matchId,$edited,0);
  $check('repair-manual-conflict-'.$matchId,$repair->preview($matchId,$actor)['status']==='conflict');
  try{$repair->apply($matchId,$preview['revision'],$actor);throw new RuntimeException('Concurrent edit overwritten');}catch(RuntimeException $e){if($e->getMessage()==='Concurrent edit overwritten')throw $e;}
  $check('repair-preserve-edit-'.$matchId,$repository->get($matchId)['notes']==='Manual correction');
  $edited['segments'][0]['values'][0]['numeric_value']='9';
  $repository->replace($matchId,$edited,(int)$actor->id);
  $preview=$repair->preview($matchId,$actor,true);
  $check('repair-overwrite-preview-'.$matchId,$preview['status']==='candidate');
  $repair->apply($matchId,$preview['revision'],$actor,true);
  $check('repair-overwrite-preserves-notes-'.$matchId,$repository->get($matchId)['notes']==='Manual correction');
  $repaired=$repository->get($matchId);
  $check('repair-final-'.$matchId,array_column($repaired['segments'][0]['values'],'numeric_value')===array_column($correct['segments'][0]['values'],'numeric_value'));
  $check('repair-idempotent-'.$matchId,$repair->preview($matchId,$actor)['status']==='already_repaired');
 }
 $before=$counts();$beforeResults=[];foreach([1,2,3,4] as $id)$beforeResults[$id]=$repository->get($id);
 $rejected=false;try{$service->import($sql);}catch(Throwable){$rejected=true;}
 $afterResults=[];foreach([1,2,3,4] as $id)$afterResults[$id]=$repository->get($id);
 $check('repair-reimport-preserved',$rejected && $counts()===$before && $beforeResults===$afterResults);
 if($matchCount===6){
  $incomplete=$repository->get(6);
  $incomplete['status_code']='final';$incomplete['outcome_code']='completed';$incomplete['notes']='Preserve unresolved source';
  $incomplete['metadata']=['legacy'=>$issue['legacy'],'legacy_aggregate_only'=>true];
  $incomplete['segments'][0]['metadata']=[];
  foreach([0,1] as $side){$incomplete['segments'][0]['values'][$side]['participant_id']=9+$side;$incomplete['segments'][0]['values'][$side]['numeric_value']=(string)$issue['legacy']['team'.($side+1).'_result'];}
  $repository->replace(5,$incomplete,0);
 }
 $old=$repository->get(2);unset($old['metadata']['legacy_extended_repair']);
 $old['segments'][0]['values'][0]['numeric_value']='9';
 $repository->replace(2,$old,(int)$actor->id);
 Joomla\CMS\Factory::getApplication()->loadIdentity($actor);
 $installer=new Joomla\CMS\Installer\Installer();
 $installer->setDatabase($db);
 if(!$installer->update('/opt/candidate'))throw new RuntimeException('Candidate update failed');
 $updated=$repository->get(2);
 if(!isset($updated['metadata']['legacy_extended_repair']))throw new RuntimeException('Installer repair absent: '.json_encode(['preview'=>$repair->preview(2,$actor,true),'messages'=>$app->getMessageQueue()],JSON_THROW_ON_ERROR));
 $check('repair-installer-update',isset($updated['metadata']['legacy_extended_repair']) && Joomleague\Component\Joomleague\Domain\Service\StandingsDecimal::compare($updated['segments'][0]['values'][0]['numeric_value'],'1')===0 && $updated['notes']==='Manual correction');
 if($matchCount===6){
  $draft=$repository->get(5);
  $check('incomplete-update-quarantined',$draft['status_code']==='draft' && $draft['outcome_code']===null && ($draft['metadata']['legacy_result_issue']['code']??'')==='legacy_result_requires_review' && $draft['notes']==='Preserve unresolved source' && Joomleague\Component\Joomleague\Domain\Service\CanonicalJson::checksum($draft['metadata']['legacy'])===Joomleague\Component\Joomleague\Domain\Service\CanonicalJson::checksum($issue['legacy']));
  $public=(new Joomleague\Component\Joomleague\Domain\Service\ProgrammeReader($db))->forProject(1,null,[1]);
  $hidden=array_values(array_filter($public,static fn($row)=>(int)$row['id']===5));
  $check('incomplete-excluded-from-public-final',count($hidden)===1 && $hidden[0]['played']===false && array_column($hidden[0]['participants'],'score')===[null,null]);
  $context=$reader->context(1,null,null);$scope=$context['default_scope'];
  $input=(new Joomleague\Component\Joomleague\Domain\Service\StandingsRecalculator($db,$reader))->inputForCalculation($context,$scope);
  $verified=(new Joomleague\Component\Joomleague\Domain\Service\StandingsCalculator())->calculate($context['contract'],$input['entries'],$input['matches'],$scope,$input['adjustments']);
  $verifiedMetrics=array_column($verified,'metrics','id');
  $check('incomplete-excluded-from-standings',(float)$verifiedMetrics[1]['played']===5.0 && (float)$verifiedMetrics[1]['score_for']===6.0 && (float)$verifiedMetrics[2]['score_for']===10.0);
 }
 $beforeRetry=$repository->get(2);
 if(!$installer->update('/opt/candidate'))throw new RuntimeException('Repeated candidate update failed');
 $check('repair-installer-idempotent',$repository->get(2)===$beforeRetry);
 if($matchCount===6)$check('incomplete-update-idempotent',$repository->get(5)===$draft);
 // Exercise real archive mappings through the native validator and repository,
 // exclusively inside the disposable test database. Restore the browser fixture.
 $saved=$repository->get(2);
 $cases=json_decode(file_get_contents('/opt/r1-fixtures/legacy-football-reviewed.json'),true,512,JSON_THROW_ON_ERROR);
 foreach($cases as $case){
  $plan=Joomleague\Component\Joomleague\Administrator\Service\LegacyFootballResultMapping::map($case['source'],$case['profile'],$case['project']);
  $payload=$saved;$root=$saved['segments'][0];$root['children']=[];
  $root['metadata']=[];
  foreach([0,1] as $side)$root['values'][$side]['numeric_value']=$plan['final'][$side];
  foreach($plan['children'] as [$code,$ordinal,$scores]){
   $child=$root;$child['level_code']=$code;$child['segment_type_ordinal']=$ordinal;
   $child['metadata']=['legacy_phase_aggregate'=>true,'decider'=>$code==='shootout'];
   foreach([0,1] as $side)$child['values'][$side]['numeric_value']=$scores[$side];
   $root['children'][]=$child;
  }
  if($plan['outcome']==='forfeit')$root['metadata']['legacy_awarded_result']=$plan['resolution'];
  $payload['segments']=[$root];$payload['outcome_code']=$plan['outcome'];
  $payload['metadata']['legacy_result_resolution']=$plan['resolution'];
  (new Joomleague\Component\Joomleague\Administrator\Service\MatchResultPayloadValidator())->validate($profile,array_column($root['values'],'participant_id'),$payload);
  $repository->replace(2,$payload,(int)$actor->id);
  $actual=$repository->get(2);
  $values=array_map(static fn($value)=>Joomleague\Component\Joomleague\Administrator\Service\LegacyFootballResultMapping::score($value),array_column($actual['segments'][0]['values'],'numeric_value'));
  $check('reviewed-roundtrip-'.$case['source']['id'],$values===$case['expected']['final'] && $actual['outcome_code']===$case['expected']['outcome'] && Joomleague\Component\Joomleague\Domain\Service\CanonicalJson::checksum($actual['metadata']['legacy_result_resolution'])===Joomleague\Component\Joomleague\Domain\Service\CanonicalJson::checksum($plan['resolution']));
 }
 $repository->replace(2,$saved,(int)$actor->id);
}
file_put_contents('/tmp/jl-migration-report.json',json_encode(['actorId'=>(int)($actor->id??0),'extendedResults'=>in_array(count($programme??[]),[4,6],true),'conflictResults'=>$matchCount===6,'observations'=>$observations],JSON_THROW_ON_ERROR));
