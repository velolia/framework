<?php

declare(strict_types=1);

namespace Velolia\Database;

use Velolia\Database\Query\Expression;
use Velolia\Foundation\Application;

class DatabaseManager
{
    protected Application $app;
    protected ConnectionFactory $factory;
    protected array $connections = [];
    protected array $config = [];

    public function __construct(Application $app, ConnectionFactory $factory)
    {
        $this->factory = $factory;
        $this->config = $app['config']->get('database');
    }

    public function raw(string $value): Expression
    {
        return new Expression($value);
    }

    public function table(string $table): Query\Builder
    {
        return $this->connection()->table($table);
    }

    public function connection(?string $name = null): Connection
    {
        $name = $name ?: $this->getDefaultConnection();

        if (isset($this->connections[$name])) {
            return $this->connections[$name];
        }

        $config = $this->getConnectionConfig($name);
        $this->connections[$name] = $this->factory->make($config);
        return $this->connections[$name];
    }

    public function getDefaultConnection(): string
    {
        return $this->config['default'];
    }

    public function setDefaultConnection(string $name): void
    {
        $this->config['default'] = $name;
    }

    protected function getConnectionConfig(string $name): array
    {
        if (!isset($this->config['connections'][$name])) {
            throw new \InvalidArgumentException("Database [$name] is not configured.");
        }
        return $this->config['connections'][$name];
    }

    public function __call(string $method, array $args): mixed
    {
        return $this->connection()->$method(...$args);
    }
}