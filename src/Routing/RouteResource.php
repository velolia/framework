<?php

declare(strict_types=1);

namespace Velolia\Routing;

class RouteResource
{
    protected array $only = [];
    protected array $except = [];
    protected array $names = [];
    protected array $defaultActions = [
        'index' => ['method' => 'GET', 'uri' => '', 'action' => 'index'],
        'create' => ['method' => 'GET', 'uri' => '/create', 'action' => 'create'],
        'store' => ['method' => 'POST', 'uri' => '', 'action' => 'store'],
        'show' => ['method' => 'GET', 'uri' => '/{id}', 'action' => 'show'],
        'edit' => ['method' => 'GET', 'uri' => '/{id}/edit', 'action' => 'edit'],
        'update' => ['method' => 'PUT', 'uri' => '/{id}', 'action' => 'update'],
        'destroy' => ['method' => 'DELETE', 'uri' => '/{id}', 'action' => 'destroy'],
    ];

    public function __construct(
        protected Router $router,
        protected string $name,
        protected string $controller
    ) {
        $this->register();
    }

    public function only(string|array $actions): self
    {
        $this->only = (array) $actions;
        $this->register();
        return $this;
    }

    public function except(string|array $actions): self
    {
        $this->except = (array) $actions;
        $this->register();
        return $this;
    }

    public function names(array $names): self
    {
        $this->names = $names;
        $this->register();
        return $this;
    }

    public function name(string $action, string $name): self
    {
        $this->names[$action] = $name;
        $this->register();
        return $this;
    }

    protected function register(): void
    {
        $actions = $this->getResourceActions();

        foreach ($actions as $action => $config) {
            $uri = '/' . trim($this->name, '/') . $config['uri'];
            $handler = [$this->controller, $config['action']];
            
            $method = strtolower($config['method']);
            $this->router->$method($uri, $handler);
            
            $routeName = $this->getRouteName($action);
            $this->router->name($routeName);
        }
    }

    protected function getResourceActions(): array
    {
        $actions = $this->defaultActions;

        if (!empty($this->only)) {
            $actions = array_intersect_key($actions, array_flip($this->only));
        }

        if (!empty($this->except)) {
            $actions = array_diff_key($actions, array_flip($this->except));
        }

        return $actions;
    }

    protected function getRouteName(string $action): string
    {
        if (isset($this->names[$action])) {
            return $this->names[$action];
        }

        return $this->name . '.' . $action;
    }
}