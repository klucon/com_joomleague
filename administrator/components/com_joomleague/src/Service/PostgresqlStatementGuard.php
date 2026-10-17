<?php

declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Administrator\Service;

defined('_JEXEC') or die;

use Joomla\Database\DatabaseDriver;
use Joomla\Database\QueryMonitorInterface;

/** Keeps failed PostgreSQL statements recoverable before the driver's connection probe. */
final class PostgresqlStatementGuard implements QueryMonitorInterface
{
	private ?string $savepoint = null;
	private int $sequence = 0;
	private readonly string $prefix;

	private function __construct(private readonly \PDO $connection, private readonly ?QueryMonitorInterface $previous)
	{
		$this->prefix = 'jl_statement_' . bin2hex(random_bytes(8)) . '_';
	}

	public static function attach(DatabaseDriver $database): ?self
	{
		if ($database->getName() !== 'pgsql' || $database->getMonitor() instanceof self) return null;
		$connection = $database->getConnection();
		if (!$connection instanceof \PDO) throw new \LogicException('PostgreSQL PDO connection required.');
		$guard = new self($connection, $database->getMonitor());
		$database->setMonitor($guard);
		return $guard;
	}

	public function detach(DatabaseDriver $database): void
	{
		$database->setMonitor($this->previous);
	}

	public function startQuery(string $sql, ?array $boundParams = null): void
	{
		$this->previous?->startQuery($sql, $boundParams);
		$this->savepoint = null;
		if (!$this->connection->inTransaction() || preg_match('/^\s*(?:BEGIN|START|SAVEPOINT|RELEASE|ROLLBACK|COMMIT|END)\b/i', $sql)) return;
		$this->savepoint = $this->prefix . ++$this->sequence;
		$this->connection->exec('SAVEPOINT ' . $this->savepoint);
	}

	public function stopQuery(): void
	{
		try {
			if ($this->savepoint !== null) {
				$savepoint = $this->savepoint;
				$this->savepoint = null;
				try {
					$this->connection->exec('RELEASE SAVEPOINT ' . $savepoint);
				} catch (\PDOException $error) {
					// 25P02 means the statement aborted the transaction, not the connection.
					if ((string) $error->getCode() !== '25P02') throw $error;
					$this->connection->exec('ROLLBACK TO SAVEPOINT ' . $savepoint);
					$this->connection->exec('RELEASE SAVEPOINT ' . $savepoint);
				}
			}
		} finally {
			$this->previous?->stopQuery();
		}
	}
}
