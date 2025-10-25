<?php

declare(strict_types=1);

namespace Velolia\Support\Facades;

use RuntimeException;
use Velolia\Foundation\Application;

abstract class Facade
{
    /**
     * The application instance being facaded.
     *
     * @var Application|null
     */
    protected static $app;

    /**
     * Get the application instance behind the facade.
     *
     * @return Application
     */
    public static function getFacadeApplication(): Application
    {
        return static::$app ?? Application::getInstance();
    }

    /**
     * Set the application instance.
     *
     * @param Application $app
     * @return void
     */
    public static function setFacadeApplication(Application $app): void
    {
        static::$app = $app;
    }

    /**
     * Get the registered name of the component.
     *
     * @return string
     *
     * @throws RuntimeException
     */
    protected static function getFacadeAccessor(): string
    {
        throw new RuntimeException('Facade does not implement getFacadeAccessor method.');
    }

    /**
     * Get the registered component instance.
     *
     * @return mixed
     */
    protected static function getFacadeRoot()
    {
        return static::getFacadeApplication()->make(static::getFacadeAccessor());
    }

    /**
     * Handle dynamic, static calls to the object.
     *
     * @param string $method
     * @param array $args
     * @return mixed
     */
    public static function __callStatic($method, $args)
    {
        $instance = static::getFacadeRoot();

        if (! $instance) {
            throw new RuntimeException('A facade root has not been set.');
        }

        return $instance->$method(...$args);
    }
}