<?php

declare(strict_types=1);

namespace Velolia\Routing;

use Velolia\Database\Model;
use Velolia\Http\Request;
use Velolia\Http\Response;

class Router
{
    protected array $staticRoutes = [];
    protected array $variableRoutes = [];
    protected array $compiledRoutes = [];
    protected array $namedRoutes = [];
    protected array $groupStack = [];
    protected array $reflectionCache = [];
    protected array $middlewareGroups = [];
    protected array $routeMiddleware = [];

    public function __construct(protected \Velolia\Core\Application $app) {}

    public function setMiddlewareGroups(array $groups): void
    {
        $this->middlewareGroups = $groups;
    }

    public function setRouteMiddleware(array $middleware): void
    {
        $this->routeMiddleware = $middleware;
    }

    protected array $tempAttributes = [];

    protected const CHUNK_SIZE = 10;
    protected bool $isCompiled = false;

    public function group(array|callable $attributes, ?callable $callback = null): void
    {
        if (is_callable($attributes)) {
            $callback = $attributes;
            $attributes = $this->tempAttributes;
            $this->tempAttributes = [];
        }

        $this->updateGroupStack($attributes);

        if ($callback) {
            $callback($this);
        }

        array_pop($this->groupStack);
    }

    protected function updateGroupStack(array $attributes): void
    {
        $last = end($this->groupStack) ?: [];

        $new = [
            'prefix' => trim(($last['prefix'] ?? '') . '/' . trim($attributes['prefix'] ?? '', '/'), '/'),
            'middleware' => array_merge($last['middleware'] ?? [], (array) ($attributes['middleware'] ?? [])),
            'name' => ($last['name'] ?? '') . ($attributes['name'] ?? ''),
        ];

        $this->groupStack[] = $new;
    }

    public function prefix(string $prefix): self
    {
        $this->tempAttributes['prefix'] = $prefix;
        return $this;
    }

    public function middleware(string|array $middleware): self
    {
        $this->tempAttributes['middleware'] = array_merge($this->tempAttributes['middleware'] ?? [], (array) $middleware);
        return $this;
    }

    public function name(string $name): self
    {
        $this->tempAttributes['name'] = ($this->tempAttributes['name'] ?? '') . $name;
        return $this;
    }

    public function addRoute(string|array $methods, string $uri, callable|array|string $action): RouteDefinition
    {
        $methods = (array) $methods;

        $group = end($this->groupStack) ?: [];
        if (!empty($this->tempAttributes)) {
            $group = [
                'prefix' => trim(($group['prefix'] ?? '') . '/' . trim($this->tempAttributes['prefix'] ?? '', '/'), '/'),
                'middleware' => array_merge($group['middleware'] ?? [], (array) ($this->tempAttributes['middleware'] ?? [])),
                'name' => ($group['name'] ?? '') . ($this->tempAttributes['name'] ?? ''),
            ];
            $this->tempAttributes = [];
        }

        $prefix = $group['prefix'] ?? '';
        $uri = trim($prefix . '/' . trim($uri, '/'), '/');
        $uri = ($uri === '') ? '/' : '/' . $uri;

        $routeData = [
            'action' => $action,
            'middleware' => $group['middleware'] ?? [],
            'name' => $group['name'] ?? null,
        ];

        foreach ($methods as $method) {
            $method = strtoupper($method);
            if (str_contains($uri, '{')) {
                $variables = [];
                if (preg_match_all('/\{([a-zA-Z0-9_]+)\}/', $uri, $matches)) {
                    $variables = $matches[1];
                }
                $regex = preg_replace('/\{[a-zA-Z0-9_]+\}/', '([^/]+)', preg_quote($uri, '~'));
                $this->variableRoutes[$method][] = array_merge($routeData, [
                    'original_uri' => $uri,
                    'regex' => $regex,
                    'variables' => $matches[1],
                ]);
            } else {
                $this->staticRoutes[$method][$uri] = $routeData;
            }

            if ($routeData['name']) {
                $this->namedRoutes[$routeData['name']] = $uri;
            }
        }

        return new RouteDefinition($this, $methods, $uri);
    }

