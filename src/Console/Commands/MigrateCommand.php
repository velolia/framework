<?php

declare(strict_types=1);

namespace Velolia\Console\Commands;

use Velolia\Console\Command;

class MigrateCommand extends Command
{
    protected string $signature = 'migrate';
    protected string $description = 'Run the database migrations';

    public function handle(array $args): int
    {
        $directory = $this->app->basePath() . '/database/migrations';
        
        $this->ensureDatabaseExists();

        $this->comment('Running migrations...');

        $migrator = new \Velolia\Database\Schema\Migrator($this->app);
        $migrated = $migrator->run($directory);

        if (empty($migrated)) {
            $this->info('Nothing to migrate.');
            return 0;
        }

        foreach ($migrated as $file) {
            $this->success("Migrated:  {$file}");
        }

        return 0;
    }
}
