<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli' || getenv('ALLOW_PERSISTENT_DEMO') !== '1') {
	throw new RuntimeException('Explicit CLI opt-in is required.');
}
define('_JEXEC', 1);
define('JPATH_BASE', '/var/www/html');
require_once JPATH_BASE . '/includes/defines.php';
require_once JPATH_BASE . '/includes/framework.php';
foreach (['Administrator', 'Domain'] as $namespace) {
	JLoader::registerNamespace('Joomleague\\Component\\Joomleague\\' . $namespace, JPATH_ADMINISTRATOR . '/components/com_joomleague/src', false, false, 'psr4');
}
$container = Joomla\CMS\Factory::getContainer();
$container->alias('session', 'session.cli')
	->alias(Joomla\Session\SessionInterface::class, 'session.cli');
Joomla\CMS\Factory::$application = $container->get(Joomla\Console\Application::class);
$database = $container->get(Joomla\Database\DatabaseInterface::class);
if ($database->getServerType() !== 'postgresql') {
	throw new RuntimeException('This runner is restricted to PostgreSQL.');
}
$generator = new Joomleague\Component\Joomleague\Administrator\Service\DemoDataGenerator($database);
$summary = $generator->generate(array_keys($generator::PROFILES), 0);
echo json_encode($summary, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR) . "\n";
