<?php

declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Site\Service;

defined('_JEXEC') or die;

use Joomla\CMS\Uri\Uri;

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

		if ($description !== '') {
			$document->setDescription($description);
			$document->setMetaData('og:description', $description, 'property');
		}

		$image = $this->absoluteImage($image);
		if ($image !== '') {
			$document->setMetaData('og:image', $image, 'property');
			$document->setMetaData('twitter:image', $image);
			$document->setMetaData('twitter:card', 'summary_large_image');
		} else {
			$document->setMetaData('twitter:card', 'summary');
		}
		$document->setMetaData('twitter:title', $title);
		if ($description !== '') {
			$document->setMetaData('twitter:description', $description);
		}
	}

	private function plainText(string $value): string
	{
		return trim((string) preg_replace('/\s+/u', ' ', strip_tags($value)));
	}

	private function absoluteImage(string $image): string
	{
		$image = trim($image);
		if ($image === '' || preg_match('#^(?:https?:)?//#i', $image)) {
			return $image;
		}

		return rtrim(Uri::root(), '/') . '/' . ltrim($image, '/');
	}
}
