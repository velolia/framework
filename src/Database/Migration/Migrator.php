<?php

declare(strict_types=1);

namespace Velolia\Database\Migration;

use Velolia\Database\DatabaseManager;

class Migrator
{
    protected DatabaseManager $db;
    protected string $table = 'migrations';

    public function __construct(?DatabaseManager $db = null, bool $autoCreateTable = true)
    {
        $this->db = $db ?? app('db');
        
        if ($autoCreateTable) {
            $this->ensureMigrationsTable();
        }
    }

    /**
     * Run all pending migrations
     * 
     * @param string $path Path to migrations directory
     * @param callable|null $callback Callback function called after each migration
     */
    public function run(string $path, ?callable $callback = null): array
    {
        $files = glob($path . '/*.php');
        
        if ($files === false || empty($files)) {
            return [];
        }
        
        sort($files);
        
        $ran = $this->getRan();
        $batch = $this->getNextBatch();
        $migrated = [];

        foreach ($files as $file) {
            $name = basename($file, '.php');

            if (in_array($name, $ran)) {
                continue;
            }

            $migration = require $file;
            
            if (!is_object($migration) || !method_exists($migration, 'up')) {
                throw new \RuntimeException("Invalid migration: {$name}");
            }
            
            $migration->up();
            $this->log($name, $batch);
            
            $migrated[] = $name;
            
            // Call callback immediately after migration succeeds
            if ($callback !== null) {
                $callback($name);
            }
        }

        return $migrated;
    }

    /**
     * Rollback last batch
     * 
     * @param string $path Path to migrations directory
     * @param callable|null $callback Callback function called after each rollback
     */
    public function rollback(string $path, ?callable $callback = null): array
    {
        $migrations = $this->getLastBatch();
        
        if (empty($migrations)) {
            return [];
        }
        
        $rolledBack = [];
        $migrations = array_reverse($migrations);

        foreach ($migrations as $migration) {
            $name = is_object($migration) ? $migration->migration : $migration['migration'];
            $file = $path . '/' . $name . '.php';
            
            if (file_exists($file)) {
                $instance = require $file;
                
                if (is_object($instance) && method_exists($instance, 'down')) {
                    $instance->down();
                }
            }
            
            $this->delete($name);
            $rolledBack[] = $name;
            
            // Call callback immediately after rollback succeeds
            if ($callback !== null) {
                $callback($name);
            }
        }

        return $rolledBack;
    }

    /**
     * Reset all migrations
     */
    public function reset(string $path): array
    {
        $allRolledBack = [];
        
        while (true) {
            $rolledBack = $this->rollback($path);
            
            if (empty($rolledBack)) {
                break;
            }
            
            $allRolledBack = array_merge($allRolledBack, $rolledBack);
        }
        
        return $allRolledBack;
    }

    /**
     * Get all ran migrations
     */
    protected function getRan(): array
    {
        $results = $this->db->select(
            "SELECT migration FROM {$this->table} ORDER BY batch, id"
        );
        
        return array_map(function($row) {
            return is_object($row) ? $row->migration : $row['migration'];
        }, $results);
    }

    /**
     * Get next batch number
     */
    protected function getNextBatch(): int
    {
        $row = $this->db->selectOne("SELECT MAX(batch) as batch FROM {$this->table}");
        
        if (!$row) {
            return 1;
        }
        
        $batch = is_object($row) ? $row->batch : $row['batch'];
        return ((int) $batch) + 1;
    }

    /**
     * Get migrations from last batch
     */
    protected function getLastBatch(): array
    {
        $row = $this->db->selectOne("SELECT MAX(batch) as batch FROM {$this->table}");
        
        if (!$row) {
            return [];
        }
        
        $batch = is_object($row) ? $row->batch : $row['batch'];
        
        return $this->db->select(
            "SELECT * FROM {$this->table} WHERE batch = ? ORDER BY id",
            [$batch]
        );
    }

    /**
     * Log migration
     */
    protected function log(string $name, int $batch): void
    {
        $this->db->insert(
            "INSERT INTO {$this->table} (migration, batch) VALUES (?, ?)",
            [$name, $batch]
        );
    }

    /**
     * Delete migration record
     */
    protected function delete(string $name): void
    {
        $this->db->delete(
            "DELETE FROM {$this->table} WHERE migration = ?",
            [$name]
        );
    }

    public function dropAllTables(): void
    {
        $connection = $this->db->connection();
        $connection->dropAllTables();
    }

    /**
     * Ensure migrations table exists
     */
    public function ensureMigrationsTable(): void
    {
        $connection = $this->db->connection();
        
        $connection->statement("
            CREATE TABLE IF NOT EXISTS {$this->table} (
                id INT AUTO_INCREMENT PRIMARY KEY,
                migration VARCHAR(255) NOT NULL,
                batch INT NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )
        ");
    }
}