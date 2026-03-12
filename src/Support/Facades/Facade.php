<?php

declare(strict_types=1);

namespace Velolia\Support\Facades;

use Velolia\Core\Application;
use RuntimeException;

abstract class Facade
{
    protected static $app;

    public static function getFacadeApplication(): Application
    {
        return static::$app ?? Application::getInstance();
    }

    public static function setFacadeApplication(Application $app): void
    {
        static::$app = $app;
    }

    protected static function getFacadeAccessor(): string
    {
        throw new RuntimeException('Facade does not implement getFacadeAccessor method.');
    }

    protected static function getFacadeRoot()
    {
        return static::getFacadeApplication()->make(static::getFacadeAccessor());
    }

    public static function __callStatic($method, $args)
    {
        $instance = static::getFacadeRoot();

        if (! $instance) {
            throw new RuntimeException('A facade root has not been set.');
        }

        return $instance->$method(...$args);
    }
}