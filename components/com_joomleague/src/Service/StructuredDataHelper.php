<?php
/**
 * @package     JoomLeague
 * @copyright   Copyright (C) 2026 Ondřej Klučka (https://klucon.cz). All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */


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

	public static function externalUrl(?string $url): ?string
	{
		$url = trim((string) $url);

		if ($url === '') {
			return null;
		}

		return preg_match('#^https?://#i', $url) ? $url : 'https://' . ltrim($url, '/');
	}

	public static function imageUrl(?string $path): ?string
	{
		$path = trim((string) $path);

		if ($path === '') {
			return null;
		}

		$url = self::absoluteUrl($path);
		$urlPath = $url !== null ? (string) parse_url($url, PHP_URL_PATH) : '';
		$extension = strtolower(pathinfo($urlPath, PATHINFO_EXTENSION));

		return in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'webp'], true) ? $url : null;
	}

	public static function currentUrl(): string
	{
		return Uri::getInstance()->toString(['scheme', 'host', 'port', 'path']);
	}

	public static function webPage(string $name, ?string $description = null, ?string $url = null): array
	{
		$url = self::absoluteUrl($url ?: self::currentUrl());

		return [
			'@type' => 'WebPage',
			'@id' => $url ? $url . '#webpage' : null,
			'name' => $name,
			'description' => $description,
			'url' => $url,
		];
	}

	public static function collectionPage(string $name, array $items = [], ?string $description = null, ?string $url = null): array
	{
		$page = self::webPage($name, $description, $url);
		$page['@type'] = 'CollectionPage';

		if ($items !== []) {
			$page['mainEntity'] = self::itemList($items, $name);
		}

		return $page;
	}

	public static function itemList(array $items, ?string $name = null): array
	{
		return [
			'@type' => 'ItemList',
			'name' => $name,
			'numberOfItems' => count($items),
			'itemListElement' => array_map(
				static fn (array $item, int $index): array => [
					'@type' => 'ListItem',
					'position' => $index + 1,
					'item' => $item,
				],
				array_values($items),
				array_keys(array_values($items))
			),
		];
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
