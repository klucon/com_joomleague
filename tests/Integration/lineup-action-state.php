<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli' || !str_starts_with((string) getenv('JL_R1_DB_HOST'), 'jl-r1-')) throw new RuntimeException('Disposable target required');
define('_JEXEC', 1); define('JPATH_BASE', '/var/www/html');
$_SERVER['HTTP_HOST']='localhost'; $_SERVER['SCRIPT_NAME']='/index.php'; $_SERVER['REQUEST_URI']='/';
require JPATH_BASE.'/includes/defines.php'; require JPATH_BASE.'/includes/framework.php';
$c=Joomla\CMS\Factory::getContainer(); $c->alias('session','session.cli')->alias(Joomla\Session\SessionInterface::class,'session.cli');
$app=$c->get(Joomla\CMS\Application\SiteApplication::class); Joomla\CMS\Factory::$application=$app;
if ($app->get('host')!==getenv('JL_R1_DB_HOST') || $app->get('db')!=='r1_test' || $app->get('dbprefix')!=='r1_') throw new RuntimeException('Unsafe target');
$app->createExtensionNamespaceMap(); $app->bootComponent('com_joomleague');
$db=$c->get(Joomla\Database\DatabaseInterface::class);
$fixture=json_decode(file_get_contents('/tmp/jl-r1-fixture.json'),true,512,JSON_THROW_ON_ERROR);
$a=$fixture['projects']['a']; $match=(int)$a['records']['Match'];
if (($argv[1]??'')==='diagnose') {
    $logs=[]; foreach(glob($app->get('log_path').'/*')?:[] as $file)if(is_file($file))$logs[basename($file)]=substr(file_get_contents($file),-12000);
    $member=$db->setQuery('SELECT id,match_participant_id FROM #__joomleague_match_lineup_member WHERE match_id='.$match.' ORDER BY id',0,1)->loadObject();
    $db->transactionStart();
    try{(new Joomleague\Component\Joomleague\Administrator\Service\MatchLineupRepository($db))->remove($match,(int)$member->match_participant_id,(int)$member->id);}
    catch(Throwable $e){$logs['exception']=$e->getMessage();}
    finally{try{$db->transactionRollback();}catch(Throwable $e){$logs['rollback']=$e->getMessage();}}
    echo json_encode($logs,JSON_THROW_ON_ERROR);exit;
}
if (($argv[1]??'')==='setup') {
    $insert=static function(string $table,array $values)use($db):int{
        $row=(object)(['uuid'=>Joomleague\Component\Joomleague\Domain\Service\UuidFactory::v4()]+$values);
        $db->insertObject('#__joomleague_'.$table,$row,'id'); return (int)$row->id;
    };
    $members=[(int)$a['records']['Entrymember']];
    for($i=2;$i<=14;$i++){
        $person=$insert('person',['first_name'=>'Lineup','last_name'=>'Member '.$i]);
        $members[]=$insert('project_entry_member',['entry_id'=>(int)$a['entries'][0],'person_id'=>$person,'member_person_type'=>$i===14?'staff':'player','role_code'=>$i===14?'head_coach':'defender']);
    }
    $participant=(int)$db->setQuery('SELECT id FROM #__joomleague_match_participant WHERE match_id='.$match.' AND slot_number=1')->loadResult();
    $official = $insert('person', ['first_name'=>'S101', 'last_name'=>'Referee']);
    $profile = (new Joomleague\Component\Joomleague\Administrator\Service\ProjectContextRepository($db))->get((int)$a['id'])->profile;
    $role = array_values(array_filter($profile['positions'], static fn(array $role):bool => $role['code']==='referee'))[0];
    $pool = $insert('project_actor_role', ['project_id'=>$a['id'],'actor_kind'=>'person','person_id'=>$official,'person_type'=>$role['person_type'],'role_code'=>'referee','role_domain'=>$role['role_domain']]);
    echo json_encode(['match'=>$match,'round'=>(int)$a['round'],'participant'=>$participant,'players'=>array_slice($members,0,13),'staff'=>$members[13],'official'=>$official,'official_pool'=>$pool,'official_domain'=>$role['role_domain']],JSON_THROW_ON_ERROR); exit;
}
if (($argv[1]??'')==='officials') {
    echo json_encode($db->setQuery('SELECT id,role_code,role_domain FROM #__joomleague_match_actor_role WHERE match_id='.$match.' AND published=1 ORDER BY id')->loadAssocList(),JSON_THROW_ON_ERROR); exit;
}
if (($argv[1]??'')==='substitutions') {
    echo json_encode($db->setQuery('SELECT id FROM #__joomleague_match_lineup_change WHERE match_id='.$match)->loadColumn(),JSON_THROW_ON_ERROR); exit;
}
$rows=$db->setQuery('SELECT id,source_entry_member_id,member_person_type,lineup_status FROM #__joomleague_match_lineup_member WHERE match_id='.$match.' ORDER BY id')->loadAssocList();
echo json_encode($rows,JSON_THROW_ON_ERROR);
