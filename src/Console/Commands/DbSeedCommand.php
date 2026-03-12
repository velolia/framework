<?php

declare(strict_types=1);

namespace Velolia\Console\Commands;

use Velolia\Console\Command;

class DbSeedCommand extends Command
{
    protected string $signature = 'db:seed {--class=DatabaseSeeder}';
    protected string $description = 'Seed the database with records';

    public function handle(array $args): int
    {
        $class = 'Database\\Seeders\\DatabaseSeeder';
        
        foreach ($args as $arg) {
            if (str_starts_with($arg, '--class=')) {
                $class = substr($arg, 8);
                if (!str_contains($class, '\\')) {
                    $class = 'Database\\Seeders\\' . $class;
                }
            }
        }

        if (!class_exists($class)) {
            $this->error("Seeder class [{$class}] not found.");
            return 1;
        }

        $this->comment("Seeding database...");

        $seeder = new $class($this->app);
        $seeder->run();

        $this->success("Database seeded successfully.");

        return 0;
    }
}
