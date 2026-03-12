<?php

declare(strict_types=1);

namespace Velolia\Database;

use PDO;
use PDOStatement;

class Connection
{
    protected static array $queries = [];

    public function __construct(protected PDO $pdo, protected array $config) {}

    public function getPdo(): PDO
    {
        return $this->pdo;
    }

    public function table(string $table): QueryBuilder
    {
        return (new QueryBuilder($this))->table($table);
    }

    public function select(string $query, array $bindings = []): array
    {
        $statement = $this->run($query, $bindings);
        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }

    public function insert(string $query, array $bindings = []): bool
    {
        return $this->run($query, $bindings)->rowCount() > 0;
    }

    public function update(string $query, array $bindings = []): int
    {
        return $this->run($query, $bindings)->rowCount();
    }

    public function delete(string $query, array $bindings = []): int
    {
        return $this->run($query, $bindings)->rowCount();
    }

    protected function run(string $query, array $bindings = []): PDOStatement
    {
        $start = microtime(true);
        $statement = $this->pdo->prepare($query);
        $statement->execute($bindings);
        
        self::$queries[] = [
            'sql' => $query,
            'bindings' => $bindings,
            'time' => (microtime(true) - $start) * 1000,
        ];

        return $statement;
    }

    public static function getQueryLog(): array
    {
        return self::$queries;
    }

    public function lastInsertId(): string|false
    {
        return $this->pdo->lastInsertId();
    }
}
