<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') exit(1);
require '/var/www/html/configuration.php';
$config = new JConfig();
$config->mailonline = false;
$config->force_ssl = 0;
$config->live_site = '';
$config->sef = false;
$config->error_reporting = 'maximum';
$text = "<?php\nclass JConfig {\n";
foreach (get_object_vars($config) as $key => $value) {
    if (!preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/D', $key)) throw new RuntimeException('Invalid config field.');
    $text .= 'public $' . $key . ' = ' . var_export($value, true) . ";\n";
}
file_put_contents('/var/www/html/configuration.php', $text . "}\n", LOCK_EX);
echo "Isolated mail disabled and local HTTP configured.\n";
define('_JEXEC', 1);
define('JPATH_BASE', '/var/www/html');
$_SERVER['HTTP_HOST'] = 'localhost';
$_SERVER['REQUEST_URI'] = '/administrator/index.php';
$_SERVER['SCRIPT_NAME'] = '/administrator/index.php';
require JPATH_BASE . '/includes/defines.php';
require JPATH_BASE . '/includes/framework.php';
$container = Joomla\CMS\Factory::getContainer();
$container->alias('session', 'session.cli')->alias(Joomla\Session\SessionInterface::class, 'session.cli');
Joomla\CMS\Factory::$application = $container->get(Joomla\CMS\Application\AdministratorApplication::class);
Joomla\CMS\Factory::$application->createExtensionNamespaceMap();
$user = $container->get(Joomla\CMS\User\UserFactoryInterface::class)->loadUserByUsername('admin');
Joomla\CMS\Factory::$application->loadIdentity($user);
Joomla\CMS\Factory::$application->getSession()->set('user', $user);
if (!$user->authorise('core.admin')) throw new RuntimeException('Synthetic administrator lacks core.admin.');
$user->setParam('allowTourAutoStart', 0);
if (!$user->save()) throw new RuntimeException('Could not configure the synthetic test administrator: ' . $user->getError());
echo "Synthetic administrator automatic onboarding disabled.\n";
