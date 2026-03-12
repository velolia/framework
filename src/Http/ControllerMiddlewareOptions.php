<?php

declare(strict_types=1);

namespace Velolia\Http;

class ControllerMiddlewareOptions
{
    public function __construct(protected array &$options) {}

    public function only(array|string $methods): self
    {
        $this->options['only'] = (array) $methods;
        return $this;
    }

    public function except(array|string $methods): self
    {
        $this->options['except'] = (array) $methods;
        return $this;
    }
}
