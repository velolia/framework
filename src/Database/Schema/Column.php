<?php

declare(strict_types=1);

namespace Velolia\Database\Schema;

class Column
{
    public string $name;
    public string $type;
    public ?string $params = null;
    public bool $autoIncrement = false;
    public bool $nullable = false;
    public bool $unique = false;
    public bool $isUnique = false;

    protected ?string $default = null;
    protected bool $defaultIsRaw = false;

    public ?string $references = null;
    public ?string $on = null;
    public bool $cascadeOnDelete = false;

    public function __construct(string $type, string $name, ?string $params = null)
    {
        $this->type = $type;
        $this->name = $name;
        $this->params = $params;
    }

    public function nullable(): self { $this->nullable = true; return $this; }
    public function unique(): self { $this->isUnique = true; return $this; }

    public function default(string $value): self { $this->default = $value; $this->defaultIsRaw = false; return $this; }
    public function defaultRaw(string $expression): self { $this->default = $expression; $this->defaultIsRaw = true; return $this; }

    public function autoIncrement(): self { $this->autoIncrement = true; return $this; }

    public function constrained(string $table, string $column = 'id'): self
    {
        $this->references = $column;
        $this->on = $table;
        return $this;
    }

    public function cascadeOnDelete(): self
    {
        $this->cascadeOnDelete = true;
        return $this;
    }

    public function toSql(): string
    {
        $sql = "`{$this->name}` {$this->type}";

        if ($this->params) {
            $sql .= "({$this->params})";
        }

        if ($this->autoIncrement) {
            $sql .= " AUTO_INCREMENT";
        }

        $sql .= $this->nullable ? " NULL" : " NOT NULL";

        if ($this->default !== null) {
            if ($this->defaultIsRaw) {
                $sql .= " DEFAULT {$this->default}";
            } else {
                $sql .= " DEFAULT '" . str_replace("'", "\\'", $this->default) . "'";
            }
        }

        return $sql;
    }

    /**
     * Build foreign key clause. We accept $tableName so we can create Laravel-like constraint name.
     */
    public function foreignKeySql(?string $tableName = null): ?string
    {
        if ($this->references && $this->on) {
            $constraintName = $tableName ? "{$tableName}_{$this->name}_foreign" : null;
            $constraintPart = $constraintName ? "CONSTRAINT `{$constraintName}` " : "";
            $sql = $constraintPart . "FOREIGN KEY (`{$this->name}`) REFERENCES `{$this->on}`(`{$this->references}`)";

            if ($this->cascadeOnDelete) {
                $sql .= " ON DELETE CASCADE";
            }

            return $sql;
        }

        return null;
    }
}
