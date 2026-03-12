<?php

declare(strict_types=1);

namespace Velolia\Support\Facades;

/**
 * @method static \Velolia\Routing\Router get(string $uri, callable|array $action)
 * @method static \Velolia\Routing\Router post(string $uri, callable|array $action)
 * @method static \Velolia\Routing\Router put(string $uri, callable|array $action)
 * @method static \Velolia\Routing\Router delete(string $uri, callable|array $action)
 * @method static \Velolia\Routing\Router patch(string $uri, callable|array $action)
 * @method static \Velolia\Routing\Router options(string $uri, callable|array $action)
 * @method static \Velolia\Routing\Router any(string $uri, callable|array $action)
 * @method static \Velolia\Routing\Router group(array $attributes, callable $callback)
 * @method static \Velolia\Routing\Router resource(string $name, string $controller)
 * @method static \Velolia\Routing\Router middleware(string|array $middleware)
 */
class Route extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'router';
    }
}