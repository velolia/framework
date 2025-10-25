<?php

declare(strict_types=1);

namespace Velolia\Routing;

use Closure;
use InvalidArgumentException;
use RuntimeException;
use ReflectionFunction;
use ReflectionMethod;
use ReflectionParameter;
use ReflectionNamedType;
use Velolia\Foundation\Application;
use Velolia\Http\Request;
use Velolia\Http\Response;
use Velolia\Database\Eloquent\Model;
use Velolia\Routing\Exception\RouteNotFoundException;
use Velolia\Routing\Exception\ControllerMethodNotFoundException;

class Router
{
    protected Application $app;
    protected string $currentPrefix = '';
    protected array $currentMiddleware = [];
    protected array $namedRoutes = [];
    protected ?string $lastRoutePath = null;
    protected ?string $urlGeneratorBaseUrl = null;
    
    private array $staticRoutes = [];
    private array $dynamicRoutes = [];

    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    // ==================== HTTP Method Shortcuts ====================
    
    public function get(string $path, $handler): self
    {
        return $this->addRoute('GET', $path, $handler);
    }

    public function post(string $path, $handler): self
    {
        return $this->addRoute('POST', $path, $handler);
    }

    public function put(string $path, $handler): self
    {
        return $this->addRoute('PUT', $path, $handler);
    }

    public function patch(string $path, $handler): self
    {
        return $this->addRoute('PATCH', $path, $handler);
    }

    public function delete(string $path, $handler): self
    {
        return $this->addRoute('DELETE', $path, $handler);
    }

    public function options(string $path, $handler): self
    {
        return $this->addRoute('OPTIONS', $path, $handler);
    }

    // ==================== Route Registration ====================

    public function addRoute(string $method, string $path, $handler): self
    {
        $path = $this->normalizePath($this->currentPrefix . '/' . trim($path, '/'));
        
        $route = $this->createRouteDefinition($method, $path, $handler, $this->currentMiddleware);
        $this->addRouteToCollection($route);
        
        $this->lastRoutePath = $path;

        return $this;
    }

    // ==================== Route Grouping ====================

    public function group(array $attributes, Closure $callback): self
    {
        $previousPrefix = $this->currentPrefix;
        $previousMiddleware = $this->currentMiddleware;

        if (isset($attributes['prefix'])) {
            $this->currentPrefix = $this->normalizePrefix($previousPrefix, $attributes['prefix']);
        }

        if (isset($attributes['middleware'])) {
            $middlewares = (array) $attributes['middleware'];
            $this->currentMiddleware = array_merge($previousMiddleware, $middlewares);
        }

        $callback($this);

        $this->currentPrefix = $previousPrefix;
        $this->currentMiddleware = $previousMiddleware;

        return $this;
    }

    // ==================== Named Routes ====================

    public function name(string $name): self
    {
        if ($this->lastRoutePath) {
            $this->namedRoutes[$name] = $this->lastRoutePath;
        }
        return $this;
    }

    // ==================== Middleware ====================

    public function middleware(string|array $middleware): self
    {
        if ($this->lastRoutePath) {
            $middlewares = (array) $middleware;
            $this->applyMiddlewareToRoute($this->lastRoutePath, $middlewares);
        }
        return $this;
    }

    // ==================== Resource Routes ====================

    public function resource(string $name, string $controller): RouteResource
    {
        return new RouteResource($this, $name, $controller);
    }

    // ==================== Route Dispatching ====================

    public function dispatch(Request $request): Response
    {
        $matched = $this->matchRoute($request->getMethod(), $request->getPath());

        if (!$matched) {
            throw new RouteNotFoundException();
        }

        return $this->dispatchRoute($matched['route'], $request, $matched['params']);
    }

    // ==================== URL Generation ====================

    public function route(string $name, mixed $parameters = [], bool $absolute = true): string
    {
        if (!isset($this->namedRoutes[$name])) {
            throw new InvalidArgumentException("Route [$name] not defined.");
        }

        $path = $this->buildUrl($this->namedRoutes[$name], $parameters);
        return $this->toUrl($path);
    }

    public function url(string $path = '', mixed $parameters = []): string
    {
        return $this->toUrl($path, $parameters);
    }

    // ==================== RouteCollection Implementation ====================

    private function addRouteToCollection(array $route): void
    {
        $method = $route['method'];
        $pattern = $route['pattern'];
        
        if ($route['isDynamic']) {
            $this->dynamicRoutes[$method][] = $route;
        } else {
            $this->staticRoutes[$method][$pattern] = $route;
        }
    }

    private function matchRoute(string $method, string $path): ?array
    {
        $path = parse_url($path, PHP_URL_PATH) ?? $path;
        $path = '/' . trim($path, '/');

        if (isset($this->staticRoutes[$method][$path])) {
            return [
                'route' => $this->staticRoutes[$method][$path],
                'params' => []
            ];
        }

        if (isset($this->dynamicRoutes[$method])) {
            foreach ($this->dynamicRoutes[$method] as $route) {
                if ($params = $this->matchesPattern($route, $path)) {
                    return ['route' => $route, 'params' => $params];
                }
            }
        }

        return null;
    }

