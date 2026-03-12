<?php

declare(strict_types=1);

namespace Velolia\Http;

use Velolia\Session\Session;
use Velolia\Validation\Validator;

class Request
{
    protected array $query;
    protected array $request;
    protected array $attributes = [];
    protected array $cookies;
    protected array $files;
    protected array $server;
    protected array $headers;
    protected ?string $content = null;
    protected array $routeMiddleware = [];

    public function __construct(array $query = [], array $request = [], array $attributes = [], array $cookies = [], array $files = [], array $server = [], ?string $content = null)
    {
        $this->query = $query;
        $this->request = $request;
        $this->attributes = $attributes;
        $this->cookies = $cookies;
        $this->files = $files;
        $this->server = $server;
        $this->content = $content;
        $this->headers = $this->extractHeaders($server);
    }

    public static function capture(): static
    {
        return new static($_GET, $_POST, [], $_COOKIE, $_FILES, $_SERVER, file_get_contents('php://input'));
    }

    public function setRouteMiddleware(array $middleware): void
    {
        $this->routeMiddleware = $middleware;
    }

    public function getRouteMiddleware(): array
    {
        return $this->routeMiddleware;
    }

    public function method(): string
    {
        $method = strtoupper($this->server['REQUEST_METHOD'] ?? 'GET');

        if ($method === 'POST') {
            if (isset($this->request['_method'])) {
                return strtoupper($this->request['_method']);
            }

            if (isset($this->server['HTTP_X_HTTP_METHOD_OVERRIDE'])) {
                return strtoupper($this->server['HTTP_X_HTTP_METHOD_OVERRIDE']);
            }
        }

        return $method;
    }

    public function getMethod(): string
    {
        return strtoupper($this->server['REQUEST_METHOD'] ?? 'GET');
    }

    public function isMethod(string $method): bool
    {
        return $this->method() === strtoupper($method);
    }

    public function getPathInfo(): string
    {
        $path = parse_url($this->server['REQUEST_URI'] ?? '/', PHP_URL_PATH);
        return $path === '' ? '/' : $path;
    }

    public function header(string $key, $default = null)
    {
        $key = strtolower($key);
        return $this->headers[$key] ?? $default;
    }

    public function headers(): array
    {
        return $this->headers;
    }

    public function attribute(string $key, $value = null)
    {
        if ($value === null) {
            return $this->attributes[$key] ?? null;
        }

        $this->attributes[$key] = $value;
        return $this;
    }

    public function all(): array
    {
        return array_merge($this->query, $this->request);
    }

    public function input(?string $key = null, $default = null)
    {
        if ($key === null) {
            return array_merge($this->query, $this->request);
        }

        return $this->request[$key] ?? $this->query[$key] ?? $default;
    }

    public function has(string|array $keys): bool
    {
        $keys = is_array($keys) ? $keys : func_get_args();
        $data = $this->all();
        foreach ($keys as $key) {
            if (!array_key_exists($key, $data)) {
                return false;
            }
        }

        return true;
    }

    public function only($keys): array
    {
        $keys = is_array($keys) ? $keys : func_get_args();
        $data = $this->all();
        $result = [];

        foreach ($keys as $key) {
            if (array_key_exists($key, $data)) {
                $result[$key] = $data[$key];
            }
        }

        return $result;
    }

    public function except($keys): array
    {
        $keys = is_array($keys) ? $keys : func_get_args();
        $data = $this->all();

        foreach ($keys as $key) {
            unset($data[$key]);
        }

        return $data;
    }

    public function merge(array $input): void
    {
        $this->request = array_merge($this->request, $input);
    }

    public function add(array $input): void
    {
        $this->merge($input);
    }

    public function filled(string $key): bool
    {
        $value = $this->input($key);
        return !empty($value);
    }

    public function is(string ...$patterns): bool
    {
        $path = ltrim($this->getPathInfo(), '/');

        foreach ($patterns as $pattern) {
            $regex = '#^' . str_replace('\*', '.*', preg_quote($pattern, '#')) . '$#u';
            if (preg_match($regex, $path)) {
                return true;
            }
        }

        return false;
    }

    public function getContent(): string
    {
        return $this->content ?? '';
    }

    public function json(): array
    {
        $contentType = $this->header('Content-Type', '');

        if (str_contains($contentType, 'application/json')) {
            return json_decode($this->getContent(), true);
        }

        return [];
    }

    public function expectsJson(): bool
    {
        return str_contains($this->header('Accept', ''), 'application/json');
    }

    public function isAjax(): bool
    {
        return $this->header('X-Requested-With') === 'XMLHttpRequest';
    }

    protected function extractHeaders(array $server): array
    {
        $headers = [];

        foreach ($server as $key => $value) {
            if (str_starts_with($key, 'HTTP_')) {
                $name = str_replace('_', '-', strtolower(substr($key, 5)));
                $headers[$name] = $value;
            } elseif (in_array($key, ['CONTENT_TYPE', 'CONTENT_LENGTH'])) {
                $name = str_replace('_', '-', strtolower($key));
                $headers[$name] = $value;
            }
        }

        return $headers;
    }

    public function validate(array $rules, array $messages = [], array $attributes = [])
    {
        $data = $this->all();
        /** @var Validator $validator */
        $validator = app(Validator::class)->make($data, $rules, $messages, $attributes);

        if ($validator->fails()) {
            $response = app('response');

            if ($this->isAjax() || $this->expectsJson()) {
                $response->json(['errors' => $validator->errors()], 422)->send();
            } else {
                $response->back()->withErrors($validator->errors())->withInput()->send();
            }

            exit;
        }

        return $this->only(array_keys($rules));
    }

    public function session(): Session
    {
        return app(Session::class);
    }

    public function __get(string $key): mixed
    {
        return $this->input($key);
    }

    public function __isset(string $key): bool
    {
        return $this->has($key);
    }
}
