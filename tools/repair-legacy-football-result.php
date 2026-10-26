<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { http_response_code(403); exit; }
$options = getopt('', ['root:', 'actor:', 'match:', 'apply', 'revision:', 'backup:', 'backup-sha256:']);
try {
    $root = realpath((string) ($options['root'] ?? ''));
    if (!$root || !is_file($root . '/configuration.php')) throw new RuntimeException('Explicit Joomla --root required.');
    define('_JEXEC', 1); define('JPATH_BASE', $root);
    $_SERVER['HTTP_HOST'] = 'localhost'; $_SERVER['REQUEST_URI'] = '/administrator/index.php'; $_SERVER['SCRIPT_NAME'] = '/administrator/index.php';
    require $root . '/includes/defines.php'; require $root . '/includes/framework.php';
    $container = Joomla\CMS\Factory::getContainer();
    $container->alias('session', 'session.cli')->alias(Joomla\Session\SessionInterface::class, 'session.cli');
    $app = $container->get(Joomla\CMS\Application\AdministratorApplication::class);
    Joomla\CMS\Factory::$application = $app;
    $app->createExtensionNamespaceMap(); $app->bootComponent('com_joomleague');
    $actor = $container->get(Joomla\CMS\User\UserFactoryInterface::class)->loadUserById((int) ($options['actor'] ?? 0));
    $service = new Joomleague\Component\Joomleague\Administrator\Service\LegacyFootballResultRepair($container->get(Joomla\Database\DatabaseInterface::class));
    $matchId = (int) ($options['match'] ?? 0);
    if (!array_key_exists('apply', $options)) {
        echo json_encode($service->preview($matchId, $actor), JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR), PHP_EOL;
        exit;
    }
    $backup = realpath((string) ($options['backup'] ?? ''));
    $checksum = (string) ($options['backup-sha256'] ?? '');
    if (!$backup || !is_file($backup) || is_link((string)$options['backup']) || filesize($backup) < 100 || str_starts_with($backup, $root . DIRECTORY_SEPARATOR) || preg_match('/^[a-f0-9]{64}$/D', $checksum) !== 1 || !hash_equals($checksum, hash_file('sha256', $backup))) throw new RuntimeException('Verified non-public database backup and SHA-256 required before apply.');
    $id = $service->apply($matchId, (string) ($options['revision'] ?? ''), $actor);
    echo json_encode(['status'=>'repaired', 'match_id'=>$matchId, 'result_id'=>$id], JSON_THROW_ON_ERROR), PHP_EOL;
} catch (Throwable $error) {
    fwrite(STDERR, $error->getMessage() . PHP_EOL); exit(1);
}
