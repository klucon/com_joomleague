<?php

declare(strict_types=1);

define('_JEXEC', 1);
require_once __DIR__ . '/../../components/com_joomleague/src/Service/SeoMetadata.php';

use Joomleague\Component\Joomleague\Site\Service\SeoMetadata;

$document = new class {
	public string $title = '';
	public string $description = '';
	public array $links = [];
	public array $metadata = [];
	public array $tags = [];
	public function setTitle(string $value): void { $this->title = $value; }
	public function setDescription(string $value): void { $this->description = $value; }
	public function addHeadLink(string $url, string $relation): void { $this->links[$relation] = $url; }
	public function setMetaData(string $name, string $value, ?string $attribute = null): void { $this->metadata[$name] = [$value, $attribute]; }
	public function addCustomTag(string $value): void { $this->tags[] = $value; }
};

$seo = new SeoMetadata();
$seo->apply($document, ' Demo event ', 'https://example.test/event', '<p>Public   description</p>', 'https://example.test/image.jpg');
$seo->addStructuredData($document, ['@context' => 'https://schema.org', '@type' => 'Event', 'name' => '</script>']);

if ($document->title !== 'Demo event' || $document->description !== 'Public description') throw new RuntimeException('SEO title or description normalization failed.');
if (($document->links['canonical'] ?? '') !== 'https://example.test/event') throw new RuntimeException('Canonical link is missing.');
if (($document->metadata['og:title'][0] ?? '') !== 'Demo event' || ($document->metadata['og:title'][1] ?? '') !== 'property') throw new RuntimeException('Open Graph metadata is invalid.');
if (($document->metadata['twitter:card'][0] ?? '') !== 'summary_large_image') throw new RuntimeException('Twitter card metadata is invalid.');
if (($document->metadata['twitter:image'][0] ?? '') !== 'https://example.test/image.jpg') throw new RuntimeException('Twitter image metadata is invalid.');
if (!str_contains($document->tags[0] ?? '', '\\u003C/script\u003E')) throw new RuntimeException('Structured data JSON is not script-safe.');

echo "Canonical, Open Graph and structured-data metadata service OK\n";
