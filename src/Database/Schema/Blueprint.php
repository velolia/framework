<?php

declare(strict_types=1);

namespace Velolia\Database\Schema;

class Blueprint
{
    protected string $table;
    /** @var Column[] */
    protected array $columns = [];
    protected array $primaryKeys = [];

    public function __construct(string $table)
    {
        $this->table = $table;
    }

    protected function addColumn(string $type, string $name, ?string $params = null, bool $autoIncrement = false): Column
    {
        $column = new Column($type, $name, $params);

        if ($autoIncrement) {
            $column->autoIncrement();
            $this->primaryKeys[] = $name;
        }

        $this->columns[] = $column;
        return $column;
    }

    public function id(string $name = 'id'): Column { return $this->addColumn("BIGINT UNSIGNED", $name, null, true); }
    public function foreignId(string $name): Column { return $this->addColumn("BIGINT UNSIGNED", $name); }
    public function string(string $name, int $length = 255): Column { return $this->addColumn("VARCHAR", $name, (string)$length); }
    public function text(string $name): Column { return $this->addColumn("TEXT", $name); }
    public function integer(string $name): Column { return $this->addColumn("INT", $name); }
    public function decimal(string $name, int $total = 8, int $places = 2): Column { return $this->addColumn("DECIMAL", $name, "{$total},{$places}"); }
    public function float(string $name, int $total = 8, int $places = 2): Column { return $this->addColumn("FLOAT", $name, "{$total},{$places}"); }
    public function double(string $name, int $total = 8, int $places = 2): Column { return $this->addColumn("DOUBLE", $name, "{$total},{$places}"); }

    public function timestamps(): void
    {
        $this->addColumn("TIMESTAMP", "created_at")->nullable();
        $this->addColumn("TIMESTAMP", "updated_at")->nullable();
    }

    public function toSql(): string
    {
        $columnSql = [];
        $indexes = [];
        $foreignKeys = [];

        foreach ($this->columns as $col) {
            $columnSql[] = $col->toSql();

            // unique index
            if ($col->isUnique) {
                $indexes[] = "UNIQUE KEY `{$this->table}_{$col->name}_unique` (`{$col->name}`)";
            }

            // foreign key index + constraint
            if ($col->references && $col->on) {
                $indexes[] = "KEY `{$this->table}_{$col->name}_foreign` (`{$col->name}`)";

                if ($fk = $col->foreignKeySql($this->table)) {
                    $foreignKeys[] = $fk;
                }
            }
        }

        if (!empty($this->primaryKeys)) {
            $columnSql[] = "PRIMARY KEY(`" . implode("`,`", $this->primaryKeys) . "`)";
        }

        $all = array_merge($columnSql, $indexes, $foreignKeys);

        return "CREATE TABLE `{$this->table}` (" . implode(", ", $all) . ") ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
    }

}
