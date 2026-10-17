<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli' || !str_starts_with((string)getenv('JL_R1_DB_HOST'), 'jl-r1-')) throw new RuntimeException('Disposable database required');
define('_JEXEC', 1);
define('JPATH_BASE', '/var/www/html');
$_SERVER['HTTP_HOST'] = 'localhost';
$_SERVER['SCRIPT_NAME'] = '/administrator/index.php';
require JPATH_BASE . '/includes/defines.php';
require JPATH_BASE . '/includes/framework.php';
$container = Joomla\CMS\Factory::getContainer();
$container->alias('session', 'session.cli')->alias(Joomla\Session\SessionInterface::class, 'session.cli');
$app = $container->get(Joomla\CMS\Application\AdministratorApplication::class);
Joomla\CMS\Factory::$application = $app;
if ($app->get('host') !== getenv('JL_R1_DB_HOST') || $app->get('db') !== 'r1_test' || $app->get('dbprefix') !== 'r1_') throw new RuntimeException('Unsafe target');
$app->createExtensionNamespaceMap();
$app->loadLanguage(Joomla\CMS\Language\Language::getInstance('en-GB'));
$db = $container->get(Joomla\Database\DatabaseInterface::class);
$factory = $app->bootComponent('com_joomleague')->getMVCFactory();
$fixture = json_decode(file_get_contents('/tmp/jl-r1-fixture.json'), true, 512, JSON_THROW_ON_ERROR);
$a = $fixture['projects']['a'];
$id = (int)$a['records']['Match'];
$user = $container->get(Joomla\CMS\User\UserFactoryInterface::class)->loadUserById($fixture['users']['a']);
$app->loadIdentity($user);
$model = static function () use ($factory, $user) {
    $model = $factory->createModel('Match', 'Administrator', ['ignore_request'=>true]);
    $model->setCurrentUser($user);
    return $model;
};
$assert = static function (bool $ok, string $message): void { if (!$ok) throw new RuntimeException($message); };
$snapshot = static function () use ($db): array {
    $rows = [];
    foreach (['project_match', 'match_participant', 'match_result'] as $table) $rows[$table] = $db->setQuery('SELECT * FROM ' . $db->quoteName('#__joomleague_' . $table) . ' ORDER BY id')->loadAssocList();
    return $rows;
};
$valid = static function () use ($model, $id, $a): array {
    return array_replace((array)$model()->getItem($id), ['description'=>'R4 valid', 'participant_slot_1'=>$a['entries'][0], 'participant_slot_2'=>$a['entries'][1], 'scheduled_date'=>'2026-10-20', 'scheduled_time'=>'10:30']);
};
$action = $argv[1] ?? 'verify';
// Deliberate late SQL failure after the old participants were deleted and slot 1 inserted.
$fault = static function (bool $enable) use ($db): void {
    $mysql = $db->getServerType() === 'mysql';
    if ($enable) {
        if ($mysql) $sql = "CREATE TRIGGER r1_atomicity_fault BEFORE INSERT ON r1_joomleague_match_participant FOR EACH ROW BEGIN IF NEW.slot_number = 2 THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'R4 injected slot failure'; END IF; END";
        else {
            $db->setQuery("CREATE FUNCTION r1_atomicity_fault_fn() RETURNS trigger LANGUAGE plpgsql AS 'BEGIN IF NEW.slot_number = 2 THEN RAISE EXCEPTION ''R4 injected slot failure''; END IF; RETURN NEW; END'")->execute();
            $sql = 'CREATE TRIGGER r1_atomicity_fault BEFORE INSERT ON r1_joomleague_match_participant FOR EACH ROW EXECUTE FUNCTION r1_atomicity_fault_fn()';
        }
        $db->setQuery($sql)->execute();
    } else {
        $db->setQuery($mysql ? 'DROP TRIGGER IF EXISTS r1_atomicity_fault' : 'DROP TRIGGER IF EXISTS r1_atomicity_fault ON r1_joomleague_match_participant')->execute();
        if (!$mysql) $db->setQuery('DROP FUNCTION IF EXISTS r1_atomicity_fault_fn()')->execute();
    }
};
if ($action === 'fault-on' || $action === 'fault-off') { $fault($action === 'fault-on'); exit; }
if ($action === 'lock' || $action === 'unlock') {
    if ($action === 'lock') {
        $row = (object)['uuid'=>Joomleague\Component\Joomleague\Domain\Service\UuidFactory::v4(), 'match_id'=>$id, 'result_type'=>'score', 'status_code'=>'draft'];
        $db->insertObject('#__joomleague_match_result', $row);
    } else $db->setQuery('DELETE FROM #__joomleague_match_result WHERE match_id=' . $id)->execute();
    exit;
}
if ($action === 'snapshot') { echo json_encode($snapshot(), JSON_THROW_ON_ERROR); exit; }
if ($action === 'swap') {
    $data=$valid(); [$data['participant_slot_1'],$data['participant_slot_2']]=[$data['participant_slot_2'],$data['participant_slot_1']];
    $assert($model()->save($data),'Concurrent swap failed');
    exit;
}
if ($action !== 'verify') throw new InvalidArgumentException('Unknown action');
$priorMonitor = $db->getMonitor();
$testMonitor = new class implements Joomla\Database\QueryMonitorInterface {
    public int $starts = 0;
    public int $stops = 0;
    public function startQuery(string $sql, ?array $boundParams = null): void { $this->starts++; }
    public function stopQuery(): void { $this->stops++; }
};
$db->setMonitor($testMonitor);
$concurrent = static function (string $action) use ($assert): void {
    $process=proc_open([PHP_BINARY,__FILE__,$action],[1=>['pipe','w'],2=>['pipe','w']],$pipes);
    $assert(is_resource($process),'Cannot start concurrent connection');
    $output=stream_get_contents($pipes[1]).stream_get_contents($pipes[2]);
    fclose($pipes[1]); fclose($pipes[2]);
    $assert(proc_close($process)===0,'Concurrent action failed: '.$output);
};
$baseline = $snapshot();
$fail = static function (array $data, string $label) use ($model, $assert, $snapshot, $db): void {
    $before = $snapshot();
    $current = $model();
    $current->setState('match.id', (int)$data['id']);
    $depth = new ReflectionProperty($db, 'transactionDepth');
    $beforeDepth = $depth->getValue($db);
    $monitor = $db->getMonitor();
    $connection = $db->getConnection();
    $rejected = false;
    $errorMessage = '';
    try { $rejected = $current->save($data) === false; }
    catch (Throwable $error) { $rejected = true; $errorMessage = $error->getMessage(); }
    $assert($rejected, $label . ': save unexpectedly succeeded');
    if (str_starts_with($label, 'late SQL')) $assert(str_contains($errorMessage, 'R4 injected slot failure') || ($db->getName()==='pgsql' && str_contains($errorMessage,'P0001')), $label . ': expected injected error was not reached: ' . $errorMessage);
    $assert($snapshot() === $before, $label . ': partial data persisted');
    $assert($depth->getValue($db) === $beforeDepth, $label . ': transaction nesting leaked');
    $assert($db->getMonitor() === $monitor, $label . ': query monitor was not restored');
    $assert($db->getConnection() === $connection, $label . ': connection was replaced');
    $assert((int)$current->getState('match.id') === (int)$data['id'], $label . ': stale rolled-back model ID');
    echo 'PASS: ' . $label . "\n";
};
foreach ([
    'missing participants'=>['participant_slot_1'=>0,'participant_slot_2'=>0],
    'duplicate participants'=>['participant_slot_2'=>$a['entries'][0]],
    'foreign participant'=>['participant_slot_2'=>$fixture['projects']['b']['entries'][0]],
    'invalid date'=>['scheduled_date'=>'2026-02-30'],
    'incomplete date'=>['scheduled_time'=>''],
    'invalid attendance'=>['attendance'=>'invalid'],
    'invalid parent status'=>['status_code'=>'not a status'],
] as $label=>$changes) {
    foreach ([false,true] as $create) {
        $data = array_replace($valid(),$changes);
        if ($create) { $data['id']=0; $data['uuid']=''; $data['code']='r4_new'; }
        $fail($data,($create?'create: ':'edit: ') . $label);
    }
}
$fault(true);
try {
    $data = $valid();
    [$data['participant_slot_1'],$data['participant_slot_2']] = [$data['participant_slot_2'],$data['participant_slot_1']];
    $fail($data,'late SQL failure restores parent and deleted participants');
    $data['id']=0; $data['uuid']=''; $data['code']='r4_new';
    $fail($data,'late SQL failure leaves no orphan new match');
} finally { $fault(false); }
$db->transactionStart();
try {
    $sentinel = (object)['id'=>$fixture['projects']['b']['records']['Match'],'description'=>'outer transaction sentinel'];
    $db->updateObject('#__joomleague_project_match',$sentinel,'id');
    $data = $valid(); $data['participant_slot_2']=0;
    $fail($data,'rejected nested save preserves caller transaction');
    $assert($model()->save($valid()),'Valid nested edit failed');
    $data=$valid(); $data['id']=0; $data['uuid']=''; $data['code']='r4_outer';
    $current=$model();
    $assert($current->save($data),'Valid nested creation failed');
    $newId=(int)$current->getState('match.id');
    $assert($newId>0 && (int)$db->setQuery('SELECT COUNT(*) FROM #__joomleague_match_participant WHERE match_id='.$newId)->loadResult()===2,'Nested new participants missing');
    $assert($db->setQuery('SELECT description FROM #__joomleague_project_match WHERE id='.(int)$sentinel->id)->loadResult()==='outer transaction sentinel','Caller transaction was lost');
} finally { $db->transactionRollback(); }
$assert($snapshot()===$baseline,'Inner save committed caller transaction');
echo "PASS: successful nested edit/create remains controlled by caller rollback\n";
$fault(true);
$db->transactionStart();
try {
    $sentinel = (object)['id'=>$fixture['projects']['b']['records']['Match'],'description'=>'outer SQL failure sentinel'];
    $db->updateObject('#__joomleague_project_match',$sentinel,'id');
    $data=$valid(); [$data['participant_slot_1'],$data['participant_slot_2']]=[$data['participant_slot_2'],$data['participant_slot_1']];
    $fail($data,'late SQL failure preserves caller writes and database connection');
    $assert($db->setQuery('SELECT description FROM #__joomleague_project_match WHERE id='.(int)$sentinel->id)->loadResult()==='outer SQL failure sentinel','SQL failure discarded caller writes');
} finally { $db->transactionRollback(); $fault(false); }
$assert($snapshot()===$baseline,'Outer rollback after SQL failure did not restore data');
$db->transactionStart();
try {
    $snapshot(); // Establish a repeatable-read snapshot before another connection swaps slots.
    $concurrent('swap');
    $assert($model()->save($valid()),'Saving after a stale snapshot failed');
    $slots=array_map('intval',$db->setQuery('SELECT project_entry_id FROM #__joomleague_match_participant WHERE match_id='.$id.' ORDER BY slot_number FOR UPDATE')->loadColumn());
    $assert($slots===$a['entries'],'Stale snapshot mixed the parent and participant payloads');
} finally { $db->transactionRollback(); }
$assert($model()->save($valid()),'Restoring original slots after concurrent test failed');
echo "PASS: current participant reads override an older caller snapshot\n";
$db->transactionStart();
try {
    $snapshot();
    $concurrent('lock');
    $data=$valid(); [$data['participant_slot_1'],$data['participant_slot_2']]=[$data['participant_slot_2'],$data['participant_slot_1']];
    $fail($data,'competition data committed after caller snapshot still prevents swaps');
} finally { $db->transactionRollback(); $concurrent('unlock'); }
$result=(object)['uuid'=>Joomleague\Component\Joomleague\Domain\Service\UuidFactory::v4(),'match_id'=>$id,'result_type'=>'score','status_code'=>'draft'];
$db->insertObject('#__joomleague_match_result',$result);
try {
    $data=$valid(); [$data['participant_slot_1'],$data['participant_slot_2']]=[$data['participant_slot_2'],$data['participant_slot_1']];
    $fail($data,'competition lock rejects swap without changing parent or result');
    $assert($model()->save($valid()),'Unchanged participants must remain editable with results');
} finally { $db->setQuery('DELETE FROM #__joomleague_match_result WHERE match_id='.$id)->execute(); }
$data=$valid(); [$data['participant_slot_1'],$data['participant_slot_2']]=[$data['participant_slot_2'],$data['participant_slot_1']];
$assert($model()->save($data),'Valid unlocked swap failed');
$slots=array_map('intval',$db->setQuery('SELECT project_entry_id FROM #__joomleague_match_participant WHERE match_id='.$id.' ORDER BY slot_number')->loadColumn());
$assert($slots===[$a['entries'][1],$a['entries'][0]],'Swap did not persist both participants');
$assert($model()->save($valid()),'Restoring original slots failed');
echo "PASS: valid standalone parent/schedule/participant edit and swap persist together\n";
$fault(true);
try {
    $before=$snapshot();
    $data=$valid(); [$data['participant_slot_1'],$data['participant_slot_2']]=[$data['participant_slot_2'],$data['participant_slot_1']];
    $errorMessage='';
    try { (new Joomleague\Component\Joomleague\Administrator\Service\MatchScheduleEditor($db))->save($id,$a['round'],$data,(int)$user->id); }
    catch (Throwable $error) { $errorMessage=$error->getMessage(); }
    $assert(str_contains($errorMessage,'R4 injected slot failure') || ($db->getName()==='pgsql' && str_contains($errorMessage,'P0001')),'Direct editor did not reach injected SQL failure');
    $assert($snapshot()===$before,'Direct editor did not roll back schedule and participants');
    $assert((new ReflectionProperty($db,'transactionDepth'))->getValue($db)===0,'Direct editor leaked transaction');
} finally { $fault(false); }
echo "PASS: direct schedule service rolls back late SQL failure without a model wrapper\n";
$assert($db->getMonitor()===$testMonitor && $testMonitor->starts>0 && $testMonitor->starts===$testMonitor->stops,'Existing query monitor was not preserved');
$db->setMonitor($priorMonitor);
echo "PASS: existing query monitor forwarded and restored after success and failure\n";
