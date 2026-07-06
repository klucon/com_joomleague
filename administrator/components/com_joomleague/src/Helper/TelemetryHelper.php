<?php

declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Administrator\Helper;

\defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Http\HttpFactory;
use Joomla\Database\DatabaseInterface;
use Joomla\Registry\Registry;

/**
 * Anonymní, opt-in telemetrie JoomLeague.
 *
 * Sbírá VÝHRADNĚ: verzi JoomLeague, verzi Joomly, verzi PHP, jazyk webu
 * a náhodné install-ID (bez osobních dat). NIKDY: doménu, URL, IP, název webu.
 * Data se odesílají pouze po výslovném souhlasu uživatele na veřejný,
 * transparentní sběrný bod https://stats.klucon.cz/collect.
 */
final class TelemetryHelper
{
	public const ENDPOINT = 'https://stats.klucon.cz/collect';

	private const HEARTBEAT_DAYS = 30;

	/**
	 * Sesbírá data, která se (po souhlasu) odešlou. Konkrétní hodnoty jsou tak
	 * zobrazitelné uživateli PŘED odesláním.
	 */
	public static function collect(): array
	{
		return [
			'install_id'     => self::getInstallId(),
			'jl_version'     => self::version(),
			'joomla_version' => JVERSION,
			'php_version'    => PHP_VERSION,
			'language'       => (string) Factory::getApplication()->get('language', 'en-GB'),
		];
	}

	/** Aktuální rozhodnutí o souhlasu: '' (nerozhodnuto) | once | monthly | never. */
	public static function getConsent(): string
	{
		[, $params] = self::loadParams();

		return (string) $params->get('telemetry_consent', '');
	}

	/** Uloží rozhodnutí o souhlasu do parametrů komponenty. */
	public static function setConsent(string $mode): void
	{
		if (!\in_array($mode, ['once', 'monthly', 'never'], true)) {
			return;
		}

		[$id, $params] = self::loadParams();

		if ((string) $params->get('telemetry_install_id', '') === '') {
			$params->set('telemetry_install_id', self::uuid4());
		}

		$params->set('telemetry_consent', $mode);
		self::saveParams($id, $params);
	}

	/** Náhodné install-ID (vygeneruje se jednou a uloží). Neobsahuje osobní data. */
	public static function getInstallId(): string
	{
		[$id, $params] = self::loadParams();
		$uuid = (string) $params->get('telemetry_install_id', '');

		if ($uuid === '') {
			$uuid = self::uuid4();
			$params->set('telemetry_install_id', $uuid);
			self::saveParams($id, $params);
		}

		return $uuid;
	}

	/** Odešle jeden anonymní ping (jen když je souhlas once/monthly). Best-effort. */
	public static function send(string $event = 'install'): bool
	{
		$mode = self::getConsent();

		if ($mode !== 'once' && $mode !== 'monthly') {
			return false;
		}

		$payload          = self::collect();
		$payload['event'] = \in_array($event, ['install', 'update', 'heartbeat'], true) ? $event : 'install';
		$payload['mode']  = $mode;

		try {
			$http     = HttpFactory::getHttp([], ['curl', 'stream']);
			$response = $http->post(self::ENDPOINT, json_encode($payload), ['Content-Type' => 'application/json'], 6);

			[$id, $params] = self::loadParams();
			$params->set('telemetry_last_sent', gmdate('Y-m-d'));
			self::saveParams($id, $params);

			$code = (int) $response->getStatusCode();

			return $code >= 200 && $code < 300;
		} catch (\Throwable $exception) {
			// Telemetrie nesmí nikdy nic shodit.
			return false;
		}
	}

	/** Měsíční heartbeat — volá se z quickicon pluginu při načtení nástěnky. */
	public static function maybeHeartbeat(): void
	{
		try {
			if (self::getConsent() !== 'monthly') {
				return;
			}

			[, $params] = self::loadParams();
			$last = (string) $params->get('telemetry_last_sent', '');

			if ($last !== '' && (time() - (int) strtotime($last)) < self::HEARTBEAT_DAYS * 86400) {
				return;
			}

			self::send('heartbeat');
		} catch (\Throwable $exception) {
			// no-op
		}
	}

	/** Verze balíčku (fallback komponenta) z manifest_cache. */
	public static function version(): string
	{
		try {
			$db = Factory::getContainer()->get(DatabaseInterface::class);

			foreach ([['pkg_joomleague', 'package'], ['com_joomleague', 'component']] as [$element, $type]) {
				$cache = (string) $db->setQuery(
					$db->createQuery()
						->select($db->quoteName('manifest_cache'))
						->from($db->quoteName('#__extensions'))
						->where($db->quoteName('element') . ' = ' . $db->quote($element))
						->where($db->quoteName('type') . ' = ' . $db->quote($type))
				)->loadResult();

				if ($cache !== '') {
					$data = json_decode($cache, true);

					if (!empty($data['version'])) {
						return (string) $data['version'];
					}
				}
			}
		} catch (\Throwable $exception) {
			// ignore
		}

		return '';
	}

	/** @return array{0:int,1:Registry} extension_id komponenty + její parametry */
	private static function loadParams(): array
	{
		$db = Factory::getContainer()->get(DatabaseInterface::class);

		$id = (int) $db->setQuery(
			$db->createQuery()
				->select($db->quoteName('extension_id'))
				->from($db->quoteName('#__extensions'))
				->where($db->quoteName('element') . ' = ' . $db->quote('com_joomleague'))
				->where($db->quoteName('type') . ' = ' . $db->quote('component'))
		)->loadResult();

		$json = (string) $db->setQuery(
			$db->createQuery()
				->select($db->quoteName('params'))
				->from($db->quoteName('#__extensions'))
				->where($db->quoteName('extension_id') . ' = ' . $id)
		)->loadResult();

		return [$id, new Registry($json !== '' ? $json : '{}')];
	}

	private static function saveParams(int $id, Registry $params): void
	{
		if ($id < 1) {
			return;
		}

		$db   = Factory::getContainer()->get(DatabaseInterface::class);
		$json = $params->toString();

		$db->setQuery(
			$db->createQuery()
				->update($db->quoteName('#__extensions'))
				->set($db->quoteName('params') . ' = :params')
				->where($db->quoteName('extension_id') . ' = ' . $id)
				->bind(':params', $json)
		)->execute();
	}

	private static function uuid4(): string
	{
		$bytes    = random_bytes(16);
		$bytes[6] = \chr((\ord($bytes[6]) & 0x0f) | 0x40);
		$bytes[8] = \chr((\ord($bytes[8]) & 0x3f) | 0x80);

		return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($bytes), 4));
	}
}
