<?php

declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Administrator\Service;

defined('_JEXEC') or die;

use Joomla\Database\DatabaseInterface;

final class ComponentTableCatalog
{
	/** @return list<string> */
	public static function installed(DatabaseInterface $database): array
	{
		$prefix = $database->replacePrefix('#__joomleague_');
		$tables = array_values(array_filter(
			$database->getTableList(),
			static fn (string $table): bool => str_starts_with($table, $prefix)
		));
		sort($tables, SORT_STRING);

		return array_map(
			static fn (string $table): string => '#__joomleague_' . substr($table, strlen($prefix)),
			$tables
		);
	}

	public static function accepts(string $table): bool
	{
		return preg_match('/^#__joomleague_[a-z0-9_]+$/', $table) === 1;
	}
}
