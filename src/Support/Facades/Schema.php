<?php

declare(strict_types=1);

namespace Velolia\Support\Facades;

class Schema extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'db.schema';
    }
}