<?php

declare(strict_types=1);

namespace Velolia\Database\Query;

use Closure;
use Velolia\Database\Connection;
use Velolia\Support\Collection;

class Builder
{
    protected Connection $connection;
    protected string $table;
    protected array $select = ['*'];
    protected array $wheres = [];
    protected array $bindings = [];
    protected array $join = [];
    protected array $orders = [];
    protected array $groups = [];
    protected ?int $limit = null;
    protected ?int $offset = null;

    public function __construct(Connection $connection, string $table)
    {
        $this->connection = $connection;
        $this->table = $table;
    }

    public function get(): Collection
    {
        $sql = $this->buildSelectSql();
        $results = $this->connection->select($sql, $this->bindings);
        return new Collection($results);
    }

    public function select(array|string|Expression $columns = ['*']): self
    {
        $columns = is_array($columns) ? $columns : func_get_args();

        if ($this->select === ['*']) {
            $this->select = [];
        }

        $this->select = array_merge($this->select, $columns);

        return $this;
    }

    public function where(string|array $column, mixed $operator = '=', mixed $value = null): self
    {
        if (is_array($column)) {
            foreach ($column as $key => $val) {
                $this->where($key, '=', $val);
            }
            return $this;
        }

        if (func_num_args() === 2) {
            $value = $operator;
            $operator = '=';
        }

        $this->wheres[] = [
            'type' => 'basic',
            'column' => $column,
            'operator' => $operator,
            'value' => $value,
            'boolean' => 'and',
        ];

        if (! $this->isExpression($value)) {
            $this->bindings[] = $value;
        }

        return $this;
    }

    public function whereIn(string $column, array $values): self
    {
        $this->wheres[] = [
            'type' => 'in',
            'column' => $column,
            'values' => $values,
            'boolean' => 'and',
        ];

        $this->bindings = array_merge($this->bindings, $values);

        return $this;
    }

    public function orderBy(string|Expression $column, string $direction = 'asc'): self
    {
        $this->orders[] = [
            'column' => $column,
            'direction' => strtoupper($direction),
        ];

        return $this;
    }

    public function groupBy(string|array ...$groups): self
    {
        foreach ($groups as $group) {
            if (is_array($group)) {
                $this->groups = array_merge($this->groups, $group);
            } else {
                $this->groups[] = $group;
            }
        }
        
        return $this;
    }

    public function orderByAsc(string $column): self
    {
        return $this->orderBy($column, 'asc');
    }

    public function orderByDesc(string $column): self
    {
        return $this->orderBy($column, 'desc');
    }

