<?php

declare(strict_types=1);

namespace Velolia\Database;

use DateTimeImmutable;
use DateTimeInterface;
use Exception;
use JsonSerializable;
use ReflectionClass;
use Velolia\Database\Exception\MassAssignmentException;
use Velolia\Database\Exception\RelationNotFoundException;
use Velolia\Support\Facades\Facade;
use Velolia\Database\Relations\Relation;
use Velolia\Database\Relations\HasMany;
use Velolia\Database\Relations\BelongsTo;
use Velolia\Database\Relations\BelongsToMany;

abstract class Model implements JsonSerializable
{
    protected string $table;
    protected string $primaryKey = 'id';
    protected array $attributes = [];
    protected array $original = [];
    protected array $relations = [];
    protected array $fillable = [];
    protected array $guarded = ['*'];
    protected array $casts = [];
    public bool $exists = false;
    public bool $timestamps = true;
    protected string $createdAtColumn = 'created_at';
    protected string $updatedAtColumn = 'updated_at';
    protected static array $tableCache = [];

    public function __construct(array $attributes = [])
    {
        $this->fill($attributes);
    }

    public function fill(array $attributes): self
    {
        foreach ($attributes as $key => $value) {
            if ($this->isFillable($key)) {
                $this->attributes[$key] = $value;
            } elseif ($this->completelyGuarded()) {
                throw new MassAssignmentException($key, static::class);
            }
        }
        return $this;
    }

    protected function isFillable(string $key): bool
    {
        if (in_array($key, $this->fillable)) {
            return true;
        }

        if ($this->isGuarded($key)) {
            return false;
        }

        return !empty($this->fillable) || (is_array($this->guarded) && $this->guarded !== ['*']);
    }

    protected function isGuarded(string $key): bool
    {
        if (empty($this->guarded)) {
            return false;
        }

        return in_array($key, $this->guarded) || $this->guarded === ['*'];
    }

    protected function completelyGuarded(): bool
    {
        return empty($this->fillable) && $this->guarded === ['*'];
    }

    public static function query(): QueryBuilder
    {
        $instance = new static();
        return Facade::getFacadeApplication()
            ->make('db')
            ->table($instance->getTable())
            ->setModel(static::class);
    }

