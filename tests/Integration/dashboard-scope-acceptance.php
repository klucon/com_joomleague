<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli' || !str_starts_with((string) getenv('JL_R1_DB_HOST'), 'jl-r1-')) throw new RuntimeException('Disposable target required');
define('_JEXEC', 1);
define('JPATH_BASE', '/var/www/html');
$_SERVER['HTTP_HOST'] = 'localhost';
$_SERVER['SCRIPT_NAME'] = '/administrator/index.php';
$_SERVER['REQUEST_URI'] = '/administrator/index.php';
require JPATH_BASE . '/includes/defines.php';
require JPATH_BASE . '/includes/framework.php';
$container = Joomla\CMS\Factory::getContainer();
$container->alias('session', 'session.cli')->alias(Joomla\Session\SessionInterface::class, 'session.cli');
$app = $container->get(Joomla\CMS\Application\AdministratorApplication::class);
Joomla\CMS\Factory::$application = $app;
if ($app->get('host') !== getenv('JL_R1_DB_HOST') || $app->get('db') !== 'r1_test' || $app->get('dbprefix') !== 'r1_') throw new RuntimeException('Unsafe target');
$app->createExtensionNamespaceMap();
$factory = $app->bootComponent('com_joomleague')->getMVCFactory();
$db = $container->get(Joomla\Database\DatabaseInterface::class);
$fixture = json_decode(file_get_contents('/tmp/jl-r1-fixture.json'), true, 512, JSON_THROW_ON_ERROR);
$app->loadIdentity($container->get(Joomla\CMS\User\UserFactoryInterface::class)->loadUserById((int) $fixture['users']['a']));
$params = Joomla\CMS\Component\ComponentHelper::getParams('com_joomleague');
$model = $factory->createModel('Dashboard', 'Administrator', ['ignore_request' => true]);
$read = static fn (int $limit = 50): array => array_column($model->getClubMatches($limit), 'id');
if (($argv[1] ?? '') === 'read') {
    echo json_encode(['params' => $params->toArray(), 'ids' => $read()], JSON_THROW_ON_ERROR);
    exit;
}
$insert = static function (string $table, array $data, bool $published = true) use ($db): int {
    $row = (object) (['uuid' => Joomleague\Component\Joomleague\Domain\Service\UuidFactory::v4()] + $data + ($published ? ['published' => 1] : []));
    $db->insertObject('#__joomleague_' . $table, $row, 'id');
    return (int) $row->id;
};
$a = $fixture['projects']['a'];
$b = $fixture['projects']['b'];
$club = $insert('club', ['name' => 'S100 <img src=x onerror=alert(1)> Club']);
foreach ([$a, $b] as $project) {
    $team = (int) $db->setQuery('SELECT team_id FROM #__joomleague_project_entry WHERE id=' . (int) $project['entries'][0])->loadResult();
    $update = (object) ['id' => $team, 'club_id' => $club];
    $db->updateObject('#__joomleague_team', $update, 'id');
}
$makeMatch = static function (array $project, ?string $date, string $status = 'scheduled') use ($insert): int {
    $id = $insert('project_match', ['project_id' => $project['id'], 'stage_id' => $project['stage'], 'round_id' => $project['round'], 'scheduled_start' => $date, 'status_code' => $status]);
    foreach ($project['entries'] as $index => $entry) $insert('match_participant', ['project_id' => $project['id'], 'match_id' => $id, 'project_entry_id' => $entry, 'slot_number' => $index + 1]);
    return $id;
};
$near = $makeMatch($a, Joomla\CMS\Factory::getDate('+2 days')->toSql());
$far = $makeMatch($a, Joomla\CMS\Factory::getDate('+4 days')->toSql());
$other = $makeMatch($b, Joomla\CMS\Factory::getDate('+3 days')->toSql());
$recent = $makeMatch($a, Joomla\CMS\Factory::getDate('-1 day')->toSql(), 'completed');
$old = $makeMatch($b, '1945-01-01 12:00:00', 'completed');
$makeMatch($b, null);
$makeMatch($b, null, 'completed');
$makeMatch($b, '1945-01-01 12:00:00');
$makeMatch($a, Joomla\CMS\Factory::getDate('+1 day')->toSql(), 'cancelled');
$params->set('site_club_id', $club);
$params->set('dashboard_project_ids', []);
$params->set('dashboard_result_lookback_days', 30);
$params->set('dashboard_match_limit', 9);
$params->set('dashboard_schedule_display', 'all');
$count = 0;
$assert = static function (bool $ok, string $message) use (&$count): void { if (!$ok) throw new RuntimeException($message); ++$count; };
$assert($read() === [$recent, $near, $other, $far], 'Dated current events must displace null/1945/cancelled events');
$assert($read(1) === [$recent] && $read(0) === [], 'Limit and disabled shortcut');
$params->set('dashboard_project_ids', [$a['id']]);
$assert($read() === [$recent, $near, $far], 'Selected project only');
$params->set('dashboard_project_ids', [$a['id'], $b['id']]);
$assert($read() === [$recent, $near, $other, $far], 'Multiple selected projects');
$params->set('dashboard_project_ids', ['1 OR 1=1']);
$assert($read() === [], 'Malformed scope must not broaden to all projects');
$params->set('dashboard_project_ids', []);
$params->set('dashboard_result_lookback_days', 0);
$assert($read() === [$near, $other, $far], 'Upcoming-only mode');
$params->set('dashboard_result_lookback_days', 30);
$db->transactionStart();
try {
    $insert('match_result', ['match_id' => $recent, 'result_type' => 'numeric_score', 'status_code' => 'final'], false);
    $assert($read() === [$near, $other, $far], 'Final results excluded from missing-result work');
} finally { $db->transactionRollback(); }
foreach (['joomleague_project' => $a['id'], 'joomleague_competition' => null, 'joomleague_season' => null] as $table => $id) {
    if ($id === null) {
        $column = $table === 'joomleague_competition' ? 'competition_id' : 'season_id';
        $id = (int) $db->setQuery('SELECT ' . $column . ' FROM #__joomleague_project WHERE id=' . (int) $a['id'])->loadResult();
    }
    foreach (['published=0', 'access=999999'] as $mutation) {
        $db->transactionStart();
        try {
            $db->setQuery('UPDATE #__' . $table . ' SET ' . $mutation . ' WHERE id=' . (int) $id)->execute();
            $expected = $table === 'joomleague_project' ? [$other] : [];
            $assert($read() === $expected, 'Parent publication/access: ' . $table . ' ' . $mutation);
        } finally { $db->transactionRollback(); }
    }
}
$opponent = (int) $db->setQuery('SELECT team_id FROM #__joomleague_project_entry WHERE id=' . (int) $a['entries'][1])->loadResult();
foreach (['published=0', 'access=999999'] as $mutation) {
    $db->transactionStart();
    try {
        $db->setQuery('UPDATE #__joomleague_team SET ' . $mutation . ' WHERE id=' . $opponent)->execute();
        $assert($read() === [$other], 'Private/unpublished opponent must not leak');
    } finally { $db->transactionRollback(); }
}
foreach ([['project_stage', $a['stage']], ['project_round', $a['round']], ['project_entry', $a['entries'][0]], ['project_entry', $a['entries'][1]]] as [$table, $id]) {
    $db->transactionStart();
    try {
        $db->setQuery('UPDATE #__joomleague_' . $table . ' SET published=0 WHERE id=' . (int) $id)->execute();
        $assert($read() === [$other], 'Unpublished scope: ' . $table);
    } finally { $db->transactionRollback(); }
}
$db->transactionStart();
try {
    $db->setQuery('UPDATE #__joomleague_match_participant SET published=0 WHERE match_id=' . $near . ' AND slot_number=2')->execute();
    $assert($read() === [$recent, $other, $far], 'Unpublished match participant must not leak');
} finally { $db->transactionRollback(); }
$db->transactionStart();
try {
    $hiddenClub = $insert('club', ['name' => 'Hidden opponent club', 'access' => 999999]);
    $db->setQuery('UPDATE #__joomleague_team SET club_id=' . $hiddenClub . ' WHERE id=' . $opponent)->execute();
    $assert($read() === [$other], 'Private opponent club must not leak');
} finally { $db->transactionRollback(); }
$params->set('site_club_id', 0);
$assert($read() === [], 'No club configured');
$params->set('site_club_id', $club);
$db->transactionStart();
try {
    $db->setQuery('UPDATE #__joomleague_club SET access=999999 WHERE id=' . $club)->execute();
    $assert($model->getSiteClub() === null && $read() === [], 'Private site club hidden');
} finally { $db->transactionRollback(); }
$extension = (object) ['element' => 'com_joomleague', 'params' => $params->toString()];
$db->updateObject('#__extensions', $extension, 'element');
file_put_contents('/tmp/jl-dashboard-scope.json', json_encode(['club' => $club, 'a' => $a['id'], 'b' => $b['id'], 'all' => [$recent, $near, $other, $far], 'selected' => [$recent, $near, $far], 'upcoming' => [$near, $far]], JSON_THROW_ON_ERROR));
echo 'PASS: dashboard scope ' . $count . " assertions.\n";
