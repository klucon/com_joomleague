<?php

declare(strict_types=1);

define('_JEXEC', 1);
define('JPATH_BASE', '/var/www/html');
$_SERVER['HTTP_HOST'] ??= 'localhost';
$_SERVER['REQUEST_URI'] ??= '/';
$_SERVER['SCRIPT_NAME'] = '/index.php';
require_once JPATH_BASE . '/includes/defines.php';
require_once JPATH_BASE . '/includes/framework.php';
require_once JPATH_ADMINISTRATOR . '/components/com_joomleague/src/Extension/JoomleagueComponent.php';

use Joomla\CMS\Factory;
use Joomla\Database\DatabaseInterface;
use Joomla\Registry\Registry;
use Joomleague\Module\Standings\Site\Helper\StandingsHelper;

$container = Factory::getContainer();
$container->alias('session', 'session.cli')
	->alias('JSession', 'session.cli')
	->alias(Joomla\CMS\Session\Session::class, 'session.cli')
	->alias(Joomla\Session\Session::class, 'session.cli')
	->alias(Joomla\Session\SessionInterface::class, 'session.cli');
Factory::$application = $container->get(Joomla\Console\Application::class);
Factory::getApplication()->bootComponent('com_joomleague');

require_once JPATH_ROOT . '/components/com_joomleague/src/Service/ProjectTemplateProvider.php';
require_once JPATH_ROOT . '/components/com_joomleague/src/Service/RankingColumnFilter.php';
require_once JPATH_ROOT . '/modules/mod_joomleague_standings/src/Helper/StandingsHelper.php';

$database = $container->get(DatabaseInterface::class);
$query = $database->getQuery(true)
	->select($database->quoteName('snapshot.project_id'))
	->from($database->quoteName('#__joomleague_standing_snapshot', 'snapshot'))
	->innerJoin($database->quoteName('#__joomleague_standing_snapshot_row', 'standing_row') . ' ON standing_row.snapshot_id = snapshot.id')
	->group($database->quoteName('snapshot.project_id'))
	->order($database->quoteName('snapshot.project_id') . ' ASC');
$projectId = (int) $database->setQuery($query, 0, 1)->loadResult();
if ($projectId < 1) {
	printf("Standings module template SKIP: no published standings fixture\n");
	exit(0);
}

$helper = new StandingsHelper();
$inherited = $helper->getStandings(new Registry(['project_id' => $projectId]));
if (isset($inherited['error']) || ($inherited['columns'] ?? []) === []) {
	throw new RuntimeException('Standings module could not render inherited project-template columns.');
}

$hidden = $helper->getStandings(new Registry([
	'project_id' => $projectId,
	'template_show_score' => '0',
	'template_show_goal_difference' => '0',
	'template_show_sets' => '0',
	'template_show_points' => '0',
]));
if (isset($hidden['error'])) {
	throw new RuntimeException('Standings module failed with presentation overrides.');
}
if (count($hidden['columns']) >= count($inherited['columns'])) {
	throw new RuntimeException('Restrictive module presentation overrides did not reduce visible score, difference, set or points columns.');
}

printf("Standings module template OK on %s: project %d, %d inherited/%d restricted columns\n", $database->getServerType(), $projectId, count($inherited['columns']), count($hidden['columns']));
