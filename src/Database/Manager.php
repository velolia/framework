<?php

declare(strict_types=1);

namespace Velolia\Database;

use PDO;
use Exception;
use Velolia\Core\Application;

class Manager
{
    protected array $connections = [];
    protected array $config;

    public function __construct(protected Application $app)
    {
        $this->config = $app->make('config')->get('database');
    }

    public function connection(?string $name = null): Connection
    {
        $name = $name ?: $this->config['default'];

        if (!isset($this->connections[$name])) {
            $this->connections[$name] = $this->makeConnection($name);
        }

        return $this->connections[$name];
    }

    protected function makeConnection(string $name): Connection
    {
        $config = $this->config['connections'][$name] ?? null;

        if (!$config) {
            throw new Exception("Database connection [$name] not configured.");
        }

        return new Connection($this->createPdo($config), $config);
    }

    protected function createPdo(array $config): PDO
    {
        $driver = $config['driver'];

        switch ($driver) {
            case 'mysql':
                $dsn = "mysql:host={$config['host']};port={$config['port']};dbname={$config['database']};charset={$config['charset']}";
                return new PDO($dsn, $config['username'], $config['password'], [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ]);
            case 'sqlite':
                $dsn = "sqlite:{$config['database']}";
                return new PDO($dsn, null, null, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                ]);
            default:
                throw new Exception("Unsupported driver [$driver].");
        }
    }

    public function __call(string $method, array $parameters)
    {
        return $this->connection()->$method(...$parameters);
    }
}
