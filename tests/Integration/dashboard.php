<?php

declare(strict_types=1);

define('_JEXEC', 1);
define('JPATH_BASE', '/var/www/html');
$_SERVER['HTTP_HOST'] = 'localhost';
$_SERVER['SERVER_NAME'] = 'localhost';
$_SERVER['SERVER_PORT'] = '80';
$_SERVER['REQUEST_URI'] = '/';
$_SERVER['SCRIPT_NAME'] = '/index.php';
require_once JPATH_BASE . '/includes/defines.php';
require_once JPATH_BASE . '/includes/framework.php';
require_once JPATH_ADMINISTRATOR . '/components/com_joomleague/src/Extension/JoomleagueComponent.php';
require_once JPATH_ADMINISTRATOR . '/components/com_joomleague/src/Model/DashboardModel.php';

use Joomla\CMS\Factory;
use Joomla\Database\DatabaseInterface;
use Joomleague\Component\Joomleague\Administrator\Model\DashboardModel;

$container = Factory::getContainer();
$container->alias('session', 'session.cli')
	->alias('JSession', 'session.cli')
	->alias(Joomla\CMS\Session\Session::class, 'session.cli')
	->alias(Joomla\Session\Session::class, 'session.cli')
	->alias(Joomla\Session\SessionInterface::class, 'session.cli');
Factory::$application = $container->get(Joomla\Console\Application::class);

$database = Factory::getContainer()->get(DatabaseInterface::class);
$model = new DashboardModel(['dbo' => $database]);
$summary = $model->getSummary();
$projects = $model->getRecentProjects();
$attention = $model->getAttentionCounts();
$clubMatchLoader = new ReflectionMethod($model, 'loadClubMatches');
$clubMatches = $clubMatchLoader->invoke($model, 1, false, 1);

foreach (['projects', 'upcoming_matches', 'missing_results', 'incomplete_projects'] as $key) {
	if (!isset($summary[$key]) || !is_int($summary[$key])) throw new RuntimeException('Dashboard summary is incomplete.');
}
if (!is_array($projects) || !is_array($attention) || !is_array($clubMatches)) {
	throw new RuntimeException('Dashboard operational data is incomplete.');
}

echo "Dashboard operational queries OK\n";
