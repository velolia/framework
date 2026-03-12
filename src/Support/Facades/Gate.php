<?php

declare(strict_types=1);

namespace Velolia\Support\Facades;

use Velolia\Auth\Access\Gate as GateManager;

/**
 *
 */
class Gate extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return GateManager::class;
    }
}
