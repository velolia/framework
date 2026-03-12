<?php

declare(strict_types=1);

namespace Velolia\Database;

use Velolia\Database\Paginator;

class QueryBuilder
{
    protected string $table;
    protected ?string $modelClass = null;
    protected array $columns = ['*'];
    protected array $wheres = [];
    protected array $bindings = [];
    protected ?int $limit = null;
    protected ?int $offset = null;
    protected array $orders = [];
    protected array $with = [];
    protected array $joins = [];

    public function __construct(protected Connection $connection) {}

    public function table(string $table): self
    {
        $this->table = $table;
        return $this;
    }

    public function setModel(string $modelClass): self
    {
        $this->modelClass = $modelClass;
        return $this;
    }

    public function select(array $columns = ['*']): self
    {
        $this->columns = $columns;
        return $this;
    }

    public function where(string $column, mixed $operator, mixed $value = null): self
    {
        if (func_num_args() === 2) {
            $value = $operator;
            $operator = '=';
        }

        $this->wheres[] = [
            'type' => 'Basic',
            'column' => $column,
            'operator' => $operator,
            'value' => $value,
        ];

        $this->bindings[] = $value;

        return $this;
    }

    public function whereIn(string $column, array $values): self
    {
        $this->wheres[] = [
            'type' => 'In',
            'column' => $column,
            'values' => $values,
        ];

        foreach ($values as $value) {
            $this->bindings[] = $value;
        }

        return $this;
    }

    public function limit(int $value): self
    {
        $this->limit = $value;
        return $this;
    }

    public function offset(int $value): self
    {
        $this->offset = $value;
        return $this;
    }

    public function orderBy(string $column, string $direction = 'asc'): self
    {
        $this->orders[] = compact('column', 'direction');
        return $this;
    }

    public function with(array|string ...$relations): self
    {
        foreach ($relations as $relation) {
            if (is_array($relation)) {
                $this->with = array_merge($this->with, $relation);
            } else {
                $this->with[] = $relation;
            }
        }
        return $this;
    }

    public function join(string $table, string $first, string $operator, string $second, string $type = 'inner'): self
    {
        $this->joins[] = compact('table', 'first', 'operator', 'second', 'type');
        return $this;
    }

    public function get()
    {
        $results = $this->connection->select($this->toSql(), $this->bindings);

        if ($this->modelClass) {
            $class = $this->modelClass;
            $models = [];
            
            foreach ($results as $attributes) {
                $model = new $class();
                $model->setRawAttributes($attributes, true);
                $model->exists = true;
                $models[] = $model;
            }

            if (!empty($this->with) && !empty($models)) {
                $this->eagerLoadRelations($models);
            }

            return new \Velolia\Database\Collection($models);
        }

        return new \Velolia\Support\Collection($results);
    }

    public function paginate(int $perPage = 15): Paginator
    {
        $page = (int) ($_GET['page'] ?? 1);
        $total = $this->count();
        
        $this->limit($perPage)->offset(($page - 1) * $perPage);
        $items = $this->get();

        return new Paginator($items->all(), $total, $perPage, $page);
    }

    protected function eagerLoadRelations(array $models): void
    {
        foreach ($this->with as $relation) {
            $first = $models[0];
            if (method_exists($first, $relation)) {
                $relationObj = $first->$relation();
                if (method_exists($relationObj, 'eagerLoad')) {
                    $relationObj->eagerLoad($models, $relation);
                }
            }
        }
    }

    public function first(): mixed
    {
        $results = $this->limit(1)->get();
        return $results[0] ?? null;
    }

    public function insert(array $values): bool
    {
        $columns = implode(', ', array_map(fn($col) => "`{$col}`", array_keys($values)));
        $placeholders = implode(', ', array_fill(0, count($values), '?'));
        
        $sql = "INSERT INTO `{$this->table}` ({$columns}) VALUES ({$placeholders})";
        
        return $this->connection->insert($sql, array_values($values));
    }

    public function update(array $values): int
    {
        $sets = [];
        $bindings = [];

        foreach ($values as $column => $value) {
            $sets[] = "`{$column}` = ?";
            $bindings[] = $value;
        }

        $sql = "UPDATE `{$this->table}` SET " . implode(', ', $sets) . $this->compileWheres();
        
        return $this->connection->update($sql, array_merge($bindings, $this->bindings));
    }

    public function delete(): int
    {
        $sql = "DELETE FROM {$this->table}" . $this->compileWheres();
        return $this->connection->delete($sql, $this->bindings);
    }

    public function toSql(): string
    {
        $columns = array_map(function ($column) {
            if ($column === '*') return '*';
            if (str_contains($column, '(') || str_contains($column, ' as ')) return $column;
            return str_contains($column, '.') ? $column : "`{$column}`";
        }, $this->columns);

        $sql = "SELECT " . implode(', ', $columns) . " FROM `{$this->table}`";

        foreach ($this->joins as $join) {
            $sql .= " {$join['type']} JOIN `{$join['table']}` ON {$join['first']} {$join['operator']} {$join['second']}";
        }

        $sql .= $this->compileWheres();

        if (!empty($this->orders)) {
            $sql .= " ORDER BY " . implode(', ', array_map(function ($o) {
                $column = str_contains($o['column'], '.') ? $o['column'] : "`{$o['column']}`";
                return "{$column} {$o['direction']}";
            }, $this->orders));
        }

        if ($this->limit !== null) {
            $sql .= " LIMIT {$this->limit}";
        }

        if ($this->offset !== null) {
            $sql .= " OFFSET {$this->offset}";
        }

        return $sql;
    }

    protected function compileWheres(): string
    {
        if (empty($this->wheres)) {
            return '';
        }

        $sql = ' WHERE ';
        $parts = [];

        foreach ($this->wheres as $where) {
            $column = str_contains($where['column'], '.') ? $where['column'] : "`{$where['column']}`";
            
            if ($where['type'] === 'In') {
                $placeholders = implode(', ', array_fill(0, count($where['values']), '?'));
                $parts[] = "{$column} IN ({$placeholders})";
            } else {
                $parts[] = "{$column} {$where['operator']} ?";
            }
        }

        return $sql . implode(' AND ', $parts);
    }

    public function count(): int
    {
        $originalColumns = $this->columns;
        $this->columns = ['COUNT(*) as aggregate'];
        
        $sql = $this->toSql();
        $result = $this->connection->select($sql, $this->bindings);
        
        $this->columns = $originalColumns;
        
        return (int) ($result[0]['aggregate'] ?? 0);
    }

    public function getConnection(): Connection
    {
        return $this->connection;
    }
}
