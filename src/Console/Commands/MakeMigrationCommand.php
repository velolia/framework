<?php

declare(strict_types=1);

namespace Velolia\Console\Commands;

use Velolia\Console\Command;

class MakeMigrationCommand extends Command
{
    protected function configure(): void
    {
        $this->name = 'make:migration';
        $this->description = 'Create a new migration file';
        
        $this->signature = [
            'arguments' => [
                'name' => [
                    'description' => 'The name of the migration',
                    'required' => true
                ]
            ],
            'options' => []
        ];
    }

    public function handle(): int
    {
        $name = $this->argument('name');

        if (!$name) {
            $this->output->error('Migration name is required!');
            $this->output->info('Usage: php pool make:migration create_users_table');
            return 1;
        }

        $this->generateMigration($name);

        return 0;
    }
}