<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli' || !str_starts_with((string)getenv('JL_R1_DB_HOST'),'jl-r1-')) throw new RuntimeException('Disposable target required');
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
if ($app->get('host')!==getenv('JL_R1_DB_HOST') || $app->get('db')!=='r1_test' || $app->get('dbprefix')!=='r1_') throw new RuntimeException('Unsafe target');
$app->createExtensionNamespaceMap();
$app->loadLanguage(Joomla\CMS\Language\Language::getInstance('en-GB'));
$guest = new Joomla\CMS\User\User();
if (in_array(2, $guest->getAuthorisedViewLevels(), true)) throw new RuntimeException('Guest must not have registered access.');
$app->loadIdentity($guest);
$db = $container->get(Joomla\Database\DatabaseInterface::class);
$db->setQuery($db->getQuery(true)->update($db->quoteName('#__extensions'))->set('enabled = 1')->where("type = 'plugin'")->where("element = 'joomleague'")->whereIn('folder', ['content', 'quickicon'], Joomla\Database\ParameterType::STRING))->execute();
// Connection-local shadow table only. Persistent person data remains untouched.
$db->setQuery('CREATE TEMPORARY TABLE ' . $db->quoteName('#__joomleague_person') . ' (id INTEGER PRIMARY KEY, first_name VARCHAR(255), last_name VARCHAR(255), alias VARCHAR(255), published INTEGER, access INTEGER)')->execute();
foreach ([[90001, 'Public', 'Person', 1, 1], [90002, 'Private', 'Person', 1, 2], [90003, 'Hidden', 'Person', 0, 1]] as [$id, $first, $last, $published, $access]) {
    $row = (object) ['id' => $id, 'first_name' => $first, 'last_name' => $last, 'alias' => 'audit-person-' . $id, 'published' => $published, 'access' => $access];
    $db->insertObject('#__joomleague_person', $row);
}
$plugin = $app->bootPlugin('joomleague', 'content');
$cases = [
    ['public-id', '{joomleague-person 90001}', true],
    ['private-id', '{joomleague-person 90002}Label{/joomleague-person}', false],
    ['unpublished-id', '{joomleague-person 90003}Label{/joomleague-person}', false],
    ['missing-id', '{joomleague-person 99999999}Label{/joomleague-person}', false],
    ['legacy-name', '{jl_player}Public Person{/jl_player}', true],
    ['private-legacy-name', '{jl_player}Private Person{/jl_player}', false],
    ['html-label', '{joomleague-person 90001}<img src=x onerror=alert(1)>Label{/joomleague-person}', true],
];
$failed = 0; $observations=[];
foreach ($cases as [$name, $text, $linked]) {
    $item = (object) ['text' => $text];
    $event = new Joomla\CMS\Event\Content\ContentPrepareEvent('onContentPrepare', ['context' => 'com_content.article', 'subject' => $item, 'params' => new Joomla\Registry\Registry(), 'page' => 0]);
    $plugin->onContentPrepare($event);
    $ok = str_contains($item->text, '<a href=') === $linked && !preg_match('/<img|onerror|javascript:/i', $item->text);
    if ($name === 'public-id') $ok = $ok && str_contains($item->text, 'Public Person');
    if (!$linked) $ok = $ok && !str_contains($item->text, '{joomleague-person');
    $failed += $ok ? 0 : 1;
    echo ($ok ? 'PASS' : 'FAIL') . ': content plugin ' . $name . "\n";
    $observations[]=['id'=>$name,'status'=>200,'outcome'=>$ok?'PASS':'FAIL','evidenceCount'=>1];
}
require_once JPATH_ADMINISTRATOR . '/modules/mod_quickicon/src/Event/QuickIconsEvent.php';
$quickicon = $app->bootPlugin('joomleague', 'quickicon');
$adminId = (int) $db->setQuery('SELECT id FROM #__users WHERE username=' . $db->quote('admin'))->loadResult();
if ($adminId < 1) throw new RuntimeException('Existing local admin required.');
foreach ([['guest', $guest, false], ['admin', new Joomla\CMS\User\User($adminId), true]] as [$name, $identity, $visible]) {
    $app->loadIdentity($identity);
    $event = new Joomla\Module\Quickicon\Administrator\Event\QuickIconsEvent('onGetIcons', ['result' => []]);
    $event->setContext('mod_quickicon');
    $quickicon->onGetIcons($event);
    $result = $event->getArgument('result', []);
    $ok = ($result !== []) === $visible;
    if ($visible) $ok = $ok && ($result[0][0]['link'] ?? '') === 'index.php?option=com_joomleague&view=dashboard';
    $failed += $ok ? 0 : 1;
    echo ($ok ? 'PASS' : 'FAIL') . ': quickicon permission ' . $name . "\n";
    $observations[]=['id'=>'quickicon-'.$name,'status'=>200,'outcome'=>$ok?'PASS':'FAIL','evidenceCount'=>1];
}
file_put_contents('/tmp/jl-plugin-report.json',json_encode(['observations'=>$observations],JSON_THROW_ON_ERROR));
exit($failed ? 1 : 0);
