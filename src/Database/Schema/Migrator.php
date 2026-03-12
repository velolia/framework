<?php

declare(strict_types=1);

namespace Velolia\Database\Schema;

use Velolia\Core\Application;

class Migrator
{
    public function __construct(protected Application $app) {}

    public function run(string $directory): array
    {
        $this->ensureMigrationsTableExists();

        $files = $this->getMigrationFiles($directory);
        $batch = $this->getNextBatchNumber();
        $migrated = [];

        foreach ($files as $file) {
            if ($this->isMigrationApplied($file)) {
                continue;
            }

            $path = $directory . '/' . $file;
            $migration = require $path;
            
            $migration->up();
            
            $this->markMigrationAsApplied($file, $batch);
            $migrated[] = $file;
        }

        return $migrated;
    }

    public function rollback(string $directory): array
    {
        $db = $this->app->make('db');
        
        if (!$this->migrationsTableExists()) {
            return [];
        }

        $lastBatch = $this->getLastBatchNumber();

        if ($lastBatch === null) {
            return [];
        }

        $migrations = $db->select("SELECT * FROM `migrations` WHERE `batch` = ? ORDER BY `migration` DESC", [$lastBatch]);
        $rolledBack = [];

        foreach ($migrations as $m) {
            $file = $m['migration'];
            $path = $directory . '/' . $file;

            if (file_exists($path)) {
                $migration = require $path;
                $migration->down();
            }

            $db->delete("DELETE FROM `migrations` WHERE `id` = ?", [$m['id']]);
            $rolledBack[] = $file;
        }

        return $rolledBack;
    }

    public function getMigrationFiles(string $directory): array
    {
        if (!is_dir($directory)) {
            return [];
        }

        $files = array_diff(scandir($directory), ['.', '..']);
        sort($files);
        return $files;
    }

    protected function ensureMigrationsTableExists(): void
    {
        $db = $this->app->make('db')->getPdo();
        $db->exec("CREATE TABLE IF NOT EXISTS `migrations` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `migration` VARCHAR(255) NOT NULL,
            `batch` INT NOT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");
    }

    protected function migrationsTableExists(): bool
    {
        $db = $this->app->make('db');
        return !empty($db->select("SHOW TABLES LIKE 'migrations'"));
    }

    protected function getNextBatchNumber(): int
    {
        return ($this->getLastBatchNumber() ?? 0) + 1;
    }

    protected function getLastBatchNumber(): ?int
    {
        $db = $this->app->make('db');
        $result = $db->select("SELECT MAX(`batch`) as max_batch FROM `migrations`")[0];
        return $result['max_batch'] !== null ? (int) $result['max_batch'] : null;
    }

    protected function isMigrationApplied(string $file): bool
    {
        $db = $this->app->make('db');
        $result = $db->select("SELECT * FROM `migrations` WHERE `migration` = ?", [$file]);
        return !empty($result);
    }

    protected function markMigrationAsApplied(string $file, int $batch): void
    {
        $db = $this->app->make('db');
        $db->insert("INSERT INTO `migrations` (`migration`, `batch`) VALUES (?, ?)", [$file, $batch]);
    }
}
