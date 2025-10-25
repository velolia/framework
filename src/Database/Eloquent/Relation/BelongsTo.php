<?php

declare(strict_types=1);

namespace Velolia\Database\Eloquent\Relations;

use Velolia\Database\Eloquent\Builder;
use Velolia\Database\Eloquent\Collection;
use Velolia\Database\Eloquent\Model;

class BelongsTo
{
    protected Builder $query;
    protected Model $child;
    protected string $foreignKey;
    protected string $ownerKey;

    /**
     * Create a new belongs to relationship instance.
     *
     * @param  \Velolia\Database\Eloquent\Builder  $query
     * @param  \Velolia\Database\Eloquent\Model  $parent
     * @param  string  $foreignKey
     * @param  string  $ownerKey
     * @param  string  $relation
     * @return void
     */
    public function __construct(Builder $query, Model $child, string $foreignKey, string $ownerKey)
    {
        $this->query      = $query;
        $this->child      = $child;
        $this->foreignKey = $foreignKey;
        $this->ownerKey   = $ownerKey;
    }

    public function getResult(): ?Model
    {
        $value = $this->child->getAttribute($this->foreignKey);

        if (is_null($value)) {
            return null;
        }

        return $this->query
            ->where($this->ownerKey, '=', $value)
            ->first();
    }

    public function __invoke(): ?Model
    {
        return $this->getResult();
    }

    public function first(): ?Model
    {
        return $this->getResult();
    }

    public function eagerLoad(Collection $models, string $relation): void
    {
        $foreignKeyValues = [];

        foreach ($models as $model) {
            $val = $model->getAttribute($this->foreignKey);
            if ($val !== null) {
                $foreignKeyValues[] = $val;
            }
        }

        if (empty($foreignKeyValues)) return;

        $parents = $this->query
            ->whereIn($this->ownerKey, $foreignKeyValues)
            ->get()
            ->keyBy($this->ownerKey);

        foreach ($models as $model) {
            $key = $model->getAttribute($this->foreignKey);
            $model->setRelation($relation, $parents[$key] ?? null);
        }
    }

    public function toSql(): string
    {
        return $this->query->toSql();
    }

    public function getBindings(): array
    {
        return $this->query->getQuery()->getBindings();
    }
}