<?php
declare(strict_types=1);

namespace Joomla\CMS\Router {
	class Route {
		public const TLS_IGNORE = 0;
		public static function _(string $url, bool $escape, int $tls, bool $absolute): string {
			if ($escape || !$absolute) throw new \RuntimeException('Metadata requires a raw absolute URL.');
			return 'https://example.test/' . $url;
		}
	}
}
namespace {
	define('_JEXEC', 1);
	require __DIR__ . '/../../components/com_joomleague/src/Service/SeoMetadata.php';
	$document = new class {
		public array $links = [];
		public array $metadata = [];
		public function setTitle(string $value): void {}
		public function setDescription(string $value): void {}
		public function addHeadLink(string $url, string $relation): void { $this->links[$relation] = $url; }
		public function setMetaData(string $name, string $value, ?string $attribute = null): void { $this->metadata[$name] = $value; }
	};
	$seo = new \Joomleague\Component\Joomleague\Site\Service\SeoMetadata();
	$seo->applyView($document, 'comparison', ['project_id' => 3, 'first_id' => 98, 'second_id' => 99], 'Comparison', 'Description');
	$url = $document->links['canonical'];
	if ($document->metadata['og:url'] !== $url || str_contains($url, '&amp;') || !str_contains($url, '&second_id=99')) throw new \RuntimeException('Metadata URL is HTML-escaped.');
	foreach (glob(__DIR__ . '/../../components/com_joomleague/src/View/*/HtmlView.php') as $file) {
		if (str_contains(file_get_contents($file), 'true, Route::TLS_IGNORE, true')) throw new \RuntimeException('Escaped metadata route: ' . basename(dirname($file)));
	}
	echo "SEO canonical, Open Graph and schema routes use raw absolute URLs OK\n";
}
