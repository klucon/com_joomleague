<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli' || !str_starts_with((string) getenv('JL_R1_DB_HOST'), 'jl-r1-')) throw new RuntimeException('Disposable target required.');
define('_JEXEC', 1);
define('JPATH_BASE', '/var/www/html');
$_SERVER['HTTP_HOST']='localhost';$_SERVER['SCRIPT_NAME']='/index.php';$_SERVER['REQUEST_URI']='/';
require JPATH_BASE . '/includes/defines.php';
require JPATH_BASE . '/includes/framework.php';
$container=Joomla\CMS\Factory::getContainer();
$container->alias('session','session.cli')->alias(Joomla\Session\SessionInterface::class,'session.cli');
$app=$container->get(Joomla\CMS\Application\SiteApplication::class);
Joomla\CMS\Factory::$application=$app;
if ($app->get('host')!==getenv('JL_R1_DB_HOST') || $app->get('db')!=='r1_test' || $app->get('dbprefix')!=='r1_') throw new RuntimeException('Unsafe target.');
$app->createExtensionNamespaceMap();$app->bootComponent('com_joomleague');
$app->loadIdentity(new Joomla\CMS\User\User());
$db=$container->get(Joomla\Database\DatabaseInterface::class);
$base=json_decode(file_get_contents('/tmp/jl-r1-fixture.json'),true,512,JSON_THROW_ON_ERROR);
$insert=static function(string $table,array $data,bool $published=true)use($db):int{
 $row=(object)(['uuid'=>Joomleague\Component\Joomleague\Domain\Service\UuidFactory::v4()]+$data+($published?['published'=>1]:[]));
 $db->insertObject('#__joomleague_'.$table,$row,'id');return (int)$row->id;
};
$own=$insert('club',['name'=>'R82 own club']);$opponent=$insert('club',['name'=>'R82 opponent club']);
$teams=[];$entries=[];
foreach(['ownA'=>[$own,'R82 First team'],'ownB'=>[$own,'R82 Reserve team'],'oppA'=>[$opponent,'R82 Opponent first'],'oppB'=>[$opponent,'R82 Opponent reserves'],'private'=>[$opponent,'R82 Private opponent']] as $key=>[$club,$name]){
 $teams[$key]=$insert('team',['club_id'=>$club,'name'=>$name,'access'=>$key==='private'?2:1]);
 foreach(['a','b'] as $p)$entries[$p][$key]=$insert('project_entry',['project_id'=>$base['projects'][$p]['id'],'entry_kind'=>'team','team_id'=>$teams[$key]]);
}
$person=$insert('person',['first_name'=>'R82','last_name'=>'Referee']);$matches=[];
foreach([
 ['a','ownA','oppA',[2,0]],['b','ownB','oppA',[0,3]],
 ['a','oppB','ownA',[1,1]],['b','ownB','oppB',null],['a','ownA','private',[9,0]],
] as $i=>[$p,$home,$away,$scores]){
 $project=$base['projects'][$p];
 $match=$insert('project_match',['project_id'=>$project['id'],'stage_id'=>$project['stage'],'round_id'=>$project['round'],'scheduled_start'=>'2026-10-'.(10+$i).' 12:00:00','status_code'=>$scores===null?'scheduled':'completed','match_number'=>'R82-'.($i+1)]);
 $matches[]=$match;
 $participants=[];
 foreach([$home,$away] as $slot=>$key)$participants[]=$insert('match_participant',['project_id'=>$project['id'],'match_id'=>$match,'project_entry_id'=>$entries[$p][$key],'slot_number'=>$slot+1,'role_code'=>$slot===0?'home':'away']);
 $insert('match_actor_role',['project_id'=>$project['id'],'match_id'=>$match,'actor_kind'=>'person','person_id'=>$person,'role_code'=>'referee','person_type'=>'referee','role_domain'=>'adjudicator','display_name_snapshot'=>'R82 Referee']);
 if($scores!==null){
  $insert('match_result',['match_id'=>$match,'result_type'=>'numeric_score','status_code'=>'final','outcome_code'=>'completed'],false);
  $root=$insert('match_score_segment',['match_id'=>$match,'level_code'=>'result','sequence_number'=>1],false);
  foreach($scores as $slot=>$score)$insert('match_score_value',['match_id'=>$match,'segment_id'=>$root,'participant_id'=>$participants[$slot],'numeric_value'=>(string)$score],false);
 }
}
$component=$db->setQuery("SELECT extension_id,params FROM #__extensions WHERE type='component' AND element='com_joomleague'")->loadObject();
$params=new Joomla\Registry\Registry($component->params);$params->set('site_club_id',$own);$component->params=$params->toString();
$db->updateObject('#__extensions',$component,'extension_id');Joomla\CMS\Component\ComponentHelper::getParams('com_joomleague')->set('site_club_id',$own);
$read=static function(int $team=0,int $project=0,int $clubFilter=0)use($person,$own):array{
 $model=new Joomleague\Component\Joomleague\Site\Model\PersonModel(['ignore_request'=>true]);
 $model->setState('person_id',$person);$model->setState('site_club_id',$own);
 $model->setState('filter.team_id',$team);$model->setState('filter.project_id',$project);$model->setState('filter.opponent_club_id',$clubFilter);
 return $model->getPerson()['officiating_summary'];
};
$checks=[];$assert=static function(bool $condition,string $id)use(&$checks):void{if(!$condition)throw new RuntimeException($id);$checks[]=$id;};
$all=$read();$a=$read($teams['ownA']);$b=$read($teams['ownB']);
$assert(count($all['filters']['teams'])===2 && isset($all['filters']['teams'][$teams['ownA']],$all['filters']['teams'][$teams['ownB']]),'own-club-team-options');
$assert($all['site_club_stats']['played']===3 && $all['site_club_stats']['scored']==='3' && $all['site_club_stats']['conceded']==='4','club-total-preserved');
$assert(count($all['site_club_stats']['opponents'])===2 && count(array_unique(array_column($all['site_club_stats']['opponents'],'team_id')))===2,'opponent-squads-not-merged');
$assert($a['total']===2 && $a['site_club_stats']['played']===2 && $a['site_club_stats']['scored']==='3' && $a['site_club_stats']['conceded']==='1','first-team-isolated');
$assert($b['total']===2 && $b['completed']===1 && $b['site_club_stats']['played']===1 && $b['site_club_stats']['scored']==='0' && $b['site_club_stats']['conceded']==='3','reserve-includes-upcoming-not-first');
$assert($read($teams['ownA'],$base['projects']['b']['id'])['total']===0,'project-team-intersection');
$assert($read($teams['ownB'],0,$opponent)['total']===1,'club-filter-compatible');
$assert($read($teams['oppA'])['total']===0,'foreign-team-fails-closed');
$assert($read($teams['private'])['total']===0 && !isset($all['filters']['teams'][$teams['private']]),'private-team-fails-closed');
$assert($read(99999999)['total']===0,'unknown-team-fails-closed');
$assert($a['team']['name']==='R82 First team' && $b['team']['name']==='R82 Reserve team','selected-team-label');
$assert(array_column($a['site_club_stats']['matches'],'match_id')===[$matches[0],$matches[2]],'exact-match-identity');
$db->transactionStart();
try{
 $p=$base['projects']['a'];
 $derby=$insert('project_match',['project_id'=>$p['id'],'stage_id'=>$p['stage'],'round_id'=>$p['round'],'status_code'=>'completed']);
 $root=$insert('match_score_segment',['match_id'=>$derby,'level_code'=>'result','sequence_number'=>1],false);
 $insert('match_result',['match_id'=>$derby,'result_type'=>'numeric_score','status_code'=>'final','outcome_code'=>'completed'],false);
 $insert('match_actor_role',['project_id'=>$p['id'],'match_id'=>$derby,'actor_kind'=>'person','person_id'=>$person,'role_code'=>'referee','person_type'=>'referee','role_domain'=>'adjudicator','display_name_snapshot'=>'R82 Referee']);
 foreach(['ownA','ownB'] as $slot=>$key){
  $participant=$insert('match_participant',['project_id'=>$p['id'],'match_id'=>$derby,'project_entry_id'=>$entries['a'][$key],'slot_number'=>$slot+1]);
  $insert('match_score_value',['match_id'=>$derby,'segment_id'=>$root,'participant_id'=>$participant,'numeric_value'=>$slot===0?'1':'0'],false);
 }
 $derbyA=$read($teams['ownA']);$derbyB=$read($teams['ownB']);
 $assert($derbyA['site_club_stats']['played']===3 && $derbyA['site_club_stats']['scored']==='4' && $derbyB['site_club_stats']['played']===2 && $derbyB['site_club_stats']['conceded']==='4','same-club-derby-team-perspective');
}finally{$db->transactionRollback();}
foreach([['team',$teams['ownA']],['club',$own],['project_stage',$base['projects']['a']['stage']]] as [$table,$id]){
 $db->transactionStart();
 try{
  $record=(object)['id'=>$id,'published'=>0];$db->updateObject('#__joomleague_'.$table,$record,'id');
  $hidden=$read($teams['ownA']);$assert($hidden['total']===0 && !isset($hidden['filters']['teams'][$teams['ownA']]),'hidden-parent-'.$table);
 }finally{$db->transactionRollback();}
}
$fixture=['person'=>$person,'teams'=>$teams,'matches'=>$matches,'projects'=>array_map(static fn($p)=>$p['id'],$base['projects'])];
file_put_contents('/tmp/jl-referee-scope.json',json_encode(['passed'=>true,'checks'=>$checks,'fixture'=>$fixture],JSON_THROW_ON_ERROR));
echo 'PASS: referee team scope; '.count($checks)." native assertions.\n";
