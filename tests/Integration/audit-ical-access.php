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
$app->loadLanguage(Joomla\CMS\Language\Language::getInstance('en-GB'));
$app->bootComponent('com_joomleague');
$db = $container->get(Joomla\Database\DatabaseInterface::class);
if ($db->getServerType() !== 'mysql') throw new RuntimeException('MariaDB temporary-table test only.');
$project = $db->setQuery('SELECT id, competition_id, season_id, sport_type_id, name, published, access FROM #__joomleague_project WHERE id=1')->loadObject();
$adminId = (int) $db->setQuery("SELECT id FROM #__users WHERE username='admin'")->loadResult();
if (!$project || !$adminId) throw new RuntimeException('Local project 1 and admin fixtures required.');
$guest = new Joomla\CMS\User\User();
$admin = new Joomla\CMS\User\User($adminId);
if (in_array(2, $guest->getAuthorisedViewLevels(), true) || !in_array(2, $admin->getAuthorisedViewLevels(), true)) {
    throw new RuntimeException('Registered access-level fixture assumption is invalid.');
}
$table = $db->quoteName('#__joomleague_project');
// A connection-local shadow hides the persistent table only for this test.
$db->setQuery('CREATE TEMPORARY TABLE ' . $table . ' (id BIGINT PRIMARY KEY, competition_id BIGINT, season_id BIGINT, sport_type_id BIGINT, name VARCHAR(255), published INTEGER, access INTEGER)')->execute();
try {
    $project->access = 2;
    $project->published = 1;
    $project->name = 'Audit private calendar marker';
    $db->insertObject('#__joomleague_project', $project);
    $app->getInput()->set('project_id', 1);
    $app->getInput()->set('scope', 'project');
    $model = new Joomleague\Component\Joomleague\Site\Model\IcalModel();
    $app->loadIdentity($guest);
    $public = $model->getCalendar();
    if (isset($public['content'])) throw new RuntimeException('Guest received private calendar.');
    echo "PASS: guest cannot export private project calendar\n";
    $app->loadIdentity($admin);
    $private = $model->getCalendar();
    if (!str_contains($private['content'] ?? '', 'BEGIN:VEVENT') || !str_contains($private['content'] ?? '', $project->name)) {
        throw new RuntimeException('Authorized identity must receive nonempty private calendar.');
    }
    echo "PASS: authorized identity receives nonempty private project calendar\n";
} finally {
    $db->setQuery('DROP TEMPORARY TABLE ' . $table)->execute();
}
