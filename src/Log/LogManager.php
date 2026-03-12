<?php

declare(strict_types=1);

namespace Velolia\Log;

use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Velolia\Core\Application;

class LogManager
{
    protected array $drivers = [];

    public function __construct(protected Application $app) {}

    public function driver(?string $driver = null): LoggerInterface
    {
        $driver = $driver ?: $this->getDefaultDriver();

        if (!isset($this->drivers[$driver])) {
            $this->drivers[$driver] = $this->createDriver($driver);
        }

        return $this->drivers[$driver];
    }

    protected function createDriver(string $driver): LoggerInterface
    {
        return match ($driver) {
            'file' => new FileLogger($this->app->storagePath('logs')),
            default => throw new InvalidArgumentException("Driver [{$driver}] is not supported."),
        };
    }

    protected function getDefaultDriver(): string
    {
        return $this->app->make('config')->get('logging.default', 'file');
    }

    public function __call(string $method, array $parameters): mixed
    {
        return $this->driver()->$method(...$parameters);
    }
}
