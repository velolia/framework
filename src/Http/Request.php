<?php

declare(strict_types=1);

namespace Velolia\Http;

class Request
{
    protected array $headers = [];
    protected array $body = [];
    protected array $query = [];
    protected string $method = '';
    protected string $uri = '';
    protected string $path = '';
    protected array $files = [];
    protected array $server = [];
    protected array $cookies = [];
    protected ?string $rawBody = null;

    public function __construct(
        array $query = [],
        array $body = [],
        array $files = [],
        array $cookies = [],
        array $server = [],
        ?string $rawBody = null
    ) {
        $this->query = $query;
        $this->body = $body;
        $this->files = $files;
        $this->cookies = $cookies;
        $this->server = $server;
        $this->rawBody = $rawBody;
        
        $this->headers = $this->extractHeaders($server);
        $this->method = $this->extractMethod($server);
        $this->uri = $this->extractUri($server);
        $this->path = $this->extractPath($this->uri);
    }

    /**
     * Capture request from globals with security measures
     */
    public static function capture(): self
    {
        $rawBody = file_get_contents('php://input');
        
        // Parse body based on content type
        $body = $_POST;
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
        
        if (empty($body) && $rawBody) {
            if (str_contains($contentType, 'application/json')) {
                $decoded = json_decode($rawBody, true);
                $body = is_array($decoded) ? $decoded : [];
            } elseif (str_contains($contentType, 'application/x-www-form-urlencoded')) {
                parse_str($rawBody, $body);
            }
        }

        // Sanitize inputs
        $query = self::sanitizeArray($_GET);
        $body = self::sanitizeArray($body);
        $files = $_FILES;
        $cookies = self::sanitizeArray($_COOKIE);
        $server = $_SERVER;

        return new self($query, $body, $files, $cookies, $server, $rawBody);
    }

    /**
     * Extract headers from server variables
     */
    protected function extractHeaders(array $server): array
    {
        $headers = [];
        
        foreach ($server as $key => $value) {
            if (str_starts_with($key, 'HTTP_')) {
                // $headerName = str_replace('_', '-', substr($key, 5));
                $headerName = strtolower(str_replace('_', '-', substr($key, 5)));
                $headers[$headerName] = $value;
            } elseif (in_array($key, ['CONTENT_TYPE', 'CONTENT_LENGTH', 'CONTENT_MD5'])) {
                // $headerName = str_replace('_', '-', $key);
                $headerName = strtolower(str_replace('_', '-', $key));
                $headers[$headerName] = $value;
            }
        }
        
        return $headers;
    }

    /**
     * Extract HTTP method with method override support
     */
    protected function extractMethod(array $server): string
    {
        $method = strtoupper($server['REQUEST_METHOD'] ?? 'GET');
        
        // Support method override via _method field or X-HTTP-Method-Override header
        if ($method === 'POST') {
            $override = $this->body['_method'] ?? $this->headers['X-HTTP-METHOD-OVERRIDE'] ?? null;
            if ($override && in_array(strtoupper($override), ['PUT', 'PATCH', 'DELETE'])) {
                $method = strtoupper($override);
            }
        }
        
        return $method;
    }

    /**
     * Extract URI from server variables
     */
    protected function extractUri(array $server): string
    {
        return $server['REQUEST_URI'] ?? '/';
    }

    /**
     * Extract and secure path from URI
     */
    protected function extractPath(string $uri): string
    {
        // Parse URL to get path only (without query string)
        $path = parse_url($uri, PHP_URL_PATH) ?? '/';
        
        // Security: Remove directory traversal attempts
        $path = str_replace(['../', '..\\', '\\'], '', $path);
        
        // Security: Normalize multiple slashes
        $path = preg_replace('#/+#', '/', $path);
        
        // Security: Remove null bytes
        $path = str_replace("\0", '', $path);
        
        // Ensure path starts with /
        $path = '/' . ltrim($path, '/');
        
        // Decode URL encoding
        $path = urldecode($path);
        
        return $path;
    }

    /**
     * Secure path getter with additional validation
     */
    public function getPath(): string
    {
        return $this->path;
    }

