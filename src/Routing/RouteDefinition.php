<?php

declare(strict_types=1);

namespace Velolia\Routing;

class RouteDefinition
{
    public function __construct(
        protected Router $router,
        protected array $methods,
        protected string $uri
    ) {}

    public function name(string $name): self
    {
        $this->router->updateRouteAttribute($this->methods, $this->uri, 'name', $name);
        return $this;
    }

    public function middleware(string|array $middleware): self
    {
        $this->router->updateRouteAttribute($this->methods, $this->uri, 'middleware', (array) $middleware);
        return $this;
    }
}