    public function get(string $uri, callable|array|string $action): RouteDefinition
    {
        return $this->addRoute('GET', $uri, $action);
    }

    public function post(string $uri, callable|array|string $action): RouteDefinition
    {
        return $this->addRoute('POST', $uri, $action);
    }

    public function put(string $uri, callable|array|string $action): RouteDefinition
    {
        return $this->addRoute('PUT', $uri, $action);
    }

    public function patch(string $uri, callable|array|string $action): RouteDefinition
    {
        return $this->addRoute('PATCH', $uri, $action);
    }

    public function delete(string $uri, callable|array|string $action): RouteDefinition
    {
        return $this->addRoute('DELETE', $uri, $action);
    }

    public function resource(string $name, string $controller): void
    {
        $this->get("$name", [$controller, 'index'])->name("$name.index");
        $this->get("$name/create", [$controller, 'create'])->name("$name.create");
        $this->post("$name", [$controller, 'store'])->name("$name.store");
        $this->get("$name/{id}", [$controller, 'show'])->name("$name.show");
        $this->get("$name/{id}/edit", [$controller, 'edit'])->name("$name.edit");
        $this->put("$name/{id}", [$controller, 'update'])->name("$name.update");
        $this->patch("$name/{id}", [$controller, 'update'])->name("$name.patch");
        $this->delete("$name/{id}", [$controller, 'destroy'])->name("$name.destroy");
    }

    protected function compileRoutes(): void
    {
        if ($this->isCompiled || empty($this->variableRoutes)) {
            return;
        }

        foreach ($this->variableRoutes as $method => $routes) {
            $chunks = array_chunk($routes, self::CHUNK_SIZE);

            foreach ($chunks as $chunk) {
                $regexes = [];
                $routeMap = [];
                $groupOffset = 1;

                foreach ($chunk as $route) {
                    $numVariables = count($route['variables']);

                    $routeMap[$groupOffset + $numVariables] = [
                        'action'     => $route['action'],
                        'variables'  => $route['variables'],
                        'middleware' => $route['middleware'],
                        'offset'     => $groupOffset,
                    ];

                    $groupOffset += $numVariables + 1;
                    $regexes[] = $route['regex'] . '()';
                }

                $this->compiledRoutes[$method][] = [
                    'regex'    => '~^(?:' . implode('|', $regexes) . ')$~',
                    'routeMap' => $routeMap,
                ];
            }
        }

        $this->isCompiled = true;
    }

    public function getRouteUrl(string $name, array $params = []): string
    {
        if (!isset($this->namedRoutes[$name])) {
            throw new \Exception("Route [$name] not found.");
        }

        $uri = $this->namedRoutes[$name];

        if (!empty($params) && array_is_list($params)) {
            foreach ($params as $value) {
                $uri = preg_replace('/\{[a-zA-Z0-9_]+\}/', (string) $value, $uri, 1);
            }
        } else {
            foreach ($params as $key => $value) {
                $uri = str_replace('{' . $key . '}', (string) $value, $uri);
            }
        }

        return $uri;
    }

    public function updateRouteAttribute(array $methods, string $uri, string $key, $value): void
    {
        foreach ($methods as $method) {
            $method = strtoupper($method);

            if (isset($this->staticRoutes[$method][$uri])) {
                if ($key === 'middleware') {
                    $this->staticRoutes[$method][$uri][$key] = array_merge($this->staticRoutes[$method][$uri][$key], $value);
                } else {
                    $this->staticRoutes[$method][$uri][$key] = $value;
                }

                if ($key === 'name') {
                    $this->namedRoutes[$value] = $uri;
                }
            }

            if (isset($this->variableRoutes[$method])) {
                foreach ($this->variableRoutes[$method] as &$route) {
                    if ($route['regex'] === preg_replace('/\{[a-zA-Z0-9_]+\}/', '([^/]+)', $uri)) {
                        if ($key === 'middleware') {
                            $route[$key] = array_merge($route[$key], $value);
                        } else {
                            $route[$key] = $value;
                        }

                        if ($key === 'name') {
                            $this->namedRoutes[$value] = $uri;
                        }
                    }
                }
            }
        }
    }

