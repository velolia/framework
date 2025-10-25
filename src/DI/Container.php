<?php

declare(strict_types=1);

namespace Velolia\DI;

use ArrayAccess;
use Closure;
use ReflectionClass;
use ReflectionFunction;
use ReflectionFunctionAbstract;
use ReflectionMethod;
use ReflectionParameter;
use Throwable;
use Psr\Container\ContainerInterface;

class Container implements ArrayAccess, ContainerInterface
{
    /**
     * get instance of container
     * 
     * @var static
    */
    protected static $instance;

    /**
     * container bindings
     * 
     * @var array
    */
    protected array $bindings = [];

    /**
     * container instances
     * 
     * @var array
    */
    protected array $instances = [];

    /**
     * container aliases
     * 
     * @var array
    */
    protected array $aliases = [];

    /**
     * container build stack
     * 
     * @var array
    */
    protected array $buildStack = [];

    /**
     * set instance of container
     * 
     * @param ContainerInterface|null $container
    */
    public static function setInstance(?ContainerInterface $container = null)
    {
        return static::$instance = $container;
    }

    /**
     * get instance of container
     * 
     * @return static
    */
    public static function getInstance(): static
    {
        return static::$instance ??= new static();
    }

    /**
     * bound container
     * 
     * @return bool
    */
    public function bound($abstract): bool
    {
        return isset($this->bindings[$abstract]) || isset($this->instances[$abstract]) || isset($this->aliases[$abstract]);
    }

    /**
     * bind container
     * 
     * @param string $abstract
     * @param string $concrete
     * @param bool $shared
    */
    public function bind($abstract, $concrete = null, $shared = false)
    {
        $this->dropStaleInstances($abstract);

        if (is_null($concrete)) {
            $concrete = $abstract;
        }

        if (!$concrete instanceof Closure) {
            $concrete = $this->getClosure($abstract, $concrete);
        }

        $this->bindings[$abstract] = compact('concrete', 'shared');
    }

    /**
     * drop stale instances
     * 
     * @param string $abstract
    */
    protected function dropStaleInstances($abstract)
    {
        unset($this->instances[$abstract], $this->aliases[$abstract]);
    }

    /**
     * get closure
     * 
     * @param string $abstract
     * @param string $concrete
    */
    protected function getClosure($abstract, $concrete)
    {
        return function ($container, $parameters = []) use ($abstract, $concrete) {
            if ($abstract == $concrete) {
                return $container->build($concrete);
            }

            return $container->resolve($concrete, $parameters);
        };
    }

    /**
     * make container
     * 
     * @param string $abstract
     * @param array $parameters
    */
    public function make($abstract, array $parameters = [])
    {
        return $this->resolve($abstract, $parameters);
    }

    /**
     * resolve container
     * 
     * @param string $abstract
     * @param array $parameters
    */
    protected function resolve($abstract, $parameters = [])
    {
        $abstract = $this->getAlias($abstract);

        if (isset($this->instances[$abstract])) {
            return $this->instances[$abstract];
        }

        $concrete = $this->getConcrete($abstract);

        if ($this->isBuildable($concrete, $abstract)) {
            $object = $this->build($concrete, $parameters);
        } else {
            $object = $this->make($concrete, $parameters);
        }

        if ($this->isShared($abstract)) {
            $this->instances[$abstract] = $object;
        }

        return $object;
    }

    /**
     * get alias
     * 
     * @param string $abstract
    */
    protected function getConcrete($abstract)
    {
        if (!isset($this->bindings[$abstract])) {
            return $abstract;
        }

        return $this->bindings[$abstract]['concrete'];
    }

    /**
     * is buildable
     * 
     * @param string $concrete
     * @param string $abstract
    */
    protected function isBuildable($concrete, $abstract)
    {
        return $concrete === $abstract || $concrete instanceof Closure;
    }

    /**
     * build container
     * 
     * @param string $concrete
     * @param array $parameters
    */
    public function build($concrete, array $parameters = [])
    {
        if ($concrete instanceof Closure) {
            return $concrete($this, $parameters);
        }

        $reflector = new ReflectionClass($concrete);

        if (!$reflector->isInstantiable()) {
            throw new \Exception(sprintf('Target [%s] is not instantiable.', $concrete));
        }

        $this->buildStack[] = $concrete;

        $constructor = $reflector->getConstructor();

        if (is_null($constructor)) {
            array_pop($this->buildStack);

            return new $concrete;
        }

        $dependencies = $constructor->getParameters();
        $instances = $this->resolveDependencies($dependencies, $parameters);

        array_pop($this->buildStack);

        return $reflector->newInstanceArgs($instances);
    }

    /**
     * resolve dependencies
     * 
     * @param array $dependencies
     * @param array $parameters
    */
    protected function resolveDependencies(array $dependencies, array $parameters)
    {
        $results = [];

        foreach ($dependencies as $dependency) {
            if (array_key_exists($dependency->getName(), $parameters)) {
                $results[] = $parameters[$dependency->getName()];
            } elseif ($dependency->getType() && !$dependency->getType()->isBuiltin()) {
                $results[] = $this->make($dependency->getType()->getName());
            } elseif ($dependency->isDefaultValueAvailable()) {
                $results[] = $dependency->getDefaultValue();
            } else {
                throw new \Exception(sprintf('Unresolvable dependency resolving [%s] in class %s', $dependency->getName(), $dependency->getDeclaringClass()->getName()));
            }
        }

        return $results;
    }

    /**
     * is shared
     * 
     * @param string $abstract
    */
    protected function isShared($abstract)
    {
        return isset($this->bindings[$abstract]['shared']) && $this->bindings[$abstract]['shared'] === true;
    }

