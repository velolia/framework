<?php

declare(strict_types=1);

namespace Velolia\Database;

use Velolia\Core\Application;

abstract class Seeder
{
    /**
     * Create a new seeder instance.
     */
    public function __construct(protected Application $app) {}

    /**
     * Run the database seeds.
     */
    abstract public function run(): void;

    /**
     * Run the given seeder class.
     */
    public function call(string $class): void
    {
        $seeder = new $class($this->app);
        $seeder->run();
    }
}
