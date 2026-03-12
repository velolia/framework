<?php

declare(strict_types=1);

namespace Velolia\Database\Schema;

use Velolia\Support\Facades\Facade;
use Closure;

class Schema
{
    protected static function getConn()
    {
        return Facade::getFacadeApplication()->make('db');
    }

    public static function create(string $table, Closure $callback): void
    {
        $blueprint = new Blueprint($table);
        $callback($blueprint);

        $sql = (new static)->buildCreateSql($blueprint);
        static::getConn()->getPdo()->exec($sql);
    }

    public static function dropIfExists(string $table): void
    {
        $sql = "DROP TABLE IF EXISTS `{$table}`";
        static::getConn()->getPdo()->exec($sql);
    }

    protected function buildCreateSql(Blueprint $blueprint): string
    {
        $table = $blueprint->getTable();
        $definitions = [];
        
        foreach ($blueprint->getColumns() as $col) {
            if ($col['type'] === 'unique') {
                $cols = implode('`, `', $col['columns']);
                $definitions[] = "UNIQUE (`{$cols}`)";
                continue;
            }

            if ($col['type'] === 'index') {
                $cols = implode('`, `', $col['columns']);
                $definitions[] = "INDEX (`{$cols}`)";
                continue;
            }

            $columnSql = "`{$col['name']}` " . $this->mapType($col['type']);
            
            if ($col['type'] === 'enum') {
                $options = implode("', '", $col['allowed']);
                $columnSql .= "('{$options}')";
            } elseif ($col['type'] === 'decimal') {
                $columnSql .= "({$col['precision']}, {$col['scale']})";
            } elseif (isset($col['length'])) {
                $columnSql .= "({$col['length']})";
            }
            
            if (isset($col['unsigned']) && $col['unsigned']) {
                $columnSql .= " UNSIGNED";
            }
            
            if (!$col['nullable']) {
                $columnSql .= " NOT NULL";
            }
            
            if (isset($col['auto_increment']) && $col['auto_increment']) {
                $columnSql .= " AUTO_INCREMENT";
            }
            
            if (isset($col['primary']) && $col['primary']) {
                $columnSql .= " PRIMARY KEY";
            }

            if (isset($col['unique']) && $col['unique']) {
                $columnSql .= " UNIQUE";
            }

            if ($col['default'] !== null) {
                if (is_bool($col['default'])) {
                    $default = $col['default'] ? '1' : '0';
                } elseif (is_numeric($col['default'])) {
                    $default = $col['default'];
                } else {
                    $default = "'{$col['default']}'";
                }
                $columnSql .= " DEFAULT {$default}";
            }
            
            $definitions[] = $columnSql;
        }

        foreach ($blueprint->getForeignKeys() as $fk) {
            $fkSql = "FOREIGN KEY (`{$fk['column']}`) REFERENCES `{$fk['on']}`(`{$fk['references']}`)";
            if ($fk['onDelete']) {
                $fkSql .= " ON DELETE {$fk['onDelete']}";
            }
            $definitions[] = $fkSql;
        }

        $definitionList = implode(",\n            ", $definitions);
        
        return "CREATE TABLE `{$table}` (
            {$definitionList}
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
    }

    protected function mapType(string $type): string
    {
        return match ($type) {
            'bigint' => "BIGINT",
            'string' => "VARCHAR",
            'text' => "TEXT",
            'integer' => "INT",
            'boolean' => "TINYINT(1)",
            'timestamp' => "TIMESTAMP NULL",
            'enum' => "ENUM",
            'date' => "DATE",
            'decimal' => "DECIMAL",
            default => "TEXT",
        };
    }
}
