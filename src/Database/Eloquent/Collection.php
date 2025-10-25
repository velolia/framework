<?php

declare(strict_types=1);

namespace Velolia\Database\Eloquent;

use Velolia\Support\Collection as BaseCollection;

class Collection extends BaseCollection
{
    public function keyBy(string|callable $key): static
    {
        $results = [];

        foreach ($this->items as $item) {
            if (is_callable($key)) {
                $k = $key($item);
            } else {
                $k = is_array($item)
                    ? ($item[$key] ?? null)
                    : ($item->{$key} ?? null);
            }
            if ($k !== null) {
                $results[$k] = $item;
            }
        }

        return new static($results);
    }

    /**
     * Transform each item in the collection using a callback.
     *
     * @param  callable  $callback
     * @return static
     */
    public function map(callable $callback): static
    {
        $results = [];

        foreach ($this->items as $key => $item) {
            $results[$key] = $callback($item, $key);
        }

        return new static($results);
    }

    public function implode(string $glue): string
    {
        return implode($glue, $this->items);
    }

    public function first(): mixed
    {
        return $this->items[0] ?? null;
    }

    public function groupBy(string $key): self
    {
        $grouped = [];
        foreach ($this->items as $item) {
            $value = is_array($item) ? $item[$key] : $item->$key;
            $grouped[$value][] = $item;
        }

        foreach ($grouped as $k => $v) {
            $grouped[$k] = new static($v);
        }

        return new static($grouped);
    }
}