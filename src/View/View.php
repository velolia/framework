<?php

declare(strict_types=1);

namespace Velolia\View;

class View
{
    protected array $data = [];

    public function __construct(protected Factory $factory, protected string $path, array $data = [])
    {
        $this->data = $data;
    }

    public function with(string|array $key, mixed $value = null): self
    {
        if (is_array($key)) {
            $this->data = array_merge($this->data, $key);
        } else {
            $this->data[$key] = $value;
        }
        return $this;
    }

    public function render(): string
    {
        return $this->factory->render($this->path, $this->data);
    }

    public function __toString(): string
    {
        return $this->render();
    }
}