    private function applyMiddlewareToRoute(string $path, array $middlewares): void
    {
        foreach ($this->staticRoutes as &$routes) {
            if (isset($routes[$path])) {
                $routes[$path]['middleware'] = array_merge(
                    $routes[$path]['middleware'],
                    $middlewares
                );
            }
        }

        foreach ($this->dynamicRoutes as &$routes) {
            foreach ($routes as &$route) {
                if ($route['pattern'] === $path) {
                    $route['middleware'] = array_merge(
                        $route['middleware'],
                        $middlewares
                    );
                }
            }
        }
    }

    // ==================== RouteDefinition Implementation ====================

    private function createRouteDefinition(string $method, string $pattern, mixed $handler, array $middleware = []): array
    {
        $isDynamic = str_contains($pattern, '{');
        $route = [
            'method' => $method,
            'pattern' => $pattern,
            'handler' => $handler,
            'middleware' => $middleware,
            'isDynamic' => $isDynamic,
            'compiledPattern' => null,
            'paramNames' => []
        ];
        
        if ($isDynamic) {
            $this->compilePattern($route);
        }
        
        return $route;
    }

    private function compilePattern(array &$route): void
    {
        preg_match_all('/\{([^}:]+)(?::([^}]+))?\}/', $route['pattern'], $matches);
        $route['paramNames'] = $matches[1];
        
        $pattern = preg_replace('/\{[^}]+\}/', '([^/]+)', $route['pattern']);
        $route['compiledPattern'] = '#^' . $pattern . '$#';
    }

    private function matchesPattern(array $route, string $path): ?array
    {
        if (!$route['isDynamic']) {
            return $route['pattern'] === $path ? [] : null;
        }

        if (!preg_match($route['compiledPattern'], $path, $matches)) {
            return null;
        }

        array_shift($matches);
        return array_combine($route['paramNames'], $matches);
    }

    // ==================== RouteDispatcher Implementation ====================

    private function dispatchRoute(array $route, Request $request, array $params): Response
    {
        $pipeline = $this->buildPipeline($route['middleware'], $route['handler']);
        $result = $pipeline($request, $params);
        return $this->toResponse($result);
    }

    private function buildPipeline(array $middlewares, mixed $handler): Closure
    {
        $pipeline = fn(Request $req, array $params) => $this->handleRoute($handler, $req, $params);

        foreach (array_reverse($middlewares) as $middleware) {
            $next = $pipeline;
            $pipeline = function (Request $req, array $params) use ($middleware, $next) {
                $instance = $this->app->make($middleware);
                return $instance($req, fn($r = null) => $next($r ?? $req, $params));
            };
        }

        return $pipeline;
    }

    private function toResponse(mixed $result): Response
    {
        if ($result instanceof Response) {
            return $result;
        }

        $response = app('response');
        
        if (is_array($result) || is_object($result)) {
            $response->setJson($result);
        } else {
            $response->setContent((string) $result);
        }

        return $response;
    }

    // ==================== Handler Implementation ====================

    private function handleRoute(mixed $handler, Request $request, array $params): mixed
    {
        if ($handler instanceof Closure) {
            return $this->handleClosure($handler, $request, $params);
        }

        if (is_string($handler) && str_contains($handler, '@')) {
            [$class, $method] = explode('@', $handler, 2);
            return $this->handleController($class, $method, $request, $params);
        }

        if (is_array($handler)) {
            return $this->handleController($handler[0], $handler[1], $request, $params);
        }

        throw new RuntimeException('Invalid handler type');
    }

    private function handleClosure(Closure $closure, Request $request, array $params): mixed
    {
        $reflection = new ReflectionFunction($closure);
        $args = $this->resolveParameters($reflection->getParameters(), $params, $request);
        return $closure(...$args);
    }

    private function handleController(string $controller, string $method, Request $request, array $params): mixed
    {
        $instance = $this->app->make($controller);
        
        if (!method_exists($instance, $method)) {
            throw new ControllerMethodNotFoundException(404);
        }

        $reflection = new ReflectionMethod($instance, $method);
        $args = $this->resolveParameters($reflection->getParameters(), $params, $request);
        
        return $instance->{$method}(...$args);
    }

    // ==================== ParameterResolver Implementation ====================

    private function resolveParameters(array $parameters, array $routeParams, Request $request): array
    {
        $args = [];

        foreach ($parameters as $param) {
            $args[] = $this->resolveParameter($param, $routeParams, $request);
        }

        return $args;
    }

    private function resolveParameter(
        ReflectionParameter $param,
        array $routeParams,
        Request $request
    ): mixed {
        $type = $param->getType();

        if ($type && !$type->isBuiltin()) {
            return $this->resolveTypedParameter($param, $type, $routeParams, $request);
        }

        return $this->resolveScalarParameter($param, $routeParams);
    }

