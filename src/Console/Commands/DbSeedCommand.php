<?php

declare(strict_types=1);

namespace Velolia\Console\Commands;

use Database\Seeders\DatabaseSeeder;
use Exception;
use Velolia\Console\Command;
use Velolia\Support\Facades\DB;

class DbSeedCommand extends Command
{
    protected function configure(): void
    {
        $this->name = 'db:seed';
        $this->description = 'Seed the database with records';

        $this->signature = [
            'arguments' => [],
            'options' => [
                'class' => [
                    'short' => 'c',
                    'description' => 'The seeder class to run (e.g. --class=UserSeeder)',
                ],
                'force' => [
                    'short' => 'f',
                    'description' => 'Force the operation to run without confirmation',
                ],
            ],
        ];
    }

    public function handle(): int
    {
        $class = $this->option('class');

        if ($class) {
            return $this->runSpecificSeeder($class);
        }

        return $this->runAllSeeders();
    }

    protected function runSpecificSeeder(string $class): int
    {
        $fqcn = $this->resolveSeederNamespace($class);

        if (!class_exists($fqcn)) {
            $this->output->error("Seeder class '{$fqcn}' not found.");
            return 1;
        }

        try {
            DB::beginTransaction();

            $this->output->info("Running {$fqcn}...");
            $seeder = new $fqcn();
            $seeder->run();

            DB::commit();

            $this->output->success("Seeder '{$fqcn}' completed.");
            return 0;
        } catch (Exception $e) {
            DB::rollBack();
            $this->output->error("Seeder '{$fqcn}' failed: " . $e->getMessage());
            return 1;
        }
    }

    protected function runAllSeeders(): int
    {
        if (!class_exists(DatabaseSeeder::class)) {
            $this->output->warning("No DatabaseSeeder class found.");
            return 0;
        }

        try {
            DB::beginTransaction();
            $this->output->info("Running database seeders...");
            $databaseSeeder = new DatabaseSeeder();
            $databaseSeeder->run();
            DB::commit();

            $this->output->success("Database seeders completed successfully.");
            return 0;
        } catch (Exception $e) {
            DB::rollBack();
            $this->output->error("Database seeding failed: " . $e->getMessage());
            return 1;
        }
    }

    protected function resolveSeederNamespace(string $class): string
    {
        if (str_contains($class, '\\')) {
            return $class;
        }

        return "Database\\Seeders\\{$class}";
    }
}