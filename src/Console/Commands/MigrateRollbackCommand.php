<?php

declare(strict_types=1);

namespace Velolia\Console\Commands;

use Velolia\Console\Command;
use Velolia\Database\Migration\Migrator;

class MigrateRollbackCommand extends Command
{
    protected function configure(): void
    {
        $this->name = 'migrate:rollback';
        $this->description = 'Rollback the last database migration batch or step by step.';
        
        $this->signature = [
            'arguments' => [],
            'options' => [
                'step' => [
                    'short' => 's',
                    'description' => 'Rollback the last N batches',
                ]
            ]
        ];
    }

    public function handle(): int
    {
        try {
            $migrator = new Migrator();

            $path = base_path('database/migrations');
            $step = (int) ($this->hasOption('step') ?? 0);

            $totalRolledBack = 0;

            if ($step > 0) {
                $this->output->warning("Rolling back {$step} batch(es)...");
                
                for ($i = 0; $i < $step; $i++) {
                    $rolledBack = $migrator->rollback($path, function($name) {
                        $this->output->success("Rolled back: {$name}");
                    });

                    if (empty($rolledBack)) {
                        if ($totalRolledBack === 0) {
                            $this->output->info("No migrations to rollback.");
                        } else {
                            $this->output->info("No more batches to rollback.");
                        }
                        break;
                    }

                    $totalRolledBack += count($rolledBack);
                }
            } else {
                $this->output->warning("Rolling back last batch...");
                
                $rolledBack = $migrator->rollback($path, function($name) {
                    $this->output->success("Rolled back: {$name}");
                });

                if (empty($rolledBack)) {
                    $this->output->info("No migrations to rollback.");
                } else {
                    $totalRolledBack += count($rolledBack);
                }
            }

            if ($totalRolledBack > 0) {
                $this->output->success("Rollback completed!");
            } else {
            }

            return 0;

        } catch (\Throwable $e) {
            $this->output->error("Rollback failed: {$e->getMessage()}");
            return 1;
        }
    }
}
