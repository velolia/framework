<?php

declare(strict_types=1);

namespace Velolia\Database\Relations;

class HasMany extends Relation
{
    public function addEagerConstraints(array $models): void
    {
        $keys = [];
        foreach ($models as $model) {
            $keys[] = $model->{$this->localKey};
        }

        $this->query->whereIn($this->foreignKey, array_unique($keys));
    }

    public function eagerLoad(array $models, string $relation): void
    {
        $this->query = ($this->relatedClass)::query();
        $this->addEagerConstraints($models);
        $results = $this->query->get();

        $dictionary = [];
        foreach ($results as $result) {
            $dictionary[$result->{$this->foreignKey}][] = $result;
        }

        foreach ($models as $model) {
            $key = $model->{$this->localKey};
            $model->setRelation($relation, $dictionary[$key] ?? []);
        }
    }

    public function getResults()
    {
        return $this->query->get();
    }
}
