<?php

declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Administrator\Service;

defined('_JEXEC') or die;

use Joomla\CMS\Application\CMSApplicationInterface;
use Joomla\CMS\Http\HttpFactory;
use Joomla\Database\DatabaseInterface;
use Joomla\Registry\Registry;

final class TelemetryService
{
	public const ENDPOINT = 'https://stats.klucon.cz/collect';

	private const HEARTBEAT_SECONDS = 30 * 86400;

	public function __construct(
		private readonly DatabaseInterface $database,
		private readonly CMSApplicationInterface $application,
	) {
	}

	/** @return array<string, string> */
	public function collect(): array
	{
		return [
			'install_id' => $this->installId(),
			'jl_version' => $this->componentVersion(),
			'joomla_version' => JVERSION,
			'php_version' => PHP_VERSION,
			'language' => (string) $this->application->get('language', 'en-GB'),
		];
	}

	public function maybeSend(): bool
	{
		try {
			[$extensionId, $params] = $this->loadParams();
			$consent = (string) $params->get('telemetry_consent', 'never');
			$lastSent = (string) $params->get('telemetry_last_sent', '');

			if ($consent === 'once' && $lastSent === '') {
				return $this->send('install', $extensionId, $params);
			}

			if ($consent !== 'monthly') {
				return false;
			}

			if ($lastSent !== '' && time() - (int) strtotime($lastSent) < self::HEARTBEAT_SECONDS) {
				return false;
			}

			return $this->send('heartbeat', $extensionId, $params);
		} catch (\Throwable) {
			return false;
		}
	}

	private function send(string $event, int $extensionId, Registry $params): bool
	{
		$payload = $this->collect();
		$payload['event'] = $event;
		$payload['mode'] = (string) $params->get('telemetry_consent', 'never');

		try {
			$response = HttpFactory::getHttp([], ['curl', 'stream'])->post(
				self::ENDPOINT,
				json_encode($payload, JSON_THROW_ON_ERROR),
				['Content-Type' => 'application/json', 'Accept' => 'application/json'],
				3,
			);

			$status = (int) $response->getStatusCode();

			if ($status < 200 || $status >= 300) {
				return false;
			}

			$params->set('telemetry_last_sent', gmdate('c'));
			$this->saveParams($extensionId, $params);

			return true;
		} catch (\Throwable) {
			return false;
		}
	}

	private function installId(): string
	{
		[$extensionId, $params] = $this->loadParams();
		$installId = (string) $params->get('telemetry_install_id', '');

		if ($installId !== '') {
			return $installId;
		}

		$bytes = random_bytes(16);
		$bytes[6] = chr((ord($bytes[6]) & 0x0f) | 0x40);
		$bytes[8] = chr((ord($bytes[8]) & 0x3f) | 0x80);
		$installId = vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($bytes), 4));
		$params->set('telemetry_install_id', $installId);
		$this->saveParams($extensionId, $params);

		return $installId;
	}

	private function componentVersion(): string
	{
		$query = $this->database->createQuery()
			->select($this->database->quoteName('manifest_cache'))
			->from($this->database->quoteName('#__extensions'))
			->where($this->database->quoteName('type') . ' = ' . $this->database->quote('component'))
			->where($this->database->quoteName('element') . ' = ' . $this->database->quote('com_joomleague'));
		$data = json_decode((string) $this->database->setQuery($query)->loadResult(), true);

		return is_array($data) ? (string) ($data['version'] ?? '') : '';
	}

	/** @return array{0: int, 1: Registry} */
	private function loadParams(): array
	{
		$query = $this->database->createQuery()
			->select($this->database->quoteName(['extension_id', 'params']))
			->from($this->database->quoteName('#__extensions'))
			->where($this->database->quoteName('type') . ' = ' . $this->database->quote('component'))
			->where($this->database->quoteName('element') . ' = ' . $this->database->quote('com_joomleague'));
		$row = $this->database->setQuery($query)->loadObject();

		if ($row === null) {
			throw new \RuntimeException('JoomLeague component is not installed.');
		}

		return [(int) $row->extension_id, new Registry((string) $row->params)];
	}

	private function saveParams(int $extensionId, Registry $params): void
	{
		$json = $params->toString();
		$query = $this->database->createQuery()
			->update($this->database->quoteName('#__extensions'))
			->set($this->database->quoteName('params') . ' = :params')
			->where($this->database->quoteName('extension_id') . ' = :extensionId')
			->bind(':params', $json)
			->bind(':extensionId', $extensionId);
		$this->database->setQuery($query)->execute();
	}
}
