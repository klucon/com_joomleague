<?php

declare(strict_types=1);

define('_JEXEC', 1);
define('JPATH_BASE', '/var/www/html');
require_once JPATH_BASE . '/includes/defines.php';
require_once JPATH_BASE . '/includes/framework.php';

use Joomla\CMS\Factory;
use Joomla\CMS\Http\HttpFactory;
use Joomla\Database\DatabaseInterface;

$container = Factory::getContainer();
$container->alias('session', 'session.cli')
    ->alias('JSession', 'session.cli')
    ->alias(Joomla\CMS\Session\Session::class, 'session.cli')
    ->alias(Joomla\Session\Session::class, 'session.cli')
    ->alias(Joomla\Session\SessionInterface::class, 'session.cli');
$application = $container->get(Joomla\Console\Application::class);
$application->createExtensionNamespaceMap();
Factory::$application = $application;

$database = $container->get(DatabaseInterface::class);
$element = 'pkg_joomleague';
$type = 'package';
$location = 'https://downloads.joomleague.eu/update.xml';
$extensionQuery = $database->createQuery()
    ->select([$database->quoteName('extension_id'), $database->quoteName('manifest_cache')])
    ->from($database->quoteName('#__extensions'))
    ->where($database->quoteName('element') . ' = :element')
    ->where($database->quoteName('type') . ' = :type')
    ->bind(':element', $element)
    ->bind(':type', $type);
$extension = $database->setQuery($extensionQuery)->loadObject();

if ($extension === null) {
    throw new RuntimeException('The JoomLeague package is not installed.');
}

$manifest = json_decode((string) $extension->manifest_cache, true, 512, JSON_THROW_ON_ERROR);
$installedVersion = (string) ($manifest['version'] ?? '');

if ($installedVersion === '') {
    throw new RuntimeException('The installed package version is missing.');
}

$http = HttpFactory::getHttp();
$response = $http->get($location, [], 30);

if ((int) $response->code !== 200) {
    throw new RuntimeException(sprintf('The update server returned HTTP %d.', (int) $response->code));
}

$xml = simplexml_load_string((string) $response->body);

if (!$xml instanceof SimpleXMLElement) {
    throw new RuntimeException('The update server returned invalid XML.');
}

$update = null;
foreach ($xml->update as $candidate) {
    if ((string) $candidate->element === $element && (string) $candidate->type === $type) {
        $update = $candidate;
        break;
    }
}

if (!$update instanceof SimpleXMLElement) {
    throw new RuntimeException('The update XML does not contain the JoomLeague package.');
}

$offeredVersion = (string) $update->version;
$targetPlatform = (string) $update->targetplatform['version'];
$downloadUrl = (string) $update->downloads->downloadurl;
$expectedSha256 = strtolower((string) $update->sha256);

if ($offeredVersion === '' || version_compare($offeredVersion, $installedVersion, '<')) {
    throw new RuntimeException('The update server offered an invalid or older package.');
}

$platformSeries = preg_match('/^\d+\.\d+/', JVERSION, $platformMatch) === 1 ? $platformMatch[0] : '';
if ($targetPlatform === '' || $platformSeries === '' || preg_match('/^' . $targetPlatform . '$/', $platformSeries) !== 1) {
    throw new RuntimeException(sprintf('The update does not target Joomla %s.', JVERSION));
}

if (!filter_var($downloadUrl, FILTER_VALIDATE_URL) || !preg_match('/^[a-f0-9]{64}$/', $expectedSha256)) {
    throw new RuntimeException('The update package URL or SHA-256 checksum is invalid.');
}

$packageResponse = $http->get($downloadUrl, [], 60);
if ((int) $packageResponse->code !== 200 || hash('sha256', (string) $packageResponse->body) !== $expectedSha256) {
    throw new RuntimeException('The downloadable package is unavailable or its SHA-256 checksum differs.');
}

printf(
    "JoomLeague update server OK: installed %s, offered %s, Joomla %s, checksum verified\n",
    $installedVersion,
    $offeredVersion,
    JVERSION,
);
