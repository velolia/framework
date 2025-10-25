<?php

declare(strict_types=1);

namespace Velolia\Console\Commands;

use Velolia\Console\Command;
use Velolia\Database\Migration\Migrator;

class MigrateCommand extends Command
{
    protected function configure(): void
    {
        $this->name = 'migrate';
        $this->description = 'Run the database migrations';
        
        $this->signature = [
            'arguments' => [],
            'options' => []
        ];
    }

    public function handle(): int
    {
        try {
            $migrator = new Migrator();
            $path = base_path('database/migrations');
            
            $this->output->warning("Running migrations...");
            
            $migrated = $migrator->run($path, function($name) {
                $this->output->success("Migrated: {$name}");
            });
            
            if (empty($migrated)) {
                $this->output->info("Nothing to migrate.");
            }
            
            return 0;
            
        } catch (\Throwable $e) {
            $this->output->error("Migration failed: {$e->getMessage()}");
            return 1;
        }
    }
}