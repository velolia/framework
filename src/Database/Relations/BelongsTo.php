<?php

declare(strict_types=1);

namespace Velolia\Database\Relations;

class BelongsTo extends Relation
{
    public function addEagerConstraints(array $models): void
    {
        $keys = [];
        foreach ($models as $model) {
            if ($value = $model->{$this->foreignKey}) {
                $keys[] = $value;
            }
        }

        $this->query->whereIn($this->localKey, array_unique($keys));
    }

    public function eagerLoad(array $models, string $relation): void
    {
        $this->query = ($this->relatedClass)::query();
        $this->addEagerConstraints($models);
        $results = $this->query->get();

        $dictionary = [];
        foreach ($results as $result) {
            $dictionary[$result->{$this->localKey}] = $result;
        }

        foreach ($models as $model) {
            $key = $model->{$this->foreignKey};
            if (isset($dictionary[$key])) {
                $model->setRelation($relation, $dictionary[$key]);
            }
        }
    }

    public function getResults()
    {
        return $this->query->first();
    }
}
