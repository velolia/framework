<?php

declare(strict_types=1);

namespace Velolia\Database\Eloquent\Relations;

use Velolia\Database\Eloquent\Builder;
use Velolia\Database\Eloquent\Collection;
use Velolia\Database\Eloquent\Model;
use Velolia\Support\Str;

class BelongsToMany
{
    protected Model $parent;
    protected string $relatedClass;
    protected string $pivotTable;
    protected string $foreignKey;
    protected string $relatedKey;
    protected ?Builder $query = null;

    public function __construct(
        Model $parent,
        string $related,
        ?string $pivotTable = null,
        ?string $foreignKey = null,
        ?string $relatedKey = null
    ) {
        $this->parent       = $parent;
        $this->relatedClass = $related;

        // ✅ auto generate nama pivot
        if (!$pivotTable) {
            $tables = [
                Str::snake(class_basename($parent)),
                Str::snake(class_basename($related)),
            ];
            sort($tables);
            $pivotTable = implode('_', $tables);
        }

        $relatedInstance = $this->newRelatedInstance();

        $this->pivotTable = $pivotTable;
        $this->foreignKey = $foreignKey ?: $parent->getForeignKey();
        $this->relatedKey = $relatedKey ?: $relatedInstance->getForeignKey();

        // ✅ query dasar
        $this->query = $relatedInstance->newQuery()
            ->select($relatedInstance->getTable().'.*')
            ->join(
                $this->pivotTable,
                "{$this->pivotTable}.{$this->relatedKey}",
                '=',
                "{$relatedInstance->getTable()}.{$relatedInstance->getKeyName()}"
            )
            ->where("{$this->pivotTable}.{$this->foreignKey}", $this->parent->getRouteKey());
    }

    /** Buat instance model relasi baru */
    protected function newRelatedInstance(): Model
    {
        $class = $this->relatedClass;
        return new $class;
    }

    /** Ambil data relasi */
    public function getResult(): Collection
    {
        return $this->query->get();
    }

    /** Proxy ke builder */
    public function __call(string $method, array $parameters)
    {
        return $this->query->$method(...$parameters);
    }

    /** Attach */
    public function attach(int|array $ids): void
    {
        $ids = (array) $ids;
        $db  = $this->parent->getConnection();

        foreach ($ids as $id) {
            $db->table($this->pivotTable)->insert([
                $this->foreignKey => $this->parent->getRouteKey(),
                $this->relatedKey => $id,
            ]);
        }
    }

    /** Detach */
    public function detach(int|array $ids = []): void
    {
        $db    = $this->parent->getConnection();
        $query = $db->table($this->pivotTable)
            ->where($this->foreignKey, $this->parent->getRouteKey());

        if (!empty($ids)) {
            $query->whereIn($this->relatedKey, (array) $ids);
        }

        $query->delete();
    }

    /** Sync */
    public function sync(array $ids): void
    {
        $this->detach();
        $this->attach($ids);
    }

    /** Eager load */
    public function eagerLoad(Collection $models, string $relation): void
    {
        $keys = $models->pluck($this->parent->getKeyName())->all();
        if (empty($keys)) {
            return;
        }

        $relatedInstance = $this->newRelatedInstance();

        $rows = $relatedInstance->newQuery()
            ->select([
                $relatedInstance->getTable() . '.*',
                "{$this->pivotTable}.{$this->foreignKey} as pivot_parent_id"
            ])
            ->join(
                $this->pivotTable,
                "{$relatedInstance->getTable()}.{$relatedInstance->getKeyName()}",
                '=',
                "{$this->pivotTable}.{$this->relatedKey}"
            )
            ->whereIn("{$this->pivotTable}.{$this->foreignKey}", $keys)
            ->get();

        $grouped = $rows->groupBy('pivot_parent_id');

        foreach ($models as $model) {
            $id = $model->getKey();
            $model->setRelation($relation, $grouped[$id] ?? new Collection());
        }
    }
}