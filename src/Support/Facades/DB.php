<?php

declare(strict_types=1);

namespace Velolia\Support\Facades;

/**
 * @method static \Velolia\Database\QueryBuilder table(string $table)
 * @method static \Velolia\Database\QueryBuilder select(array $columns)
 * @method static \Velolia\Database\QueryBuilder where(string $column, string $operator, mixed $value)
 * @method static \Velolia\Database\QueryBuilder get()
 * @method static \Velolia\Database\QueryBuilder first()
 * @method static \Velolia\Database\QueryBuilder find(int $id)
 * @method static \Velolia\Database\QueryBuilder create(array $data)
 * @method static \Velolia\Database\QueryBuilder update(array $data)
 * @method static \Velolia\Database\QueryBuilder delete()
 */
class DB extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'db';
    }
}
