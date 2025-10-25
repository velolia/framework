<?php

declare(strict_types=1);

namespace Velolia\Console\Commands;

use Velolia\Console\Command;
use Velolia\Database\Migration\Migrator;

class MigrateFreshCommand extends Command
{
    protected function configure(): void
    {
        $this->name = 'migrate:fresh';
        $this->description = 'Drop all tables and re-run all migrations';
        
        $this->signature = [
            'arguments' => [],
            'options' => [
                'seed' => [
                    'short' => 's',
                    'description' => 'Run seeders after migration',
                ]
            ]
        ];
    }

    public function handle(): int
    {
        try {
            $this->output->newLine();
            $this->output->error("  WARNING: This will DROP ALL TABLES and data will be lost!");
            $this->output->newLine();
            
            if (!$this->confirm("Are you sure you want to continue?")) {
                $this->output->info("Operation cancelled.");
                return 0;
            }
            
            $this->output->newLine();
            
            $migrator = new Migrator();
            
            // Drop all tables
            $this->output->warning("Dropping all tables...");
            $count = $migrator->dropAllTables();
            
            if ($count > 0) {
                $this->output->success("Dropped {$count} tables!");
            } else {
                $this->output->info("No tables to drop.");
            }
            
            // Run migrations
            $this->output->newLine();
            $this->output->warning("Running migrations...");
            
            $path = base_path('database/migrations');
            
            // Debug: Check migration files
            $files = glob($path . '/*.php');
            if ($files === false || empty($files)) {
                $this->output->error("No migration files found in: {$path}");
                return 1;
            }
            
            $this->output->info("Found " . count($files) . " migration file(s)");
            
            // Create migrator and run
            // $migrator = new Migrator($db, false);
            $migrator->ensureMigrationsTable();
            
            $migrated = $migrator->run($path, function($name) {
                $this->output->success("Migrated: {$name}");
            });
            
            if (empty($migrated)) {
                $this->output->warning("No migrations were executed.");
            }
            
            // Run seeders if option provided
            if ($this->hasOption('seed')) {
                $this->output->newLine();
                $this->output->warning("Running seeders...");
                // TODO: Implement seeder runner
                // $this->output->info("Seeder functionality not yet implemented.");
            }
            
            $this->output->newLine();
            $this->output->comment("Fresh migration completed!");
            
            return 0;
            
        } catch (\Throwable $e) {
            $this->output->error("Migration failed: {$e->getMessage()}");
            $this->output->error("Stack trace: " . $e->getTraceAsString());
            return 1;
        }
    }
}