<?php
declare(strict_types=1);
if(PHP_SAPI!=='cli'||!str_starts_with((string)getenv('JL_R1_DB_HOST'),'jl-r1-'))throw new RuntimeException('Disposable target required');
define('_JEXEC',1);define('JPATH_BASE','/var/www/html');
$_SERVER['HTTP_HOST']='localhost';$_SERVER['SCRIPT_NAME']='/index.php';$_SERVER['REQUEST_URI']='/';
require JPATH_BASE.'/includes/defines.php';require JPATH_BASE.'/includes/framework.php';
$container=Joomla\CMS\Factory::getContainer();
$container->alias('session','session.cli')->alias(Joomla\Session\SessionInterface::class,'session.cli');
$app=$container->get(Joomla\CMS\Application\SiteApplication::class);Joomla\CMS\Factory::$application=$app;
if($app->get('host')!==getenv('JL_R1_DB_HOST')||$app->get('db')!=='r1_test'||$app->get('dbprefix')!=='r1_')throw new RuntimeException('Unsafe target');
$app->createExtensionNamespaceMap();$app->bootComponent('com_joomleague');
$fixture=json_decode(file_get_contents('/tmp/jl-r1-fixture.json'),true,512,JSON_THROW_ON_ERROR);
$db=$container->get(Joomla\Database\DatabaseInterface::class);
$action=$argv[1]??'state';
if($action==='assets-fixture'){
    $db->setQuery($db->getQuery(true)->update($db->quoteName('#__extensions'))->set('enabled = 1')->where("type = 'plugin'")->where("element = 'joomleague'")->whereIn('folder',['content','quickicon'],Joomla\Database\ParameterType::STRING))->execute();
    $a=$fixture['projects']['a'];$project=(int)$a['id'];
    $sport=(int)$db->setQuery($db->getQuery(true)->select('sport_type_id')->from($db->quoteName('#__joomleague_project'))->where('id = :project')->bind(':project',$project,Joomla\Database\ParameterType::INTEGER))->loadResult();
    $position=(object)['uuid'=>Joomleague\Component\Joomleague\Domain\Service\UuidFactory::v4(),'sport_type_id'=>$sport,'code'=>'audit_asset_position','name'=>'Audit asset position','person_type'=>'player','role_domain'=>'participant','published'=>1];$db->insertObject('#__joomleague_sport_position',$position,'id');
    $event=(object)['uuid'=>Joomleague\Component\Joomleague\Domain\Service\UuidFactory::v4(),'sport_type_id'=>$sport,'code'=>'audit_asset_event','name'=>'Audit asset event','person_type'=>'player','published'=>1];$db->insertObject('#__joomleague_event_type',$event,'id');
    $stat=(object)['uuid'=>Joomleague\Component\Joomleague\Domain\Service\UuidFactory::v4(),'sport_type_id'=>$sport,'code'=>'audit_asset_stat','name'=>'Audit asset statistic','scope'=>'person','published'=>1];$db->insertObject('#__joomleague_statistic',$stat,'id');
    $otherProject=(int)$fixture['projects']['b']['id'];$otherSport=(int)$db->setQuery($db->getQuery(true)->select('sport_type_id')->from($db->quoteName('#__joomleague_project'))->where('id = :project')->bind(':project',$otherProject,Joomla\Database\ParameterType::INTEGER))->loadResult();
    $foreign=(object)['uuid'=>Joomleague\Component\Joomleague\Domain\Service\UuidFactory::v4(),'sport_type_id'=>$otherSport,'code'=>'audit_foreign_event','name'=>'Audit foreign event','person_type'=>'player','published'=>1];$db->insertObject('#__joomleague_event_type',$foreign,'id');
    $meta=['position'=>(int)$position->id,'event'=>(int)$event->id,'statistic'=>(int)$stat->id,'foreign'=>(int)$foreign->id];file_put_contents('/tmp/jl-assets.json',json_encode($meta,JSON_THROW_ON_ERROR));echo json_encode($meta,JSON_THROW_ON_ERROR);exit;
}
if(in_array($action,['assets-links','assets-stat-links'],true)){
    $meta=json_decode(file_get_contents('/tmp/jl-assets.json'),true,512,JSON_THROW_ON_ERROR);$position=$meta['position'];
    $repo=new Joomleague\Component\Joomleague\Administrator\Service\PositionCapabilityRepository($db);
    echo json_encode(($action==='assets-links'?$repo->events($position):$repo->statistics($position))['assigned'],JSON_THROW_ON_ERROR);exit;
}
if($action==='plugin-link'){
    $app->loadIdentity(new Joomla\CMS\User\User());$app->loadLanguage(Joomla\CMS\Language\Language::getInstance('en-GB'));
    $item=(object)['text'=>'{joomleague-person '.(int)$fixture['person'].'}'];
    $event=new Joomla\CMS\Event\Content\ContentPrepareEvent('onContentPrepare',['context'=>'com_content.article','subject'=>$item,'params'=>new Joomla\Registry\Registry(),'page'=>0]);
    $app->bootPlugin('joomleague','content')->onContentPrepare($event);echo json_encode(['html'=>$item->text],JSON_THROW_ON_ERROR);exit;
}
if(preg_match('/^family-(browser|state|clean)-(tennis|running_race|mma_boxing)$/',$action,$parts)){
    require __DIR__.'/ResultFamilyAcceptance.php';$code=$parts[2];
    if($parts[1]==='browser'){echo json_encode(verifyResultFamily($db,$code,$fixture,true),JSON_THROW_ON_ERROR);exit;}
    $path='/tmp/jl-family-'.$code.'.json';$metadata=json_decode(file_get_contents($path),true,512,JSON_THROW_ON_ERROR);
    if($parts[1]==='state'){echo json_encode((new Joomleague\Component\Joomleague\Administrator\Service\MatchResultRepository($db))->get($metadata['match']),JSON_THROW_ON_ERROR);exit;}
    foreach(['project','sport_type'] as $table){$id=(int)$metadata[$table==='project'?'project':'sport'];$db->setQuery($db->getQuery(true)->delete($db->quoteName('#__joomleague_'.$table))->where('id = :id')->bind(':id',$id,Joomla\Database\ParameterType::INTEGER))->execute();}
    foreach($metadata['before'] as $table=>$expected){$actual=(int)$db->setQuery($db->getQuery(true)->select('COUNT(*)')->from($db->quoteName('#__joomleague_'.$table)))->loadResult();if($actual!==$expected)throw new RuntimeException('Browser family fixture cleanup failed');}
    unlink($path);echo json_encode(['clean'=>true],JSON_THROW_ON_ERROR);exit;
}
if(str_starts_with($action,'family-')){
    require __DIR__.'/ResultFamilyAcceptance.php';
    echo json_encode(verifyResultFamily($db,substr($action,7),$fixture),JSON_THROW_ON_ERROR);exit;
}
if($action==='ranking-fixture'){
    $a=$fixture['projects']['a'];$match=(int)$a['records']['Match'];
    $participant=(int)$db->setQuery($db->getQuery(true)->select('id')->from($db->quoteName('#__joomleague_match_participant'))->where('match_id = :match')->order('slot_number')->bind(':match',$match,Joomla\Database\ParameterType::INTEGER))->loadResult();
    $sequence=(int)$db->setQuery($db->getQuery(true)->select('MAX(sequence_number)')->from($db->quoteName('#__joomleague_match_event'))->where('match_id = :match')->bind(':match',$match,Joomla\Database\ParameterType::INTEGER))->loadResult();
    foreach(['person','participant'] as $kind){
        $event=(object)['uuid'=>Joomleague\Component\Joomleague\Domain\Service\UuidFactory::v4(),'match_id'=>$match,'project_id'=>$a['id'],'match_participant_id'=>$participant,'event_code'=>'audit_'.$kind,'event_name_key'=>'Audit '.$kind,'sequence_number'=>++$sequence,'profile_metadata_json'=>'{}'];
        if($kind==='person'){$event->primary_person_id=$fixture['person'];$event->primary_name_snapshot='R1 Player';}
        $db->insertObject('#__joomleague_match_event',$event,'id');
        $stat=(object)['uuid'=>Joomleague\Component\Joomleague\Domain\Service\UuidFactory::v4(),'match_id'=>$match,'project_id'=>$a['id'],'match_participant_id'=>$participant,'statistic_code'=>'audit_'.$kind,'statistic_name_key'=>'Audit '.$kind,'statistic_type'=>'basic','scope_code'=>'match','value_type'=>'integer','calculation_source'=>'manual_or_import','target_kind'=>$kind,'target_key'=>$kind.':'.$participant,'target_name_snapshot'=>'Audit '.$kind,'numeric_value'=>2,'profile_metadata_json'=>'{}'];
        if($kind==='person')$stat->person_id=$fixture['person'];
        $db->insertObject('#__joomleague_match_statistic_value',$stat,'id');
    }
    echo json_encode(['created'=>4],JSON_THROW_ON_ERROR);exit;
}
if($action==='rankings'){
    $project=$fixture['projects']['a']['id'];$result=[];
    foreach(['person','participant'] as $kind){
        $result[$kind]=['events'=>(new Joomleague\Component\Joomleague\Domain\Service\EventRankingReader($db))->forProject($project,'audit_'.$kind,10,[1])['rows'],'statistics'=>(new Joomleague\Component\Joomleague\Domain\Service\StatisticRankingReader($db))->forProject($project,'audit_'.$kind,10,[1])['rows']];
    }
    $match=(int)$fixture['projects']['a']['records']['Match'];
    foreach(['match_event','match_statistic_value'] as $table)$result['stored'][$table]=$db->setQuery($db->getQuery(true)->select('*')->from($db->quoteName('#__joomleague_'.$table))->where('match_id = :match')->order('id')->bind(':match',$match,Joomla\Database\ParameterType::INTEGER))->loadAssocList();
    echo json_encode($result,JSON_THROW_ON_ERROR);exit;
}
if(in_array($action,['rankings-private','rankings-public'],true)){
    $access=$action==='rankings-private'?2:1;$person=(int)$fixture['person'];$entry=(int)$fixture['projects']['a']['entries'][0];
    $team=(int)$db->setQuery($db->getQuery(true)->select('team_id')->from($db->quoteName('#__joomleague_project_entry'))->where('id = :entry')->bind(':entry',$entry,Joomla\Database\ParameterType::INTEGER))->loadResult();
    foreach([['person',$person],['team',$team]] as [$table,$id]){$row=(object)['id'=>$id,'access'=>$access];$db->updateObject('#__joomleague_'.$table,$row,'id');}
    echo json_encode(['access'=>$access],JSON_THROW_ON_ERROR);exit;
}
if($action==='bracket-fixture'){
    $a=$fixture['projects']['a'];$matches=[];
    foreach([2,3] as $sequence){
        $round=(object)['uuid'=>Joomleague\Component\Joomleague\Domain\Service\UuidFactory::v4(),'published'=>1,'project_id'=>$a['id'],'stage_id'=>$a['stage'],'name'=>$sequence===2?'Bracket </script><script>window.bracketInjected=1</script>':'Bracket final','code'=>'bracket_round_'.$sequence,'sequence_number'=>$sequence];
        $db->insertObject('#__joomleague_project_round',$round,'id');
        $match=(object)['uuid'=>Joomleague\Component\Joomleague\Domain\Service\UuidFactory::v4(),'published'=>1,'project_id'=>$a['id'],'stage_id'=>$a['stage'],'round_id'=>$round->id,'match_number'=>'Bracket '.$sequence,'code'=>'bracket_match_'.$sequence];
        $db->insertObject('#__joomleague_project_match',$match,'id');$matches[]=(int)$match->id;
        foreach($a['entries'] as $slot=>$entry){
            $participant=(object)['uuid'=>Joomleague\Component\Joomleague\Domain\Service\UuidFactory::v4(),'published'=>1,'match_id'=>$match->id,'project_id'=>$a['id'],'project_entry_id'=>$entry,'slot_number'=>$slot+1];
            $db->insertObject('#__joomleague_match_participant',$participant,'id');
        }
    }
    echo json_encode(['matches'=>$matches],JSON_THROW_ON_ERROR);exit;
}
if($action==='progression'){
    $a=$fixture['projects']['a'];
    $query=$db->getQuery(true)->select('id')->from($db->quoteName('#__joomleague_stage_transition'))->where('project_id = :project')->where('code = :code');
    $code='football_outcome';$project=(int)$a['id'];$query->bind(':project',$project,Joomla\Database\ParameterType::INTEGER)->bind(':code',$code);
    $id=(int)$db->setQuery($query)->loadResult();
    if(!$id){
        $stage=(object)['project_id'=>$project,'name'=>'Football winner target','code'=>'football_winner_target','stage_type'=>'league_phase'];
        $stage->uuid=Joomleague\Component\Joomleague\Domain\Service\UuidFactory::v4();$stage->published=1;
        $db->insertObject('#__joomleague_project_stage',$stage,'id');
        $transition=(object)['project_id'=>$project,'source_stage_id'=>$a['stage'],'target_stage_id'=>$stage->id,'code'=>$code,'name'=>'Football outcome','selector_type'=>'match_outcome','selector_config_json'=>json_encode(['outcome'=>'winner'],JSON_THROW_ON_ERROR)];
        $transition->uuid=Joomleague\Component\Joomleague\Domain\Service\UuidFactory::v4();$transition->published=1;
        $db->insertObject('#__joomleague_stage_transition',$transition,'id');$id=(int)$transition->id;
    }
    $service=new Joomleague\Component\Joomleague\Administrator\Service\StageProgressionService($db);
    $preview=$service->preview($id);$service->apply($id,(int)$fixture['users']['a']);
    $target=(int)$preview['transition']->target_stage_id;
    $query=$db->getQuery(true)->select('entry_id')->from($db->quoteName('#__joomleague_stage_entry'))->where('stage_id = :stage')->order('entry_id')->bind(':stage',$target,Joomla\Database\ParameterType::INTEGER);
    echo json_encode(['preview'=>array_column($preview['entries'],'id'),'assigned'=>array_map('intval',$db->setQuery($query)->loadColumn())],JSON_THROW_ON_ERROR);exit;
}
if($action==='summary'){
    $project=$fixture['projects']['a']['id'];
    $app->loadIdentity($container->get(Joomla\CMS\User\UserFactoryInterface::class)->loadUserById(0));
    $app->loadLanguage(Joomla\CMS\Language\Language::getInstance('en-GB'));
    $params=new Joomla\Registry\Registry(['project_id'=>$project,'mode'=>'upcoming']);
    $program=(new Joomleague\Module\Program\Site\Helper\ProgramHelper())->getProgramme($params);
    $next=(new Joomleague\Module\NextEvent\Site\Helper\NextEventHelper())->getEvent($params);
    $model=$app->bootComponent('com_joomleague')->getMVCFactory()->createModel('Teamplan','Site',['ignore_request'=>true]);
    $model->setState('project_id',$project);$model->setState('entry_id',$fixture['projects']['a']['entries'][0]);
    echo json_encode(['statistics'=>(new Joomleague\Component\Joomleague\Domain\Service\ProjectStatisticsReader($db))->forProject($project,[1]),'programme'=>(new Joomleague\Component\Joomleague\Domain\Service\ProgrammeReader($db))->forProject($project,null,[1]),'program_module'=>$program,'next_module'=>$next,'teamplan'=>$model->getPlan()],JSON_THROW_ON_ERROR);exit;
}
if($action==='standings'){
    $reader=new Joomleague\Component\Joomleague\Domain\Service\StandingsReader($db);
    echo json_encode($reader->current($fixture['projects']['a']['id'],null,'total'),JSON_THROW_ON_ERROR);exit;
}
if($action!=='state')throw new InvalidArgumentException('Unknown action');
$state=[];
foreach(['project_match','match_participant','match_result','match_score_segment','match_score_value','standing_freshness','standing_current']as$table){
    $order=in_array($table,['standing_freshness','standing_current'],true)?'project_id, stage_key, scope_code':'id';
    $state[$table]=$db->setQuery('SELECT * FROM '.$db->quoteName('#__joomleague_'.$table).' ORDER BY '.$order)->loadAssocList();
}
echo json_encode($state,JSON_THROW_ON_ERROR);
