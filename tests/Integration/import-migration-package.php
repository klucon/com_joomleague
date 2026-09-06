<?php

declare(strict_types=1);

define('_JEXEC', 1);
define('JPATH_BASE', '/var/www/html');
$configurationPath = getenv('JOOMLEAGUE_TEST_CONFIGURATION');
if (is_string($configurationPath) && $configurationPath !== '') {
	define('JPATH_CONFIGURATION', $configurationPath);
}
require_once JPATH_BASE . '/includes/defines.php';
require_once JPATH_BASE . '/includes/framework.php';
require_once JPATH_ADMINISTRATOR . '/components/com_joomleague/src/Service/ComponentTableCatalog.php';
require_once JPATH_ADMINISTRATOR . '/components/com_joomleague/src/Service/CanonicalJson.php';
require_once JPATH_ADMINISTRATOR . '/components/com_joomleague/src/Service/UuidFactory.php';
require_once JPATH_ADMINISTRATOR . '/components/com_joomleague/src/Service/SportTypeProfileMaterializer.php';
require_once JPATH_ADMINISTRATOR . '/components/com_joomleague/src/Service/SqlDataExchangeService.php';

use Joomla\CMS\Factory;
use Joomla\Database\DatabaseInterface;
use Joomleague\Component\Joomleague\Administrator\Service\SqlDataExchangeService;

$path = $argv[1] ?? '';

if ($path === '' || !is_file($path)) {
	throw new RuntimeException('Provide a readable migration SQL file.');
}

$container = Factory::getContainer();
$container->alias('session', 'session.cli')
	->alias('JSession', 'session.cli')
	->alias(Joomla\CMS\Session\Session::class, 'session.cli')
	->alias(Joomla\Session\Session::class, 'session.cli')
	->alias(Joomla\Session\SessionInterface::class, 'session.cli');
Factory::$application = $container->get(Joomla\Console\Application::class);

$database = $container->get(DatabaseInterface::class);
$service = new SqlDataExchangeService($database, JPATH_ADMINISTRATOR . '/components/com_joomleague');
$result = $service->import((string) file_get_contents($path));

echo json_encode([
	'driver' => $database->getName(),
	'executed' => $result['executed'],
	'skipped' => $result['skipped'],
], JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT) . PHP_EOL;
