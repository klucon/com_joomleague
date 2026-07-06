<?php

declare(strict_types=1);

\defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Installer\InstallerAdapter;
use Joomla\CMS\Installer\InstallerScriptInterface;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Session\Session;
use Joomla\CMS\Uri\Uri;
use Joomla\Database\DatabaseInterface;
use Joomleague\Component\Joomleague\Administrator\Helper\TelemetryHelper;

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

		$this->renderTelemetryConsent();

		return true;
	}

	/**
	 * Zobrazí HNED po instalaci výzvu k anonymní telemetrii se třemi tlačítky.
	 * Uživatel vidí konkrétní data, která se odešlou. Vše je opt-in.
	 */
	private function renderTelemetryConsent(): void
	{
		try {
			$helper = JPATH_ADMINISTRATOR . '/components/com_joomleague/src/Helper/TelemetryHelper.php';

			if (!is_file($helper)) {
				return;
			}

			require_once $helper;

			// Pokud už uživatel rozhodl (update/reinstalace), výzvu neopakujeme.
			if (TelemetryHelper::getConsent() !== '') {
				return;
			}

			$data = TelemetryHelper::collect();

			Factory::getApplication()->getLanguage()->load('com_joomleague', JPATH_ADMINISTRATOR);

			$token     = Session::getFormToken();
			$adminBase = rtrim(Uri::base(), '/');
			$ajax      = $adminBase . '/index.php?option=com_joomleague&task=ajax.telemetryconsent&format=json';
			$component = $adminBase . '/index.php?option=com_joomleague';

			$rows = [
				Text::_('COM_JOOMLEAGUE_TELEMETRY_FIELD_JL')     => $data['jl_version'],
				Text::_('COM_JOOMLEAGUE_TELEMETRY_FIELD_JOOMLA') => $data['joomla_version'],
				Text::_('COM_JOOMLEAGUE_TELEMETRY_FIELD_PHP')    => $data['php_version'],
				Text::_('COM_JOOMLEAGUE_TELEMETRY_FIELD_LANG')   => $data['language'],
				Text::_('COM_JOOMLEAGUE_TELEMETRY_FIELD_ID')     => $data['install_id'],
			];

			$tableRows = '';

			foreach ($rows as $label => $value) {
				$tableRows .= '<tr><th style="text-align:left;padding:.4rem .9rem;white-space:nowrap;color:#334155;font-weight:600;background:#eef2f7;border-bottom:1px solid #e2e8f0;">'
					. htmlspecialchars((string) $label, ENT_QUOTES, 'UTF-8')
					. '</th><td style="padding:.4rem .9rem;font-family:monospace;color:#0f172a;background:#ffffff;border-bottom:1px solid #e2e8f0;">'
					. htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8')
					. '</td></tr>';
			}

			// Samostatná SVĚTLÁ karta s explicitními barvami — čitelná i v tmavém adminu.
			echo '<div id="jl-telemetry" style="max-width:720px;margin:1.2rem 0;background:#ffffff;color:#0f172a;border:1px solid #cbd5e1;border-radius:12px;overflow:hidden;box-shadow:0 6px 22px rgba(15,23,42,.18);">'
				. '<div style="background:linear-gradient(120deg,#10b981,#0891b2);color:#ffffff;padding:.9rem 1.1rem;font-weight:700;font-size:1.05rem;">'
				. htmlspecialchars(Text::_('COM_JOOMLEAGUE_TELEMETRY_TITLE'), ENT_QUOTES, 'UTF-8') . '</div>'
				. '<div style="padding:1.1rem;background:#ffffff;color:#0f172a;">'
				. '<p style="margin:.1rem 0 .9rem;color:#1f2937;font-size:.95rem;">' . htmlspecialchars(Text::_('COM_JOOMLEAGUE_TELEMETRY_INTRO'), ENT_QUOTES, 'UTF-8') . '</p>'
				. '<table style="border-collapse:collapse;width:100%;max-width:560px;margin:.2rem 0 .9rem;border:1px solid #e2e8f0;border-radius:8px;overflow:hidden;">' . $tableRows . '</table>'
				. '<p style="margin:.2rem 0 1rem;color:#b91c1c;font-weight:600;font-size:.92rem;">' . htmlspecialchars(Text::_('COM_JOOMLEAGUE_TELEMETRY_NOPERSONAL'), ENT_QUOTES, 'UTF-8') . '</p>'
				. '<div id="jl-telemetry-actions" style="display:flex;flex-wrap:wrap;gap:.5rem;">'
				. '<button type="button" class="btn btn-success" style="color:#fff;" onclick="jlTelemetry(\'once\')">' . htmlspecialchars(Text::_('COM_JOOMLEAGUE_TELEMETRY_BTN_ONCE'), ENT_QUOTES, 'UTF-8') . '</button>'
				. '<button type="button" class="btn btn-primary" style="color:#fff;" onclick="jlTelemetry(\'monthly\')">' . htmlspecialchars(Text::_('COM_JOOMLEAGUE_TELEMETRY_BTN_MONTHLY'), ENT_QUOTES, 'UTF-8') . '</button>'
				. '<button type="button" class="btn btn-outline-danger" onclick="jlTelemetry(\'never\')">' . htmlspecialchars(Text::_('COM_JOOMLEAGUE_TELEMETRY_BTN_NEVER'), ENT_QUOTES, 'UTF-8') . '</button>'
				. '</div>'
				. '<div id="jl-telemetry-done" style="display:none;margin-top:.6rem;font-weight:600;color:#0f7a4d;"></div>'
				. '<p style="margin:.9rem 0 0;font-size:.85rem;color:#64748b;">'
				. htmlspecialchars(Text::_('COM_JOOMLEAGUE_TELEMETRY_PUBLIC'), ENT_QUOTES, 'UTF-8')
				. ' <a href="https://stats.klucon.cz" target="_blank" rel="noopener" style="color:#0284c7;">stats.klucon.cz</a></p>'
				. '<div style="margin-top:1.1rem;padding-top:1rem;border-top:1px solid #e2e8f0;">'
				. '<a class="btn btn-primary" style="color:#fff;text-decoration:none;" href="' . htmlspecialchars($component, ENT_QUOTES, 'UTF-8') . '">'
				. htmlspecialchars(Text::_('COM_JOOMLEAGUE_TELEMETRY_CONTINUE'), ENT_QUOTES, 'UTF-8') . ' &rarr;</a>'
				. '</div>'
				. '</div></div>'
				. '<script>function jlTelemetry(mode){'
				. 'var u=' . json_encode($ajax) . '+"&mode="+mode+"&' . $token . '=1";'
				. 'var show=function(msg,ok){'
				. 'document.getElementById("jl-telemetry-actions").style.display="none";'
				. 'var d=document.getElementById("jl-telemetry-done");'
				. 'd.textContent=msg;d.style.color=ok?"#0f7a4d":"#b45309";d.style.display="block";};'
				. 'if(mode==="never"){fetch(u,{method:"POST",credentials:"same-origin"}).catch(function(){});'
				. 'show(' . json_encode(Text::_('COM_JOOMLEAGUE_TELEMETRY_DECLINED')) . ',true);return;}'
				. 'fetch(u,{method:"POST",credentials:"same-origin"}).then(function(r){return r.json();}).then(function(j){'
				. 'show((j&&j.sent)?' . json_encode(Text::_('COM_JOOMLEAGUE_TELEMETRY_THANKS')) . ':' . json_encode(Text::_('COM_JOOMLEAGUE_TELEMETRY_UNREACHABLE')) . ',!!(j&&j.sent));'
				. '}).catch(function(){show(' . json_encode(Text::_('COM_JOOMLEAGUE_TELEMETRY_UNREACHABLE')) . ',false);});}</script>';
		} catch (\Throwable $exception) {
			// Výzva je nadstavba; případná chyba nesmí ovlivnit instalaci.
		}
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
