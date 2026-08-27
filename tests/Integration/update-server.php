<?php

declare(strict_types=1);

define('_JEXEC', 1);
define('JPATH_BASE', '/var/www/html');
require_once JPATH_BASE . '/includes/defines.php';
require_once JPATH_BASE . '/includes/framework.php';

use Joomla\CMS\Factory;
use Joomla\CMS\Updater\Updater;
use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;

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

$siteQuery = $database->createQuery()
    ->select('COUNT(*)')
    ->from($database->quoteName('#__update_sites', 's'))
    ->join('INNER', $database->quoteName('#__update_sites_extensions', 'x') . ' ON x.update_site_id = s.update_site_id')
    ->where('x.extension_id = :extensionId')
    ->where($database->quoteName('s.location') . ' = :location')
    ->where($database->quoteName('s.enabled') . ' = 1')
    ->bind(':extensionId', $extension->extension_id, ParameterType::INTEGER)
    ->bind(':location', $location);

if ((int) $database->setQuery($siteQuery)->loadResult() !== 1) {
    throw new RuntimeException('The JoomLeague update site is not registered and enabled.');
}

$database->truncateTable('#__updates');
$timestampQuery = $database->createQuery()
    ->update($database->quoteName('#__update_sites'))
    ->set($database->quoteName('last_check_timestamp') . ' = 0')
    ->where($database->quoteName('location') . ' = :location')
    ->bind(':location', $location);
$database->setQuery($timestampQuery)->execute();

Updater::getInstance()->findUpdates((int) $extension->extension_id, 0, Updater::STABILITY_DEV);

$updateQuery = $database->createQuery()
    ->select($database->quoteName('version'))
    ->from($database->quoteName('#__updates'))
    ->where($database->quoteName('element') . ' = :element')
    ->where($database->quoteName('type') . ' = :type')
    ->bind(':element', $element)
    ->bind(':type', $type);
$offeredVersion = (string) $database->setQuery($updateQuery)->loadResult();
$manifest = json_decode((string) $extension->manifest_cache, true, 512, JSON_THROW_ON_ERROR);
$installedVersion = (string) ($manifest['version'] ?? '');

if ($installedVersion === '') {
    throw new RuntimeException('The installed package version is missing.');
}

if ($offeredVersion !== '' && version_compare($offeredVersion, $installedVersion, '<')) {
    throw new RuntimeException('The update server offered an older package.');
}

if ($offeredVersion === $installedVersion) {
    $database->truncateTable('#__updates');
    $offeredVersion = '';
}

printf(
    "JoomLeague update server OK: installed %s%s\n",
    $installedVersion,
    $offeredVersion !== '' ? ', offered ' . $offeredVersion : ', no newer release',
);
