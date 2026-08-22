<?php

declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Administrator\Service;

defined('_JEXEC') or die;

use Joomla\Database\DatabaseInterface;

final class SourceSchemaInventory
{
	public function __construct(
		private readonly DatabaseInterface $database,
		private readonly SourceSchemaClassifier $classifier = new SourceSchemaClassifier()
	) {
	}

	/** @return array{classification: string, confidence: string, evidence: list<string>, candidates: list<string>, table_count: int, tables: list<array{name: string, column_count: int}>} */
	public function inspectCurrentDatabase(): array
	{
		$prefix = $this->database->replacePrefix('#__');
		$schema = [];
		$tables = [];

		foreach ($this->database->getTableList() as $physicalTable) {
			if (!str_starts_with($physicalTable, $prefix . 'joomleague_')) {
				continue;
			}

			$name = substr($physicalTable, strlen($prefix));
			$columns = array_keys($this->database->getTableColumns($physicalTable, false));
			sort($columns, SORT_STRING);
			$schema[$name] = $columns;
			$tables[] = ['name' => $name, 'column_count' => count($columns)];
		}

		ksort($schema, SORT_STRING);
		usort($tables, static fn(array $left, array $right): int => strcmp($left['name'], $right['name']));

		return [...$this->classifier->classify($schema), 'table_count' => count($tables), 'tables' => $tables];
	}
}
