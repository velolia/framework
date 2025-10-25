<?php

declare(strict_types=1);

namespace Velolia\Support;

use ArrayAccess;
use ArrayIterator;
use Countable;
use IteratorAggregate;
use JsonSerializable;

class Collection implements ArrayAccess, Countable, IteratorAggregate, JsonSerializable
{
    /**
     * The items contained in the collection.
     * 
     * @var array
    */
    protected $items = [];

    /**
     * Create a new collection instance.
     *
     * @param  array  $items
     * @return void
     */
    public function __construct(array $items = [])
    {
        $this->items = $items;
    }

    /**
     * Get all of the items in the collection.
     *
     * @return array
     */
    public function all()
    {
        return $this->items;
    }

    /**
     * Get the first item from the collection.
     */
    public function first(): mixed
    {
        return $this->items[0] ?? null;
    }

    /**
     * Determine if the collection is empty.
     *
     * @return bool
     */
    public function isEmpty()
    {
        return empty($this->items);
    }

    /**
     * Pluck an array of values from an array.
     *
     * @param  string|array  $value
     * @param  string|null  $key
     * @return static
     */
    public function pluck(string $key): self
    {
        $values = [];
        foreach ($this->items as $item) {
            if (is_array($item) && isset($item[$key])) {
                $values[] = $item[$key];
            } elseif (is_object($item) && isset($item->$key)) {
                $values[] = $item->$key;
            }
        }
        return new static($values);
    }

    /**
     * Get the collection of items as a plain array.
     */
    public function toArray(): array
    {
        return array_map(function ($value) {
            return is_object($value) && method_exists($value, 'toArray') ? $value->toArray() : $value;
        }, $this->items);
    }

    /**
     * Get the collection of items as JSON.
     */
    public function toJson(int $options = 0): string
    {
        return json_encode($this->toArray(), $options);
    }

    /**
     * Count the number of items in the collection.
     */
    public function count(): int
    {
        return count($this->items);
    }

    /**
     * Get an iterator for the items.
     */
    public function getIterator(): ArrayIterator
    {
        return new ArrayIterator($this->items);
    }

    /**
     * Determine if an item exists at an offset.
     */
    public function offsetExists(mixed $offset): bool
    {
        return array_key_exists($offset, $this->items);
    }

    /**
     * Get an item at a given offset.
     */
    public function offsetGet(mixed $offset): mixed
    {
        return $this->items[$offset];
    }

    /**
     * Set the item at a given offset.
     */
    public function offsetSet(mixed $offset, mixed $value): void
    {
        if ($offset === null) {
            $this->items[] = $value;
        } else {
            $this->items[$offset] = $value;
        }
    }

    /**
     * Unset the item at a given offset.
     */
    public function offsetUnset(mixed $offset): void
    {
        unset($this->items[$offset]);
    }

    /**
     * Convert the object to its JSON representation.
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    /**
     * Convert the collection to its string representation.
     */
    public function __toString(): string
    {
        return $this->toJson();
    }
}