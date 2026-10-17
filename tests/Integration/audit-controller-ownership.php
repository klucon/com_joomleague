<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') exit(1);
define('_JEXEC', 1);
define('JPATH_BASE', '/var/www/html');
$_SERVER['HTTP_HOST'] = 'joomla62.local';
$_SERVER['REQUEST_URI'] = '/';
$_SERVER['SCRIPT_NAME'] = '/index.php';
require JPATH_BASE . '/includes/defines.php';
require JPATH_BASE . '/includes/framework.php';
$container = Joomla\CMS\Factory::getContainer();
$container->alias('session', 'session.cli')->alias(Joomla\Session\SessionInterface::class, 'session.cli');
$app = $container->get(Joomla\CMS\Application\SiteApplication::class);
Joomla\CMS\Factory::$application = $app;
$app->createExtensionNamespaceMap();
$db = $container->get(Joomla\Database\DatabaseInterface::class);
$factory = $app->bootComponent('com_joomleague')->getMVCFactory();
// Optional source candidate lives in /tmp, not in the installed application.
$candidate = getenv('JOOMLEAGUE_AUDIT_CONTROLLER_DIR');
if ($candidate) {
    if (!str_starts_with($candidate, '/tmp/')) throw new RuntimeException('Temporary candidate directory required.');
    foreach (['Match', 'Round', 'Projectentry', 'Standingadjustment', 'Entrymember', 'Stagetransition'] as $name) {
        require $candidate . '/' . $name . 'Controller.php';
    }
}

// Synthetic identity only: no user, group, permission or business record is written.
$user = new class extends Joomla\CMS\User\User {
    public int $permittedProject = 0;
    public function authorise($action, $assetname = null) {
        return $assetname === 'com_joomleague.project.' . $this->permittedProject;
    }
};
$app->loadIdentity($user);
$observations = [];
foreach (['Match' => 'project_match', 'Round' => 'project_round', 'Projectentry' => 'project_entry', 'Standingadjustment' => 'standing_adjustment', 'Stagetransition' => 'stage_transition'] as $name => $table) {
    $record = $db->setQuery('SELECT id, project_id FROM #__joomleague_' . $table . ' WHERE project_id > 0 ORDER BY id', 0, 1)->loadAssoc();
    if (!$record) {
        $observations[] = ['controller' => $name, 'status' => 'SKIP', 'reason' => 'No existing record'];
        continue;
    }
    $allowed = (int) $db->setQuery('SELECT id FROM #__joomleague_project WHERE id <> ' . (int) $record['project_id'] . ' ORDER BY id', 0, 1)->loadResult();
    if (!$allowed) throw new RuntimeException('Two projects required.');
    $user->permittedProject = $allowed;
    $controller = $factory->createController($name, 'Administrator', ['base_path' => JPATH_ADMINISTRATOR . '/components/com_joomleague'], $app, $app->getInput());
    $method = new ReflectionMethod($controller, 'allowEdit');
    $normal = $method->invoke($controller, ['id' => (int) $record['id']]);
    $forged = $method->invoke($controller, ['id' => (int) $record['id'], 'project_id' => $allowed]);
    $observations[] = ['controller' => $name, 'stored_owner_denied' => !$normal, 'forged_owner_accepted' => $forged];
}
$member = $db->setQuery('SELECT m.id, e.project_id FROM #__joomleague_project_entry_member AS m INNER JOIN #__joomleague_project_entry AS e ON e.id=m.entry_id ORDER BY m.id', 0, 1)->loadAssoc();
if ($member) {
    $entry = $db->setQuery('SELECT id, project_id FROM #__joomleague_project_entry WHERE project_id <> ' . (int) $member['project_id'] . ' ORDER BY id', 0, 1)->loadAssoc();
    if (!$entry) throw new RuntimeException('Entry from another project required.');
    $user->permittedProject = (int) $entry['project_id'];
    $controller = $factory->createController('Entrymember', 'Administrator', ['base_path' => JPATH_ADMINISTRATOR . '/components/com_joomleague'], $app, $app->getInput());
    $method = new ReflectionMethod($controller, 'allowEdit');
    $observations[] = ['controller' => 'Entrymember', 'stored_owner_denied' => !$method->invoke($controller, ['id' => (int) $member['id']]), 'forged_owner_accepted' => $method->invoke($controller, ['id' => (int) $member['id'], 'entry_id' => (int) $entry['id']])];
} else {
    $observations[] = ['controller' => 'Entrymember', 'status' => 'SKIP', 'reason' => 'No existing membership'];
}
echo json_encode(['mode' => 'read-only, synthetic scoped identity, native Joomla controllers and real stored ownership', 'observations' => $observations], JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR) . "\n";
if (count($observations) !== 6) throw new RuntimeException('Required controller ownership observations are missing.');
foreach ($observations as $observation) {
    if (($observation['status'] ?? '') === 'SKIP') throw new RuntimeException('Required controller ownership fixture is missing.');
    if (!$observation['stored_owner_denied'] || $observation['forged_owner_accepted']) exit(1);
}
