<?php

namespace Velolia\Console\Commands;

use Velolia\Console\Command;

class MakeModelCommand extends Command
{
    protected function configure(): void
    {
        $this->name = 'make:model';
        $this->description = 'Create a new model class';
        
        $this->signature = [
            'arguments' => [
                'name' => [
                    'description' => 'The name of the model',
                    'required' => true
                ]
            ],
            'options' => [
                'migration' => [
                    'short' => 'm',
                    'description' => 'Create a migration file for the model'
                ],
                'controller' => [
                    'short' => 'c',
                    'description' => 'Create a controller for the model'
                ],
                'resource' => [
                    'short' => 'r',
                    'description' => 'Create a resource controller'
                ],
                'all' => [
                    'short' => 'a',
                    'description' => 'Generate model, migration, and resource controller'
                ]
            ]
        ];
    }

    public function handle(): int
    {
        $name = $this->argument('name');

        if (!$name) {
            $this->output->error('Model name is required!');
            $this->output->info('Usage: php pool make:model User [-m] [-c] [-r] [-a]');
            return 1;
        }

        // Generate model
        $this->generateModel($name);

        // Generate migration if -m or --migration
        if ($this->hasOption('migration') || $this->hasOption('all')) {
            $this->generateMigration($name);
        }

        // Generate controller if -c or --controller
        if ($this->hasOption('controller') || $this->hasOption('all')) {
            $isResource = $this->hasOption('resource') || $this->hasOption('all');
            $this->generateController($name, $isResource);
        }

        // $this->output->newLine();
        // $this->output->success('All files generated successfully!');

        return 0;
    }
}
