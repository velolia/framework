<?php

namespace Velolia\Support\Facades;

/**
 * @method static \Velolia\Routing\Router get($uri, array|string $action, array $parameters = [])
 * @method static \Velolia\Routing\Router post($uri, array|string $action, array $parameters = [])
 * @method static \Velolia\Routing\Router put($uri, array|string $action, array $parameters = [])
 * @method static \Velolia\Routing\Router patch($uri, array|string $action, array $parameters = [])
 * @method static \Velolia\Routing\Router delete($uri, array|string $action, array $parameters = [])
 * @method static \Velolia\Routing\Router options($uri, array|string $action, array $parameters = [])
 * @method static \Velolia\Routing\Router group(array $attributes, Closure $callback)
 * @method static \Velolia\Routing\Router resource($name, string $controller, array $options = [])
 * 
*/
class Route extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor(): string
    {
        return 'router';
    }
}