    /**
     * Sanitize array recursively
     */
    protected static function sanitizeArray(array $data): array
    {
        $sanitized = [];
        
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $sanitized[$key] = self::sanitizeArray($value);
            } else {
                // Remove null bytes and control characters
                $sanitized[$key] = is_string($value) 
                    ? str_replace("\0", '', $value)
                    : $value;
            }
        }
        
        return $sanitized;
    }

    // ==================== Getters ====================

    public function getMethod(): string
    {
        return $this->method;
    }

    public function getUri(): string
    {
        return $this->uri;
    }

    public function getHeaders(): array
    {
        return $this->headers;
    }

    public function getHeader(string $name, ?string $default = null): ?string
    {
        $name = strtoupper(str_replace('-', '_', $name));
        
        foreach ($this->headers as $key => $value) {
            if (strtoupper(str_replace('-', '_', $key)) === $name) {
                return $value;
            }
        }
        
        return $default;
    }

    public function header(string $name, ?string $default = null): ?string
    {
        return $this->getHeader($name, $default);
    }

    public function hasHeader(string $name): bool
    {
        return $this->getHeader($name) !== null;
    }

    public function getQuery(?string $key = null, mixed $default = null): mixed
    {
        if ($key === null) {
            return $this->query;
        }
        
        return $this->query[$key] ?? $default;
    }

    public function getBody(?string $key = null, mixed $default = null): mixed
    {
        if ($key === null) {
            return $this->body;
        }
        
        return $this->body[$key] ?? $default;
    }

    public function input(?string $key = null, mixed $default = null): mixed
    {
        if ($key === null) {
            return array_merge($this->query, $this->body);
        }
        
        return $this->body[$key] ?? $this->query[$key] ?? $default;
    }

    public function all(): array
    {
        return array_merge($this->query, $this->body);
    }

    public function only(array $keys): array
    {
        $data = $this->all();
        $result = [];
        
        foreach ($keys as $key) {
            if (array_key_exists($key, $data)) {
                $result[$key] = $data[$key];
            }
        }
        
        return $result;
    }

    public function except(array $keys): array
    {
        $data = $this->all();
        
        foreach ($keys as $key) {
            unset($data[$key]);
        }
        
        return $data;
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

    public function filled(string|array $keys): bool
    {
        $keys = is_array($keys) ? $keys : func_get_args();
        
        foreach ($keys as $key) {
            $value = $this->input($key);
            
            if ($value === null || $value === '' || $value === []) {
                return false;
            }
        }
        
        return true;
    }

    public function missing(string $key): bool
    {
        return !$this->has($key);
    }

    public function getFiles(): array
    {
        return $this->files;
    }

    public function file(string $key): mixed
    {
        return $this->files[$key] ?? null;
    }

    public function hasFile(string $key): bool
    {
        $file = $this->file($key);
        
        return $file && 
               isset($file['error']) && 
               $file['error'] === UPLOAD_ERR_OK &&
               isset($file['size']) &&
               $file['size'] > 0;
    }

    public function getCookies(): array
    {
        return $this->cookies;
    }

    public function cookie(string $key, mixed $default = null): mixed
    {
        return $this->cookies[$key] ?? $default;
    }

    public function getServer(?string $key = null, mixed $default = null): mixed
    {
        if ($key === null) {
            return $this->server;
        }
        
        return $this->server[$key] ?? $default;
    }

    public function getRawBody(): ?string
    {
        return $this->rawBody;
    }

    // ==================== Request Information ====================

    public function isMethod(string $method): bool
    {
        return $this->method === strtoupper($method);
    }

    public function isGet(): bool
    {
        return $this->isMethod('GET');
    }

    public function isPost(): bool
    {
        return $this->isMethod('POST');
    }

    public function isPut(): bool
    {
        return $this->isMethod('PUT');
    }

    public function isPatch(): bool
    {
        return $this->isMethod('PATCH');
    }

    public function isDelete(): bool
    {
        return $this->isMethod('DELETE');
    }

    public function isAjax(): bool
    {
        return $this->getHeader('X-Requested-With') === 'XMLHttpRequest';
    }

    public function isJson(): bool
    {
        $contentType = $this->getHeader('Content-Type') ?? '';
        return str_contains($contentType, 'application/json');
    }

    public function expectsJson(): bool
    {
        $accept = $this->getHeader('Accept') ?? '';
        return str_contains($accept, 'application/json');
    }

    public function wantsJson(): bool
    {
        return $this->isAjax() || $this->expectsJson();
    }

    public function isSecure(): bool
    {
        return $this->getServer('HTTPS') === 'on' ||
               $this->getServer('SERVER_PORT') == 443 ||
               $this->getHeader('X-Forwarded-Proto') === 'https';
    }

    public function ip(): ?string
    {
        // Check for proxies
        if ($ip = $this->getHeader('X-Forwarded-For')) {
            // Get first IP if comma-separated
            return trim(explode(',', $ip)[0]);
        }
        
        if ($ip = $this->getHeader('X-Real-IP')) {
            return $ip;
        }
        
        return $this->getServer('REMOTE_ADDR');
    }

    public function userAgent(): ?string
    {
        return $this->getHeader('User-Agent');
    }

    public function getFullUrl(): string
    {
        $scheme = $this->isSecure() ? 'https' : 'http';
        $host = $this->getHeader('Host') ?? $this->getServer('HTTP_HOST') ?? 'localhost';
        
        return $scheme . '://' . $host . $this->uri;
    }

    // ==================== Magic Methods ====================

    public function __get(string $key): mixed
    {
        return $this->input($key);
    }

    public function __isset(string $key): bool
    {
        return $this->has($key);
    }
}