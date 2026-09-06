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
use Joomleague\Module\ProgrammeTicker\Site\Helper\ProgrammeTickerHelper;

$container = Factory::getContainer();
$container->alias('session', 'session.cli')->alias('JSession', 'session.cli')
	->alias(Joomla\CMS\Session\Session::class, 'session.cli')->alias(Joomla\Session\Session::class, 'session.cli')
	->alias(Joomla\Session\SessionInterface::class, 'session.cli');
Factory::$application = $container->get(Joomla\CMS\Application\SiteApplication::class);
Factory::getApplication()->bootComponent('com_joomleague');
require_once JPATH_ROOT . '/components/com_joomleague/src/Service/ProjectTemplateProvider.php';
require_once JPATH_ROOT . '/modules/mod_joomleague_programme_ticker/src/Helper/ProgrammeTickerHelper.php';

$database = $container->get(DatabaseInterface::class);
$publishedEvents = (int) $database->setQuery(
	$database->getQuery(true)->select('COUNT(*)')->from($database->quoteName('#__joomleague_project_match'))->where('published = 1')
)->loadResult();
if ($publishedEvents < 1) {
	printf("Programme ticker SKIP on %s: no published programme fixture\n", $database->getServerType());
	exit(0);
}

$ticker = (new ProgrammeTickerHelper())->getTicker(new Registry([
	'days_before' => 5000,
	'days_after' => 5000,
	'completed_limit' => 3,
	'upcoming_limit' => 5,
	'template_show_match_detail_button' => '0',
]));
if (isset($ticker['error'])) {
	throw new RuntimeException('Programme ticker did not aggregate the published fixture.');
}
$items = [...($ticker['completed'] ?? []), ...($ticker['upcoming'] ?? [])];
if ($items === [] || count($ticker['completed'] ?? []) > 3 || count($ticker['upcoming'] ?? []) > 5) {
	throw new RuntimeException('Programme ticker limits or grouping contract failed.');
}
foreach ($items as $item) {
	if (($item['show_detail'] ?? true) !== false) {
		throw new RuntimeException('Programme ticker detail-link override was not applied.');
	}
}

printf("Programme ticker OK on %s: %d completed/%d upcoming\n", $database->getServerType(), count($ticker['completed'] ?? []), count($ticker['upcoming'] ?? []));
