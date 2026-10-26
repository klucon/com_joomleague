<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli' || !str_starts_with((string) getenv('JL_R1_DB_HOST'), 'jl-r1-')) throw new RuntimeException('Disposable target required');
define('_JEXEC', 1);
define('JPATH_BASE', '/var/www/html');
$_SERVER['HTTP_HOST'] = 'localhost';
$_SERVER['SCRIPT_NAME'] = '/administrator/index.php';
$_SERVER['REQUEST_URI'] = '/administrator/';
require JPATH_BASE . '/includes/defines.php';
require JPATH_BASE . '/includes/framework.php';
$container = Joomla\CMS\Factory::getContainer();
$container->alias('session', 'session.cli')->alias(Joomla\Session\SessionInterface::class, 'session.cli');
$app = $container->get(Joomla\CMS\Application\AdministratorApplication::class);
Joomla\CMS\Factory::$application = $app;
if ($app->get('host') !== getenv('JL_R1_DB_HOST') || $app->get('db') !== 'r1_test' || $app->get('dbprefix') !== 'r1_') throw new RuntimeException('Unsafe target');
$app->createExtensionNamespaceMap();
$app->bootComponent('com_joomleague');
$db = $container->get(Joomla\Database\DatabaseInterface::class);
$fixture = json_decode(file_get_contents('/tmp/jl-r1-fixture.json'), true, 512, JSON_THROW_ON_ERROR);
$project = (int) $fixture['projects']['a']['id'];
$match = (int) $fixture['projects']['a']['records']['Match'];
$stage = (int) $fixture['projects']['a']['stage'];
$person = (int) $fixture['person'];
$assertions = 0;
$assert = static function (bool $ok, string $message) use (&$assertions): void { if (!$ok) throw new RuntimeException($message); ++$assertions; };
$insert = static function (string $table, array $data) use ($db): int {
    $row = (object) (['uuid' => Joomleague\Component\Joomleague\Domain\Service\UuidFactory::v4()] + $data);
    $db->insertObject('#__joomleague_' . $table, $row, 'id');
    return (int) $row->id;
};
$db->setQuery('UPDATE #__joomleague_project_match SET scheduled_start=' . $db->quote('2026-10-15 23:59:59') . ' WHERE id=' . $match)->execute();
$insert('match_result', ['match_id' => $match, 'result_type' => 'numeric_score', 'status_code' => 'final']);
$participant = (int) $db->setQuery('SELECT id FROM #__joomleague_match_participant WHERE match_id=' . $match . ' ORDER BY id')->loadResult();
foreach (['person' => 3, 'participant' => 1] as $kind => $count) {
    for ($i = 0; $i < $count; ++$i) {
        $data = ['project_id' => $project, 'match_id' => $match, 'match_participant_id' => $participant,
            'event_code' => 'audit_count', 'event_name_key' => 'Audit count', 'sequence_number' => $kind === 'person' ? $i + 1 : 4,
            'published' => 1, 'profile_metadata_json' => '{}'];
        if ($kind === 'person') $data += ['primary_person_id' => $person, 'primary_name_snapshot' => 'Ranking person'];
        $insert('match_event', $data);
    }
}
$reader = new Joomleague\Component\Joomleague\Domain\Service\EventRankingReader($db);
$db->setQuery('UPDATE #__joomleague_person SET picture=' . $db->quote('media/com_joomleague/brand/joomleague-logo.png') . ' WHERE id=' . $person)->execute();
$read = static fn (array $options = [], ?string $code = 'audit_count'): array => $reader->forProject($project, $code, 100, [1], $options)['rows'];
$rows = $read();
$assert(!property_exists($rows[0], 'image_path'), 'Image columns omitted unless requested');
$imageRows = $read(['include_images' => true, 'participant_kind' => 'person']);
$assert($imageRows[0]->image_path === 'media/com_joomleague/brand/joomleague-logo.png', 'Accessible person image selected');
$assert(count($rows) === 2 && (int) $rows[0]->total_value === 3, 'Populated descending baseline');
$assert((int) $read(['direction' => 'asc'])[0]->total_value === 1, 'Ascending before row limit');
$assert(count($reader->forProject($project, 'audit_count', 1, [1], ['direction' => 'asc'])['rows']) === 1, 'Bounded rows');
$assert(count($read(['stage_id' => $stage])) === 2, 'Correct stage');
$assert($read(['stage_id' => (int) $fixture['projects']['a']['target']]) === [], 'Empty own stage');
$assert(count($read(['date_from' => '2026-10-15', 'date_until' => '2026-10-15'])) === 2, 'Inclusive UTC boundary');
$assert($read(['date_from' => '2026-10-16']) === [], 'Lower bound');
$assert($read(['strict_code' => true, 'date_from' => '2026-10-16']) === [], 'Valid code outside period stays empty');
$assert($read(['date_until' => '2026-10-14']) === [], 'Upper bound');
$assert(count($read(['participant_kind' => 'person'])) === 1, 'Person scope');
$assert(count($read(['participant_kind' => 'participant'])) === 1, 'Participant scope');
$assert($read(['participant_kind' => 'team']) === [], 'No actor team scope');
foreach ([['stage_id' => (int) $fixture['projects']['b']['stage']], ['direction' => 'desc; DROP TABLE x'],
    ['participant_kind' => 'invalid'], ['ties' => 'invalid'], ['date_from' => '2026-02-30'],
    ['date_from' => '2026-10-16', 'date_until' => '2026-10-15'], ['stage_id' => -1]] as $options) {
    $rejected = false;
    try { $read($options); } catch (InvalidArgumentException) { $rejected = true; }
    $assert($rejected, 'Invalid/cross-project filter rejected');
}
$rejected = false;
try { $read(['strict_code' => true], 'missing_code'); } catch (InvalidArgumentException) { $rejected = true; }
$assert($rejected, 'Explicit invalid code never switches to another ranking');
$assert(count($read([], 'missing_code')) === 2, 'Existing public-view fallback preserved');
foreach (['ordinal' => [1, 2, 3], 'competition' => [1, 1, 3], 'dense' => [1, 1, 2]] as $policy => $expected) {
    $ranked = array_map(static fn (int $count): object => (object) ['total_value' => $count], [3, 3, 1]);
    Joomleague\Component\Joomleague\Domain\Service\EventRankingOptions::rank($ranked, $policy);
    $assert(array_column($ranked, 'rank') === $expected, 'Tie policy ' . $policy);
}
$db->setQuery('UPDATE #__joomleague_person SET access=2 WHERE id=' . $person)->execute();
$assert(count($read()) === 1, 'Private person hidden');
$assert(count($read(['include_images' => true])) === 1 && $read(['include_images' => true])[0]->image_path === null, 'Private image path never exposed');
$db->setQuery('UPDATE #__joomleague_project_entry SET published=0 WHERE id=' . (int) $fixture['projects']['a']['entries'][0])->execute();
$assert($read() === [], 'Unpublished participant hidden');
$db->setQuery('UPDATE #__joomleague_person SET access=1 WHERE id=' . $person)->execute();
$db->setQuery('UPDATE #__joomleague_project_entry SET published=1 WHERE id=' . (int) $fixture['projects']['a']['entries'][0])->execute();
$validate = static function (array $changes = []) use ($project, $person): bool {
    Joomla\CMS\Form\Form::addFieldPath(JPATH_ROOT . '/modules/mod_joomleague_eventranking/fields');
    $manifest = simplexml_load_file(JPATH_ROOT . '/modules/mod_joomleague_eventranking/mod_joomleague_eventranking.xml');
    $form = new Joomla\CMS\Form\Form('eventranking-acceptance');
    if (!$form->load($manifest->config, true, './fields')) throw new RuntimeException('Cannot load native module form');
    $data = ['params' => $changes + ['project_id' => $project, 'event_code' => 'audit_count', 'stage_id' => 0, 'highlight_target' => 'person:' . $person]];
    $form->bind($data);
    $valid = $form->validate($data);
    if (!$valid && $changes === []) {
        throw new RuntimeException('Positive native form failed: ' . implode('; ', array_map(static fn ($error): string => $error instanceof Throwable ? $error->getMessage() : (string) $error, $form->getErrors())));
    }
    return $valid;
};
$assert($validate(), 'Native selector positive control');
$assert(!$validate(['stage_id' => (int) $fixture['projects']['b']['stage']]), 'Native form rejects foreign stage');
$assert(!$validate(['event_code' => 'missing_code']), 'Native form rejects stale event code');
$assert(!$validate(['highlight_target' => 'team:' . $person]), 'Native form rejects unavailable target');
$module = new Joomla\CMS\Table\Module($db);
$configuration = new Joomla\Registry\Registry(new JConfig());
$configuration->set('caching', 1);
if (file_put_contents(JPATH_CONFIGURATION . '/configuration.php', $configuration->toString('PHP', ['class' => 'JConfig', 'closingtag' => false])) === false) throw new RuntimeException('Cannot enable isolated Joomla cache');
$module->title = 'Event ranking acceptance';
$module->module = 'mod_joomleague_eventranking';
$module->position = 'sidebar-right';
$module->published = 1;
$module->access = 1;
$module->client_id = 0;
$module->language = '*';
$module->showtitle = 0;
$module->params = json_encode(['project_id' => $project, 'event_code' => 'audit_count', 'stage_id' => 0, 'limit' => 5], JSON_THROW_ON_ERROR);
if (!$module->check() || !$module->store()) throw new RuntimeException('Cannot create browser module fixture');
$mapping = (object) ['moduleid' => (int) $module->id, 'menuid' => 0];
$db->insertObject('#__modules_menu', $mapping);
file_put_contents('/tmp/jl-eventranking-browser.json', json_encode(['module' => (int) $module->id, 'stage' => $stage, 'person' => $person], JSON_THROW_ON_ERROR));
echo 'PASS: event ranking query configuration; ' . $assertions . " mandatory assertions.\n";
