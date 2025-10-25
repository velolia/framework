<?php

declare(strict_types=1);

namespace Velolia\Config;

use ArrayAccess;

class Config implements ArrayAccess
{
    /**
     * The configuration items.
     *
     * @var array
     */
    protected array $items = [];

    /**
     * Create a new configuration repository.
     *
     * @param  array  $items
     * @return void
     */
    public function __construct(array $items = [])
    {
        $this->items = $items;
    }
    
    /**
     * Load configuration files from a directory.
     *
     * @param  string  $path
     * @return self
     */
    public function load(string $path): self
    {
        if (!is_dir($path)) {
            throw new \InvalidArgumentException("The provided path is not a valid directory: {$path}");
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

    /**
     * Get all configuration items.
     *
     * @return array
     */
    public function all()
    {
        return $this->items;
    }

    /**
     * Get the specified configuration value.
     *
     * @param  string|null  $key
     * @param  mixed  $default
     * @return mixed
     */
    public function get($key = null, $default = null)
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

    /**
     * Set a configuration item.
     *
     * @param  string  $key
     * @param  mixed  $value
     * @return void
     */
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

    /**
     * Check if a configuration item exists.
     *
     * @param  string  $key
     * @return bool
     */
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

    /**
     * Remove a configuration item.
     *
     * @param  string  $key
     * @return void
     */
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

    /**
     * Merge configuration items.
     *
     * @param  string  $key
     * @param  array  $values
     * @return void
     */
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

    /**
     * Determine if the given configuration option exists.
     *
     * @param  string  $key
     * @return bool
     */
    public function offsetExists($key): bool
    {
        return $this->has($key);
    }

    /**
     * Get a configuration option.
     *
     * @param  string  $key
     * @return mixed
     */
    public function offsetGet($key): mixed
    {
        return $this->get($key);
    }

    /**
     * Set a configuration option.
     *
     * @param  string  $key
     * @param  mixed  $value
     * @return void
     */
    public function offsetSet($key, $value): void
    {
        $this->set($key, $value);
    }

    /**
     * Unset a configuration option.
     *
     * @param  string  $key
     * @return void
     */
    public function offsetUnset($key): void
    {
        $this->remove($key);
    }
}