<?php
/**
 * @package     JoomLeague
 * @copyright   Copyright (C) 2026 Ondřej Klučka (https://klucon.cz). All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Administrator\Model;

\defined('_JEXEC') or die;

use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomleague\Component\Joomleague\Administrator\Helper\LanguageStatusHelper;

final class LanguagesModel extends BaseDatabaseModel
{
	private const LANGUAGE_PACKAGE_BASE = 'https://update.klucon.cz/joomleague/languages';
	private const LANGUAGE_PACKAGE_MANIFEST = 'https://update.klucon.cz/joomleague/languages/manifest.json';

	public function getLanguages(): array
	{
		$languages = LanguageStatusHelper::getLanguages();
		$packages = $this->getPackageManifest()['languages'] ?? [];

		foreach ($languages as $tag => &$language) {
			$package = is_array($packages) && isset($packages[$tag]) && is_array($packages[$tag]) ? $packages[$tag] : [];
			$language['package_available'] = $package !== [];
			$language['package_version'] = (string) ($package['version'] ?? '');
			$language['package_updated'] = (string) ($package['updated'] ?? '');
			$language['package_size'] = (int) ($package['size'] ?? 0);
			$language['package_sha256'] = (string) ($package['sha256'] ?? '');
			$language['package_url'] = (string) ($package['url'] ?? '');
		}
		unset($language);

		return $languages;
	}

	public function getSummary(): array
	{
		return LanguageStatusHelper::getSummary();
	}

	public function removeLanguage(string $tag): int
	{
		if ($tag === 'en-GB' || !in_array($tag, LanguageStatusHelper::getAvailableLanguageTags(), true)) {
			return 0;
		}

		$removed = 0;

		foreach (LanguageStatusHelper::getInstalledLanguageFiles($tag) as $file) {
			if (!is_file($file)) {
				continue;
			}

			if (@unlink($file)) {
				$removed++;
			}
		}

		return $removed;
	}

	public function downloadLanguage(string $tag): array
	{
		if ($tag === 'en-GB' || !in_array($tag, LanguageStatusHelper::getAvailableLanguageTags(), true)) {
			return ['written' => 0, 'failed' => 0];
		}

		if (!class_exists('\\ZipArchive')) {
			return ['written' => 0, 'failed' => 1];
		}

		$package = $this->downloadPackage($tag);

		if ($package === null) {
			return ['written' => 0, 'failed' => 1];
		}

		$result = $this->installPackage($package, $tag);
		@unlink($package);

		return $result;
	}

	private function downloadPackage(string $tag): ?string
	{
		$url = self::LANGUAGE_PACKAGE_BASE . '/joomleague-language-' . rawurlencode($tag) . '.zip';
		$context = stream_context_create([
			'http' => [
				'timeout' => 30,
				'ignore_errors' => false,
				'header' => "Accept: application/zip, application/octet-stream\r\n",
			],
		]);
		$content = @file_get_contents($url, false, $context);

		if (!is_string($content) || $content === '') {
			return null;
		}

		$tmpDirectory = JPATH_ROOT . '/tmp';

		if (!is_dir($tmpDirectory) || !is_writable($tmpDirectory)) {
			$tmpDirectory = sys_get_temp_dir();
		}

		$tmpFile = tempnam($tmpDirectory, 'jl_lang_');

		if ($tmpFile === false) {
			return null;
		}

		if (file_put_contents($tmpFile, $content) === false) {
			@unlink($tmpFile);

			return null;
		}

		return $tmpFile;
	}

	private function getPackageManifest(): array
	{
		static $manifest = null;

		if (is_array($manifest)) {
			return $manifest;
		}

		$context = stream_context_create([
			'http' => [
				'timeout' => 10,
				'ignore_errors' => false,
				'header' => "Accept: application/json\r\n",
			],
		]);
		$content = @file_get_contents(self::LANGUAGE_PACKAGE_MANIFEST, false, $context);

		if (!is_string($content) || $content === '') {
			$manifest = [];

			return $manifest;
		}

		$data = json_decode($content, true);
		$manifest = is_array($data) ? $data : [];

		return $manifest;
	}

	private function installPackage(string $package, string $tag): array
	{
		$zip = new \ZipArchive();

		if ($zip->open($package) !== true) {
			return ['written' => 0, 'failed' => 1];
		}

		$written = 0;
		$failed = 0;

		for ($i = 0; $i < $zip->numFiles; $i++) {
			$name = (string) $zip->getNameIndex($i);
			$target = $this->targetPathFromPackageEntry($name, $tag);

			if ($target === null) {
				$failed++;
				continue;
			}

			$content = $zip->getFromIndex($i);

			if (!is_string($content)) {
				$failed++;
				continue;
			}

			$directory = dirname($target);

			if (!is_dir($directory) && !mkdir($directory, 0755, true) && !is_dir($directory)) {
				$failed++;
				continue;
			}

			if (file_put_contents($target, $content) === false) {
				$failed++;
				continue;
			}

			$written++;
		}

		$zip->close();

		return ['written' => $written, 'failed' => $failed];
	}

	private function targetPathFromPackageEntry(string $entry, string $tag): ?string
	{
		$entry = str_replace('\\', '/', $entry);

		if ($entry === '' || str_contains($entry, '..') || str_starts_with($entry, '/') || str_ends_with($entry, '/')) {
			return null;
		}

		$baseName = basename($entry);

		if (!str_ends_with($baseName, '.ini') || !str_contains($baseName, 'joomleague')) {
			return null;
		}

		if ($entry === 'administrator/language/' . $tag . '/' . $baseName) {
			return JPATH_ADMINISTRATOR . '/language/' . $tag . '/' . $baseName;
		}

		if ($entry === 'language/' . $tag . '/' . $baseName) {
			return JPATH_ROOT . '/language/' . $tag . '/' . $baseName;
		}

		return null;
	}
}
