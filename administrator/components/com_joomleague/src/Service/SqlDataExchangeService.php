<?php

declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Administrator\Service;

defined('_JEXEC') or die;

use Joomla\Database\DatabaseInterface;
use Joomla\CMS\Utility\Utility;
use RuntimeException;
use Throwable;

final class SqlDataExchangeService
{
	public function __construct(private readonly DatabaseInterface $database, private readonly string $componentRoot)
	{
	}

	/** @param list<string> $requestedTables */
	public function export(array $requestedTables): string
	{
		$installed = ComponentTableCatalog::installed($this->database);
		$tables = array_values(array_intersect($installed, array_unique($requestedTables)));

		if ($tables === []) {
			throw new RuntimeException('COM_JOOMLEAGUE_DATAEXCHANGE_ERROR_NO_TABLES');
		}

		$driver = $this->database->getName() === 'pgsql' ? 'postgresql' : 'mysql';
		$schemaFile = $this->componentRoot . '/sql/install.' . ($driver === 'mysql' ? 'mysql.utf8' : 'postgresql') . '.sql';
		$schema = (string) file_get_contents($schemaFile);
		$statements = $this->splitSql($schema);
		$output = [
			'-- JoomLeague 6.2 table export',
			'-- Database driver: ' . $driver,
			'-- Generated: ' . gmdate('Y-m-d H:i:s') . ' UTC',
			'-- Contains structure and data for ' . count($tables) . ' selected tables.',
			'',
		];

		foreach ($tables as $table) {
			$output[] = '-- Table: ' . $table;
			foreach ($statements as $statement) {
				if ($this->statementBelongsToTable($statement, $table)) {
					$output[] = rtrim($statement, "; \t\r\n") . ';';
				}
			}
			$output[] = '';
			$this->appendRows($output, $table);
			$output[] = '';
		}

		return implode("\n", $output);
	}

	/** @return array{executed: int, skipped: int} */
	public function import(string $sql): array
	{
		if (strlen($sql) > (int) Utility::getMaxUploadSize()) {
			throw new RuntimeException('COM_JOOMLEAGUE_DATAIMPORT_ERROR_SIZE');
		}

		$statements = $this->splitSql($sql);
		$statements = array_values(array_filter(array_map('trim', $statements)));
		foreach ($statements as $statement) {
			if (!$this->isAllowedImportStatement($statement)) throw new RuntimeException('COM_JOOMLEAGUE_DATAIMPORT_ERROR_STATEMENT');
		}

		$ddl = array_values(array_filter($statements, static fn (string $statement): bool => preg_match('/^CREATE\s/i', $statement) === 1));
		$inserts = array_values(array_filter($statements, static fn (string $statement): bool => preg_match('/^INSERT\s/i', $statement) === 1));
		$executed = 0;
		$skipped = 0;

		foreach ($ddl as $statement) {
			$this->database->setQuery($this->prepareStatement($statement))->execute();
			$executed++;
		}

		$this->database->transactionStart();
		try {
			foreach ($inserts as $statement) {
				$sql = $this->prepareStatement($this->duplicateTolerantInsert($statement));
				$this->database->setQuery($sql)->execute();
				if ($this->database->getAffectedRows() === 0) $skipped++; else $executed++;
			}
			$this->database->transactionCommit();
		} catch (Throwable $error) {
			try {
				$this->database->transactionRollback();
			} catch (Throwable) {
				// Preserve the original data error when a driver already closed the transaction.
			}
			throw $error;
		}

		return compact('executed', 'skipped');
	}

