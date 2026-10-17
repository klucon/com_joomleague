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
$match = $db->setQuery('SELECT id,project_id FROM #__joomleague_project_match WHERE project_id>0 ORDER BY id', 0, 1)->loadAssoc();
if (!$match) throw new RuntimeException('Existing match required.');
$otherProject = (int) $db->setQuery('SELECT id FROM #__joomleague_project WHERE id <> ' . (int) $match['project_id'], 0, 1)->loadResult();
if (!$otherProject) throw new RuntimeException('Two projects required.');
$user = new class extends Joomla\CMS\User\User {
    public int $permittedProject = 0;
    public array $checks = [];
    public function authorise($action, $assetname = null) {
        $this->checks[] = [$action, $assetname];
        return $assetname === 'com_joomleague.project.' . $this->permittedProject;
    }
};
$user->permittedProject = $otherProject;
$app->loadIdentity($user);
$input = $app->getInput();
$input->set('match_id', (int) $match['id']);
$input->set('project_id', $otherProject);
$input->post->set(Joomla\CMS\Session\Session::getFormToken(), '1');
if (!Joomla\CMS\Session\Session::checkToken()) throw new RuntimeException('Valid-token precondition failed.');
$factory = $app->bootComponent('com_joomleague')->getMVCFactory();
$failed = 0;
foreach ([
    'Matchresult' => ['save', 'apply', 'addSegment', 'removeSegment', 'cancel'],
    'Matchlineup' => ['assign', 'remove', 'addSubstitution', 'removeSubstitution'],
    'Matchevents' => ['add', 'remove'],
    'Matchstatistics' => ['saveValue', 'remove'],
    'Matchofficials' => ['assign', 'remove', 'assignDirect', 'changeRole'],
] as $name => $methods) {
    $controller = $factory->createController($name, 'Administrator', ['base_path' => JPATH_ADMINISTRATOR . '/components/com_joomleague'], $app, $input);
    foreach ($methods as $method) {
        $user->checks = [];
        $denied = false;
        try { $controller->$method(); }
        catch (RuntimeException $error) { $denied = $error->getCode() === 403; }
        $actualOwner = $user->checks !== [] && end($user->checks)[1] === 'com_joomleague.project.' . $match['project_id'];
        $ok = $denied && $actualOwner;
        $failed += $ok ? 0 : 1;
        echo ($ok ? 'PASS' : 'FAIL') . ': valid-token foreign-project denial ' . $name . '.' . $method . "\n";
    }
}
echo "Synthetic scoped identity only; no user or business records created.\n";
exit($failed ? 1 : 0);
