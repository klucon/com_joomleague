<?php

declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Domain\Service;

defined('_JEXEC') or die;

final class MediaPathResolver
{
	public const DEFAULT_BASE = 'images/com_joomleague';
	public const DIRECTORIES = ['', 'clubs', 'teams/logos', 'teams/photos', 'persons', 'venues', 'projects', 'placeholders', 'organizations/logos/history'];

	public function __construct(
		private readonly string $root,
		private readonly string $urlRoot,
		private readonly string $base = self::DEFAULT_BASE,
	) {}

	public static function basePath(string $path): string
	{
		$path = trim($path);
		if ($path === '') return self::DEFAULT_BASE;
		$parts = explode('/', rtrim($path, '/'));
		if (array_shift($parts) !== 'images' || $parts === [] || array_filter($parts, static fn (string $part): bool => !preg_match('~^[\pL\pN_-]+$~uD', $part))) {
			throw new \InvalidArgumentException('Invalid media base path.');
		}
		return rtrim($path, '/');
	}

	/** Create missing directories only. Existing user files are never modified. */
	public static function ensureDirectories(string $root, string $base): void
	{
		$base = self::basePath($base);
		$root = realpath($root);
		if ($root === false) throw new \RuntimeException('Invalid installation root.');
		foreach (self::DIRECTORIES as $directory) {
			$path = $root . '/' . $base . ($directory === '' ? '' : '/' . $directory);
			$ancestor = $path;
			while (!file_exists($ancestor) && !is_link($ancestor)) $ancestor = dirname($ancestor);
			$real = realpath($ancestor);
			if ($real === false || ($real !== $root && !str_starts_with($real, $root . DIRECTORY_SEPARATOR))) {
				throw new \InvalidArgumentException('Media directory escapes the installation root.');
			}
			if (!is_dir($path) && !@mkdir($path, 0755, true) && !is_dir($path)) {
				throw new \RuntimeException('Cannot create media directory: ' . $base . '/' . $directory);
			}
		}
	}

	public function resolve(array $paths, bool $allowPlaceholder = false): string
	{
		foreach ($paths as $value) {
			$path = explode('#joomlaImage://', trim((string) $value), 2)[0];
			if ($path === '' || (!$allowPlaceholder && preg_match('~(?:^|/)placeholders?/|(?:^|/)placeholder[^/]*$~i', $path))) continue;
			if (preg_match('~^https?://~i', $path)) {
				if (filter_var($path, FILTER_VALIDATE_URL) && !preg_match('/[\x00-\x20\x7f]/', $path)
					&& parse_url($path, PHP_URL_USER) === null && parse_url($path, PHP_URL_PASS) === null) return $path;
				continue;
			}
			if (str_starts_with($path, '//')) continue;
			$path = rawurldecode($path);
			if (preg_match('~[\\\\:\x00-\x1f\x7f?#]|(?:^|/)\.{1,2}(?:/|$)~', $path)) continue;
			$urlRoot = rtrim($this->urlRoot, '/');
			if ($urlRoot !== '' && str_starts_with($path, $urlRoot . '/')) $path = substr($path, strlen($urlRoot) + 1);
			$path = ltrim($path, '/');
			if (!preg_match('~\.(?:png|jpe?g|gif|webp|avif|svg|ico|bmp)$~iD', $path)) continue;
			$candidates = [$path];
			$bases = array_unique(['images/com_joomleague/database', $this->base, self::DEFAULT_BASE, 'media/com_joomleague']);
			foreach ($bases as $prefix) {
				if (!str_starts_with($path, $prefix . '/')) continue;
				$suffix = substr($path, strlen($prefix) + 1);
				foreach ($bases as $target) $candidates[] = $target . '/' . $suffix;
				break;
			}
			if (!str_starts_with($path, 'images/') && !str_starts_with($path, 'media/')) $candidates[] = $this->base . '/' . $path;
			$root = realpath($this->root);
			foreach (array_unique($candidates) as $candidate) {
				$file = realpath($this->root . '/' . $candidate);
				if ($root !== false && $file !== false && str_starts_with($file, $root . DIRECTORY_SEPARATOR) && is_file($file)) {
					return $urlRoot . '/' . implode('/', array_map('rawurlencode', explode('/', $candidate)));
				}
			}
		}
		return '';
	}
}
