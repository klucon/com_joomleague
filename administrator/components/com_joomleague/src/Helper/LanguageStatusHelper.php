<?php
/**
 * @package     JoomLeague
 * @copyright   Copyright (C) 2026 Ondřej Klučka (https://klucon.cz). All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Administrator\Helper;

\defined('_JEXEC') or die;

final class LanguageStatusHelper
{
	private const SOURCE_LANGUAGE = 'en-GB';

	private const AVAILABLE_LANGUAGES = [
		'af-ZA' => 'Afrikaans',
		'be-BY' => 'Belarusian',
		'bg-BG' => 'Bulgarian',
		'ca-ES' => 'Catalan',
		'zh-CN' => 'Chinese, Simplified',
		'zh-TW' => 'Chinese, Traditional',
		'hr-HR' => 'Croatian',
		'cs-CZ' => 'Czech',
		'da-DK' => 'Danish',
		'nl-NL' => 'Dutch',
		'en-AU' => 'English, Australia',
		'en-CA' => 'English, Canada',
		'en-GB' => 'English, United Kingdom',
		'en-NZ' => 'English, New Zealand',
		'en-US' => 'English, USA',
		'et-EE' => 'Estonian',
		'fi-FI' => 'Finnish',
		'nl-BE' => 'Flemish',
		'fr-FR' => 'French',
		'fr-CA' => 'French, Canada',
		'ka-GE' => 'Georgian',
		'de-DE' => 'German',
		'de-AT' => 'German, Austria',
		'de-LI' => 'German, Liechtenstein',
		'de-LU' => 'German, Luxembourg',
		'de-CH' => 'German, Switzerland',
		'el-GR' => 'Greek',
		'hu-HU' => 'Hungarian',
		'it-IT' => 'Italian',
		'ja-JP' => 'Japanese',
		'lo-LA' => 'Laotian',
		'lv-LV' => 'Latvian',
		'lt-LT' => 'Lithuanian',
		'ms-MY' => 'Malay',
		'nb-NO' => 'Norwegian Bokmal',
		'fa-IR' => 'Persian Farsi',
		'pl-PL' => 'Polish',
		'pt-BR' => 'Portuguese, Brazil',
		'pt-PT' => 'Portuguese, Portugal',
		'ro-RO' => 'Romanian',
		'ru-RU' => 'Russian',
		'sr-RS' => 'Serbian, Cyrillic',
		'sr-YU' => 'Serbian, Latin',
		'sk-SK' => 'Slovak',
		'sl-SI' => 'Slovenian',
		'es-ES' => 'Spanish',
		'sv-SE' => 'Swedish',
		'ta-IN' => 'Tamil, India',
		'th-TH' => 'Thai',
		'tr-TR' => 'Turkish',
		'uk-UA' => 'Ukrainian',
		'cy-GB' => 'Welsh',
	];

	public static function getSummary(): array
	{
		$languages = self::getLanguages();
		$installed = array_filter($languages, static fn (array $language): bool => (bool) $language['installed']);
		$updates = array_filter(
			$languages,
			static fn (array $language): bool => $language['tag'] !== self::SOURCE_LANGUAGE
				&& (bool) $language['installed']
				&& ((int) $language['missing'] > 0 || (int) $language['obsolete'] > 0)
		);

		return [
			'source' => self::SOURCE_LANGUAGE,
			'source_total' => $languages[self::SOURCE_LANGUAGE]['source_total'] ?? 0,
			'available' => count($languages),
			'installed' => count($installed),
			'updates' => count($updates),
			'best_percent' => self::getBestTranslationPercent($languages),
		];
	}

	public static function getLanguages(): array
	{
		$sourceFiles = self::discoverSourceFiles();
		$sourceKeys = self::readSourceKeys($sourceFiles);
		$sourceTotal = array_sum(array_map('count', $sourceKeys));
		$languages = [];

		foreach (self::AVAILABLE_LANGUAGES as $tag => $name) {
			$translated = 0;
			$missing = 0;
			$obsolete = 0;
			$filesPresent = 0;

			foreach ($sourceKeys as $sourceFile => $keys) {
				$targetFile = str_replace('/' . self::SOURCE_LANGUAGE . '/', '/' . $tag . '/', $sourceFile);
				$targetKeys = is_file($targetFile) ? self::readLanguageFile($targetFile) : [];

				if ($targetKeys !== []) {
					$filesPresent++;
				}

				foreach ($keys as $key => $sourceValue) {
					if (array_key_exists($key, $targetKeys) && trim((string) $targetKeys[$key]) !== '') {
						$translated++;
					} else {
						$missing++;
					}
				}

				foreach ($targetKeys as $key => $targetValue) {
					if (!array_key_exists($key, $keys)) {
						$obsolete++;
					}
				}
			}

			$languages[$tag] = [
				'tag' => $tag,
				'name' => $name,
				'source' => $tag === self::SOURCE_LANGUAGE,
				'installed' => $tag === self::SOURCE_LANGUAGE || $filesPresent > 0,
				'files_present' => $filesPresent,
				'files_total' => count($sourceFiles),
				'source_total' => $sourceTotal,
				'translated' => $tag === self::SOURCE_LANGUAGE ? $sourceTotal : $translated,
				'missing' => $tag === self::SOURCE_LANGUAGE ? 0 : $missing,
				'obsolete' => $tag === self::SOURCE_LANGUAGE ? 0 : $obsolete,
				'percent' => $sourceTotal > 0 ? (int) round((($tag === self::SOURCE_LANGUAGE ? $sourceTotal : $translated) / $sourceTotal) * 100) : 0,
			];
		}

		return $languages;
	}

	public static function getAvailableLanguageTags(): array
	{
		return array_keys(self::AVAILABLE_LANGUAGES);
	}

	public static function getInstalledLanguageFiles(string $tag): array
	{
		if (!in_array($tag, self::getAvailableLanguageTags(), true)) {
			return [];
		}

		$files = [];

		foreach (self::discoverSourceFiles() as $sourceFile) {
			$targetFile = str_replace('/' . self::SOURCE_LANGUAGE . '/', '/' . $tag . '/', $sourceFile);

			if (is_file($targetFile)) {
				$files[$targetFile] = $targetFile;
			}
		}

		ksort($files, SORT_NATURAL);

		return array_values($files);
	}

	private static function getBestTranslationPercent(array $languages): int
	{
		$best = 0;

		foreach ($languages as $language) {
			if (($language['tag'] ?? '') === self::SOURCE_LANGUAGE || !($language['installed'] ?? false)) {
				continue;
			}

			$best = max($best, (int) ($language['percent'] ?? 0));
		}

		return $best;
	}

	private static function discoverSourceFiles(): array
	{
		$patterns = [
			JPATH_ROOT . '/language/' . self::SOURCE_LANGUAGE . '/joomleague*.ini',
			JPATH_ROOT . '/language/' . self::SOURCE_LANGUAGE . '/pkg_joomleague*.ini',
			JPATH_ROOT . '/language/' . self::SOURCE_LANGUAGE . '/com_joomleague*.ini',
			JPATH_ROOT . '/language/' . self::SOURCE_LANGUAGE . '/mod_joomleague*.ini',
			JPATH_ADMINISTRATOR . '/language/' . self::SOURCE_LANGUAGE . '/com_joomleague*.ini',
			JPATH_ADMINISTRATOR . '/language/' . self::SOURCE_LANGUAGE . '/plg_*joomleague*.ini',
			JPATH_ADMINISTRATOR . '/components/com_joomleague/language/' . self::SOURCE_LANGUAGE . '/com_joomleague*.ini',
			JPATH_SITE . '/components/com_joomleague/language/' . self::SOURCE_LANGUAGE . '/com_joomleague*.ini',
			JPATH_SITE . '/modules/mod_joomleague_*/language/' . self::SOURCE_LANGUAGE . '/*.ini',
			JPATH_ROOT . '/plugins/*/*joomleague*/language/' . self::SOURCE_LANGUAGE . '/*.ini',
		];
		$files = [];

		foreach ($patterns as $pattern) {
			foreach (glob($pattern) ?: [] as $file) {
				if (is_file($file)) {
					$files[$file] = $file;
				}
			}
		}

		ksort($files, SORT_NATURAL);

		return array_values($files);
	}

	private static function readSourceKeys(array $sourceFiles): array
	{
		$sourceKeys = [];

		foreach ($sourceFiles as $file) {
			$sourceKeys[$file] = self::readLanguageFile($file);
		}

		return $sourceKeys;
	}

	private static function readLanguageFile(string $file): array
	{
		$lines = file($file, FILE_IGNORE_NEW_LINES) ?: [];
		$keys = [];

		foreach ($lines as $line) {
			$line = trim($line);

			if ($line === '' || str_starts_with($line, ';') || str_starts_with($line, '#') || !str_contains($line, '=')) {
				continue;
			}

			[$key, $value] = explode('=', $line, 2);
			$key = trim($key);

			if ($key === '') {
				continue;
			}

			$keys[$key] = trim($value, " \t\n\r\0\x0B\"");
		}

		return $keys;
	}
}