    public function match(Request $request): ?array
    {
        $method = $request->method();
        $uri    = $request->getPathInfo();

        if (isset($this->staticRoutes[$method][$uri])) {
            return array_merge($this->staticRoutes[$method][$uri], ['params' => []]);
        }

        foreach ($this->staticRoutes as $registeredMethod => $routes) {
            if ($registeredMethod !== $method && isset($routes[$uri])) {
                abort(405, "Method Not Allowed");
            }
        }

        $this->compileRoutes();

        if (isset($this->compiledRoutes[$method])) {
            foreach ($this->compiledRoutes[$method] as $chunk) {
                if (preg_match($chunk['regex'], $uri, $matches, PREG_UNMATCHED_AS_NULL)) {
                    foreach ($chunk['routeMap'] as $markerIndex => $routeData) {
                        if (isset($matches[$markerIndex])) {
                            $params = [];
                            $offset = $routeData['offset'];

                            foreach ($routeData['variables'] as $i => $name) {
                                $params[$name] = $matches[$offset + $i] ?? '';
                            }

                            return [
                                'action'     => $routeData['action'],
                                'middleware' => $routeData['middleware'],
                                'params'     => $params,
                            ];
                        }
                    }
                }
            }
        }

        foreach ($this->variableRoutes as $registeredMethod => $routes) {
            if ($registeredMethod !== $method) {
                foreach ($routes as $route) {
                    if (preg_match('~^' . $route['regex'] . '$~', $uri)) {
                        abort(405, "Method Not Allowed");
                    }
                }
            }
        }

        abort(404, "Not Found");

        return null;
    }

    public function dispatch(Request $request): Response
    {
        $route = $this->match($request);

        if (!$route) {
            abort(404);
        }

        $middlewares = $route['middleware'] ?? [];

        foreach ($route['params'] as $key => $value) {
            $request->attribute($key, $value);
        }

        if (is_array($route['action'])) {
            [$controllerClass, $method] = $route['action'];
            if (is_string($controllerClass)) {
                $controller = $this->app->resolve($controllerClass);

                if (method_exists($controller, 'getMiddleware')) {
                    foreach ($controller->getMiddleware() as $middlewareData) {
                        $middleware = $middlewareData['middleware'];
                        $options = $middlewareData['options'];

                        if ($this->middlewareShouldRun($method, $options)) {
                            $middlewares[] = $middleware;
                        }
                    }
                }
            }
        }

        $resolvedMiddlewares = $this->resolveMiddleware($middlewares);

        return (new \Velolia\Http\Pipeline($this->app))
            ->send($request)
            ->through($resolvedMiddlewares)
            ->then(function ($request) use ($route) {
                return $this->runAction($route['action'], $request, $route['params'] ?? []);
            });
    }

    protected function middlewareShouldRun(string $method, array $options): bool
    {
        if (isset($options['only']) && !in_array($method, $options['only'])) {
            return false;
        }

        if (isset($options['except']) && in_array($method, $options['except'])) {
            return false;
        }

        return true;
    }

