<?php

declare(strict_types=1);

namespace Velolia\Database\Relations;

use Velolia\Database\Model;
use Velolia\Database\QueryBuilder;

class BelongsToMany extends Relation
{
    public function __construct(
        QueryBuilder $query,
        Model $parent,
        protected string $table,
        protected string $foreignPivotKey,
        protected string $relatedPivotKey,
        protected string $parentKey,
        protected string $relatedKey,
        string $relatedClass
    ) {
        parent::__construct($query, $parent, $relatedClass, $foreignPivotKey, $parentKey);
    }

    public function getResults(): mixed
    {
        return $this->get();
    }

    public function get()
    {
        $related = new $this->relatedClass();
        $relatedTable = $related->getTable();

        return $this->query
            ->join($this->table, "{$this->table}.{$this->relatedPivotKey}", '=', "{$relatedTable}.{$this->relatedKey}")
            ->select(["{$relatedTable}.*", "{$this->table}.{$this->foreignPivotKey} as pivot_{$this->foreignPivotKey}", "{$this->table}.{$this->relatedPivotKey} as pivot_{$this->relatedPivotKey}"])
            ->get();
    }

    public function addEagerConstraints(array $models): void
    {
        $ids = array_map(fn($m) => $m->{$this->parentKey}, $models);
        $this->query->whereIn("{$this->table}.{$this->foreignPivotKey}", $ids);
    }

    public function eagerLoad(array $models, string $relation): void
    {
        $this->query = ($this->relatedClass)::query();
        $this->addEagerConstraints($models);
        $results = $this->get();

        $dictionary = [];
        foreach ($results as $result) {
            $dictionary[$result->{"pivot_{$this->foreignPivotKey}"}][] = $result;
        }

        foreach ($models as $model) {
            $key = $model->{$this->parentKey};
            $model->setRelation($relation, $dictionary[$key] ?? []);
        }
    }

    public function attach($id, array $attributes = []): bool
    {
        $values = array_merge($attributes, [
            $this->foreignPivotKey => $this->parent->{$this->parentKey},
            $this->relatedPivotKey => $id instanceof Model ? $id->{$this->relatedKey} : $id,
        ]);

        return $this->query->table($this->table)->insert($values);
    }

    public function detach($id = null): int
    {
        $query = $this->query->table($this->table)
            ->where($this->foreignPivotKey, $this->parent->{$this->parentKey});

        if ($id !== null) {
            $query->where($this->relatedPivotKey, $id instanceof Model ? $id->{$this->relatedKey} : $id);
        }

        return $query->delete();
    }

    public function sync(array $ids): array
    {
        $changes = [
            'attached' => [], 'detached' => [], 'updated' => []
        ];

        $current = array_column($this->query->table($this->table)
            ->where($this->foreignPivotKey, $this->parent->{$this->parentKey})
            ->select([$this->relatedPivotKey])
            ->get()->all(), $this->relatedPivotKey);

        $detach = array_diff($current, $ids);
        if (!empty($detach)) {
            $this->detach($detach);
            $changes['detached'] = $detach;
        }

        $attach = array_diff($ids, $current);
        foreach ($attach as $id) {
            $this->attach($id);
            $changes['attached'][] = $id;
        }

        return $changes;
    }
}
