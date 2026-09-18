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

		$isCanonicalMigration = str_contains($sql, '-- Canonical JoomLeague 6.2 migration package');
		$profileCodes = $isCanonicalMigration ? $this->profileCodes($sql) : [];
		$statements = $this->splitSql($sql);
		$statements = array_values(array_filter(array_map('trim', $statements)));
		foreach ($statements as $statement) {
			if (!$this->isAllowedImportStatement($statement)) throw new RuntimeException('COM_JOOMLEAGUE_DATAIMPORT_ERROR_STATEMENT');
		}

		$ddl = array_values(array_filter($statements, static fn (string $statement): bool => preg_match('/^CREATE\s/i', $statement) === 1));
		$inserts = array_values(array_filter($statements, static fn (string $statement): bool => preg_match('/^INSERT\s/i', $statement) === 1));
		$preparedDdl = array_map(fn (string $statement): string => $this->prepareStatement($statement), $ddl);
		$preparedInserts = array_map(
			fn (string $statement): string => $this->prepareStatement($this->duplicateTolerantInsert($statement)),
			$inserts
		);
		$executed = 0;
		$skipped = 0;
		$createdTables = [];
		$createdIndexes = [];
		$transactionStarted = false;
		$this->acquireImportLock();

		try {
			foreach ($ddl as $index => $statement) {
				$object = $this->ddlObject($statement);
				$tableExisted = $this->tableExists($object['table']);
				$indexExisted = $object['index'] !== null && $tableExisted
					? $this->indexExists($object['table'], $object['index'])
					: false;

				$this->database->setQuery($preparedDdl[$index])->execute();
				$executed++;

				if (!$tableExisted && $this->tableExists($object['table'])) {
					$createdTables[] = $object['table'];
				}
				if ($object['index'] !== null && !$indexExisted && $this->indexExists($object['table'], $object['index'])) {
					$createdIndexes[] = $object;
				}
			}

			$this->database->transactionStart();
			$transactionStarted = true;
			foreach ($preparedInserts as $statement) {
				$this->database->setQuery($statement)->execute();
				if ($this->database->getAffectedRows() === 0) $skipped++; else $executed++;
			}
			if ($isCanonicalMigration) {
				$executed += $this->materializeImportedSportTypes($profileCodes);
				$this->synchronizePostgreSqlIdentitySequences();
			}
			// Imported rows bypass model cascade hooks. Removing these cheap
			// markers makes the next standings read rebuild every affected scope.
			$this->database->setQuery(
				$this->database->getQuery(true)->delete($this->database->quoteName('#__joomleague_standing_freshness'))
			)->execute();
			$this->database->transactionCommit();
			$transactionStarted = false;
		} catch (Throwable $error) {
			if ($transactionStarted) {
				try {
					$this->database->transactionRollback();
				} catch (Throwable) {
					// Preserve the original import error when a driver already closed the transaction.
				}
			}

			if ($createdTables !== [] || $createdIndexes !== []) {
				$recovered = $this->recoverCreatedObjects($createdTables, $createdIndexes);
				throw new RuntimeException(
					$recovered
						? 'COM_JOOMLEAGUE_DATAIMPORT_ERROR_RECOVERED'
						: 'COM_JOOMLEAGUE_DATAIMPORT_ERROR_RECOVERY_INCOMPLETE',
					0,
					$error
				);
			}

			throw $error;
		} finally {
			$this->releaseImportLock();
		}

		return compact('executed', 'skipped');
	}

	private function acquireImportLock(): void
	{
		if ($this->database->getName() === 'pgsql') {
			$this->database->setQuery("SELECT pg_advisory_lock(hashtext('com_joomleague.sql_import'))")->execute();
			return;
		}

		$locked = (int) $this->database->setQuery("SELECT GET_LOCK('com_joomleague.sql_import', 30)")->loadResult();
		if ($locked !== 1) {
			throw new RuntimeException('COM_JOOMLEAGUE_DATAIMPORT_ERROR_LOCK');
		}
	}

	private function releaseImportLock(): void
	{
		try {
			if ($this->database->getName() === 'pgsql') {
				$this->database->setQuery("SELECT pg_advisory_unlock(hashtext('com_joomleague.sql_import'))")->execute();
				return;
			}

			$this->database->setQuery("SELECT RELEASE_LOCK('com_joomleague.sql_import')")->execute();
		} catch (Throwable) {
			// The database connection also releases a session lock when it closes.
		}
	}

	/** @return array{table: string, index: ?string} */
	private function ddlObject(string $statement): array
	{
		if (preg_match('/^CREATE\s+TABLE\s+IF\s+NOT\s+EXISTS\s+[`"](#__joomleague_[a-z0-9_]+)[`"]\s*\(/i', $statement, $match) === 1) {
			return ['table' => $match[1], 'index' => null];
		}

		if (preg_match('/^CREATE\s+(?:UNIQUE\s+)?INDEX\s+(?:IF\s+NOT\s+EXISTS\s+)?[`"]([a-z0-9_]+)[`"]\s+ON\s+[`"](#__joomleague_[a-z0-9_]+)[`"]\s*\(/i', $statement, $match) === 1) {
			return ['table' => $match[2], 'index' => $match[1]];
		}

		throw new RuntimeException('COM_JOOMLEAGUE_DATAIMPORT_ERROR_STATEMENT');
	}

	private function tableExists(string $table): bool
	{
		return in_array($this->database->replacePrefix($table), $this->database->getTableList(), true);
	}

	private function indexExists(string $table, string $index): bool
	{
		if (!$this->tableExists($table)) {
			return false;
		}

		foreach ($this->database->getTableKeys($this->database->replacePrefix($table)) as $key) {
			$name = $key->Key_name ?? $key->idxName ?? null;
			if (is_string($name) && strcasecmp($name, $index) === 0) {
				return true;
			}
		}

		return false;
	}

	/**
	 * @param list<string> $tables
	 * @param list<array{table: string, index: ?string}> $indexes
	 */
	private function recoverCreatedObjects(array $tables, array $indexes): bool
	{
		$complete = true;

		foreach (array_reverse($indexes) as $object) {
			if ($object['index'] === null || !$this->indexExists($object['table'], $object['index'])) {
				continue;
			}

			try {
				$sql = $this->database->getName() === 'pgsql'
					? 'DROP INDEX ' . $this->database->quoteName($object['index'])
					: 'DROP INDEX ' . $this->database->quoteName($object['index']) . ' ON ' . $this->database->quoteName($object['table']);
				$this->database->setQuery($sql)->execute();
			} catch (Throwable) {
				$complete = false;
			}
		}

		foreach (array_reverse(array_unique($tables)) as $table) {
			if (!$this->tableExists($table)) {
				continue;
			}

			try {
				$count = (int) $this->database->setQuery(
					$this->database->getQuery(true)->select('COUNT(*)')->from($this->database->quoteName($table))
				)->loadResult();
				if ($count !== 0) {
					$complete = false;
					continue;
				}
				$this->database->setQuery('DROP TABLE ' . $this->database->quoteName($table))->execute();
			} catch (Throwable) {
				$complete = false;
			}
		}

		return $complete;
	}

	private function synchronizePostgreSqlIdentitySequences(): void
	{
		if ($this->database->getName() !== 'pgsql') {
			return;
		}

		foreach (ComponentTableCatalog::installed($this->database) as $table) {
			if (!array_key_exists('id', $this->database->getTableColumns($table, false))) {
				continue;
			}

			$physicalTable = $this->database->replacePrefix($table);
			$query = $this->database->getQuery(true)
				->select('pg_get_serial_sequence(' . $this->database->quote($physicalTable) . ', ' . $this->database->quote('id') . ')');
			$sequence = (string) $this->database->setQuery($query)->loadResult();

			if ($sequence === '') {
				continue;
			}

			$query = $this->database->getQuery(true)
				->select('COALESCE(MAX(' . $this->database->quoteName('id') . '), 0)')
				->from($this->database->quoteName($table));
			$maximum = (int) $this->database->setQuery($query)->loadResult();
			$called = $maximum > 0 ? 'TRUE' : 'FALSE';
			$nextValue = max(1, $maximum);
			$this->database->setQuery(
				'SELECT setval(' . $this->database->quote($sequence) . ', ' . $nextValue . ', ' . $called . ')'
			)->execute();
		}
	}

	/** @return list<string> */
	private function profileCodes(string $sql): array
	{
		preg_match_all('/\{\{profile_version:([a-z][a-z0-9_]*)\}\}/', $sql, $matches);

		return array_values(array_unique($matches[1] ?? []));
	}

	/** @param list<string> $profileCodes */
	private function materializeImportedSportTypes(array $profileCodes): int
	{
		$created = 0;

		foreach ($profileCodes as $profileCode) {
			$query = $this->database->getQuery(true)
				->select([
					$this->database->quoteName('sport_type.id'),
					$this->database->quoteName('sport_type.profile_version_id'),
				])
				->from($this->database->quoteName('#__joomleague_sport_type', 'sport_type'))
				->innerJoin($this->database->quoteName('#__joomleague_sport_profile_version', 'version') . ' ON version.id = sport_type.profile_version_id')
				->innerJoin($this->database->quoteName('#__joomleague_sport_profile', 'profile') . ' ON profile.id = version.profile_id')
				->where($this->database->quoteName('profile.code') . ' = :profileCode')
				->bind(':profileCode', $profileCode);

			foreach ($this->database->setQuery($query)->loadObjectList() as $sportType) {
				$options = [
					'positions' => !$this->sportCatalogExists('#__joomleague_sport_position', (int) $sportType->id),
					'event_types' => !$this->sportCatalogExists('#__joomleague_event_type', (int) $sportType->id),
					'statistics' => !$this->sportCatalogExists('#__joomleague_statistic', (int) $sportType->id),
				];

				if (!in_array(true, $options, true)) {
					continue;
				}

				$counts = (new SportTypeProfileMaterializer($this->database))->materialize(
					(int) $sportType->id,
					(int) $sportType->profile_version_id,
					$options,
					0
				);
				$created += array_sum($counts);
			}
		}

		return $created;
	}

	private function sportCatalogExists(string $table, int $sportTypeId): bool
	{
		$query = $this->database->getQuery(true)
			->select('COUNT(*)')
			->from($this->database->quoteName($table))
			->where($this->database->quoteName('sport_type_id') . ' = :sportTypeId')
			->bind(':sportTypeId', $sportTypeId, \Joomla\Database\ParameterType::INTEGER);

		return (int) $this->database->setQuery($query)->loadResult() > 0;
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
		$statement = $this->database->replacePrefix($statement);

		if ($this->database->getName() === 'pgsql') {
			$statement = preg_replace('/`([A-Za-z0-9_]+)`/', '"$1"', $statement);
			if ($statement === null) throw new RuntimeException('COM_JOOMLEAGUE_DATAIMPORT_ERROR_STATEMENT');
		}

		return $statement;
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
