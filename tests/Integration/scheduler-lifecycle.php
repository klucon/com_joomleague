<?php
declare(strict_types=1);

// This bootstrap also runs the existing plugin checks on the guarded disposable database.
require __DIR__ . '/execution-plugins.php';

$observations = [];
register_shutdown_function(static function () use (&$observations): void {
    file_put_contents('/tmp/jl-scheduler-report.json', json_encode(['observations' => $observations], JSON_THROW_ON_ERROR));
});
$paramsBefore = (string) $db->setQuery("SELECT params FROM #__extensions WHERE element='com_joomleague' AND type='component'")->loadResult();
if ((new Joomla\Registry\Registry($paramsBefore))->get('telemetry_consent') !== 'never') {
    throw new RuntimeException('Telemetry must remain disabled');
}
$model = $app->bootComponent('com_scheduler')->getMVCFactory()->createModel('Task', 'Administrator', ['ignore_request' => true]);
$saved = $model->save([
    'title' => 'JoomLeague isolated lifecycle acceptance',
    'type' => 'joomleague.telemetry', 'state' => 1,
    'execution_rules' => ['rule-type' => 'interval-minutes', 'interval-minutes' => 5, 'exec-day' => gmdate('d'), 'exec-time' => gmdate('H:i')],
    'params' => [],
]);
$id = (int) $model->getState('task.id');
$row = static fn () => $db->setQuery('SELECT * FROM #__scheduler_tasks WHERE id=' . $id)->loadObject();
$scheduler = new Joomla\Component\Scheduler\Administrator\Scheduler\Scheduler();
$check('scheduler-save', $saved && $id > 0 && strtotime($row()->next_execution . ' UTC') > time());
$options = ['id' => $id, 'allowConcurrent' => false, 'bypassScheduling' => false];
$check('scheduler-not-due', $scheduler->getTask($options) === null && (int) $row()->times_executed === 0);
$due = static function () use ($db, $id): void {
    $db->setQuery('UPDATE #__scheduler_tasks SET next_execution=' . $db->quote(gmdate('Y-m-d H:i:s', time() - 60)) . ' WHERE id=' . $id)->execute();
};
$due();
$task = $scheduler->getTask($options);
$check('scheduler-lock', $task !== null && $row()->locked !== null && $scheduler->getTask($options) === null);
if ($task === null) throw new RuntimeException('No locked task');
$pipes = [];
$process = proc_open([PHP_BINARY, __DIR__ . '/execution-plugins.php', 'scheduler-contender', (string) $id], [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
if (!is_resource($process)) throw new RuntimeException('Contender could not start');
foreach ($pipes as $pipe) stream_set_blocking($pipe, false);
$output = '';
$errors = '';
$deadline = microtime(true) + 15;
do {
    $output .= stream_get_contents($pipes[1]);
    $errors .= stream_get_contents($pipes[2]);
    $status = proc_get_status($process);
    if (!$status['running']) break;
    usleep(10000);
} while (microtime(true) < $deadline);
if ($status['running']) proc_terminate($process, 9);
$output .= stream_get_contents($pipes[1]);
$errors .= stream_get_contents($pipes[2]);
foreach ($pipes as $pipe) fclose($pipe);
proc_close($process);
$check('scheduler-process-lock', !$status['running'] && $status['exitcode'] === 0 && $output === 'LOCK_REJECTED' && $errors === '' && (int) $row()->times_executed === 0 && $row()->locked !== null);
$started = time();
$task->run();
$after = $row();
$next = strtotime($after->next_execution . ' UTC');
$check('scheduler-run', (int) $after->last_exit_code === Joomla\Component\Scheduler\Administrator\Task\Status::OK && (int) $after->times_executed === 1 && (int) $after->times_failed === 0 && $after->last_execution !== null);
$check('scheduler-reschedule', $after->locked === null && $next >= $started + 299 && $next <= time() + 301);
$check('scheduler-no-repeat', $scheduler->getTask($options) === null && (int) $row()->times_executed === 1);
$due();
$db->setQuery('UPDATE #__scheduler_tasks SET state=0 WHERE id=' . $id)->execute();
$check('scheduler-disabled', $scheduler->getTask($options) === null && $row()->locked === null);
$db->setQuery('UPDATE #__scheduler_tasks SET state=1 WHERE id=' . $id)->execute();
$second = $scheduler->getTask($options);
if ($second === null) throw new RuntimeException('Second due execution missing');
$second->run();
$check('scheduler-second-run', (int) $row()->times_executed === 2 && (int) $row()->times_failed === 0 && $row()->locked === null);
$paramsAfter = (string) $db->setQuery("SELECT params FROM #__extensions WHERE element='com_joomleague' AND type='component'")->loadResult();
$check('scheduler-opt-out-preserved', $paramsBefore === $paramsAfter);
