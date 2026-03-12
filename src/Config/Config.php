<?php

declare(strict_types=1);

namespace Velolia\Config;

use ArrayAccess;
use InvalidArgumentException;

class Config implements ArrayAccess
{
    protected array $items = [];

    public function __construct(array $items = [])
    {
        $this->items = $items;
    }

    public function load(string $path): self
    {
        if (!is_dir($path)) {
            throw new InvalidArgumentException("The provided path is not a valid directory: {$path}");
        }

        $files = glob($path . '/*.php');

        foreach ($files as $file) {
            $key = basename($file, '.php');
            $config = include $file;

            if (is_array($config)) {
                $this->set($key, $config);
            }
        }

        return $this;
    }

    public function all(): array
    {
        return $this->items;
    }

    public function get(string $key, $default = null)
    {
        if (is_null($key)) {
            return $this->items;
        }

        if (array_key_exists($key, $this->items)) {
            return $this->items[$key];
        }

        if (strpos($key, '.') === false) {
            return $default;
        }

        $keys = explode('.', $key);
        $value = $this->items;

        foreach ($keys as $segment) {
            if (is_array($value) && array_key_exists($segment, $value)) {
                $value = $value[$segment];
            } else {
                return $default;
            }
        }

        return $value;
    }

    public function set($key, $value = null)
    {
        if (is_array($key)) {
            foreach ($key as $innerKey => $innerValue) {
                $this->set($innerKey, $innerValue);
            }
            return;
        }

        if (strpos($key, '.') === false) {
            $this->items[$key] = $value;
            return;
        }

        $keys = explode('.', $key);
        $array = &$this->items;

        while (count($keys) > 1) {
            $segment = array_shift($keys);

            if (! isset($array[$segment]) || ! is_array($array[$segment])) {
                $array[$segment] = [];
            }

            $array = &$array[$segment];
        }

        $array[array_shift($keys)] = $value;
    }

    public function has($key)
    {
        if (array_key_exists($key, $this->items)) {
            return true;
        }

        if (strpos($key, '.') === false) {
            return false;
        }

        $keys = explode('.', $key);
        $array = $this->items;

        foreach ($keys as $segment) {
            if (is_array($array) && array_key_exists($segment, $array)) {
                $array = $array[$segment];
            } else {
                return false;
            }
        }

        return true;
    }

    public function remove($key)
    {
        if (strpos($key, '.') === false) {
            unset($this->items[$key]);
            return;
        }

        $keys = explode('.', $key);
        $array = &$this->items;
        $lastKey = array_pop($keys);
        
        foreach ($keys as $segment) {
            if (! isset($array[$segment]) || ! is_array($array[$segment])) {
                return;
            }
            
            $array = &$array[$segment];
        }
        
        unset($array[$lastKey]);
    }

    public function merge($key, array $values)
    {
        if (strpos($key, '.') === false) {
            $this->items[$key] = array_merge(
                $this->get($key, []), $values
            );
            return;
        }

        $keys = explode('.', $key);
        $array = &$this->items;
        
        while (count($keys) > 1) {
            $segment = array_shift($keys);
            
            if (! isset($array[$segment]) || ! is_array($array[$segment])) {
                $array[$segment] = [];
            }
            
            $array = &$array[$segment];
        }
        
        $lastKey = array_shift($keys);
        
        if (! isset($array[$lastKey]) || ! is_array($array[$lastKey])) {
            $array[$lastKey] = [];
        }
        
        $array[$lastKey] = array_merge($array[$lastKey], $values);
    }

    public function offsetExists($key): bool
    {
        return $this->has($key);
    }

    public function offsetGet($key): mixed
    {
        return $this->get($key);
    }

    public function offsetSet($key, $value): void
    {
        $this->set($key, $value);
    }

    public function offsetUnset($key): void
    {
        $this->remove($key);
    }
}