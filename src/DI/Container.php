<?php

declare(strict_types=1);

namespace Velolia\DI;

use Closure;
use Exception;
use ReflectionClass;
use ReflectionException;
use ReflectionFunction;
use ReflectionMethod;

class Container
{
    protected array $bindings = [];
    protected array $instances = [];
    protected array $aliases = [];
    protected array $reflectionCache = [];
    protected static ?self $instance = null;

    public static function setInstance(?self $container = null): ?self
    {
        return static::$instance = $container;
    }

    public static function getInstance(): static
    {
        if (is_null(static::$instance)) {
            static::$instance = new static();
        }

        return static::$instance;
    }

    public function get(string $id): mixed
    {
        return $this->resolve($id);
    }

    public function has(string $id): bool
    {
        return isset($this->bindings[$id]) 
            || isset($this->instances[$id]) 
            || isset($this->aliases[$id])
            || class_exists($id);
    }

    public function bind(string $abstract, mixed $concrete = null, bool $shared = false): void
    {
        $concrete = $concrete ?? $abstract;
        
        $this->bindings[$abstract] = [
            'concrete' => $concrete,
            'shared' => $shared
        ];
    }

    public function singleton(string $abstract, mixed $concrete = null): void
    {
        $this->bind($abstract, $concrete, true);
    }

    public function instance(string $abstract, object $instance): void
    {
        $this->instances[$abstract] = $instance;
    }

    public function alias(string $abstract, string $alias): void
    {
        $this->aliases[$alias] = $abstract;
    }

    public function make(string $abstract, array $parameters = []): mixed
    {
        return $this->resolve($abstract, $parameters);
    }

    public function resolve(string $abstract, array $parameters = []): mixed
    {
        $abstract = $this->getAlias($abstract);

        if (isset($this->instances[$abstract])) {
            return $this->instances[$abstract];
        }

        $concrete = $this->getConcrete($abstract);

        $object = $this->build($concrete, $parameters);

        if (isset($this->bindings[$abstract]) && $this->bindings[$abstract]['shared']) {
            $this->instances[$abstract] = $object;
        }

        return $object;
    }

    protected function getAlias(string $abstract): string
    {
        return $this->aliases[$abstract] ?? $abstract;
    }

    protected function getConcrete(string $abstract): mixed
    {
        if (isset($this->bindings[$abstract])) {
            return $this->bindings[$abstract]['concrete'];
        }

        return $abstract;
    }

    protected function build(mixed $concrete, array $parameters = []): mixed
    {
        if ($concrete instanceof Closure) {
            return $concrete($this, $parameters);
        }

        if (isset($this->reflectionCache[$concrete])) {
            $reflector = $this->reflectionCache[$concrete];
        } else {
            try {
                $reflector = new ReflectionClass($concrete);
                $this->reflectionCache[$concrete] = $reflector;
            } catch (ReflectionException $e) {
                throw new Exception("Target class [$concrete] does not exist.", 0, $e);
            }
        }

        if (!$reflector->isInstantiable()) {
            throw new Exception("Target [$concrete] is not instantiable.");
        }

        $constructor = $reflector->getConstructor();

        if (is_null($constructor)) {
            return new $concrete();
        }

        $dependencies = $constructor->getParameters();

        $instances = $this->resolveDependencies($dependencies, $parameters);

        return $reflector->newInstanceArgs($instances);
    }

    protected function resolveDependencies(array $dependencies, array $parameters = []): array
    {
        $results = [];

        foreach ($dependencies as $dependency) {
            if (array_key_exists($dependency->getName(), $parameters)) {
                $results[] = $parameters[$dependency->getName()];
                continue;
            }

            $type = $dependency->getType();

            if ($type === null) {
                if ($dependency->isDefaultValueAvailable()) {
                    $results[] = $dependency->getDefaultValue();
                    continue;
                }

                throw new Exception("Cannot resolve dependency [{$dependency->getName()}]");
            }

            $typeName = $type->getName();

            if ($type->isBuiltin()) {
                if ($dependency->isDefaultValueAvailable()) {
                    $results[] = $dependency->getDefaultValue();
                    continue;
                }

                throw new Exception("Cannot resolve built-in type [$typeName] for [{$dependency->getName()}]");
            }

            $results[] = $this->resolve($typeName);
        }

        return $results;
    }

    public function call(callable|array|string $callback, array $parameters = []): mixed
    {
        if (is_string($callback) && str_contains($callback, '@')) {
            $callback = explode('@', $callback);
        }

        if (is_array($callback)) {
            [$class, $method] = $callback;
            
            if (is_string($class)) {
                $class = $this->resolve($class);
            }

            $callback = [$class, $method];
        }

        $dependencies = $this->getCallableDependencies($callback);
        $resolvedParams = $this->resolveDependencies($dependencies, $parameters);

        return call_user_func_array($callback, $resolvedParams);
    }

    protected function getCallableDependencies(callable $callback): array
    {
        $cacheKey = $this->getCallableCacheKey($callback);

        if (isset($this->reflectionCache[$cacheKey])) {
            return $this->reflectionCache[$cacheKey]->getParameters();
        }

        if (is_array($callback)) {
            $reflector = new ReflectionMethod($callback[0], $callback[1]);
        } else {
            $reflector = new ReflectionFunction($callback);
        }

        $this->reflectionCache[$cacheKey] = $reflector;

        return $reflector->getParameters();
    }

    protected function getCallableCacheKey(callable $callback): string
    {
        if (is_array($callback)) {
            return (is_object($callback[0]) ? get_class($callback[0]) : $callback[0]) . '::' . $callback[1];
        }

        if ($callback instanceof Closure) {
            return 'closure_' . spl_object_hash($callback);
        }

        return (string) $callback;
    }

    public function flush(): void
    {
        $this->bindings = [];
        $this->instances = [];
        $this->aliases = [];
    }
}