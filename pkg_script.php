<?php

declare(strict_types=1);

\defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Installer\InstallerAdapter;
use Joomla\CMS\Installer\InstallerScriptInterface;
use Joomla\Database\DatabaseInterface;

/**
 * Instalační skript BALÍČKU pkg_joomleague. Postflight balíčku běží až po
 * instalaci všech dětských rozšíření (komponenta, moduly, pluginy), takže se
 * v něm dají nově nainstalované pluginy rovnou zapnout.
 */
return new class () implements InstallerScriptInterface {
	private const UPDATE_SITE_NAME = 'JoomLeague';
	private const UPDATE_SITE_LOCATION = 'https://github.com/klucon/com_joomleague/releases/latest/download/joomleague-update.xml';

	/** Vlastní pluginy balíčku, které se po instalaci automaticky zapnou. */
	private const PLUGINS = [
		['folder' => 'content', 'element' => 'joomleaguematch'],
		['folder' => 'content', 'element' => 'joomleagueperson'],
		['folder' => 'extension', 'element' => 'joomleagueesport'],
		['folder' => 'finder', 'element' => 'joomleague'],
		['folder' => 'quickicon', 'element' => 'joomleague'],
	];

	public function install(InstallerAdapter $adapter): bool
	{
		return true;
	}

	public function update(InstallerAdapter $adapter): bool
	{
		return true;
	}

	public function uninstall(InstallerAdapter $adapter): bool
	{
		return true;
	}

	public function preflight(string $type, InstallerAdapter $adapter): bool
	{
		return true;
	}

	public function postflight(string $type, InstallerAdapter $adapter): bool
	{
		$this->ensureUpdateSite();

		// Jen při první instalaci – při update respektujeme volbu uživatele.
		if ($type === 'install') {
			$this->enablePlugins();
		}

		return true;
	}

	private function ensureUpdateSite(): void
	{
		try {
			$db = Factory::getContainer()->get(DatabaseInterface::class);
		} catch (\Throwable $exception) {
			return;
		}

		try {
			$query = $db->createQuery()
				->select($db->quoteName('extension_id'))
				->from($db->quoteName('#__extensions'))
				->where($db->quoteName('type') . ' = ' . $db->quote('package'))
				->where($db->quoteName('element') . ' = ' . $db->quote('pkg_joomleague'));

			$extensionId = (int) $db->setQuery($query)->loadResult();

			if ($extensionId < 1) {
				return;
			}

			$query = $db->createQuery()
				->select($db->quoteName('update_site_id'))
				->from($db->quoteName('#__update_sites'))
				->where($db->quoteName('location') . ' = ' . $db->quote(self::UPDATE_SITE_LOCATION));

			$updateSiteId = (int) $db->setQuery($query)->loadResult();

			if ($updateSiteId < 1) {
				$site = (object) [
					'name' => self::UPDATE_SITE_NAME,
					'type' => 'extension',
					'location' => self::UPDATE_SITE_LOCATION,
					'enabled' => 1,
					'last_check_timestamp' => 0,
				];

				$db->insertObject('#__update_sites', $site);
				$updateSiteId = (int) $db->insertid();
			} else {
				$query = $db->createQuery()
					->update($db->quoteName('#__update_sites'))
					->set($db->quoteName('name') . ' = ' . $db->quote(self::UPDATE_SITE_NAME))
					->set($db->quoteName('type') . ' = ' . $db->quote('extension'))
					->set($db->quoteName('enabled') . ' = 1')
					->where($db->quoteName('update_site_id') . ' = ' . (int) $updateSiteId);

				$db->setQuery($query)->execute();
			}

			$query = $db->createQuery()
				->select('COUNT(*)')
				->from($db->quoteName('#__update_sites_extensions'))
				->where($db->quoteName('update_site_id') . ' = ' . (int) $updateSiteId)
				->where($db->quoteName('extension_id') . ' = ' . (int) $extensionId);

			if ((int) $db->setQuery($query)->loadResult() < 1) {
				$link = (object) [
					'update_site_id' => $updateSiteId,
					'extension_id' => $extensionId,
				];

				$db->insertObject('#__update_sites_extensions', $link);
			}
		} catch (\Throwable $exception) {
			// Update site registration must not break package installation.
		}
	}

	private function enablePlugins(): void
	{
		try {
			$db = Factory::getContainer()->get(DatabaseInterface::class);

			foreach (self::PLUGINS as $plugin) {
				$folder  = $plugin['folder'];
				$element = $plugin['element'];

				$query = $db->createQuery()
					->update($db->quoteName('#__extensions'))
					->set($db->quoteName('enabled') . ' = 1')
					->where($db->quoteName('type') . ' = ' . $db->quote('plugin'))
					->where($db->quoteName('folder') . ' = :folder')
					->where($db->quoteName('element') . ' = :element')
					->bind(':folder', $folder)
					->bind(':element', $element);

				$db->setQuery($query)->execute();
			}
		} catch (\Throwable $exception) {
			// Auto-zapnutí je nice-to-have; případná chyba nesmí shodit instalaci.
		}
	}
};
