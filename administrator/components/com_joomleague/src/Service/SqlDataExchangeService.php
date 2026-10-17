<?php

declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Administrator\Service;

defined('_JEXEC') or die;

use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;
use Joomla\CMS\Utility\Utility;
use RuntimeException;
use Throwable;

final class SqlDataExchangeService
{
	private ?array $trustedImportDdl = null;
	private ?array $trustedImportTables = null;
	private array $importColumns = [];
	public function __construct(private readonly DatabaseInterface $database, private readonly string $componentRoot)
	{
	}

	/** @param list<string> $requestedTables */
	public function export(array $requestedTables): string
	{
		$installed = ComponentTableCatalog::installed($this->database);
		$tables = array_values(array_intersect(array_keys($this->schemaTables()), $installed, array_unique($requestedTables)));

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
		$this->importColumns = [];
		if (strlen($sql) > (int) Utility::getMaxUploadSize()) {
			throw new RuntimeException('COM_JOOMLEAGUE_DATAIMPORT_ERROR_SIZE');
		}

		$isCanonicalMigration = preg_match('/\A(?:\xEF\xBB\xBF)?\s*-- Canonical JoomLeague 6\.2 migration package(?:\r?\n|$)/', $sql) === 1;
		$statements = $this->splitSql($sql);
		$statements = array_values(array_filter(array_map('trim', $statements)));
		if ($statements === []) throw new RuntimeException('COM_JOOMLEAGUE_DATAIMPORT_ERROR_STATEMENT');
		foreach ($statements as $statement) {
			if (!$this->isAllowedImportStatement($statement)) throw new RuntimeException('COM_JOOMLEAGUE_DATAIMPORT_ERROR_STATEMENT');
		}

		$ddl = array_values(array_filter($statements, static fn (string $statement): bool => preg_match('/^CREATE\s/i', $statement) === 1));
		$inserts = array_values(array_filter($statements, static fn (string $statement): bool => preg_match('/^INSERT\s/i', $statement) === 1));
		$ddlOrder = array_flip($this->trustedDdl());
		usort($ddl, static fn (string $a, string $b): int => $ddlOrder[rtrim($a, ';')] <=> $ddlOrder[rtrim($b, ';')]);
		$preparedDdl = array_map(fn (string $statement): string => $this->prepareStatement($statement), $ddl);
		// Joomla SQL exports escape backslashes on both supported database drivers.
		$preparedInserts = array_map(fn (string $statement): array => (new SqlInsertDataParser())->parse($statement), $inserts);
		$preparedInserts = $this->orderInserts($preparedInserts);
		$profileCodes = [];
		foreach ($preparedInserts as $insert) foreach ($insert['rows'] as $row) foreach ($row as $value) {
			if (is_array($value)) $profileCodes[$value['profile']] = true;
		}
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
				if ($indexExisted) { $skipped++; continue; }

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
			$this->lockPostgreSqlTables();
			foreach ($preparedInserts as $insert) {
				$query = $this->boundInsert($insert);
				$this->database->setQuery($query)->execute();
				if ($this->database->getAffectedRows() === 0) $skipped++; else $executed++;
			}
			if ($isCanonicalMigration) {
				$executed += $this->materializeImportedSportTypes(array_keys($profileCodes));
			}
			$this->synchronizePostgreSqlIdentitySequences();
			// Imported rows bypass model cascade hooks. Removing these cheap
			// markers makes the next standings read rebuild every affected scope.
			if ($this->tableExists('#__joomleague_standing_freshness')) {
				$this->database->setQuery(
					$this->database->getQuery(true)->delete($this->database->quoteName('#__joomleague_standing_freshness'))
				)->execute();
			}
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
			$deadline = microtime(true) + 30;
			do {
				$locked = $this->database->setQuery("SELECT pg_try_advisory_lock(hashtext('com_joomleague.sql_import'))")->loadResult();
				if (in_array($locked, [true, 1, '1', 't'], true)) return;
				usleep(100000);
			} while (microtime(true) < $deadline);
			throw new RuntimeException('COM_JOOMLEAGUE_DATAIMPORT_ERROR_LOCK');
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

		foreach (array_intersect(array_keys($this->schemaTables()), ComponentTableCatalog::installed($this->database)) as $table) {
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
			$nameQuery = $this->database->getQuery(true)->select(['n.nspname', 'c.relname'])
				->from('pg_catalog.pg_class AS c')->innerJoin('pg_catalog.pg_namespace AS n ON n.oid = c.relnamespace')
				->where('c.oid = CAST(:sequence AS regclass)')->bind(':sequence', $sequence);
			$name = $this->database->setQuery($nameQuery)->loadAssoc();
			if (!$name) throw new RuntimeException('COM_JOOMLEAGUE_DATAIMPORT_ERROR_STATEMENT');
			$identifier = $this->database->quoteName($name['nspname']) . '.' . $this->database->quoteName($name['relname']);
			$state = $this->database->setQuery('SELECT last_value, is_called FROM ' . $identifier)->loadAssoc();
			$called = $maximum > 0 || in_array($state['is_called'], [true, 1, '1', 't'], true) ? 'TRUE' : 'FALSE';
			$nextValue = max(1, $maximum, (int) $state['last_value']);
			$this->database->setQuery(
				'SELECT setval(' . $this->database->quote($sequence) . ', ' . $nextValue . ', ' . $called . ')'
			)->execute();
		}
	}

