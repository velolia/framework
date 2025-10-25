<?php

declare(strict_types=1);

namespace Velolia\Database;

use PDO;
use PDOException;

class ConnectionFactory
{
    public function make(array $config): Connection
    {
        $driver = $config['driver'] ?? 'mysql';

        switch ($driver) {
            case 'mysql':
                return $this->createMysqlConnection($config);
                break;
            case 'sqlite':
                return $this->createSqliteConnection($config);
                break;
            default:
                throw new \InvalidArgumentException("Driver [$driver] is not supported.");
        }
    }

    protected function createMysqlConnection(array $config): Connection
    {
        $dsn = $this->createMysqlDsn($config);
        $options = $this->getDefaultOptions($config);
        $pdo = $this->createPdoConnection($dsn, $config, $options);

        if (isset($config['charset'])) {
            $pdo->prepare("SET NAMES {$config['charset']}")->execute();
        }

        if (isset($config['timezone'])) {
            $pdo->prepare("SET time_zone = '{$config['timezone']}'")->execute();
        }

        return new Connection($pdo, $config);
    }

    protected function createSqliteConnection(array $config): Connection
    {
        $dsn = $this->createSqliteDsn($config);
        $options = $this->getDefaultOptions($config);
        
        $pdo = $this->createPdoConnection($dsn, $config, $options);

        return new Connection($pdo, $config);
    }

    protected function createMysqlDsn(array $config): string
    {
        $host = $config['host'] ?? 'localhost';
        $port = $config['port'] ?? 3306;
        $database = $config['database'] ?? '';
        $charset = $config['charset'] ?? 'utf8';

        return "mysql:host={$host};port={$port};dbname={$database};charset={$charset}";
    }

    protected function createSqliteDsn(array $config): string
    {
        $database = $config['database'] ?? ':memory:';
        
        if ($database !== ':memory:') {
            $directory = dirname($database);
            if (!is_dir($directory)) {
                mkdir($directory, 0755, true);
            }
        }

        return "sqlite:{$database}";
    }

    protected function createPdoConnection(string $dsn, array $config, array $options): PDO
    {
        $username = $config['username'] ?? null;
        $password = $config['password'] ?? null;

        try {
            return new PDO($dsn, $username, $password, $options);
        } catch (PDOException $e) {
            throw new \RuntimeException(
                "Failed to connect to database: {$e->getMessage()}"
            );
        }
    }

    protected function getDefaultOptions(array $config): array
    {
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];

        if (isset($config['options']) && is_array($config['options'])) {
            $options = array_merge($options, $config['options']);
        }

        return $options;
    }
}