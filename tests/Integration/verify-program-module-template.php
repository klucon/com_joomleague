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
Factory::getApplication()->createExtensionNamespaceMap();
Factory::getApplication()->bootComponent('com_joomleague');

require_once JPATH_ROOT . '/components/com_joomleague/src/Service/ProjectTemplateProvider.php';
require_once JPATH_ROOT . '/modules/mod_joomleague_program/src/Configuration/ProgramConfiguration.php';
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
	throw new RuntimeException('Required published programme fixture is missing.');
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

$first = $programme['items'][0];
$filtered = $helper->getProgramme(new Registry([
	'project_id' => $projectId,
	'mode' => 'all',
	'stage_id' => (int) $first['stage_id'],
	'round_id' => (int) $first['round_id'],
	'venue_id' => (int) ($first['venue_id'] ?? 0),
	'status_codes' => [(string) $first['status_code']],
	'limit' => 100,
]));
if (isset($filtered['error'])) throw new RuntimeException('Valid programme filters unexpectedly returned an error.');
foreach ($filtered['items'] as $item) {
	if ((int) $item['stage_id'] !== (int) $first['stage_id'] || (int) $item['round_id'] !== (int) $first['round_id']) {
		throw new RuntimeException('Stage or round filter leaked another item.');
	}
	if ((int) ($first['venue_id'] ?? 0) > 0 && (int) $item['venue_id'] !== (int) $first['venue_id']) {
		throw new RuntimeException('Venue filter leaked another item.');
	}
	if ((string) $item['status_code'] !== (string) $first['status_code']) throw new RuntimeException('Status filter leaked another item.');
}

$limited = $helper->getProgramme(new Registry([
	'project_id' => $projectId, 'mode' => 'all', 'limit' => 1, 'participant_limit' => 1,
]));
if (isset($limited['error']) || count($limited['items']) !== 1 || count($limited['items'][0]['participants']) > 1) {
	throw new RuntimeException('Programme item or participant limit was not applied.');
}

printf("Programme module template OK on %s: project %d filters, limits and presentation override validated\n", $database->getServerType(), $projectId);
