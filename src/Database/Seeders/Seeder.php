<?php

declare(strict_types=1);

namespace Velolia\Database\Seeders;

use RuntimeException;
use Velolia\Support\Facades\DB;

abstract class Seeder
{
    /**
     * The class name of the seeder.
     * 
     * @var array
    */
    protected array $seedersToRun = [];

    /**
     * The classes that have been seeded.
     * 
     * @var array
    */
    protected array $seededClasses = [];

    /**
     * Run the database seeds.
     *
     * @return void
    */
    abstract public function run(): void;

    /**
     * Insert a single row into the database.
     *
     * @param string $table
     * @param array $data
     * @return bool
     */
    protected function insert(string $table, array $data): bool
    {
        return DB::table($table)->insert($data);
    }

    /**
     * Truncate a table.
     *
     * @param string $table
     * @return bool
     */
    protected function truncate(string $table): bool
    {
        return DB::table($table)->truncate();
    }

    /**
     * Call another seeder.
     *
     * @param string $seeder
     * @return void
     */
    public function call(string|array $seeder): void
    {
        $seeders = is_array($seeder) ? $seeder : [$seeder];

        foreach ($seeders as $seedClass) {
            if (!class_exists($seedClass)) {
                throw new RuntimeException("Seeder class not found: $seedClass");
            }
            $this->validateSeederClass($seedClass);

            $instance = new $seedClass();

            $instance->run();

            $this->seededClasses[] = $this->getShortClassName($seedClass);

            echo "Seeded: " . $this->getShortClassName($seedClass) . PHP_EOL;
        }
    }

    /**
     * Validate that the provided class is a valid seeder.
     *
     * @param class-string $class
     * @throws RuntimeException
     */
    private function validateSeederClass(string $class): void
    {
        if (!is_subclass_of($class, self::class)) {
            throw new RuntimeException("Class $class must extend " . self::class);
        }
    }

    /**
     * Get the short class name from a fully qualified class name.
     *
     * @param string $className
     * @return string
     */
    private function getShortClassName(string $className): string
    {
        return basename(str_replace('\\', '/', $className));
    }

    /**
     * Get the classes that have been seeded.
     *
     * @return array
     */
    public function getSeededClasses(): array
    {
        return $this->seededClasses;
    }
}