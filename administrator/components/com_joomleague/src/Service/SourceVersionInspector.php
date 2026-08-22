<?php

declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Administrator\Service;

defined('_JEXEC') or die;

use Joomla\Database\DatabaseInterface;

final class SourceVersionInspector
{
	private const ALLOWED_COLUMNS = ['id', 'major', 'minor', 'build', 'revision', 'version'];

	public function __construct(
		private readonly DatabaseInterface $database,
		private readonly LegacyVersionParser $parser = new LegacyVersionParser()
	) {
	}

	/**
	 * @return array{status: string, version: ?string, family: ?string, evidence: list<string>}
	 */
	public function inspect(string $schemaClassification): array
	{
		if ($schemaClassification === 'canonical') {
			return [
				'status' => 'not_applicable',
				'version' => null,
				'family' => 'joomleague_6_2_canonical',
				'evidence' => [],
			];
		}

		if (!in_array($schemaClassification, ['legacy', 'mixed'], true)) {
			return ['status' => 'unavailable', 'version' => null, 'family' => null, 'evidence' => []];
		}

		$table = $this->database->replacePrefix('#__joomleague_version');

		if (!in_array($table, $this->database->getTableList(), true)) {
			return ['status' => 'missing', 'version' => null, 'family' => null, 'evidence' => []];
		}

		$available = array_keys($this->database->getTableColumns($table, false));
		$columns = array_values(array_intersect(self::ALLOWED_COLUMNS, $available));

		if (!in_array('major', $columns, true)
			|| !in_array('minor', $columns, true)
			|| !in_array('build', $columns, true)) {
			return [
				'status' => 'invalid',
				'version' => null,
				'family' => null,
				'evidence' => ['required_version_columns_missing'],
			];
		}

		$query = $this->database->getQuery(true)
			->select($this->database->quoteName($columns))
			->from($this->database->quoteName($table));

		if (in_array('id', $columns, true)) {
			$query->order($this->database->quoteName('id') . ' DESC');
		}

		$rows = $this->database->setQuery($query, 0, 20)->loadAssocList();
		$result = $this->parser->parse($rows);

		if ($result['status'] === 'detected') {
			$result['evidence'][] = 'source_table=joomleague_version';
		}

		return $result;
	}
}