	private function lockPostgreSqlTables(): void
	{
		if ($this->database->getName() !== 'pgsql') return;
		$this->database->setQuery("SET LOCAL lock_timeout = '30s'")->execute();
		$tables = array_intersect(array_keys($this->schemaTables()), ComponentTableCatalog::installed($this->database));
		if ($tables !== []) $this->database->setQuery('LOCK TABLE ' . implode(', ', $this->database->quoteName($tables)) . ' IN SHARE ROW EXCLUSIVE MODE')->execute();
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
				$counts = (new SportTypeProfileMaterializer($this->database))->materialize(
					(int) $sportType->id,
					(int) $sportType->profile_version_id,
					['positions' => true, 'event_types' => true, 'statistics' => true],
					0
				);
				$created += array_sum($counts);
			}
		}

		return $created;
	}

	/** @param list<string> $output */
	private function appendRows(array &$output, string $table): void
	{
		$columns = array_keys($this->database->getTableColumns($table, false));
		if ($columns === []) return;
		$query = $this->database->getQuery(true)->select('*')->from($this->database->quoteName($table));
		if (in_array('id', $columns, true)) $query->order($this->database->quoteName('id') . ' ASC');
		$rows = $this->database->setQuery($query)->loadAssocList();
		$parents = $this->schemaTables()[$table]['parents'];
		if ($parents !== []) {
			$ordered = $this->orderInserts([['table' => $table, 'columns' => $columns, 'rows' => array_map('array_values', $rows)]]);
			$rows = [];
			foreach ($ordered as $insert) foreach ($insert['rows'] as $row) $rows[] = array_combine($columns, $row);
		}
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
		try {
			if (preg_match('/^INSERT\s/i', $statement) === 1) {
				$insert = (new SqlInsertDataParser())->parse($statement);
				$schema = $this->schemaTables()[$insert['table']] ?? null;
				return $schema !== null && array_diff($insert['columns'], $schema['columns']) === [];
			}
			return in_array(rtrim(trim($statement), ';'), $this->trustedDdl(), true)
				&& preg_match('/^CREATE\s/i', $statement) === 1;
		} catch (RuntimeException) { return false; }
	}

	private function trustedDdl(): array
	{
		if ($this->trustedImportDdl !== null) return $this->trustedImportDdl;
		$driver = $this->database->getName() === 'pgsql' ? 'postgresql' : 'mysql.utf8';
		$schema = $this->splitSql((string) file_get_contents($this->componentRoot . '/sql/install.' . $driver . '.sql'));
		return $this->trustedImportDdl = array_values(array_map(
			static fn ($item) => rtrim(trim($item), ';'),
			array_filter($schema, static fn ($item) => preg_match('/^CREATE\s/i', $item) === 1)
		));
	}

	/** Metadata comes exclusively from the packaged schema, never uploaded SQL. */
	private function schemaTables(): array
	{
		if ($this->trustedImportTables !== null) return $this->trustedImportTables;
		$tables = [];
		foreach ($this->trustedDdl() as $ddl) {
			if (preg_match('/^CREATE\s+TABLE\b/i', $ddl) !== 1) continue;
			$table = $this->ddlObject($ddl)['table'];
			preg_match_all('/(?:\(|,)\s*[`"]([a-z][a-z0-9_]*)[`"]\s+[a-z]/i', $ddl, $columns);
			preg_match_all('/FOREIGN\s+KEY\s*\(\s*([`"])([a-z0-9_]+)\1\s*\)\s+REFERENCES\s+([`"])(#__joomleague_[a-z0-9_]+)\3\s*\(\s*[`"]id[`"]\s*\)/i', $ddl, $references, PREG_SET_ORDER);
			$parents = [];
			foreach ($references as $reference) if ($reference[4] === $table) $parents[] = $reference[2];
			$tables[$table] = ['columns' => $columns[1], 'parents' => $parents];
		}
		return $this->trustedImportTables = $tables;
	}

	/** Keep foreign keys enabled: parents and referenced tables precede their children. */
	private function orderInserts(array $inserts): array
	{
		$groups = [];
		foreach ($inserts as $insert) $groups[$insert['table']][] = $insert;
		$result = [];
		foreach ($this->schemaTables() as $table => $schema) {
			$group = $groups[$table] ?? [];
			if ($schema['parents'] === []) { array_push($result, ...$group); continue; }
			$rows = []; $ids = [];
			foreach ($group as $insert) foreach ($insert['rows'] as $row) {
				$values = array_combine($insert['columns'], $row);
				$index = count($rows);
				$rows[] = ['table' => $table, 'columns' => $insert['columns'], 'rows' => [$row], 'values' => $values];
				if (isset($values['id']) && !is_array($values['id'])) $ids[(string) $values['id']] ??= $index;
			}
			$degrees = array_fill(0, count($rows), 0); $children = [];
			foreach ($rows as $index => $row) foreach ($schema['parents'] as $parent) {
				$value = $row['values'][$parent] ?? null;
				if ($value === null || is_array($value) || !isset($ids[(string) $value])) continue;
				$degrees[$index]++; $children[$ids[(string) $value]][] = $index;
			}
			$queue = array_keys(array_filter($degrees, static fn (int $degree): bool => $degree === 0));
			for ($cursor = 0; $cursor < count($queue); $cursor++) {
				$index = $queue[$cursor]; $row = $rows[$index]; unset($row['values']); $result[] = $row;
				foreach ($children[$index] ?? [] as $child) if (--$degrees[$child] === 0) $queue[] = $child;
			}
			if (count($queue) !== count($rows)) throw new RuntimeException('COM_JOOMLEAGUE_DATAIMPORT_ERROR_STATEMENT');
		}
		return $result;
	}

	private function boundInsert(array $insert): \Joomla\Database\DatabaseQuery
	{
		if (!isset($this->importColumns[$insert['table']])) {
			if (!$this->tableExists($insert['table'])) throw new RuntimeException('COM_JOOMLEAGUE_DATAIMPORT_ERROR_STATEMENT');
			$this->importColumns[$insert['table']] = array_keys($this->database->getTableColumns($insert['table'], false));
		}
		$columns = $this->importColumns[$insert['table']];
		if (array_diff($insert['columns'], $columns) !== []) throw new RuntimeException('COM_JOOMLEAGUE_DATAIMPORT_ERROR_STATEMENT');
		$query = $this->database->getQuery(true);
		$rows = [];
		$values = [];
		foreach ($insert['rows'] as $row) {
			$holders = [];
			foreach ($row as $value) {
				$key = ':jl' . count($values);
				$values[$key] = is_array($value) ? (string) $this->activeProfileVersionId($value['profile']) : $value;
				$holders[] = $key;
			}
			$rows[] = '(' . implode(',', $holders) . ')';
		}
		$sql = 'INSERT INTO ' . $this->database->quoteName($insert['table']) . ' (' . implode(',', $this->database->quoteName($insert['columns'])) . ') VALUES ' . implode(',', $rows);
		$sql .= $this->database->getName() === 'pgsql' ? ' ON CONFLICT DO NOTHING' : ' ON DUPLICATE KEY UPDATE ' . $this->database->quoteName($insert['columns'][0]) . '=' . $this->database->quoteName($insert['columns'][0]);
		$query->setQuery($sql);
		foreach ($values as $key => &$value) $query->bind($key, $value, $value === null ? ParameterType::NULL : ParameterType::STRING);
		return $query;
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
