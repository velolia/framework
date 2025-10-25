<?php

declare(strict_types=1);

namespace Velolia\Console\Commands;

use Velolia\Console\Command;

class MakeControllerCommand extends Command
{
    protected function configure(): void
    {
        $this->name = 'make:controller';
        $this->description = 'Create a new controller class';
        
        $this->signature = [
            'arguments' => [
                'name' => [
                    'description' => 'The name of the controller',
                    'required' => true
                ]
            ],
            'options' => [
                'resource' => [
                    'short' => 'r',
                    'description' => 'Create a resource controller'
                ],
            ]
        ];
    }

    public function handle(): int
    {
        $name = $this->argument('name');

        if (!$name) {
            $this->output->error('Controller name is required!');
            $this->output->info('Usage: php pool make:controller UserController');
            return 1;
        }

        if ($this->hasOption('resource')) {
            $this->generateController($name, true);
        } else {
            $this->generateController($name);
        }
        
        return 0;
    }
}