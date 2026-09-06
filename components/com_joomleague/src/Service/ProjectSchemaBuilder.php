<?php

declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Site\Service;

defined('_JEXEC') or die;

/** Builds sport-neutral Schema.org metadata for a public competition project. */
final class ProjectSchemaBuilder
{
	/** @param array<string,mixed> $data @return array<string,mixed> */
	public function build(array $data, string $url): array
	{
		if (!isset($data['project']) || !is_object($data['project'])) {
			return [];
		}

		$project = $data['project'];
		$name = trim((string) ($project->name ?? ''));
		if ($name === '') {
			return [];
		}

		$schema = ['@context' => 'https://schema.org', '@type' => 'EventSeries', 'name' => $name, 'url' => $url];
		$description = trim(strip_tags((string) ($project->description ?? '')));
		if ($description !== '') {
			$schema['description'] = $description;
		}
		if (!empty($project->start_date)) {
			$schema['startDate'] = (string) $project->start_date;
		}
		if (!empty($project->end_date)) {
			$schema['endDate'] = (string) $project->end_date;
		}
		if (!empty($project->competition_name)) {
			$schema['organizer'] = ['@type' => 'Organization', 'name' => (string) $project->competition_name];
		}
		if (!empty($project->picture)) {
			$image = trim((string) $project->picture);
			if (!preg_match('#^https?://#i', $image) && preg_match('#^(https?://[^/]+)#i', $url, $origin)) {
				$image = $origin[1] . '/' . ltrim($image, '/');
			}
			$schema['image'] = $image;
		}

		return $schema;
	}
}
