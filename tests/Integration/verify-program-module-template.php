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
use Joomleague\Module\Program\Site\Helper\ProgramHelper;

$container = Factory::getContainer();
$container->alias('session', 'session.cli')
	->alias('JSession', 'session.cli')
	->alias(Joomla\CMS\Session\Session::class, 'session.cli')
	->alias(Joomla\Session\Session::class, 'session.cli')
	->alias(Joomla\Session\SessionInterface::class, 'session.cli');
Factory::$application = $container->get(Joomla\CMS\Application\SiteApplication::class);
Factory::getApplication()->bootComponent('com_joomleague');

require_once JPATH_ROOT . '/components/com_joomleague/src/Service/ProjectTemplateProvider.php';
require_once JPATH_ROOT . '/modules/mod_joomleague_program/src/Helper/ProgramHelper.php';

$database = $container->get(DatabaseInterface::class);
$projectId = (int) $database->setQuery(
	$database->getQuery(true)
		->select($database->quoteName('project_id'))
		->from($database->quoteName('#__joomleague_project_match'))
		->where($database->quoteName('published') . ' = 1')
		->group($database->quoteName('project_id'))
		->order($database->quoteName('project_id') . ' ASC'),
	0,
	1
)->loadResult();
if ($projectId < 1) {
	printf("Programme module template SKIP: no published programme fixture\n");
	exit(0);
}

$helper = new ProgramHelper();
$programme = $helper->getProgramme(new Registry([
	'project_id' => $projectId,
	'mode' => 'all',
	'limit' => 1,
	'template_show_match_detail_button' => '0',
]));
if (isset($programme['error'])) {
	throw new RuntimeException('Programme module could not render the selected published fixture.');
}
if (($programme['show_detail'] ?? true) !== false) {
	throw new RuntimeException('Programme module presentation override did not disable event-detail links.');
}

printf("Programme module template OK on %s: project %d detail link override validated\n", $database->getServerType(), $projectId);
