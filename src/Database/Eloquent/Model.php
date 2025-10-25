<?php

declare(strict_types=1);

namespace Velolia\Database\Eloquent;

use ArrayAccess;
use JsonSerializable;
use RuntimeException;
use Velolia\Database\Eloquent\Relations\BelongsTo;
use Velolia\Database\Eloquent\Relations\BelongsToMany;
use Velolia\Database\Eloquent\Relations\HasMany;
use Velolia\Support\Str;

abstract class Model implements ArrayAccess, JsonSerializable
{
    protected $connection;
    protected $table;
    protected $primaryKey = 'id';
    protected $attributes = [];
    protected $original = [];
    protected $fillable = [];
    protected $guarded = ['*'];
    protected $hidden = [];
    protected $relations = [];
    protected $exists = false;

    public function __construct(array $attributes = [])
    {
        $this->fill($attributes);
    }

    public function getConnection()
    {
        return app('db')->connection($this->getConnectionName());
    }

    protected function getConnectionName(): string
    {
        if (!$this->connection) {
            $this->connection = app('db')->getDefaultConnection();
        }
        return $this->connection;
    }

    public function getTable()
    {
        if (!isset($this->table)) {
            return Str::snake(class_basename($this));
        }
        return $this->table;
    }

    public function fill(array $attributes): static
    {
        foreach ($attributes as $key => $value) {
            if ($this->isFillable($key)) {
                $this->setAttribute($key, $value);
            } else {
                if ($this->isGuarded($key)) {
                    throw new RuntimeException(
                        "Mass assignment exception: Field [{$key}] is not fillable in " . static::class
                    );
                }
            }
        }

        return $this;
    }

    public function isFillable(string $key): bool
    {
        if (!empty($this->fillable)) {
            return in_array($key, $this->fillable);
        }

        if (empty($this->guarded)) {
            return true;
        }

        if ($this->guarded === ['*']) {
            return false;
        }

        return !in_array($key, $this->guarded);
    }

    public function isGuarded(string $key): bool
    {
        if ($this->guarded === ['*']) {
            return true;
        }
        return in_array($key, $this->guarded, true);
    }

    public function getAttribute(string $key): mixed
    {
        if (!$key) {
            return null;
        }

        if (array_key_exists($key, $this->attributes)) {
            return $this->getAttributeValue($key);
        }

        return null;
    }

    protected function getAttributeValue(string $key): mixed
    {
        return $this->attributes[$key] ?? null;
    }

    public function setAttribute(string $key, mixed $value): static
    {
        $this->attributes[$key] = $value;
        return $this;
    }

    public function hydrate(array $items): Collection
    {
        $models = [];
        foreach ($items as $item) {
            $model = $this->newFromBuilder($item);
            $models[] = $model;
        }
        return new Collection($models);
    }

    public function newFromBuilder(array|object $attributes = []): static
    {
        if (is_object($attributes)) {
            $attributes = (array) $attributes;
        }

        $model = new static;
        $model->connection = $this->getConnectionName();
        $model->setRawAttributes($attributes, true);
        $model->exists = true;

        return $model;
    }

    public function setRawAttributes(array $attributes, bool $sync = false): static
    {
        $this->attributes = $attributes;

        if ($sync) {
            $this->syncOriginal();
        }

        return $this;
    }

    public function syncOriginal(): static
    {
        $this->original = $this->attributes;
        return $this;
    }

    public static function query(): Builder
    {
        return (new static)->newQuery();
    }

    public function newQuery(): Builder
    {
        $connection = app('db')->connection($this->getConnectionName());
        $queryBuilder = $connection->table($this->getTable());
        return new Builder($queryBuilder, $this);
    }

    public function save(): bool
    {
        if ($this->exists) {
            return $this->performUpdate();
        } else {
            return $this->performInsert();
        }
    }

    protected function performInsert(): bool
    {
        $attributes = $this->getAttributes();

        if (empty($attributes)) {
            return true;
        }

        $result = $this->newQuery()->getQuery()->insert($attributes);

        if ($result) {
            $this->exists = true;
            $this->syncOriginal();
        }

        return $result;
    }

    protected function performUpdate(): bool
    {
        $dirty = $this->getDirty();

        if (empty($dirty)) {
            return true;
        }

        $affected = $this->newQuery()
            ->where($this->getKeyName(), $this->getRouteKey())
            ->getQuery()
            ->update($dirty);

        if ($affected > 0) {
            $this->syncOriginal();
        }

        return $affected > 0;
    }

    public function getDirty(): array
    {
        $dirty = [];

        foreach ($this->attributes as $key => $value) {
            if (!array_key_exists($key, $this->original) || $this->original[$key] !== $value) {
                $dirty[$key] = $value;
            }
        }

        return $dirty;
    }