    private function resolveTypedParameter(
        ReflectionParameter $param,
        ReflectionNamedType $type,
        array $routeParams,
        Request $request
    ): mixed {
        $className = $type->getName();

        if ($className === Request::class) {
            return $request;
        }

        if (is_subclass_of($className, Model::class)) {
            return $this->resolveModel($param, $className, $routeParams);
        }

        return $this->app->make($className);
    }

    private function resolveModel(
        ReflectionParameter $param,
        string $modelClass,
        array $routeParams
    ): mixed {
        $paramName = $param->getName();
        $value = $routeParams[$paramName] ?? null;

        if ($value === null && count($routeParams) === 1) {
            $value = reset($routeParams);
        }

        if ($value === null) {
            if ($param->isDefaultValueAvailable()) {
                return $param->getDefaultValue();
            }
            throw new RuntimeException("Required parameter {$paramName} is missing", 400);
        }

        $model = new $modelClass();
        $instance = $model->newQuery()
            ->where($model->getRouteKeyName(), $value)
            ->first();

        if (!$instance) {
            throw new RouteNotFoundException();
        }

        return $instance;
    }

    private function resolveScalarParameter(
        ReflectionParameter $param,
        array $routeParams
    ): mixed {
        $name = $param->getName();

        if ($name === 'params') {
            return $routeParams;
        }

        if (array_key_exists($name, $routeParams)) {
            return $routeParams[$name];
        }

        if ($param->isDefaultValueAvailable()) {
            return $param->getDefaultValue();
        }

        throw new RuntimeException("Missing parameter [{$name}]");
    }

    // ==================== RouteUrlBuilder Implementation ====================

    private function buildUrl(string $pattern, mixed $parameters): string
    {
        preg_match_all('/\{([^}]+)\}/', $pattern, $matches);
        $paramNames = $matches[1] ?? [];
        
        $parameters = $this->normalizeUrlParameters($parameters, $paramNames);
        $this->validateUrlParameters($parameters, $paramNames);

        $path = $pattern;
        foreach ($parameters as $key => $value) {
            $path = str_replace("{{$key}}", (string) $value, $path);
            $path = str_replace("{{$key}?}", (string) $value, $path);
        }

        return preg_replace('/\{[^}]+\?\}/', '', $path);
    }

    private function normalizeUrlParameters(mixed $parameters, array $paramNames): array
    {
        if (is_null($parameters)) {
            return [];
        }

        if (is_scalar($parameters)) {
            $first = $paramNames[0] ?? 'id';
            return [$first => $parameters];
        }

        if ($parameters instanceof Model) {
            $first = $paramNames[0] ?? $parameters->getRouteKeyName();
            return [$first => $parameters->getRouteKey()];
        }

        if (is_array($parameters) && array_is_list($parameters)) {
            return array_combine($paramNames, $parameters);
        }

        return (array) $parameters;
    }

    private function validateUrlParameters(array $parameters, array $paramNames): void
    {
        foreach ($paramNames as $param) {
            if (!array_key_exists($param, $parameters)) {
                throw new InvalidArgumentException(
                    "Missing required parameter [{$param}]"
                );
            }
        }
    }

    // ==================== URL Helper ====================

    private function toUrl(string $path, mixed $parameters = []): string
    {
        $baseUrl = $this->getBaseUrl();
        $path = '/' . ltrim($path, '/');
        
        if (!empty($parameters) && is_array($parameters)) {
            $queryString = http_build_query($parameters);
            $path .= '?' . $queryString;
        }
        
        return $baseUrl . $path;
    }

    private function getBaseUrl(): string
    {
        if ($this->urlGeneratorBaseUrl) {
            return $this->urlGeneratorBaseUrl;
        }

        $protocol = ($_SERVER['HTTPS'] ?? '') === 'on' ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        
        if (!str_contains($host, ':') && isset($_SERVER['SERVER_PORT'])) {
            $port = $_SERVER['SERVER_PORT'];
            if (($protocol === 'http' && $port != 80) || ($protocol === 'https' && $port != 443)) {
                $host .= ':' . $port;
            }
        }
        
        return $this->urlGeneratorBaseUrl = "{$protocol}://{$host}";
    }

    // ==================== Path Normalization ====================

    protected function normalizePrefix(string $existing, string $new): string
    {
        return $this->normalizePath(rtrim($existing, '/') . '/' . trim($new, '/'));
    }

    protected function normalizePath(string $path): string
    {
        $path = parse_url($path, PHP_URL_PATH) ?? $path;
        $segments = array_filter(explode('/', $path), fn($s) => $s !== '');
        return '/' . implode('/', $segments);
    }

    // ==================== Getters ====================

    public function getRoutes(): array
    {
        return [
            'static' => $this->staticRoutes,
            'dynamic' => $this->dynamicRoutes
        ];
    }
}