	/** @param list<string> $output */
	private function appendRows(array &$output, string $table): void
	{
		$columns = array_keys($this->database->getTableColumns($table, false));
		if ($columns === []) return;
		$query = $this->database->getQuery(true)->select('*')->from($this->database->quoteName($table));
		if (in_array('id', $columns, true)) $query->order($this->database->quoteName('id') . ' ASC');
		$rows = $this->database->setQuery($query)->loadAssocList();
		$columnSql = implode(', ', array_map([$this->database, 'quoteName'], $columns));

		foreach ($rows as $row) {
			$values = array_map(fn ($value): string => $value === null ? 'NULL' : $this->database->quote((string) $value), array_values($row));
			$output[] = 'INSERT INTO ' . $this->database->quoteName($table) . ' (' . $columnSql . ') VALUES (' . implode(', ', $values) . ');';
		}
	}

	/** @return list<string> */
	private function splitSql(string $sql): array
	{
		return array_values(array_filter(array_map('trim', $this->database->splitSql($sql))));
	}

	private function statementBelongsToTable(string $statement, string $table): bool
	{
		$quoted = preg_quote($table, '/');
		return preg_match('/^(?:CREATE\s+TABLE\s+IF\s+NOT\s+EXISTS\s+[`"]' . $quoted . '[`"]|CREATE\s+(?:UNIQUE\s+)?INDEX\b[\s\S]*?\sON\s+[`"]' . $quoted . '[`"])(?:\s|\()/i', trim($statement)) === 1;
	}

	private function isAllowedImportStatement(string $statement): bool
	{
		$patterns = [
			'/^CREATE\s+TABLE\s+IF\s+NOT\s+EXISTS\s+[`"]#__joomleague_[a-z0-9_]+[`"]\s*\(/i',
			'/^CREATE\s+(?:UNIQUE\s+)?INDEX\s+(?:IF\s+NOT\s+EXISTS\s+)?[`"][a-z0-9_]+[`"]\s+ON\s+[`"]#__joomleague_[a-z0-9_]+[`"]\s*\(/i',
			'/^INSERT\s+INTO\s+[`"]#__joomleague_[a-z0-9_]+[`"]\s*\(/i',
		];
		foreach ($patterns as $pattern) {
			if (preg_match($pattern, $statement) === 1) return true;
		}

		return false;
	}

	private function duplicateTolerantInsert(string $statement): string
	{
		$statement = rtrim($statement, "; \t\r\n");
		if ($this->database->getName() === 'pgsql') return $statement . ' ON CONFLICT DO NOTHING';
		if (preg_match('/^INSERT\s+INTO\s+[`"]#__joomleague_[a-z0-9_]+[`"]\s*\(\s*([`"])([a-z0-9_]+)\1/i', $statement, $match) !== 1) {
			throw new RuntimeException('COM_JOOMLEAGUE_DATAIMPORT_ERROR_STATEMENT');
		}
		$column = $this->database->quoteName($match[2]);
		return $statement . ' ON DUPLICATE KEY UPDATE ' . $column . ' = ' . $column;
	}

	private function prepareStatement(string $statement): string
	{
		$statement = preg_replace_callback(
			'/\{\{profile_version:([a-z][a-z0-9_]*)\}\}/',
			fn (array $match): string => (string) $this->activeProfileVersionId($match[1]),
			$statement
		);
		return $this->database->replacePrefix($statement);
	}

	private function activeProfileVersionId(string $code): int
	{
		$query = $this->database->getQuery(true)
			->select($this->database->quoteName('version.id'))
			->from($this->database->quoteName('#__joomleague_sport_profile_version', 'version'))
			->innerJoin($this->database->quoteName('#__joomleague_sport_profile', 'profile') . ' ON profile.id = version.profile_id')
			->where($this->database->quoteName('profile.code') . ' = :code')
			->where($this->database->quoteName('version.state') . ' = ' . $this->database->quote('active'))
			->bind(':code', $code)
			->order($this->database->quoteName('version.id') . ' DESC');
		$id = (int) $this->database->setQuery($query, 0, 1)->loadResult();
		if ($id < 1) throw new RuntimeException('COM_JOOMLEAGUE_DATAIMPORT_ERROR_PROFILE');
		return $id;
	}
}
