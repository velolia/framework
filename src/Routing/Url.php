<?php

declare(strict_types=1);

namespace Velolia\Routing;

class Url
{
    /** @var string */
    protected string $baseUrl;

    /**
     * @param string $baseUrl
    */
    public function __construct(?string $baseUrl = null)
    {
        $this->baseUrl = rtrim($baseUrl ?? $this->makeBaseUrl(), '/');
    }

    /**
     * @param string $path
     * @param mixed $parameters
     * @return string
    */
    public function to(string $path, mixed $parameters = [], bool $absolute = true): string
    {
        $path = '/' . ltrim($path, '/');

        if (!empty($parameters) && is_array($parameters)) {
            $query = http_build_query($parameters);
            $path .= '?' . $query;
        }

        if ($absolute) {
            return $this->baseUrl . $path;
        }

        return $path;
    }

    protected function makeBaseUrl(): string
    {
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https' : 'http';
        $host   = $_SERVER['HTTP_HOST'] ?? 'localhost';
        return $scheme . '://' . $host;
    }
}