    public function getTable(): string
    {
        if (isset($this->table)) {
            return $this->table;
        }

        if (isset(static::$tableCache[static::class])) {
            return static::$tableCache[static::class];
        }

        $class = (new ReflectionClass($this))->getShortName();
        return static::$tableCache[static::class] = strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $class));
    }

    public function newFromBuilder(array $attributes): static
    {
        $model = new static();
        $model->setRawAttributes($attributes, true);
        $model->exists = true;
        return $model;
    }

    public function setRawAttributes(array $attributes, bool $sync = false): self
    {
        $this->attributes = $attributes;

        if ($sync) {
            $this->original = $attributes;
        }

        return $this;
    }

    public function delete(): bool
    {
        if (!$this->exists) {
            return false;
        }

        $result = static::query()
            ->where($this->primaryKey, $this->attributes[$this->primaryKey])
            ->delete();

        if ($result) {
            $this->exists = false;
        }

        return (bool) $result;
    }

    public function save(): bool
    {
        if ($this->timestamps) {
            $now = date('Y-m-d H:i:s');
            $this->attributes[$this->updatedAtColumn] = $now;

            if (!$this->exists) {
                $this->attributes[$this->createdAtColumn] = $now;
            }
        }

        if ($this->exists) {
            return (bool) static::query()
                ->where($this->primaryKey, $this->attributes[$this->primaryKey])
                ->update($this->attributes);
        }

        $query = static::query();
        $result = $query->insert($this->attributes);

        if ($result) {
            $this->attributes[$this->primaryKey] = $query->getConnection()->getPdo()->lastInsertId();
            $this->exists = true;
        }

        return $result;
    }

    public static function all()
    {
        return static::query()->get();
    }

    public static function paginate(int $perPage = 15): Paginator
    {
        return static::query()->paginate($perPage);
    }

    public static function find($id): ?static
    {
        $instance = new static();
        return static::where($instance->primaryKey, $id)->first();
    }

    public static function findOrFail($id): static
    {
        $model = static::find($id);

        if (!$model) {
            $exception = new \Exception("Model not found.", 404);
            throw $exception;
        }

        return $model;
    }

    public static function create(array $attributes): static
    {
        $model = new static($attributes);
        $model->save();
        return $model;
    }

    public function update(array $attributes = []): bool
    {
        if (!$this->exists) {
            return false;
        }

        $this->fill($attributes);

        if ($this->timestamps) {
            $this->attributes[$this->updatedAtColumn] = date('Y-m-d H:i:s');
        }

        return (bool) static::query()
            ->where($this->primaryKey, $this->attributes[$this->primaryKey])
            ->update($this->attributes);
    }

    public static function updateOrCreate(array $attributes, array $values = []): static
    {
        $instance = static::firstOrCreate($attributes, $values);

        if (!$instance->exists) {
            return $instance;
        }

        $instance->update($values);

        return $instance;
    }

    public static function firstOrCreate(array $conditions, array $values = []): static
    {
        $query = static::query();

        foreach ($conditions as $key => $value) {
            $query->where($key, $value);
        }

        $model = $query->first();

        if ($model) {
            return $model;
        }

        return static::create(array_merge($conditions, $values));
    }


    public function __get($key)
    {
        if (array_key_exists($key, $this->attributes)) {
            return $this->getAttributeValue($key);
        }

        if (array_key_exists($key, $this->relations)) {
            return $this->relations[$key];
        }

        if (method_exists($this, $key)) {
            $relation = $this->$key();
            if ($relation instanceof Relation) {
                $result = $relation->getResults();
                if (is_null($result)) {
                    throw new RelationNotFoundException($key, static::class);
                }
                $this->setRelation($key, $result);
                return $result;
            }
            return $relation;
        }

        return null;
    }

    protected function getAttributeValue(string $key): mixed
    {
        $value = $this->attributes[$key];
        return $this->castAttribute($key, $value);
    }

    protected function castAttribute(string $key, mixed $value): mixed
    {
        if ($value === null || !isset($this->casts[$key])) {
            return $value;
        }

        $type = strtolower(trim($this->casts[$key]));

        return match ($type) {
            'int', 'integer' => (int) $value,
            'float', 'double', 'real' => (float) $value,
            'string' => (string) $value,
            'bool', 'boolean' => (bool) $value,
            'object' => json_decode($value, false),
            'array', 'json' => json_decode($value, true),
            'date', 'datetime' => $this->asDateTime($value),
            default => $value,
        };
    }

    protected function asDateTime(mixed $value): mixed
    {
        if ($value instanceof DateTimeInterface) {
            return $value;
        }

        try {
            return new DateTimeImmutable($value);
        } catch (Exception $e) {
            return $value;
        }
    }

    public function load(array|string $relations): self
    {
        $relations = (array) $relations;
        $models = [$this];

        foreach ($relations as $relation) {
            if (method_exists($this, $relation)) {
                $relationObj = $this->$relation();
                if ($relationObj instanceof Relation) {
                    $relationObj->eagerLoad($models, $relation);
                }
            }
        }

        return $this;
    }

    public function setRelation(string $relation, mixed $value): self
    {
        $this->relations[$relation] = $value;
        return $this;
    }

    public function getRelation(string $relation)
    {
        return $this->relations[$relation] ?? null;
    }

    public function hasMany(string $related, ?string $foreignKey = null, string $localKey = 'id'): HasMany
    {
        $class = (new ReflectionClass($this))->getShortName();
        $foreignKey = $foreignKey ?: strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $class)) . '_id';

        $query = $related::query()->where($foreignKey, $this->attributes[$localKey]);

        return new HasMany($query, $this, $related, $foreignKey, $localKey);
    }

    public function belongsTo(string $related, ?string $foreignKey = null, string $ownerKey = 'id'): BelongsTo
    {
        $instance = new $related();
        $class = (new ReflectionClass($instance))->getShortName();
        $foreignKey = $foreignKey ?: strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $class)) . '_id';

        $query = $related::query()->where($ownerKey, $this->attributes[$foreignKey]);

        return new BelongsTo($query, $this, $related, $foreignKey, $ownerKey);
    }

    public function belongsToMany(
        string $related,
        ?string $table = null,
        ?string $foreignPivotKey = null,
        ?string $relatedPivotKey = null,
        string $parentKey = 'id',
        string $relatedKey = 'id'
    ): BelongsToMany {
        $instance = new $related();

        if (is_null($table)) {
            $host = $this->getTable();
            $target = $instance->getTable();
            
            $segments = [$host, $target];
            sort($segments);
            $table = implode('_', $segments);
        }

        if (is_null($foreignPivotKey)) {
            $foreignPivotKey = strtolower((new ReflectionClass($this))->getShortName()) . '_id';
        }

        if (is_null($relatedPivotKey)) {
            $relatedPivotKey = strtolower((new ReflectionClass($instance))->getShortName()) . '_id';
        }

        $query = $related::query()->where("{$table}.{$foreignPivotKey}", $this->attributes[$parentKey]);

        return new BelongsToMany(
            $query, $this, $table, $foreignPivotKey, $relatedPivotKey, $parentKey, $relatedKey, $related
        );
    }

    public function jsonSerialize(): mixed
    {
        return $this->toArray();
    }

    public function toArray(): array
    {
        return array_merge($this->attributes, $this->relations);
    }

    public function __toString(): string
    {
        return json_encode($this->toArray());
    }

    public function __set($key, $value)
    {
        $this->attributes[$key] = $value;
    }

    public static function __callStatic($method, $parameters)
    {
        return static::query()->$method(...$parameters);
    }

    public function __call($method, $parameters)
    {
        return static::query()->$method(...$parameters);
    }
}
