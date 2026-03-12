<?php

declare(strict_types=1);

namespace Velolia\Database\Relations;

use Velolia\Database\QueryBuilder;
use Velolia\Database\Model;

abstract class Relation
{
    public function __construct(
        protected QueryBuilder $query,
        protected Model $parent,
        protected string $relatedClass,
        protected string $foreignKey,
        protected string $localKey
    ) {}

    public function getQuery(): QueryBuilder
    {
        return $this->query;
    }

    public function getParent(): Model
    {
        return $this->parent;
    }

    abstract public function addEagerConstraints(array $models): void;
    abstract public function eagerLoad(array $models, string $relation): void;

    public function __call($method, $parameters)
    {
        $result = $this->query->$method(...$parameters);

        if ($result === $this->query) {
            return $this;
        }

        return $result;
    }
}
