<?php
declare(strict_types=1);

namespace Joomla\Database {
    interface QueryMonitorInterface {
        public function startQuery(string $sql, ?array $boundParams = null): void;
        public function stopQuery(): void;
    }
    class DatabaseDriver {
        public ?QueryMonitorInterface $monitor = null;
        public function __construct(public string $name, public \PDO $connection) {}
        public function getName(): string { return $this->name; }
        public function getMonitor(): ?QueryMonitorInterface { return $this->monitor; }
        public function setMonitor(?QueryMonitorInterface $monitor): void { $this->monitor = $monitor; }
        public function getConnection(): \PDO { return $this->connection; }
    }
}
namespace {
    define('_JEXEC', 1);
    require dirname(__DIR__,2).'/administrator/components/com_joomleague/src/Service/PostgresqlStatementGuard.php';
    use Joomleague\Component\Joomleague\Administrator\Service\PostgresqlStatementGuard;
    $assert = static function (bool $ok, string $message): void { if (!$ok) throw new RuntimeException($message); };
    $pdo = new class extends PDO {
        public bool $active = true;
        public bool $aborted = false;
        public array $queries = [];
        public function __construct() {}
        public function inTransaction(): bool { return $this->active; }
        public function exec(string $statement): int|false {
            $this->queries[] = $statement;
            if ($this->aborted && str_starts_with($statement, 'RELEASE')) {
                throw new class extends PDOException {
                    public function __construct() { $this->code = '25P02'; }
                };
            }
            if (str_starts_with($statement, 'ROLLBACK TO')) $this->aborted = false;
            return 0;
        }
    };
    $db = new Joomla\Database\DatabaseDriver('mysqli', $pdo);
    $assert(PostgresqlStatementGuard::attach($db)===null,'MySQL must not be wrapped');
    $db->name = 'pgsql';
    $prior = new class implements Joomla\Database\QueryMonitorInterface {
        public int $starts=0;
        public int $stops=0;
        public function startQuery(string $sql, ?array $boundParams = null): void { $this->starts++; }
        public function stopQuery(): void { $this->stops++; }
    };
    $db->monitor = $prior;
    $guard=PostgresqlStatementGuard::attach($db);
    $assert($guard!==null && $db->monitor===$guard,'Guard not attached');
    $assert(PostgresqlStatementGuard::attach($db)===null,'Nested operation must not replace existing guard');
    $guard->startQuery('UPDATE example SET value = :value',[(object)['value'=>1]]);
    $guard->stopQuery();
    $assert(count($pdo->queries)===2 && str_starts_with($pdo->queries[0],'SAVEPOINT jl_statement_') && str_starts_with($pdo->queries[1],'RELEASE SAVEPOINT '),'Successful statement must not be rolled back');
    $guard->startQuery('INSERT INTO example VALUES (1)');
    $pdo->aborted=true;
    $guard->stopQuery();
    $assert(!$pdo->aborted && str_starts_with($pdo->queries[4],'ROLLBACK TO SAVEPOINT '),'Failed statement must be recovered before connection probe');
    $count=count($pdo->queries);
    foreach (['BEGIN','START TRANSACTION','SAVEPOINT SP_1','RELEASE SAVEPOINT SP_1','ROLLBACK TO SAVEPOINT SP_1','COMMIT','END'] as $sql) {
        $guard->startQuery($sql); $guard->stopQuery();
    }
    $pdo->active=false;
    $guard->startQuery('SELECT 1'); $guard->stopQuery();
    $assert(count($pdo->queries)===$count,'Transaction control and autocommit queries must not receive statement savepoints');
    $assert($prior->starts===10 && $prior->stops===10,'Existing monitor events lost');
    $guard->detach($db);
    $assert($db->monitor===$prior,'Existing monitor not restored');
    echo "PASS: PostgreSQL statement recovery, success preservation, transaction controls, nesting and monitor restoration\n";
}
