<?php

declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Site\Service;

\defined('_JEXEC') or die;

use Joomla\CMS\Uri\Uri;

final class StructuredDataHelper
{
	public static function add(object $document, array $data): void
	{
		$data = self::clean($data);

		if ($data === []) {
			return;
		}

		$json = json_encode(
			$data,
			JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT
		);

		if ($json === false) {
			return;
		}

		$document->addScriptDeclaration($json, 'application/ld+json');
	}

	public static function absoluteUrl(?string $path): ?string
	{
		$path = trim((string) $path);

		if ($path === '') {
			return null;
		}

		if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
			return $path;
		}

		return rtrim(Uri::root(), '/') . '/' . ltrim($path, '/');
	}

	public static function currentUrl(): string
	{
		return Uri::getInstance()->toString(['scheme', 'host', 'port', 'path']);
	}

	private static function clean(array $data): array
	{
		foreach ($data as $key => $value) {
			if (is_array($value)) {
				$value = self::clean($value);
			}

			if ($value === null || $value === '' || $value === []) {
				unset($data[$key]);

				continue;
			}

			$data[$key] = $value;
		}

		return $data;
	}
}
