<?php

declare(strict_types=1);

namespace Velolia\Console\Commands;

use Velolia\Console\Command;
use Velolia\Support\Facades\Facade;

class MigrateFreshCommand extends Command
{
    protected string $signature = 'migrate:fresh [--seed]';
    protected string $description = 'Drop all tables and re-run all migrations';

    public function handle(array $args): int
    {
        $this->ensureDatabaseExists();
        
        $db = $this->app->make('db');
        $pdo = $db->getPdo();

        $this->comment('Dropping all tables...');

        $tables = $db->select("SHOW TABLES");

        $pdo->exec("SET FOREIGN_KEY_CHECKS = 0;");

        foreach ($tables as $table) {
            $tableName = array_values($table)[0];
            $pdo->exec("DROP TABLE `{$tableName}`");
        }

        $pdo->exec("SET FOREIGN_KEY_CHECKS = 1;");

        $this->success('Dropped all tables successfully.');

        $command = new MigrateCommand($this->app);
        $status = $command->handle([]);

        if ($status === 0 && in_array('--seed', $args)) {
            $seedCommand = new DbSeedCommand($this->app);
            $status = $seedCommand->handle([]);
        }

        return $status;
    }
}
