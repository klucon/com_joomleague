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
		// Jen při první instalaci – při update respektujeme volbu uživatele.
		if ($type === 'install') {
			$this->enablePlugins();
		}

		return true;
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
