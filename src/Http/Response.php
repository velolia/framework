<?php

declare(strict_types=1);

namespace Velolia\Http;

use InvalidArgumentException;

class Response
{
    protected string $content = '';
    protected int $statusCode = 200;
    protected array $headers = [];

    public function __construct(string $content = '', int $statusCode = 200, array $headers = [])
    {
        $this->content = $content;
        $this->statusCode = $statusCode;
        $this->headers = $headers;
    }

    public function getContent(): string
    {
        return $this->content;
    }

    public function setContent(string $content): mixed
    {
        if (is_array($content) || is_object($content)) {
            return $this->setJson($content);
        }

        $this->content = (string) $content;
        return $this;
    }

    public function setStatusCode(int $code): static
    {
        $this->statusCode = $code;
        return $this;
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function header(string $key, string $value): static
    {
        $this->headers[$key] = $value;
        return $this;
    }

    public function getHeader(string $key): ?string
    {
        return $this->headers[$key] ?? null;
    }

    public function getHeaders(): array
    {
        return $this->headers;
    }

    public static function json($data, int $status = 200, array $headers = []): static
    {
        $response = new static(
            json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            $status,
            $headers
        );

        $response->header('Content-Type', 'application/json');

        return $response;
    }

    public function setJson($data = null, $options = 0)
    {
        $json = json_encode($data, $options);
        if (JSON_ERROR_NONE !== json_last_error()) {
            throw new InvalidArgumentException(json_last_error_msg());
        }

        return $this->setContent($json)->header('Content-Type', 'application/json');
    }

    public function redirect(string $url = '', int $status = 302, array $headers = []): RedirectResponse
    {
        return new RedirectResponse($url, $status, $headers);
    }

    public function back(): RedirectResponse
    {
        return $this->redirect($_SERVER['HTTP_REFERER'] ?? '/');
    }

    public function send(): void
    {
        http_response_code($this->statusCode);

        foreach ($this->headers as $name => $value) {
            header("{$name}: {$value}");
        }

        echo $this->content;
    }
}
