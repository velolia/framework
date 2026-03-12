<?php

declare(strict_types=1);

namespace Velolia\Support\Schema;

use Velolia\Database\Schema\Schema as SchemaEngine;
use Closure;

class Schema
{
    public static function create(string $table, Closure $callback): void
    {
        SchemaEngine::create($table, $callback);
    }

    public static function dropIfExists(string $table): void
    {
        SchemaEngine::dropIfExists($table);
    }
}