    protected function resolveMiddleware(array $middlewares): array
    {
        $resolved = [];

        foreach ($middlewares as $middleware) {
            $name = $middleware;
            $parameters = '';

            if (is_string($middleware) && str_contains($middleware, ':')) {
                [$name, $parameters] = explode(':', $middleware, 2);
            }

            if (isset($this->middlewareGroups[$name])) {
                $resolved = array_merge($resolved, $this->middlewareGroups[$name]);
            } elseif (isset($this->routeMiddleware[$name])) {
                $resolved[] = $this->routeMiddleware[$name] . ($parameters !== '' ? ':' . $parameters : '');
            } else {
                $resolved[] = $middleware;
            }
        }

        return $resolved;
    }
    protected function runAction(mixed $action, Request $request, array $vars = []): Response
    {
        $reflection = $this->getReflection($action);
        $resolvedVars = $this->resolveArgs($reflection->getParameters(), $request, $vars);

        $result = $this->app->call($action, $resolvedVars);

        if ($result instanceof Response) {
            return $result;
        }

        if ($result instanceof \Velolia\View\View) {
            return new Response($result->render());
        }

        if (is_array($result) || $result instanceof \JsonSerializable) {
            return Response::json($result);
        }

        return new Response((string) $result);
    }

    protected function getReflection(mixed $action): \ReflectionFunctionAbstract
    {
        $cacheKey = $this->getReflectionCacheKey($action);
        if (isset($this->reflectionCache[$cacheKey])) {
            return $this->reflectionCache[$cacheKey];
        }

        if ($action instanceof \Closure) {
            $reflector = new \ReflectionFunction($action);
        } elseif (is_array($action)) {
            $reflector = new \ReflectionMethod($action[0], $action[1]);
        } elseif (is_string($action) && str_contains($action, '@')) {
            [$class, $method] = explode('@', $action);
            $reflector = new \ReflectionMethod($class, $method);
        } else {
            throw new \Exception("Invalid route action.");
        }

        return $this->reflectionCache[$cacheKey] = $reflector;
    }

    protected function getReflectionCacheKey(mixed $action): string
    {
        if ($action instanceof \Closure) {
            return 'closure_' . spl_object_hash($action);
        }
        if (is_array($action)) {
            return (is_object($action[0]) ? get_class($action[0]) : $action[0]) . '@' . $action[1];
        }
        return (string) $action;
    }

    public function exportRoutes(): array
    {
        $this->compileRoutes();

        $checkAction = function (mixed $action) {
            if ($action instanceof \Closure) {
                throw new \Exception("Route caching does not support Closures. Please use Controller actions.");
            }
        };

        foreach ($this->staticRoutes as $method => $routes) {
            foreach ($routes as $uri => $route) {
                $checkAction($route['action'] ?? null);
            }
        }

        foreach ($this->variableRoutes as $method => $routes) {
            foreach ($routes as $route) {
                $checkAction($route['action'] ?? null);
            }
        }

        return [
            'staticRoutes'   => $this->staticRoutes,
            'variableRoutes' => $this->variableRoutes,
            'compiledRoutes' => $this->compiledRoutes,
            'namedRoutes'    => $this->namedRoutes,
        ];
    }

    public function importCache(array $data): void
    {
        $this->staticRoutes   = $data['staticRoutes'] ?? [];
        $this->variableRoutes = $data['variableRoutes'] ?? [];
        $this->compiledRoutes = $data['compiledRoutes'] ?? [];
        $this->namedRoutes    = $data['namedRoutes'] ?? [];
        $this->isCompiled     = true;
    }

    protected function resolveArgs(array $reflectionParams, Request $request, array $vars): array
    {
        $args = [];

        foreach ($reflectionParams as $param) {
            $name      = $param->getName();
            $type      = $param->getType();
            $className = $type && !$type->isBuiltin() ? $type->getName() : null;

            if ($className && ($className === Request::class || is_subclass_of($className, Request::class))) {
                $args[$name] = $request;
                continue;
            }

            if ($className && is_subclass_of($className, Model::class)) {
                $id = $vars[$name] ?? array_shift($vars);
                $args[$name] = $className::findOrFail($id);
                continue;
            }

            if (isset($vars[$name])) {
                $args[$name] = $vars[$name];
                unset($vars[$name]);
                continue;
            }

            if (!empty($vars)) {
                $args[$name] = array_shift($vars);
                continue;
            }

            if ($className) {
                try {
                    $args[$name] = $this->app->make($className);
                } catch (\Throwable) {
                    //
                }
            }
        }

        return $args;
    }
}
