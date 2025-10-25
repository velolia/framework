<?php

declare(strict_types=1);

namespace Velolia\Database\Eloquent;

use Velolia\Database\Eloquent\Exception\ModelNotFoundException;
use Velolia\Database\Query\Builder as QueryBuilder;
use Velolia\Database\Query\Expression;

class Builder
{
    protected QueryBuilder $query;
    protected Model $model;
    protected array $eagerLoad = [];

    public function __construct(QueryBuilder $query, Model $model)
    {
        $this->query = $query;
        $this->model = $model;
    }

    public function get(): Collection
    {
        $records = $this->query->get();
        $models = $this->model->hydrate($records->all());

        // if (!empty($this->eagerLoad)) {
        //     $this->eagerLoadRelations($models);
        // }

        return $models;
    }

    public function all()
    {
        return $this->get();
    }

    public function select(array|string|Expression $columns = ['*']): static
    {
        $columns = is_array($columns) ? $columns : func_get_args();
        $this->query->select($columns);
        return $this;
    }

    // public function where(string|array $column, mixed $operator = '=', mixed $value = null): static
    // {
    //     $this->query->where($column, $operator, $value);
    //     return $this;
    // }

    // public function whereIn(string $column, array $values): static
    // {
    //     $this->query->whereIn($column, $values);
    //     return $this;
    // }

    // public function orderBy(string|Expression $column, string $direction = 'asc'): static
    // {
    //     $this->query->orderBy($column, $direction);
    //     return $this;
    // }

    // public function orderByAsc(string $column): static
    // {
    //     $this->query->orderByAsc($column);
    //     return $this;
    // }

    // public function orderByDesc(string $column): static
    // {
    //     $this->query->orderByDesc($column);
    //     return $this;
    // }

    // public function latest(string $column = 'created_at'): static
    // {
    //     $this->query->orderByDesc($column);
    //     return $this;
    // }

    // public function oldest(string $column = 'created_at'): static
    // {
    //     $this->query->orderBy($column, 'asc');
    //     return $this;
    // }

    // public function groupBy(string|array ...$groups): static
    // {
    //     $this->query->groupBy(...$groups);
    //     return $this;
    // }

    // public function limit(int $value): static
    // {
    //     $this->query->limit($value);
    //     return $this;
    // }

    // public function offset(int $value): static
    // {
    //     $this->query->offset($value);
    //     return $this;
    // }

    // public function take(int $value): static
    // {
    //     return $this->limit($value);
    // }

    // public function skip(int $value): static
    // {
    //     return $this->offset($value);
    // }

    public function first(): ?Model
    {
        $this->query->limit(1);
        $records = $this->query->get();
        
        if ($records->isEmpty()) {
            return null;
        }

        return $this->model->newFromBuilder($records->first());
    }

    public function firstOrFail(): Model
    {
        $model = $this->first();

        if ($model === null) {
            throw new ModelNotFoundException(
                "No query results for model [{$this->model->getModelName()}]"
            );
        }

        return $model;
    }

    public function find(mixed $id, array $columns = ['*']): mixed
    {
        // Handle array of IDs
        if (is_array($id)) {
            return $this->findMany($id, $columns);
        }

        // Single ID
        return $this->where($this->model->getKeyName(), '=', $id)
            ->select($columns)
            ->first();
    }

    public function findMany(array $ids, array $columns = ['*']): Collection
    {
        if (empty($ids)) {
            return $this->model->hydrate([]);
        }

        return $this->whereIn($this->model->getKeyName(), $ids)
            ->select($columns)
            ->get();
    }

    public function findOrFail(mixed $id, array $columns = ['*']): mixed
    {
        $result = $this->find($id, $columns);

        // If array of IDs, check if we found all of them
        if (is_array($id)) {
            if ($result instanceof Collection && $result->count() === count(array_unique($id))) {
                return $result;
            }
        } else {
            // Single ID
            if ($result !== null) {
                return $result;
            }
        }

        throw new ModelNotFoundException(
            "No query results for model [{$this->model->getModelName()}] with ID: " . 
            (is_array($id) ? implode(', ', $id) : $id)
        );
    }

    public function with(array|string $relations): static
    {
        if (is_string($relations)) {
            $relations = [$relations];
        }

        $this->eagerLoad = array_merge($this->eagerLoad, (array) $relations);
        return $this;
    }

    public function toSql(): string
    {
        return $this->query->toSql();
    }

    public function getQuery(): QueryBuilder
    {
        return $this->query;
    }

    public function __call(string $method, array $parameters)
    {
        $this->query->{$method}(...$parameters);
        return $this;
    }
}