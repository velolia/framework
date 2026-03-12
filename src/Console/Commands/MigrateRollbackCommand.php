<?php

declare(strict_types=1);

namespace Velolia\Console\Commands;

use Velolia\Console\Command;

class MigrateRollbackCommand extends Command
{
    protected string $signature = 'migrate:rollback';
    protected string $description = 'Rollback the last database migration';

    public function handle(array $args): int
    {
        $directory = $this->app->basePath() . '/database/migrations';
        
        $this->comment('Rolling back last batch of migrations...');

        $migrator = new \Velolia\Database\Schema\Migrator($this->app);
        $rolledBack = $migrator->rollback($directory);

        if (empty($rolledBack)) {
            $this->info('Nothing to rollback.');
            return 0;
        }

        foreach ($rolledBack as $file) {
            $this->success("Rolled back:  {$file}");
        }

        return 0;
    }
}
