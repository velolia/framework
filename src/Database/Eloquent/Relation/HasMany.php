<?php

declare(strict_types=1);

namespace Velolia\Database\Eloquent\Relations;

use Velolia\Database\Eloquent\Model;
use Velolia\Database\Eloquent\Builder;
use Velolia\Database\Eloquent\Collection;

class HasMany
{
    protected Builder $query;
    protected Model $parent;
    protected string $foreignKey;
    protected string $localKey;

    public function __construct(Builder $query, Model $parent, string $foreignKey, string $localKey)
    {
        $this->query      = $query;
        $this->parent     = $parent;
        $this->foreignKey = $foreignKey;
        $this->localKey   = $localKey;
    }

    /**
     * Ambil semua model terkait
     */
    public function getResult(): Collection
    {
        $value = $this->parent->getAttribute($this->localKey);

        if (is_null($value)) {
            return new Collection([]);
        }

        $items = $this->query
            ->where($this->foreignKey, '=', $value)
            ->get();

        return $items;
    }

    /**
     * Alias Laravel-like
     */
    public function all(): Collection
    {
        return $this->getResult();
    }

    /**
     * Jika hanya ingin query builder (misal tambah where lain)
     */
    public function query(): Builder
    {
        $value = $this->parent->getAttribute($this->localKey);

        return $this->query->where($this->foreignKey, '=', $value);
    }

    public function toSql(): string
    {
        return $this->query->toSql();
    }

    public function getBindings(): array
    {
        return $this->query->getQuery()->getBindings();
    }

    public function eagerLoad(Collection $models, string $relation): void
    {
        $localKeys = [];
        foreach ($models as $model) {
            $val = $model->getAttribute($this->localKey);
            if ($val !== null) {
                $localKeys[] = $val;
            }
        }

        if (empty($localKeys)) return;

        $children = $this->query
            ->whereIn($this->foreignKey, $localKeys)
            ->get();

        $grouped = [];
        foreach ($children as $child) {
            $fk = $child->{$this->foreignKey};
            $grouped[$fk][] = $child;
        }

        foreach ($models as $model) {
            $key = $model->getAttribute($this->localKey);
            $collection = new Collection($grouped[$key] ?? []);
            $model->setRelation($relation, $collection);
        }
    }
}