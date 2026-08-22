<?php

declare(strict_types=1);

define('_JEXEC', 1);
define('JPATH_BASE', '/var/www/html');
require_once JPATH_BASE . '/includes/defines.php';
require_once JPATH_BASE . '/includes/framework.php';
require_once JPATH_ADMINISTRATOR . '/components/com_joomleague/src/Service/ComponentTableCatalog.php';
require_once JPATH_ADMINISTRATOR . '/components/com_joomleague/src/Service/SqlDataExchangeService.php';
require_once JPATH_ADMINISTRATOR . '/components/com_joomleague/script.php';

use Joomla\CMS\Factory;
use Joomla\Database\DatabaseInterface;
use Joomleague\Component\Joomleague\Administrator\Service\ComponentTableCatalog;
use Joomleague\Component\Joomleague\Administrator\Service\SqlDataExchangeService;

$container = Factory::getContainer();
$container->alias('session', 'session.cli')->alias('JSession', 'session.cli')->alias(Joomla\CMS\Session\Session::class, 'session.cli')->alias(Joomla\Session\Session::class, 'session.cli')->alias(Joomla\Session\SessionInterface::class, 'session.cli');
Factory::$application = $container->get(Joomla\Console\Application::class);
$database = $container->get(DatabaseInterface::class);
$service = new SqlDataExchangeService($database, JPATH_ADMINISTRATOR . '/components/com_joomleague');
$tables = ComponentTableCatalog::installed($database);

if (count($tables) !== 48 || !in_array('#__joomleague_sport_profile', $tables, true)) throw new RuntimeException('Installed table catalogue is incomplete.');
$sql = $service->export(['#__joomleague_sport_profile']);
if (!str_contains($sql, 'CREATE TABLE IF NOT EXISTS') || !str_contains($sql, 'INSERT INTO') || !str_contains($sql, '#__joomleague_sport_profile')) throw new RuntimeException('SQL export does not contain structure and data.');
$import = $service->import($sql);
if ($import['skipped'] !== 15) throw new RuntimeException('Existing exported rows were not reported as skipped duplicates.');

try {
	$service->import('DROP TABLE #__joomleague_sport_profile;');
	throw new RuntimeException('Destructive SQL was accepted.');
} catch (RuntimeException $error) {
	if ($error->getMessage() !== 'COM_JOOMLEAGUE_DATAIMPORT_ERROR_STATEMENT') throw $error;
}

$sync = (new Com_JoomleagueInstallerScript())->synchroniseBundledProfiles();
if ($sync['processed'] !== 15) throw new RuntimeException('Bundled profile synchronization count is invalid.');
echo "Administrator tools integration passed.\n";