    public function latest(): self
    {
        return $this->orderByDesc('created_at');
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

    public function first(): ?object
    {
        $this->limit(1);
        $results = $this->get();
        return $results->first();
    }

    public function pluck(string $column): array
    {
        $results = $this->select($column)->get();
        
        $plucked = [];
        foreach ($results as $row) {
            $plucked[] = is_object($row) ? ($row->$column ?? null) : ($row[$column] ?? null);
        }
        
        return $plucked;
    }

    public function join(string $table, string $first, string $operator, string $second): self
    {
        $this->join[] = [
            'type' => 'inner',
            'table' => $table,
            'first' => $first,
            'operator' => $operator,
            'second' => $second
        ];

        return $this;
    }

    public function leftJoin(string $table, string $first, string $operator, string $second): self
    {
        $this->join[] = [
            'type' => 'left',
            'table' => $table,
            'first' => $first,
            'operator' => $operator,
            'second' => $second
        ];

        return $this;
    }

    public function count(): int
    {
        $query = clone $this;
    
        // $query->select = [new Expression('COUNT(*) as aggregate')];
        $query->select = ['COUNT(*) as aggregate'];
        $query->orders = [];
        $query->limit = null;
        $query->offset = null;
        
        $result = $query->first();
        
        return (int) ($result->aggregate ?? 0);
    }

    public function insert(array $data): bool
    {
        if (empty($data)) {
            return false;
        }

        $columns = array_keys($data);
        $values = array_values($data);
        $placeholders = str_repeat('?,', count($values) - 1) . '?';

        $sql = "INSERT INTO {$this->table} (" . implode(', ', $columns) . ") VALUES ({$placeholders})";

        return $this->connection->insert($sql, $values);
    }

    public function create(array $data): bool
    {
        return $this->insert($data);
    }

    public function update(array $data): int
    {
        if (empty($data)) {
            return 0;
        }

        $sets = [];
        $bindings = [];

        foreach ($data as $column => $value) {
            $sets[] = "{$column} = ?";
            $bindings[] = $value;
        }

        $bindings = array_merge($bindings, $this->bindings);

        $sql = "UPDATE {$this->table} SET " . implode(', ', $sets);

        if (!empty($this->wheres)) {
            $sql .= ' WHERE ' . $this->buildWhereClause();
        }

        $affected = $this->connection->update($sql, $bindings);

        return $affected;
    }

    public function delete(): int
    {
        $sql = "DELETE FROM {$this->table}";

        if (!empty($this->wheres)) {
            $sql .= ' WHERE ' . $this->buildWhereClause();
        }

        $affected = $this->connection->delete($sql, $this->bindings);

        return $affected;
    }

    protected function buildSelectSql(): string
    {
        // Build SELECT clause with Expression support
        $columns = [];
        foreach ($this->select as $column) {
            if ($this->isExpression($column)) {
                // Raw expression, use as-is
                $columns[] = $column->getValue();
            } elseif ($column === '*') {
                // Wildcard, no backticks
                $columns[] = '*';
            } elseif ($this->containsExpression($column)) {
                // Contains functions, operators, or alias - treat as raw
                $columns[] = $column;
            } else {
                // Regular column, add backticks
                $columns[] = '`' . $column . '`';
            }
        }
        
        $sql = 'SELECT ' . implode(', ', $columns) . ' FROM ' . $this->table;

        // Add JOINs
        if (!empty($this->join)) {
            foreach ($this->join as $join) {
                $type = strtoupper($join['type']);
                $sql .= " {$type} JOIN {$join['table']} ON {$join['first']} {$join['operator']} {$join['second']}";
            }
        }

        // Add WHERE clause
        if (!empty($this->wheres)) {
            $sql .= ' WHERE ' . $this->buildWhereClause();
        }

        // Add GROUP BY
        if (!empty($this->groups)) {
            $sql .= ' GROUP BY ' . implode(', ', $this->groups);
        }

        // Add ORDER BY
        if (!empty($this->orders)) {
            $orderClauses = [];
            foreach ($this->orders as $order) {
                if (isset($order['type']) && $order['type'] === 'raw') {
                    $orderClauses[] = $order['sql'];
                } else {
                    $column = $this->isExpression($order['column']) 
                        ? $order['column']->getValue() 
                        : $order['column'];
                    $orderClauses[] = "{$column} {$order['direction']}";
                }
            }
            $sql .= ' ORDER BY ' . implode(', ', $orderClauses);
        }

        // Add LIMIT
        if ($this->limit !== null) {
            $sql .= " LIMIT {$this->limit}";
        }

        // Add OFFSET
        if ($this->offset !== null) {
            $sql .= " OFFSET {$this->offset}";
        }

        return $sql;
    }

    protected function buildWhereClause(): string
    {
        $clauses = [];

        foreach ($this->wheres as $index => $where) {
            $boolean = $index === 0 ? '' : strtoupper($where['boolean']) . ' ';

            switch ($where['type']) {
                case 'basic':
                    $column = $this->isExpression($where['column']) 
                        ? $where['column']->getValue() 
                        : $where['column'];
                    
                    $valuePlaceholder = $this->isExpression($where['value']) 
                        ? $where['value']->getValue() 
                        : '?';
                    
                    $clauses[] = $boolean . "{$column} {$where['operator']} {$valuePlaceholder}";
                    break;

                case 'raw':
                    $clauses[] = $boolean . $where['sql'];
                    break;

                case 'in':
                    $placeholders = str_repeat('?,', count($where['values']) - 1) . '?';
                    $clauses[] = $boolean . "{$where['column']} IN ({$placeholders})";
                    break;
            }
        }

        return implode(' ', $clauses);
    }

    protected function containsExpression(string $column): bool
    {
        $patterns = [
            '/\s+as\s+/i',              // alias: "id as user_id"
            '/\(/i',                     // functions: "COUNT(*)", "CONCAT()"
            '/\*/i',                     // wildcard in functions: "COUNT(*)"
            '/\+|\-|\*|\//',            // operators: "price * quantity"
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $column)) {
                return true;
            }
        }

        return false;
    }

    public function getBindings(): array
    {
        return $this->bindings;
    }

    public function toSql(): string
    {
        return $this->buildSelectSql();
    }

    protected function isValidOperator(string $operator): bool
    {
        $allowed = ['=', '<', '>', '<=', '>=', '!=', '<>', 'like', 'not like', 'in', 'not in'];
        return in_array(strtolower($operator), $allowed, true);
    }

    protected function isExpression(mixed $value): bool
    {
        return $value instanceof Expression;
    }
}