<?php

declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Site\Service;

defined('_JEXEC') or die;

final class EntitySchemaBuilder
{
	/** @return array<string,mixed> */
	public function build(string $kind, object $item, string $url): array
	{
		$name = $this->name($kind, $item);
		if ($name === '' || $url === '') {
			return [];
		}

		$schema = [
			'@context' => 'https://schema.org',
			'@type' => match ($kind) {
				'club' => 'SportsOrganization',
				'team' => 'SportsTeam',
				'person' => 'Person',
				'venue' => 'Place',
				default => 'Thing',
			},
			'name' => $name,
			'url' => $url,
		];

		$description = trim(strip_tags((string) ($item->description ?? '')));
		if ($description !== '') $schema['description'] = $description;

		$image = $this->assetUrl((string) ($item->logo ?? $item->picture ?? ''), $url);
		if ($image !== '') $schema['image'] = $image;

		if ($kind === 'club') {
			if (!empty($item->founded_date)) $schema['foundingDate'] = (string) $item->founded_date;
			if (!empty($item->dissolved_date)) $schema['dissolutionDate'] = (string) $item->dissolved_date;
		}
		if ($kind === 'team' && !empty($item->club_name)) {
			$schema['memberOf'] = ['@type' => 'SportsOrganization', 'name' => (string) $item->club_name];
		}
		if ($kind === 'person') {
			if (!empty($item->nickname)) $schema['alternateName'] = (string) $item->nickname;
			if (!empty($item->club_name)) $schema['affiliation'] = ['@type' => 'SportsOrganization', 'name' => (string) $item->club_name];
			if (!empty($item->country_code)) $schema['nationality'] = ['@type' => 'Country', 'identifier' => (string) $item->country_code];
		}
		if ($kind === 'venue') {
			$address = array_filter([
				'streetAddress' => trim((string) ($item->address ?? '')),
				'postalCode' => trim((string) ($item->postal_code ?? '')),
				'addressLocality' => trim((string) ($item->city ?? '')),
				'addressRegion' => trim((string) ($item->region ?? '')),
				'addressCountry' => trim((string) ($item->country_code ?? '')),
			]);
			if ($address !== []) $schema['address'] = ['@type' => 'PostalAddress', ...$address];
			if (($item->capacity ?? null) !== null) $schema['maximumAttendeeCapacity'] = (int) $item->capacity;
		}

		return $schema;
	}

	private function name(string $kind, object $item): string
	{
		if ($kind === 'person') {
			return trim((string) ($item->first_name ?? '') . ' ' . (string) ($item->last_name ?? ''));
		}

		return trim((string) ($item->name ?? ''));
	}

	private function assetUrl(string $asset, string $pageUrl): string
	{
		$asset = trim($asset);
		if ($asset === '' || preg_match('#^https?://#i', $asset)) return $asset;
		$parts = parse_url($pageUrl);
		if (!is_array($parts) || empty($parts['scheme']) || empty($parts['host'])) return $asset;
		$origin = $parts['scheme'] . '://' . $parts['host'] . (isset($parts['port']) ? ':' . $parts['port'] : '');
		return $origin . '/' . ltrim($asset, '/');
	}
}
