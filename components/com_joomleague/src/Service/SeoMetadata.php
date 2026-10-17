<?php

declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Site\Service;

defined('_JEXEC') or die;

use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Factory;

final class SeoMetadata
{
	/** @param array<string,mixed> $schema */
	public function addStructuredData(object $document, array $schema): void
	{
		if ($schema !== []) {
			$document->addCustomTag('<script type="application/ld+json">' . json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_THROW_ON_ERROR) . '</script>');
		}
	}

	public function apply(object $document, string $title, string $url, string $description = '', string $image = '', string $type = 'website'): void
	{
		$title = trim($title);
		$url = trim($url);
		$description = $this->plainText($description);

		if ($title === '' || $url === '') {
			return;
		}

		$document->setTitle($title);
		$document->addHeadLink($url, 'canonical');
		$document->setMetaData('og:title', $title, 'property');
		$document->setMetaData('og:type', $type, 'property');
		$document->setMetaData('og:url', $url, 'property');
		if (class_exists(Factory::class)) {
			$application = Factory::getApplication();
			$siteName = trim((string) $application->get('sitename', ''));
			if ($siteName !== '') $document->setMetaData('og:site_name', $siteName, 'property');
			$locale = str_replace('-', '_', $application->getLanguage()->getTag());
			if ($locale !== '') $document->setMetaData('og:locale', $locale, 'property');
		}

		if ($description !== '') {
			$document->setDescription($description);
			$document->setMetaData('og:description', $description, 'property');
		}

		$image = $this->absoluteImage($image);
		if ($image !== '') {
			$document->setMetaData('og:image', $image, 'property');
			$document->setMetaData('og:image:alt', $title, 'property');
			$document->setMetaData('twitter:image', $image);
			$document->setMetaData('twitter:image:alt', $title);
			$document->setMetaData('twitter:card', 'summary_large_image');
		} else {
			$document->setMetaData('twitter:card', 'summary');
		}
		$document->setMetaData('twitter:title', $title);
		if ($description !== '') {
			$document->setMetaData('twitter:description', $description);
		}
	}

	public function noIndex(object $document): void
	{
		$document->setMetaData('robots', 'noindex,follow');
	}

	/** @param array<string,int|string> $parameters */
	public function applyView(object $document, string $view, array $parameters, string $title, string $description = ''): void
	{
		$route = 'index.php?option=com_joomleague&view=' . rawurlencode($view);
		foreach ($parameters as $name => $value) {
			if ($value === '' || $value === 0) continue;
			$route .= '&' . rawurlencode($name) . '=' . rawurlencode((string) $value);
		}
		$this->apply($document, $title, Route::_($route, false, Route::TLS_IGNORE, true), $description);
	}

	private function plainText(string $value): string
	{
		$value = trim((string) preg_replace('/\s+/u', ' ', strip_tags($value)));
		$length = function_exists('mb_strlen') ? mb_strlen($value, 'UTF-8') : strlen($value);
		if ($value === '' || $length <= 180) return $value;
		$short = function_exists('mb_substr') ? mb_substr($value, 0, 177, 'UTF-8') : substr($value, 0, 177);
		$wordBoundary = preg_replace('/\s+\S*$/u', '', $short);
		return rtrim((string) ($wordBoundary !== '' ? $wordBoundary : $short), " \t\n\r\0\x0B,.;:-") . '...';
	}

	private function absoluteImage(string $image): string
	{
		$image = trim($image);
		if ($image === '' || preg_match('#^https?://#i', $image)) {
			return $image;
		}
		if (str_starts_with($image, '//')) return Uri::getInstance(Uri::root())->getScheme() . ':' . $image;

		return rtrim(Uri::root(), '/') . '/' . ltrim($image, '/');
	}
}
