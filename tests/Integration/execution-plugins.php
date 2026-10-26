<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli' || !str_starts_with((string) getenv('JL_R1_DB_HOST'), 'jl-r1-')) throw new RuntimeException('Disposable target required');
define('_JEXEC', 1);
define('JPATH_BASE', '/var/www/html');
$_SERVER['HTTP_HOST'] = 'localhost';
$_SERVER['REQUEST_URI'] = '/';
$_SERVER['SCRIPT_NAME'] = '/index.php';
require JPATH_BASE . '/includes/defines.php';
require JPATH_BASE . '/includes/framework.php';
$container = Joomla\CMS\Factory::getContainer();
$container->alias('session', 'session.cli')->alias(Joomla\Session\SessionInterface::class, 'session.cli');
$app = $container->get(Joomla\CMS\Application\SiteApplication::class);
Joomla\CMS\Factory::$application = $app;
if ($app->get('host') !== getenv('JL_R1_DB_HOST') || $app->get('db') !== 'r1_test' || $app->get('dbprefix') !== 'r1_') throw new RuntimeException('Unsafe target');
$app->createExtensionNamespaceMap();
$app->loadLanguage(Joomla\CMS\Language\Language::getInstance('en-GB'));
$app->loadIdentity(new Joomla\CMS\User\User());
foreach (['com_joomleague','com_finder','com_scheduler'] as $component) $app->bootComponent($component);
$db = $container->get(Joomla\Database\DatabaseInterface::class);
if (($argv[1] ?? '') === 'scheduler-contender') {
    $taskId = filter_var($argv[2] ?? '', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    if (!$taskId) throw new RuntimeException('Task ID required');
    $owned = $db->setQuery('SELECT title FROM #__scheduler_tasks WHERE id=' . $taskId)->loadResult();
    if ($owned !== 'JoomLeague isolated lifecycle acceptance') throw new RuntimeException('Owned fixture required');
    $scheduler = new Joomla\Component\Scheduler\Administrator\Scheduler\Scheduler();
    $task = $scheduler->getTask(['id' => $taskId, 'allowConcurrent' => false, 'bypassScheduling' => false]);
    echo $task === null ? 'LOCK_REJECTED' : 'LOCK_ACQUIRED';
    exit($task === null ? 0 : 1);
}
if (($argv[1] ?? '') === 'snapshot') {
    $snapshot=[];
    foreach ($db->getTableList() as $table) {
        if (!str_starts_with($table,'r1_joomleague_')) continue;
        $rows=$db->setQuery('SELECT * FROM '.$db->quoteName($table))->loadAssocList();
        $rows=array_map(static function(array $row): string { ksort($row); return json_encode($row,JSON_THROW_ON_ERROR); },$rows);
        sort($rows);
        $snapshot[$table]=hash('sha256',json_encode($rows,JSON_THROW_ON_ERROR));
    }
    ksort($snapshot);
    echo json_encode($snapshot,JSON_THROW_ON_ERROR);
    exit(0);
}
$db->setQuery("UPDATE #__extensions SET enabled=1 WHERE type='plugin' AND element='joomleague'")->execute();
$params = new Joomla\Registry\Registry((string) $db->setQuery("SELECT params FROM #__extensions WHERE type='component' AND element='com_joomleague'")->loadResult());
$params->set('telemetry_consent', 'never');
$params->set('allow_full_data_reset', (int) (($argv[1] ?? '') === 'enable-reset'));
$db->setQuery("UPDATE #__extensions SET params=" . $db->quote($params->toString()) . " WHERE type='component' AND element='com_joomleague'")->execute();
if (($argv[1] ?? '') === 'enable-reset') exit(0);
$observations = [];
$check = static function (string $id, bool $ok) use (&$observations): void {
    $observations[] = ['id'=>$id,'status'=>200,'outcome'=>$ok?'PASS':'FAIL','evidenceCount'=>1];
    if (!$ok) throw new RuntimeException('Failed: ' . $id);
    echo 'PASS: ' . $id . "\n";
};
$finder = $app->bootPlugin('joomleague', 'finder');
$search=[];
foreach(['public'=>[1,1],'private'=>[2,1],'hidden'=>[1,0]] as $kind=>[$access,$published]){
    $token='Jlsearch'.$kind.'needle';
    $row=(object)['uuid'=>Joomleague\Component\Joomleague\Domain\Service\UuidFactory::v4(),'first_name'=>$token,'last_name'=>'Acceptance','published'=>$published,'access'=>$access];
    $db->insertObject('#__joomleague_person',$row,'id');
    $search[$kind]=['id'=>(int)$row->id,'token'=>$token,'name'=>$token.' Acceptance'];
}
file_put_contents('/tmp/jl-finder-fixture.json',json_encode($search,JSON_THROW_ON_ERROR));
foreach (['club','venue'] as $kind) {
    $row=(object)['uuid'=>Joomla\CMS\User\UserHelper::genRandomPassword(36),'name'=>'Audit '.$kind,'alias'=>'audit-'.$kind];
    $db->insertObject('#__joomleague_'.$kind,$row);
}
$call = static fn (string $method, ...$args) => (new ReflectionMethod($finder, $method))->invoke($finder, ...$args);
$total = $call('getContentCount');
$items = $call('getItems', 0, $total + 1);
$paged = [];
for ($offset=0; $offset<$total; $offset+=2) foreach ($call('getItems', $offset, 2) as $item) $paged[]=$item->id;
$check('finder-pagination', $total>0 && count($items)===$total && $paged===array_map(static fn($item)=>$item->id,$items) && count(array_unique($paged))===$total);
$kinds=array_unique(array_map(static fn($item)=>$item->entity_kind,$items));
sort($kinds);
$check('finder-entities',$kinds===['club','person','project','team','venue']);
foreach ($items as $item) $call('index', $item);
$links=$db->setQuery("SELECT url, access FROM #__finder_links WHERE url LIKE 'index.php?option=com_joomleague&%'")->loadAssocList();
$check('finder-index', count($links)===$total && count(array_unique(array_column($links,'url')))===$total);
$fixture=json_decode(file_get_contents('/tmp/jl-r1-fixture.json'),true,512,JSON_THROW_ON_ERROR);
$person=(int)$fixture['person'];
$url='index.php?option=com_joomleague&view=person&person_id='.$person;
$db->setQuery('UPDATE #__joomleague_person SET access=2 WHERE id='.$person)->execute();
foreach ($call('getItems',0,$total+1) as $item) if ($item->entity_kind==='person' && (int)$item->entity_id===$person) $call('index',$item);
$access=(int)$db->setQuery('SELECT access FROM #__finder_links WHERE url='.$db->quote($url))->loadResult();
$check('finder-access',$access===2 && !in_array($access,$app->getIdentity()->getAuthorisedViewLevels(),true));
$db->setQuery('UPDATE #__joomleague_person SET published=0 WHERE id='.$person)->execute();
$removed=$finder->onFinderGarbageCollection();
$check('finder-garbage',$removed>=1 && (int)$db->setQuery('SELECT COUNT(*) FROM #__finder_links WHERE url='.$db->quote($url))->loadResult()===0);
$plugin=$app->bootPlugin('joomleague','task');
$options=new Joomla\Component\Scheduler\Administrator\Task\TaskOptions();
$plugin->advertiseRoutines(new Joomla\Event\Event('onTaskOptionsList',['subject'=>$options]));
$check('task-advertised',$options->findOption('joomleague.telemetry')!==null && count($options->options)===1);
$before=(string)$db->setQuery("SELECT params FROM #__extensions WHERE element='com_joomleague' AND type='component'")->loadResult();
foreach (['joomleague.telemetry','audit.unknown'] as $type) {
    $task=new Joomla\Component\Scheduler\Administrator\Task\Task((object)['id'=>98765,'type'=>$type,'params'=>'{}','taskOption'=>(object)[]]);
    $event=new Joomla\Component\Scheduler\Administrator\Event\ExecuteTaskEvent('onExecuteTask',['subject'=>$task]);
    $plugin->standardRoutineHandler($event);
    $snapshot=$event->getResultSnapshot();
    $after=(string)$db->setQuery("SELECT params FROM #__extensions WHERE element='com_joomleague' AND type='component'")->loadResult();
    $check($type==='joomleague.telemetry'?'task-opt-out':'task-unknown',$before===$after && ($type==='joomleague.telemetry'?($snapshot['status']??null)===Joomla\Component\Scheduler\Administrator\Task\Status::OK:$snapshot===[]));
}
file_put_contents('/tmp/jl-execution-report.json',json_encode(['observations'=>$observations],JSON_THROW_ON_ERROR));
