<?php

declare(strict_types=1);

define('_JEXEC', 1);
define('JPATH_BASE', '/var/www/html');

require_once JPATH_BASE . '/includes/defines.php';
require_once JPATH_BASE . '/includes/framework.php';

JLoader::registerNamespace(
	'Joomleague\\Component\\Joomleague\\Administrator',
	JPATH_ADMINISTRATOR . '/components/com_joomleague/src',
	false,
	false,
	'psr4'
);
JLoader::registerNamespace(
	'Joomleague\\Component\\Joomleague\\Domain',
	JPATH_ADMINISTRATOR . '/components/com_joomleague/src',
	false,
	false,
	'psr4'
);

use Joomla\CMS\Factory;
use Joomla\Database\DatabaseInterface;
use Joomleague\Component\Joomleague\Administrator\Service\DemoDataGenerator;
use Joomleague\Component\Joomleague\Administrator\Service\RuntimeDataResetter;

$container = Factory::getContainer();
$container->alias('session', 'session.cli')
	->alias('JSession', 'session.cli')
	->alias(Joomla\CMS\Session\Session::class, 'session.cli')
	->alias(Joomla\Session\Session::class, 'session.cli')
	->alias(Joomla\Session\SessionInterface::class, 'session.cli');
Factory::$application = $container->get(Joomla\Console\Application::class);

$database = $container->get(DatabaseInterface::class);
$resetter = new RuntimeDataResetter($database);
$generator = new DemoDataGenerator($database);
$resetter->reset();

$summary = $generator->generate(['football'], 0);
if (array_keys($summary) !== ['football']) throw new RuntimeException('Football-only demo selection was not respected.');
if ((int) ($summary['football']['project_id'] ?? 0) !== 1) throw new RuntimeException('The first demo project did not start at ID 1.');
if ((int) ($summary['football']['sport_type_id'] ?? 0) !== 1) throw new RuntimeException('The first sport type did not start at ID 1.');
if ((int) ($summary['football']['entries'] ?? 0) !== 8) throw new RuntimeException('Football demo does not contain eight entries.');
if ((int) ($summary['football']['completed'] ?? 0) < 1) throw new RuntimeException('Football demo does not contain completed programme items.');

try {
	$generator->generate(['football'], 0);
	throw new RuntimeException('Demo generation was allowed over existing runtime data.');
} catch (RuntimeException $exception) {
	if ($exception->getMessage() !== 'COM_JOOMLEAGUE_DEMODATA_ERROR_NOT_EMPTY') throw $exception;
}

$resetter->reset();
if ($resetter->hasRuntimeData()) throw new RuntimeException('Runtime data remain after reset.');

$second = $generator->generate(['football'], 0);
if ((int) ($second['football']['project_id'] ?? 0) !== 1) throw new RuntimeException('Project identity was not reset to 1.');
if ((int) ($second['football']['sport_type_id'] ?? 0) !== 1) throw new RuntimeException('Sport-type identity was not reset to 1.');

$resetter->reset();

$allProfiles = $generator->generate(array_keys(DemoDataGenerator::PROFILES), 0);
if (count($allProfiles) !== 15) throw new RuntimeException('All-profile demo generation did not create 15 projects.');
if (array_column($allProfiles, 'project_id') !== range(1, 15)) throw new RuntimeException('All-profile project identities are not consecutive from 1.');

$resetter->reset();
echo "Demo data lifecycle passed on " . $database->getName() . ".\n";
