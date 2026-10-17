<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli' || preg_match('/^[a-f0-9]{32}$/D', $argv[1] ?? '') !== 1) exit(1);
define('_JEXEC', 1); define('JPATH_BASE', '/var/www/html');
$_SERVER['HTTP_HOST']='joomla62.local'; $_SERVER['REQUEST_URI']='/'; $_SERVER['SCRIPT_NAME']='/index.php';
require JPATH_BASE.'/includes/defines.php'; require JPATH_BASE.'/includes/framework.php';
$container=Joomla\CMS\Factory::getContainer();
$container->alias('session','session.cli')->alias(Joomla\Session\SessionInterface::class,'session.cli');
$app=$container->get(Joomla\CMS\Application\SiteApplication::class); Joomla\CMS\Factory::$application=$app;
$app->createExtensionNamespaceMap();
$db=$container->get(Joomla\Database\DatabaseInterface::class);
$admin=(int)$db->setQuery("SELECT id FROM #__users WHERE username='admin'")->loadResult();
$user=$container->get(Joomla\CMS\User\UserFactoryInterface::class)->loadUserById($admin);
$app->loadIdentity($user);
$fixture=json_decode(file_get_contents('/tmp/jl-media-'.$argv[1].'.json'),true,512,JSON_THROW_ON_ERROR);
$candidate = getenv('JOOMLEAGUE_AUDIT_SOURCE_DIR');
if ($candidate) {
    if (!str_starts_with($candidate, '/tmp/')) throw new RuntimeException('Temporary source directory required.');
    require $candidate . '/Model/MatchModel.php';
    require $candidate . '/Service/MatchScheduleEditor.php';
}
$id=0;
foreach($fixture['records'] as [$table,$record]) if($table==='project_match') {$id=(int)$record; break;}
if(!$id) throw new RuntimeException('No owned match fixture.');
$model=$app->bootComponent('com_joomleague')->getMVCFactory()->createModel('Match','Administrator',['ignore_request'=>true]);
$model->setCurrentUser($user);
$data=(array)$model->getItem($id);
$before=$db->setQuery('SELECT * FROM #__joomleague_project_match WHERE id='.$id)->loadAssoc();
$data['description']='Atomicity audit marker'; $data['participant_slot_1']=0; $data['participant_slot_2']=0;
$data['scheduled_date']=''; $data['scheduled_time']='';
$db->transactionStart();
try {
    try {$model->save($data); throw new RuntimeException('Invalid participants unexpectedly saved.');}
    catch (UnexpectedValueException $error) {
        $description=$db->setQuery('SELECT description FROM #__joomleague_project_match WHERE id='.$id)->loadResult();
        echo 'Rejected invalid participants, but parent record already modified = '.($description==='Atomicity audit marker'?'YES':'NO')."\n";
        if ($candidate && $db->setQuery('SELECT * FROM #__joomleague_project_match WHERE id='.$id)->loadAssoc() !== $before) throw new RuntimeException('Rejected save changed the match.');
    }
    if ($candidate) {
        $count=(int)$db->setQuery('SELECT COUNT(*) FROM #__joomleague_project_match')->loadResult();
        $newData=$data; $newData['id']=0;
        try { $model->save($newData); throw new RuntimeException('Invalid new match unexpectedly saved.'); }
        catch (UnexpectedValueException $error) {
            if ((int)$db->setQuery('SELECT COUNT(*) FROM #__joomleague_project_match')->loadResult()!==$count) throw new RuntimeException('Failed creation left an orphan match.');
            if ((int)$model->getState($model->getName().'.id')!==0) throw new RuntimeException('Failed creation retained a rolled-back ID.');
        }
        echo "PASS: failed creation leaves neither an orphan match nor a stale model ID\n";
        $entries=$db->setQuery('SELECT slot_number, project_entry_id FROM #__joomleague_match_participant WHERE match_id='.$id.' ORDER BY slot_number')->loadAssocList();
        if (count($entries)!==2) throw new RuntimeException('Two participant fixture required.');
        $data['participant_slot_1']=(int)$entries[0]['project_entry_id'];
        $data['participant_slot_2']=(int)$entries[1]['project_entry_id'];
        if (!$model->save($data)) throw new RuntimeException('Valid save failed: '.$model->getError());
        if ($db->setQuery('SELECT description FROM #__joomleague_project_match WHERE id='.$id)->loadResult()!=='Atomicity audit marker') throw new RuntimeException('Successful save did not persist within outer transaction.');
        echo "PASS: valid save persists within the caller transaction\n";
        [$data['participant_slot_1'],$data['participant_slot_2']]=[$data['participant_slot_2'],$data['participant_slot_1']];
        $data['description']='Rejected locked swap marker';
        try { $model->save($data); throw new UnexpectedValueException('Locked participant swap unexpectedly succeeded.'); }
        catch (RuntimeException $error) {
            if ($error instanceof UnexpectedValueException) throw $error;
            if ($error->getMessage()!==Joomla\CMS\Language\Text::_('COM_JOOMLEAGUE_ERROR_MATCH_PARTICIPANTS_LOCKED')) throw $error;
            if ($db->setQuery('SELECT description FROM #__joomleague_project_match WHERE id='.$id)->loadResult()!=='Atomicity audit marker') throw new RuntimeException('Locked rejection changed the parent.');
        }
        echo "PASS: locked participant swap rolls back parent changes\n";
    }
} finally { $db->transactionRollback(); }
if ($candidate && $db->setQuery('SELECT * FROM #__joomleague_project_match WHERE id='.$id)->loadAssoc() !== $before) throw new RuntimeException('Inner save committed the outer transaction.');
echo "Outer audit transaction rolled back.\n";
