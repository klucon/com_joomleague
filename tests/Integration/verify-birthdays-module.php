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
use Joomleague\Module\Birthdays\Site\Helper\BirthdaysHelper;

$container = Factory::getContainer();
$container->alias('session', 'session.cli')->alias('JSession', 'session.cli')
	->alias(Joomla\CMS\Session\Session::class, 'session.cli')->alias(Joomla\Session\Session::class, 'session.cli')
	->alias(Joomla\Session\SessionInterface::class, 'session.cli');
Factory::$application = $container->get(Joomla\CMS\Application\SiteApplication::class);
Factory::getApplication()->bootComponent('com_joomleague');
require_once JPATH_ROOT . '/modules/mod_joomleague_birthdays/src/Helper/BirthdaysHelper.php';

$database = $container->get(DatabaseInterface::class);
$suffix = bin2hex(random_bytes(6));
$uuid = sprintf('%s-%s-4%s-a%s-%s', substr($suffix . $suffix, 0, 8), substr($suffix . $suffix, 8, 4), substr($suffix . $suffix, 12, 3), substr($suffix . $suffix, 15, 3), substr($suffix . $suffix . $suffix, 18, 12));
$today = Factory::getDate('now', Factory::getApplication()->get('offset', 'UTC'));
$birthYear = (int) $today->format('Y') - 25;
$person = (object) [
	'uuid' => $uuid,
	'first_name' => 'Birthday',
	'last_name' => 'Fixture ' . $suffix,
	'birth_date' => sprintf('%04d-%s', $birthYear, $today->format('m-d')),
	'published' => 1,
	'access' => 1,
];

$database->insertObject('#__joomleague_person', $person, 'id');

try {
	$result = (new BirthdaysHelper())->getBirthdays(new Registry(['days' => 0, 'limit' => 100, 'show_age' => 1]));
	$matches = array_values(array_filter($result['items'] ?? [], static fn (object $item): bool => (int) $item->id === (int) $person->id));
	if (count($matches) !== 1 || (int) $matches[0]->days_until !== 0 || (int) $matches[0]->age !== 25) {
		throw new RuntimeException('Birthday module did not return the current-day fixture with the expected age.');
	}
	printf("Birthdays module OK on %s: current-day fixture found\n", $database->getServerType());
} finally {
	$query = $database->getQuery(true)->delete($database->quoteName('#__joomleague_person'))->where('id = ' . (int) $person->id);
	$database->setQuery($query)->execute();
}
