<?php

declare(strict_types=1);

namespace Velolia\Console\Commands;

use Velolia\Console\Command;

class MakeSeederCommand extends Command
{
    protected function configure(): void
    {
        $this->name = 'make:seeder';
        $this->description = 'Create a new seeder class';
        
        $this->signature = [
            'arguments' => [
                'name' => [
                    'description' => 'The name of the seeder',
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
            $this->output->error('Seeder name is required!');
            $this->output->info('Usage: php pool make:seeder UserSeeder');
            return 1;
        }

        $this->generateSeeder($name);
        
        return 0;
    }
}