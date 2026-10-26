<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli' || !str_starts_with((string) getenv('JL_R1_DB_HOST'), 'jl-r1-')) {
    throw new RuntimeException('Disposable R1 environment required.');
}
chdir('/var/www/html');
if (!is_file('configuration.php')) {
    $options = [
        'site-name' => 'JoomLeague R1 acceptance', 'admin-user' => 'R1 Administrator',
        'admin-username' => 'admin', 'admin-password' => getenv('JL_R1_PASSWORD'),
        'admin-email' => 'admin@example.invalid', 'db-type' => getenv('JL_R1_DRIVER'),
        'db-host' => getenv('JL_R1_DB_HOST'), 'db-user' => getenv('JL_R1_DB_USER'),
        'db-pass' => getenv('JL_R1_PASSWORD'), 'db-name' => 'r1_test',
        'db-prefix' => 'r1_', 'db-encryption' => '0', 'public-folder' => '',
    ];
    $argv = ['/var/www/html/installation/joomla.php', 'install', '--no-interaction'];
    foreach ($options as $key => $value) $argv[] = '--' . $key . '=' . $value;
    $_SERVER['argv'] = $argv;
    $_SERVER['argc'] = count($argv);
    require '/var/www/html/installation/joomla.php';
    exit;
}

require 'configuration.php';
$config = get_object_vars(new JConfig());
if (($config['host'] ?? '') !== getenv('JL_R1_DB_HOST') || ($config['db'] ?? '') !== 'r1_test' || ($config['dbprefix'] ?? '') !== 'r1_') {
    throw new RuntimeException('Refusing to modify a non-disposable Joomla configuration.');
}
$config['mailonline'] = false;
$config['session_handler'] = 'database';
$config['force_ssl'] = 0;
$config['sef'] = 0;
$config['sef_rewrite'] = 0;
$config['live_site'] = '';
$config['debug'] = false;
$config['error_reporting'] = 'maximum';
file_put_contents('configuration.php', "<?php\nclass JConfig {\n" . implode('', array_map(
    static fn ($key, $value) => 'public $' . $key . ' = ' . var_export($value, true) . ";\n",
    array_keys($config), $config
)) . "}\n");

define('_JEXEC', 1);
define('JPATH_BASE', '/var/www/html');
$_SERVER['HTTP_HOST'] = 'localhost';
$_SERVER['REQUEST_URI'] = '/administrator/index.php';
$_SERVER['SCRIPT_NAME'] = '/administrator/index.php';
require 'includes/defines.php';
require 'includes/framework.php';
$container = Joomla\CMS\Factory::getContainer();
$container->alias('session', 'session.cli')->alias(Joomla\Session\SessionInterface::class, 'session.cli');
$app = $container->get(Joomla\CMS\Application\AdministratorApplication::class);
Joomla\CMS\Factory::$application = $app;
$app->createExtensionNamespaceMap();
$app->loadLanguage(Joomla\CMS\Language\Language::getInstance('en-GB'));
$db = $container->get(Joomla\Database\DatabaseInterface::class);
$adminId = (int) $db->setQuery('SELECT id FROM #__users WHERE username=' . $db->quote('admin'))->loadResult();
$app->loadIdentity($container->get(Joomla\CMS\User\UserFactoryInterface::class)->loadUserById($adminId));
$installer = new Joomla\CMS\Installer\Installer();
$installer->setDatabase($db);
if (getenv('FCR_FLAGS_TEST') === '1') {
    Joomla\CMS\Log\Log::addLogger(['text_file'=>'flags-install.php'], Joomla\CMS\Log\Log::ALL, ['jerror', 'installer']);
}
ob_start();
$package = $argv[1] ?? '/opt/candidate';
if (!in_array($package, ['/opt/candidate', '/opt/legacy', '/opt/suite', '/opt/legacy-suite'], true) && !preg_match('#^/opt/candidate/modules/mod_joomleague_[a-z_]+$#D',$package) && !preg_match('#^/opt/candidate/plugins/(content|quickicon|finder|console|task)/joomleague$#D',$package)) throw new RuntimeException('Unknown disposable installation source.');
$writableStage = null;
if (in_array($package, ['/opt/suite', '/opt/legacy-suite'], true)) {
    $writableStage = JPATH_BASE . '/tmp/jl-package-' . bin2hex(random_bytes(8));
    if (!Joomla\Filesystem\Folder::copy($package, $writableStage)) throw new RuntimeException('Cannot stage disposable package');
}
try { $ok = $installer->install($writableStage ?? $package); }
finally { if ($writableStage !== null && is_dir($writableStage)) Joomla\Filesystem\Folder::delete($writableStage); }
$output = ob_get_clean();
if (!$ok) throw new RuntimeException('Component installation failed: ' . strip_tags($output) . ' ' . json_encode($app->getMessageQueue()));
echo "PASS: fresh Joomla and candidate component installed; mail disabled.\n";
