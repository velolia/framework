<?php

declare(strict_types=1);

namespace Velolia\Support;

use ArrayAccess;
use Countable;
use IteratorAggregate;
use ArrayIterator;
use Closure;

class Collection implements ArrayAccess, Countable, IteratorAggregate
{
    protected array $items = [];

    public function __construct(array $items = [])
    {
        $this->items = $items;
    }

    public function all(): array
    {
        return $this->items;
    }

    public function pluck(string $value, ?string $key = null): static
    {
        $results = [];

        foreach ($this->items as $item) {
            $itemValue = is_object($item) ? $item->$value : $item[$value];

            if (is_null($key)) {
                $results[] = $itemValue;
            } else {
                $itemKey = is_object($item) ? $item->$key : $item[$key];
                $results[$itemKey] = $itemValue;
            }
        }

        return new static($results);
    }

    public function map(callable $callback): static
    {
        return new static(array_map($callback, $this->items));
    }

    public function filter(?callable $callback = null): static
    {
        if ($callback) {
            return new static(array_filter($this->items, $callback));
        }

        return new static(array_filter($this->items));
    }

    public function first(?callable $callback = null, $default = null)
    {
        if (is_null($callback)) {
            if (empty($this->items)) {
                return $default;
            }

            foreach ($this->items as $item) {
                return $item;
            }
        }

        foreach ($this->items as $key => $value) {
            if ($callback($value, $key)) {
                return $value;
            }
        }

        return $default;
    }

    public function toArray(): array
    {
        return array_map(function ($value) {
            return $value instanceof self ? $value->toArray() : (is_object($value) && method_exists($value, 'toArray') ? $value->toArray() : (array) $value);
        }, $this->items);
    }

    public function getIterator(): ArrayIterator
    {
        return new ArrayIterator($this->items);
    }

    public function offsetExists($offset): bool
    {
        return isset($this->items[$offset]);
    }

    public function offsetGet($offset): mixed
    {
        return $this->items[$offset];
    }

    public function offsetSet($offset, $value): void
    {
        if (is_null($offset)) {
            $this->items[] = $value;
        } else {
            $this->items[$offset] = $value;
        }
    }

    public function contains($key, $operator = null, $value = null): bool
    {
        if (func_num_args() === 1) {
            if ($key instanceof Closure) {
                return !is_null($this->first($key));
            }

            return in_array($key, $this->items);
        }

        if (func_num_args() === 2) {
            $value = $operator;
            $operator = '=';
        }

        foreach ($this->items as $item) {
            $actual = is_object($item) ? ($item->$key ?? null) : ($item[$key] ?? null);

            if ($operator === '=') {
                if ($actual == $value) return true;
            }
        }

        return false;
    }

    public function offsetUnset($offset): void
    {
        unset($this->items[$offset]);
    }

    public function count(): int
    {
        return count($this->items);
    }
}