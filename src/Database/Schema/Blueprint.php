<?php

declare(strict_types=1);

namespace Velolia\Database\Schema;

class Blueprint
{
    protected array $columns = [];
    protected array $foreignKeys = [];

    public function __construct(protected string $table) {}

    public function getTable(): string
    {
        return $this->table;
    }

    public function id(string $name = 'id'): self
    {
        return $this->bigIncrements($name);
    }

    public function bigIncrements(string $name): self
    {
        return $this->addColumn('bigint', $name, ['auto_increment' => true, 'primary' => true, 'unsigned' => true]);
    }

    public function unsignedBigInteger(string $name): self
    {
        return $this->addColumn('bigint', $name, ['unsigned' => true]);
    }

    public function string(string $name, int $length = 255): self
    {
        return $this->addColumn('string', $name, ['length' => $length]);
    }

    public function text(string $name): self
    {
        return $this->addColumn('text', $name);
    }

    public function enum(string $name, array $allowed): self
    {
        return $this->addColumn('enum', $name, ['allowed' => $allowed]);
    }

    public function date(string $name): self
    {
        return $this->addColumn('date', $name);
    }

    public function decimal(string $name, int $precision = 8, int $scale = 2): self
    {
        return $this->addColumn('decimal', $name, ['precision' => $precision, 'scale' => $scale]);
    }

    public function integer(string $name): self
    {
        return $this->addColumn('integer', $name);
    }

    public function boolean(string $name): self
    {
        return $this->addColumn('boolean', $name);
    }

    public function timestamps(): void
    {
        $this->addColumn('timestamp', 'created_at', ['nullable' => true]);
        $this->addColumn('timestamp', 'updated_at', ['nullable' => true]);
    }

    public function timestamp(string $name): self
    {
        return $this->addColumn('timestamp', $name);
    }

    public function morphs(string $name): self
    {
        $this->addColumn('bigint', "{$name}_id", ['unsigned' => true]);
        $this->addColumn('string', "{$name}_type", ['length' => 255]);
        $this->index(["{$name}_id", "{$name}_type"]);
        return $this;
    }

    public function foreignId(string $name): self
    {
        return $this->addColumn('bigint', $name, ['unsigned' => true]);
    }

    public function nullable(): self
    {
        if (!empty($this->columns)) {
            $this->columns[count($this->columns) - 1]['nullable'] = true;
        }
        return $this;
    }

    public function unique(string|array|null $columns = null): self
    {
        if (is_array($columns)) {
            $this->columns[] = [
                'type' => 'unique',
                'columns' => $columns,
            ];
            return $this;
        }

        if ($columns !== null && is_string($columns)) {
             $this->columns[] = [
                 'type' => 'unique',
                 'columns' => [$columns],
             ];
             return $this;
        }

        if (!empty($this->columns)) {
            $this->columns[count($this->columns) - 1]['unique'] = true;
        }
        return $this;
    }

    public function index(string|array $columns): self
    {
        $this->columns[] = [
            'type' => 'index',
            'columns' => (array) $columns,
        ];
        return $this;
    }

    public function default(mixed $value): self
    {
        if (!empty($this->columns)) {
            $this->columns[count($this->columns) - 1]['default'] = $value;
        }
        return $this;
    }

    public function constrained(?string $table = null, string $column = 'id'): self
    {
        $lastColIndex = -1;
        for ($i = count($this->columns) - 1; $i >= 0; $i--) {
            if (!in_array($this->columns[$i]['type'], ['unique', 'index'])) {
                $lastColIndex = $i;
                break;
            }
        }

        if ($lastColIndex === -1) {
            return $this;
        }

        $lastColumn = $this->columns[$lastColIndex]['name'];
        
        if ($table === null) {
            $table = str_replace('_id', '', $lastColumn);

            if ($table === 'user') $table = 'users';
            elseif ($table === 'category') $table = 'categories';
            elseif ($table === 'subject') $table = 'subjects';
            elseif ($table === 'classroom') $table = 'classrooms';
            elseif ($table === 'kategori') $table = 'kategori';
            elseif (!str_ends_with($table, 's')) $table .= 's';
        }

        $this->foreignKeys[] = [
            'column' => $lastColumn,
            'references' => $column,
            'on' => $table,
            'onDelete' => null,
        ];

        return $this;
    }

    public function cascadeOnDelete(): self
    {
        if (!empty($this->foreignKeys)) {
            $this->foreignKeys[count($this->foreignKeys) - 1]['onDelete'] = 'CASCADE';
        }
        return $this;
    }

    public function nullOnDelete(): self
    {
        if (!empty($this->foreignKeys)) {
            $this->foreignKeys[count($this->foreignKeys) - 1]['onDelete'] = 'SET NULL';
            $colName = $this->foreignKeys[count($this->foreignKeys) - 1]['column'];
            foreach ($this->columns as &$col) {
                if (isset($col['name']) && $col['name'] === $colName) {
                    $col['nullable'] = true;
                    break;
                }
            }
        }
        return $this;
    }

    protected function addColumn(string $type, string $name, array $parameters = []): self
    {
        $this->columns[] = array_merge([
            'type' => $type,
            'name' => $name,
            'nullable' => false,
            'default' => null,
        ], $parameters);

        return $this;
    }

    public function getColumns(): array
    {
        return $this->columns;
    }

    public function getForeignKeys(): array
    {
        return $this->foreignKeys;
    }
}