    /**
     * singleton container
     * 
     * @param string $abstract
     * @param string $concrete
     * @param bool $shared
    */
    public function singleton($abstract, $concrete = null)
    {
        $this->bind($abstract, $concrete, true);
    }

    /**
     * instance container
     * 
     * @param string $abstract
     * @param string $instance
    */
    public function instance($abstract, $instance)
    {
        $this->instances[$abstract] = $instance;
    }

    /**
     * alias container
     * 
     * @param string $abstract
     * @param string $alias
    */
    public function alias($abstract, $alias)
    {
        $this->aliases[$alias] = $abstract;
    }

    /**
     * get alias
     * 
     * @param string $abstract
     * @return string
    */
    public function getAlias($abstract)
    {
        if (!isset($this->aliases[$abstract])) {
            return $abstract;
        }

        return $this->getAlias($this->aliases[$abstract]);
    }

    /**
     * call container
     * 
     * @param string $callback
     * @param array $parameters
    */
    public function call($callback, array $parameters = [])
    {
        try {
            if (is_array($callback)) {
                [$class, $method] = $callback;
                
                if (is_object($class)) {
                    $instance = $class;
                } else {
                    $instance = $this->make($class);
                }
                
                if (!method_exists($instance, $method)) {
                    throw new \Exception(
                        sprintf('Method %s does not exist on class %s', $method, get_class($instance))
                    );
                }

                $reflector = new ReflectionMethod($instance, $method);
            } else {
                $reflector = new ReflectionFunction($callback);
                $instance = null;
            }

            $dependencies = $this->resolveDependenciesForCall($reflector, $parameters);

            if ($instance) {
                return $reflector->invokeArgs($instance, $dependencies);
            }

            return $reflector->invokeArgs($dependencies);
        } catch (Throwable $e) {
            $context = is_array($callback) 
                ? (is_object($callback[0]) ? get_class($callback[0]) : $callback[0]) . '@' . $callback[1]
                : 'Closure';
                
            throw new \Exception(
                sprintf('Error calling %s: %s', $context, $e->getMessage()),
                0,
                $e
            );
        }
    }

    /**
     * resolve dependencies for call
     * 
     * @param ReflectionFunctionAbstract $reflector
     * @param array $parameters
    */
    protected function resolveDependenciesForCall(ReflectionFunctionAbstract $reflector, array $parameters = [])
    {
        $dependencies = [];

        foreach ($reflector->getParameters() as $parameter) {
            $dependency = $this->resolveCallDependency($parameter, $parameters);
            if (!array_key_exists($parameter->getName(), $parameters)) {
                $dependencies[] = $dependency;
            } else {
                $dependencies[$parameter->getName()] = $parameters[$parameter->getName()];
            }
        }

        return $this->keyParametersByPosition($reflector, $dependencies);
    }

    /**
     * resolve call dependency
     * 
     * @param ReflectionParameter $parameter
     * @param array $parameters
    */
    protected function resolveCallDependency(ReflectionParameter $parameter, array $parameters)
    {
        $name = $parameter->getName();
        $type = $parameter->getType();

        if (array_key_exists($name, $parameters)) {
            return $parameters[$name];
        }

        if ($type && !$type->isBuiltin()) {
            try {
                return $this->make($type->getName());
            } catch (\Exception $e) {
                if ($parameter->isDefaultValueAvailable()) {
                    return $parameter->getDefaultValue();
                }
                throw $e;
            }
        }

        if ($parameter->isDefaultValueAvailable()) {
            return $parameter->getDefaultValue();
        }

        throw new \Exception(
            sprintf(
                'Unable to resolve dependency %s for %s::%s',
                $name,
                $parameter->getDeclaringClass() ? $parameter->getDeclaringClass()->getName() : 'Closure',
                $parameter->getDeclaringFunction()->getName()
            )
        );
    }

    /**
     * key parameters by position
     * 
     * @param ReflectionFunctionAbstract $reflection
     * @param array $dependencies
    */
    protected function keyParametersByPosition(ReflectionFunctionAbstract $reflection, array $dependencies)
    {
        $position = 0;
        $parameters = [];

        foreach ($reflection->getParameters() as $parameter) {
            $name = $parameter->getName();

            if (array_key_exists($name, $dependencies)) {
                $parameters[$position] = $dependencies[$name];
            } elseif (array_key_exists($position, $dependencies)) {
                $parameters[$position] = $dependencies[$position];
            }

            $position++;
        }

        return $parameters;
    }

    /**
     * get
     * 
     * @param string $id
     * @return mixed
    */
    public function get(string $id): mixed
    {
        try {
            return $this->resolve($id);
        } catch (\Exception $e) {
            if ($this->has($id)) {
                throw $e;
            }
            throw new \Exception(sprintf('Target [%s] is not registered with the container.', $id));
        }
    }

    /**
     * has
     * 
     * @param string $id
     * @return bool
    */
    public function has(string $id): bool
    {
        return isset($this->bindings[$id]) || isset($this->instances[$id]) || isset($this->aliases[$id]);
    }

    /**
     * offsetExists
     * 
     * @param string $key
     * @return bool
    */
    public function offsetExists($key): bool
    {
        return $this->has($key);
    }

    /**
     * offsetGet
     * 
     * @param string $key
     * @return mixed
    */
    public function offsetGet($key): mixed
    {
        return $this->make($key);
    }

    /**
     * offsetSet
     * 
     * @param string $key
     * @param mixed $value
    */
    public function offsetSet($key, $value): void
    {
        $this->bind($key, $value instanceof Closure ? $value : function () use ($value) {
            return $value;
        });
    }

    /**
     * offsetUnset
     * 
     * @param string $key
    */
    public function offsetUnset($key): void
    {
        unset($this->bindings[$key], $this->instances[$key], $this->aliases[$key]);
    }
}