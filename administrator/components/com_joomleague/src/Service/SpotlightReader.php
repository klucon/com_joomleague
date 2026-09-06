<?php

declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Domain\Service;

defined('_JEXEC') or die;

use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;

final class SpotlightReader
{
	public function __construct(private readonly DatabaseInterface $database) {}

	/** @param int[] $viewLevels */
	public function read(string $kind, int $selectedId, bool $automatic, string $rotationKey, array $viewLevels): ?object
	{
		$levels = array_values(array_unique(array_filter(array_map('intval', $viewLevels), static fn (int $level): bool => $level > 0))) ?: [1];
		$query = $this->database->getQuery(true);

		if ($kind === 'person') {
			$query->select(['entity.id', 'entity.first_name', 'entity.last_name', 'entity.nickname', 'entity.picture AS image', 'entity.description', 'club.name AS context_name'])
				->from($this->database->quoteName('#__joomleague_person', 'entity'))
				->leftJoin($this->database->quoteName('#__joomleague_club', 'club') . ' ON club.id = entity.club_id AND club.published = 1 AND club.access IN (' . implode(',', $levels) . ')')
				->where('entity.death_date IS NULL')
				->where("(entity.first_name <> '' OR entity.last_name <> '')");
		} elseif ($kind === 'team') {
			$query->select(['entity.id', 'entity.name', 'entity.middle_name', 'entity.short_name', "COALESCE(NULLIF(entity.logo, ''), entity.picture) AS image", 'entity.description', 'club.name AS context_name'])
				->from($this->database->quoteName('#__joomleague_team', 'entity'))
				->leftJoin($this->database->quoteName('#__joomleague_club', 'club') . ' ON club.id = entity.club_id AND club.published = 1 AND club.access IN (' . implode(',', $levels) . ')');
		} else {
			return null;
		}

		$query->where('entity.published = 1')->where('entity.access IN (' . implode(',', $levels) . ')')->order('entity.id ASC');
		if (!$automatic) {
			if ($selectedId < 1) return null;
			$query->where('entity.id = :entityId')->bind(':entityId', $selectedId, ParameterType::INTEGER);
			return $this->normalise($this->database->setQuery($query)->loadObject(), $kind);
		}

		$countQuery = (clone $query)->clear('select')->clear('order')->select('COUNT(*)');
		$count = (int) $this->database->setQuery($countQuery)->loadResult();
		if ($count < 1) return null;
		$index = (int) sprintf('%u', crc32($kind . ':' . $rotationKey)) % $count;

		return $this->normalise($this->database->setQuery($query, $index, 1)->loadObject(), $kind);
	}

	private function normalise(?object $item, string $kind): ?object
	{
		if (!$item) return null;
		$item->kind = $kind;
		$item->display_name = $kind === 'person'
			? trim((string) $item->first_name . ' ' . (string) $item->last_name)
			: (string) $item->name;

		return $item;
	}
}
