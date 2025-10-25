<?php

declare(strict_types=1);

namespace Velolia\Database;

use PDO;
use PDOStatement;

class Connection
{
    protected PDO $pdo;
    protected array $config;

    public function __construct(PDO $pdo, array $config)
    {
        $this->pdo = $pdo;
        $this->config = $config;
    }

    public function table(string $table): Query\Builder
    {
        return new Query\Builder($this, $table);
    }

    public function select(string $sql, array $bindings = []): array
    {
        $statement = $this->prepareAndExecute($sql, $bindings);
        return $statement->fetchAll(PDO::FETCH_OBJ);
    }

    public function selectOne(string $sql, array $bindings = []): ?object
    {
        $results = $this->select($sql, $bindings);
        return empty($results) ? null : $results[0];
    }

    public function insert(string $sql, array $bindings = []): bool
    {
        $statement = $this->prepareAndExecute($sql, $bindings);
        return $statement->rowCount() > 0;
    }

    public function update(string $sql, array $bindings = []): int
    {
        $statement = $this->prepareAndExecute($sql, $bindings);
        return $statement->rowCount();
    }

    public function delete(string $sql, array $bindings = []): int
    {
        $statement = $this->prepareAndExecute($sql, $bindings);
        return $statement->rowCount();
    }

    public function statement(string $sql, array $bindings = []): bool
    {
        $statement = $this->prepareAndExecute($sql, $bindings);
        return $statement !== false;
    }

    public function lastInsertId(): string
    {
        return $this->pdo->lastInsertId();
    }

    public function beginTransaction(): bool
    {
        return $this->pdo->beginTransaction();
    }

    public function commit(): bool
    {
        return $this->pdo->commit();
    }

    public function rollBack(): bool
    {
        return $this->pdo->rollBack();
    }

    public function inTransaction(): bool
    {
        return $this->pdo->inTransaction();
    }

    public function transaction(callable $callback): mixed
    {
        $this->beginTransaction();

        try {
            $result = $callback($this);
            $this->commit();
            return $result;
        } catch (\Throwable $e) {
            $this->rollBack();
            throw $e;
        }
    }

    /**
     * Drop all tables from the database
     * WARNING: This will delete all data permanently!
     * 
     * @return int Number of tables dropped
     */
    public function dropAllTables(): int
    {
        $database = $this->config['database'] ?? null;
        
        if (!$database) {
            throw new \RuntimeException("Database name not configured");
        }
        
        $this->statement("SET FOREIGN_KEY_CHECKS = 0");
        
        try {
            $tables = $this->select("
                SELECT table_name 
                FROM information_schema.tables 
                WHERE table_schema = ?
                AND table_type = 'BASE TABLE'
            ", [$database]);
            
            if (empty($tables)) {
                return 0;
            }

            $tableNames = [];
            foreach ($tables as $table) {
                if (is_object($table)) {
                    $tableName = $table->table_name ?? $table->TABLE_NAME ?? null;
                } elseif (is_array($table)) {
                    $tableName = $table['table_name'] ?? $table['TABLE_NAME'] ?? null;
                } else {
                    $tableName = null;
                }

                if ($tableName) {
                    $tableNames[] = "`{$tableName}`";
                }
            }

            // Tambahkan 'migrations' kalau belum ada
            if (!in_array('`migrations`', $tableNames, true)) {
                $tableNames[] = '`migrations`';
            }

            $dropSql = 'DROP TABLE IF EXISTS ' . implode(',', $tableNames);
            $this->statement($dropSql);

            return count($tableNames);
        } finally {
            $this->statement("SET FOREIGN_KEY_CHECKS = 1");
        }
    }

    /**
     * Get database name from config
     */
    public function getDatabaseName(): ?string
    {
        return $this->config['database'] ?? null;
    }

    /**
     * Get all table names in current database
     */
    public function getAllTables(): array
    {
        $database = $this->getDatabaseName();
        
        if (!$database) {
            return [];
        }
        
        $tables = $this->select("
            SELECT table_name 
            FROM information_schema.tables 
            WHERE table_schema = ?
            AND table_type = 'BASE TABLE'
        ", [$database]);
        
        return array_map(function($table) {
            return is_object($table) && isset($table->table_name) 
                ? $table->table_name 
                : (is_array($table) ? $table['table_name'] : '');
        }, $tables);
    }

    /**
     * Check if table exists
     */
    public function hasTable(string $tableName): bool
    {
        $database = $this->getDatabaseName();
        
        if (!$database) {
            return false;
        }
        
        $result = $this->selectOne("
            SELECT table_name 
            FROM information_schema.tables 
            WHERE table_schema = ? 
            AND table_name = ?
            AND table_type = 'BASE TABLE'
        ", [$database, $tableName]);
        
        return $result !== null;
    }

    protected function prepareAndExecute(string $sql, array $bindings = []): PDOStatement
    {
        $statement = $this->pdo->prepare($sql);
        
        if ($statement === false) {
            throw new \RuntimeException('Failed to prepare SQL statement: ' . $sql);
        }

        $success = $statement->execute($bindings);
        
        if (!$success) {
            $errorInfo = $statement->errorInfo();
            throw new \RuntimeException(
                "Failed to execute SQL statement: {$errorInfo[2]} (SQLSTATE: {$errorInfo[0]}, Code: {$errorInfo[1]})"
            );
        }

        return $statement;
    }
}
