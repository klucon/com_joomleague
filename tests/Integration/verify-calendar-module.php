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
use Joomleague\Module\Calendar\Site\Helper\CalendarHelper;

$container = Factory::getContainer();
$container->alias('session', 'session.cli')
	->alias('JSession', 'session.cli')
	->alias(Joomla\CMS\Session\Session::class, 'session.cli')
	->alias(Joomla\Session\Session::class, 'session.cli')
	->alias(Joomla\Session\SessionInterface::class, 'session.cli');
Factory::$application = $container->get(Joomla\CMS\Application\SiteApplication::class);
Factory::getApplication()->bootComponent('com_joomleague');

require_once JPATH_ROOT . '/components/com_joomleague/src/Service/ProjectTemplateProvider.php';
require_once JPATH_ROOT . '/modules/mod_joomleague_calendar/src/Helper/CalendarHelper.php';

$database = $container->get(DatabaseInterface::class);
$publishedEvents = (int) $database->setQuery(
	$database->getQuery(true)->select('COUNT(*)')->from($database->quoteName('#__joomleague_project_match'))->where('published = 1')
)->loadResult();
if ($publishedEvents < 1) {
	printf("Calendar module SKIP on %s: no published programme fixture\n", $database->getServerType());
	exit(0);
}

$calendar = (new CalendarHelper())->getCalendar(new Registry([
	'days_before' => 365,
	'days_after' => 5000,
	'limit' => 200,
	'template_show_match_detail_button' => '0',
]));
if (isset($calendar['error']) || ($calendar['groups'] ?? []) === []) {
	throw new RuntimeException('Calendar module did not aggregate the published programme fixture.');
}
foreach ($calendar['groups'] as $group) {
	if ($group['items'] === []) {
		throw new RuntimeException('Calendar module emitted an empty date group.');
	}
	foreach ($group['items'] as $item) {
		if (($item['show_detail'] ?? true) !== false) {
			throw new RuntimeException('Calendar module detail-link override was not applied.');
		}
	}
}

printf("Calendar module OK on %s: %d date groups aggregated\n", $database->getServerType(), count($calendar['groups']));