    public function isDirty(string|array|null $attributes = null): bool
    {
        $dirty = $this->getDirty();

        if (is_null($attributes)) {
            return count($dirty) > 0;
        }

        $attributes = is_array($attributes) ? $attributes : func_get_args();

        foreach ($attributes as $attribute) {
            if (array_key_exists($attribute, $dirty)) {
                return true;
            }
        }

        return false;
    }

    public function update(array $attributes = []): bool
    {
        if (!empty($attributes)) {
            $this->fill($attributes);
        }

        if (count($this->getDirty()) === 0) {
            return true;
        }

        $dirty = $this->getDirty();

        $updated = $this->newQuery()
            ->where($this->getKeyName(), $this->getKey())
            ->update($dirty);

        if ($updated) {
            $this->syncOriginal();
        }

        return (bool) $updated;
    }

    public function delete(): bool
    {
        if (!$this->exists) {
            return false;
        }

        $deleted = $this->newQuery()
            ->where($this->getKeyName(), $this->getRouteKey())
            ->getQuery()
            ->delete();

        if ($deleted > 0) {
            $this->exists = false;
        }

        return $deleted > 0;
    }

    public function getPrimaryKey(): string
    {
        return $this->primaryKey;
    }

    public function getKeyName(): string
    {
        return $this->getPrimaryKey();
    }

    public function getRouteKey(): mixed
    {
        return $this->getAttribute($this->getKeyName());
    }

    public function getKey(): mixed
    {
        return $this->attributes[$this->getKeyName()] ?? null;
    }

    public function getRouteKeyName(): string
    {
        return $this->getKeyName();
    }

    public function getAttributes(): array
    {
        return $this->attributes;
    }

    public function getModelName(): string
    {
        return static::class;
    }

    public function getForeignKey()
    {
        return Str::snake(class_basename($this)) . '_' . $this->getRouteKeyName();
    }

    protected function newRelatedInstance(string $related): Model
    {
        return new $related;
    }

    protected function guessBelongsToRelation()
    {
        $caller = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 3);
        return $caller[2]['function'] ?? throw new \RuntimeException('Unable to guess the belongsTo relationship name.');
    }

    public function hasMany(string $related, ?string $foreignKey = null, ?string $localKey = null) : HasMany
    {
        $instance = $this->newRelatedInstance($related);
        $foreignKey = $foreignKey ?: Str::snake(class_basename($this)) . '_id';
        $localKey = $localKey ?: $this->getKeyName();
        return new HasMany($instance->newQuery(), $this, $foreignKey, $localKey);
    }

    public function belongsTo(string $related, ?string $foreignKey = null, ?string $ownerKey = null): BelongsTo
    {
        if (is_null($foreignKey)) {
            $relation = $this->guessBelongsToRelation();
            $foreignKey = Str::snake($relation) . '_id';
        }

        if (is_null($ownerKey)) {
            $ownerKey = (new $related)->getKeyName();
        }

        $instance = $this->newRelatedInstance($related);

        return new BelongsTo(
            $instance->newQuery(),
            $this,
            $foreignKey,
            $ownerKey,
            $relation ?? null
        );
    }

    public function belongsToMany(string $related, ?string $pivotTable = null, ?string $foreignKey = null, ?string $relatedKey = null): BelongsToMany
    {
        return new BelongsToMany($this, $related, $pivotTable, $foreignKey, $relatedKey);
    }

    public function setRelation(string $name, mixed $value): static
    {
        $this->relations[$name] = $value;
        return $this;
    }

    public function toArray(): array
    {
        $attributes = $this->attributes;
        
        foreach ($this->hidden as $key) {
            unset($attributes[$key]);
        }
        
        return $attributes;
    }

    public function jsonSerialize(): mixed
    {
        return $this->toArray();
    }

    public function offsetExists($offset): bool
    {
        return array_key_exists($offset, $this->attributes);
    }

    public function offsetGet($offset): mixed
    {
        return $this->attributes[$offset] ?? null;
    }

    public function offsetSet($offset, $value): void
    {
        $this->attributes[$offset] = $value;
    }

    public function offsetUnset($offset): void
    {
        unset($this->attributes[$offset]);
    }

    public static function __callStatic(string $method, array $args)
    {
        return (new static)->newQuery()->$method(...$args);
    }

    public function __get(string $key): mixed
    {
        if (array_key_exists($key, $this->relations)) {
            return $this->relations[$key];
        }

        if (method_exists($this, $key)) {
            $relation = $this->$key();
            if ($relation instanceof BelongsTo || $relation instanceof HasMany || $relation instanceof BelongsToMany) {
                $result = $relation->getResult();
                $this->relations[$key] = $result;
                return $result;
            }
        }

        return $this->getAttribute($key);
    }

    public function __set(string $key, mixed $value): void
    {
        $this->setAttribute($key, $value);
    }

    public function __isset(string $key): bool
    {
        return isset($this->attributes[$key]);
    }

    public function __unset(string $key): void
    {
        unset($this->attributes[$key]);
    }
}