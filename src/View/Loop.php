<?php

declare(strict_types=1);

namespace Velolia\View;

class Loop
{
    protected int $index = 0;
    protected int $iteration = 1;
    protected ?int $remaining;
    protected int $count;
    protected ?Loop $parent;

    public function __construct(int $count, ?Loop $parent = null)
    {
        $this->count = $count;
        $this->remaining = $count > 0 ? $count - 1 : 0;
        $this->parent = $parent;
    }

    public function __get(string $key): mixed
    {
        return match ($key) {
            'index' => $this->index,
            'iteration' => $this->iteration,
            'remaining' => $this->remaining,
            'count' => $this->count,
            'first' => $this->index === 0,
            'last' => $this->remaining === 0,
            'odd' => $this->iteration % 2 !== 0,
            'even' => $this->iteration % 2 === 0,
            'depth' => $this->parent ? $this->parent->depth + 1 : 1,
            'parent' => $this->parent,
            default => null,
        };
    }

    public function next(): void
    {
        $this->index++;
        $this->iteration++;
        
        if ($this->remaining !== null && $this->remaining > 0) {
            $this->remaining--;
        }
    }
}
