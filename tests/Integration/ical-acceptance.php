<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli' || !str_starts_with((string)getenv('JL_R1_DB_HOST'), 'jl-r1-')) throw new RuntimeException('Disposable database required');
define('_JEXEC', 1);
define('JPATH_BASE', '/var/www/html');
$_SERVER['HTTP_HOST'] = 'localhost';
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
$db = $container->get(Joomla\Database\DatabaseInterface::class);
$factory = $app->bootComponent('com_joomleague')->getMVCFactory();
$fixture = json_decode(file_get_contents('/tmp/jl-r1-fixture.json'), true, 512, JSON_THROW_ON_ERROR);
if (in_array($argv[1] ?? '', ['public', 'hide'], true)) {
    $row = (object)['id'=>$fixture['projects']['a']['id'], 'access'=>1, 'published'=>$argv[1] === 'public' ? 1 : 0];
    $db->updateObject('#__joomleague_project', $row, 'id');
    echo "PASS: disposable project visibility changed\n";
    exit;
}
$db->setQuery("UPDATE #__extensions SET enabled=1 WHERE type='plugin' AND folder='system' AND element='cache'")->execute();
foreach ($fixture['projects'] as $letter => $project) {
    $group = (int)$db->setQuery('SELECT group_id FROM #__user_usergroup_map WHERE user_id=' . (int)$fixture['users'][$letter])->loadResult();
    $level = (object)['title'=>'Calendar ' . $letter, 'ordering'=>10, 'rules'=>json_encode([$group])];
    $db->insertObject('#__viewlevels', $level, 'id');
    $row = (object)['id'=>$project['id'], 'access'=>(int)$level->id, 'name'=>'PRIVATE-CALENDAR-' . strtoupper($letter)];
    $db->updateObject('#__joomleague_project', $row, 'id');
    $match = (object)['id'=>$project['records']['Match'], 'scheduled_start'=>'2026-10-20 10:00:00'];
    $db->updateObject('#__joomleague_project_match', $match, 'id');
    $club = (object)['id'=>$letter === 'a' ? 9001 : 9002, 'uuid'=>Joomleague\Component\Joomleague\Domain\Service\UuidFactory::v4(), 'name'=>'Calendar club ' . $letter, 'published'=>1, 'access'=>1];
    $db->insertObject('#__joomleague_club', $club);
    foreach ($project['entries'] as $entryId) {
        $teamId = (int)$db->setQuery('SELECT team_id FROM #__joomleague_project_entry WHERE id=' . (int)$entryId)->loadResult();
        $team = (object)['id'=>$teamId, 'club_id'=>$club->id];
        $db->updateObject('#__joomleague_team', $team, 'id');
    }
}
Joomla\CMS\Access\Access::clearStatics();
$users = $container->get(Joomla\CMS\User\UserFactoryInterface::class);
foreach (['guest'=>0] + $fixture['users'] as $identity=>$userId) {
    $app->loadIdentity($users->loadUserById($userId));
    foreach ($fixture['projects'] as $owner=>$project) {
        foreach (['project'=>0, 'entry'=>$project['entries'][0], 'event'=>$project['records']['Match'], 'club'=>$owner === 'a' ? 9001 : 9002] as $scope=>$scopeId) {
            foreach (['project_id'=>$project['id'], 'scope'=>$scope, 'entry_id'=>$scopeId, 'event_id'=>$scopeId, 'club_id'=>$scopeId] as $key=>$value) $app->getInput()->set($key, $value);
            $calendar = $factory->createModel('Ical', 'Site')->getCalendar();
            $allowed = $identity === $owner;
            if ($allowed !== isset($calendar['content'])) throw new RuntimeException("Access failed: $identity / $owner / $scope");
            if ($allowed && !str_contains($calendar['content'], 'PRIVATE-CALENDAR-' . strtoupper($owner))) throw new RuntimeException('Missing marker');
        }
    }
}
echo "PASS: guest and two disjoint access levels, project/entry/event/club scopes, native calendar model\n";
