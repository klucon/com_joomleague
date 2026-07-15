<?php
/**
 * @package     JoomLeague
 * @copyright   Copyright (C) 2026 Ondřej Klučka (https://klucon.cz). All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */


declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Administrator\Model;

\defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;

/**
 * SQL import tool: takes a JoomLeague SQL dump (e.g. produced by the migration
 * tool), splits it per JoomLeague table and imports it table by table so the
 * admin gets per-table progress feedback.
 */
final class SqlimportModel extends BaseDatabaseModel
{
	/** Tables that must never be overwritten by an imported dump. */
	private const SKIP_TABLES = ['version'];

	/** Upper bound for an uploaded dump; analyze() reads it whole into memory. */
	private const MAX_UPLOAD_BYTES = 64 * 1024 * 1024;

	/** Job directories older than this are swept on the next analyze() call. */
	private const STALE_JOB_SECONDS = 24 * 60 * 60;

	private function baseDir(): string
	{
		$tmp = (string) Factory::getApplication()->get('tmp_path');

		return rtrim($tmp, '/\\') . '/joomleague_sqlimport';
	}

	private function jobDir(string $token): string
	{
		$token = preg_replace('/[^a-f0-9]/', '', $token);

		return $this->baseDir() . '/' . $token;
	}

	/**
	 * Left-over job directories accumulate on disk if an admin never finishes
	 * an import (no cleanupjob call). Sweep anything older than a day whenever
	 * a new job starts, so uploaded dump contents don't linger in tmp/ forever.
	 */
	private function pruneStaleJobs(): void
	{
		$base = $this->baseDir();

		if (!is_dir($base)) {
			return;
		}

		$cutoff = time() - self::STALE_JOB_SECONDS;

		foreach (glob($base . '/*', GLOB_ONLYDIR) ?: [] as $dir) {
			if ((@filemtime($dir) ?: 0) >= $cutoff) {
				continue;
			}

			foreach (glob($dir . '/*') ?: [] as $f) {
				@unlink($f);
			}

			@rmdir($dir);
		}
	}

	/**
	 * Parse the uploaded dump, split it into per-table chunks on disk and return
	 * a manifest with the record count for every JoomLeague table.
	 *
	 * @return  object{token:string, tables:array<int,object>, prefix:string}
	 */
	public function analyze(string $tmpFile): object
	{
		$this->pruneStaleJobs();

		$size = @filesize($tmpFile);

		if ($size !== false && $size > self::MAX_UPLOAD_BYTES) {
			throw new \RuntimeException('COM_JOOMLEAGUE_SQLIMPORT_ERROR_TOOLARGE');
		}

		$sql = file_get_contents($tmpFile);

		if ($sql === false || $sql === '') {
			throw new \RuntimeException('COM_JOOMLEAGUE_SQLIMPORT_ERROR_EMPTY');
		}

		if (!preg_match('/`([A-Za-z0-9_#]*?)joomleague_[a-z0-9_]+`/', $sql, $m)) {
			throw new \RuntimeException('COM_JOOMLEAGUE_SQLIMPORT_ERROR_NOTABLES');
		}

		$srcPrefix = $m[1];
		$dbPrefix  = $this->getDatabase()->getPrefix();

		// normalise the source prefix to this site's prefix
		if ($srcPrefix !== $dbPrefix) {
			$sql = str_replace('`' . $srcPrefix . 'joomleague_', '`' . $dbPrefix . 'joomleague_', $sql);
		}

		$token = bin2hex(random_bytes(12));
		$dir   = $this->jobDir($token);

		if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
			throw new \RuntimeException('COM_JOOMLEAGUE_SQLIMPORT_ERROR_TMP');
		}

		$statements = $this->getDatabase()->splitSql($sql);
		$chunks = [];   // short table name => [statements]
		$counts = [];   // short table name => estimated rows

		$re = '/`' . preg_quote($dbPrefix, '/') . 'joomleague_([a-z0-9_]+)`/';

		foreach ($statements as $stmt) {
			$stmt = trim($stmt);

			// LOCK/UNLOCK TABLES come from mysqldump/mariadb-dump but break a
			// table-by-table import (each chunk runs on its own), so drop them.
			if ($stmt === '' || preg_match('/^\s*(LOCK|UNLOCK)\s+TABLES/i', $stmt) || !preg_match($re, $stmt, $mm)) {
				continue;
			}

			$short = $mm[1];

			// The version table holds the installed component's own version
			// marker; importing it would overwrite it with the dump's value.
			if (in_array($short, self::SKIP_TABLES, true)) {
				continue;
			}

			$chunks[$short][] = $stmt;

			if (preg_match('/^\s*INSERT\s+INTO/i', $stmt)) {
				// count value tuples, tolerating "),(", "), (" and "),\n(" separators
				$counts[$short] = ($counts[$short] ?? 0) + preg_match_all('/\),\s*\(/', $stmt) + 1;
			} else {
				$counts[$short] = $counts[$short] ?? 0;
			}
		}

		if ($chunks === []) {
			throw new \RuntimeException('COM_JOOMLEAGUE_SQLIMPORT_ERROR_NOTABLES');
		}

		$tables = [];

		foreach ($chunks as $short => $stmts) {
			file_put_contents($dir . '/' . $short . '.sql', implode(";\n", $stmts) . ";\n");
			$tables[] = (object) ['name' => $short, 'count' => (int) ($counts[$short] ?? 0)];
		}

		usort($tables, static fn ($a, $b) => strcmp($a->name, $b->name));

		return (object) ['token' => $token, 'prefix' => $dbPrefix, 'tables' => $tables];
	}

	/** Import a single already-prepared table chunk. Throws on failure. */
	public function importTable(string $token, string $table): void
	{
		$table = preg_replace('/[^a-z0-9_]/', '', $table);
		$file  = $this->jobDir($token) . '/' . $table . '.sql';

		if (!is_file($file)) {
			throw new \RuntimeException('COM_JOOMLEAGUE_SQLIMPORT_ERROR_CHUNK');
		}

		$db = $this->getDatabase();
		$db->setQuery('SET FOREIGN_KEY_CHECKS = 0')->execute();

		foreach ($db->splitSql((string) file_get_contents($file)) as $stmt) {
			$stmt = trim($stmt);

			// Skip blanks and the bare ";" fragments the re-split can produce,
			// which would otherwise raise a "Query was empty" error.
			if (trim($stmt, "; \t\r\n") === '' || preg_match('/^\s*(LOCK|UNLOCK)\s+TABLES/i', $stmt)) {
				continue;
			}

			$db->setQuery($stmt)->execute();
		}
	}

	/** Remove a job's temporary chunk directory. */
	public function cleanup(string $token): void
	{
		$dir = $this->jobDir($token);

		if (is_dir($dir)) {
			foreach (glob($dir . '/*') ?: [] as $f) {
				@unlink($f);
			}

			@rmdir($dir);
		}
	}
}
