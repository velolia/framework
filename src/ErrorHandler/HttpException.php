<?php

declare(strict_types=1);

namespace Velolia\ErrorHandler;

use RuntimeException;
use Throwable;

class HttpException extends RuntimeException
{
    /**
     * Status code
     * @var int
    */
    protected int $statusCode;

    /**
     * Headers
     * @var array
    */
    protected array $headers = [];

    /**
     * Constructor
     * @param int $statusCode
     * @param string $message
     * @param array $headers
     * @param Throwable|null $previous
     * @param int $code
    */
    public function __construct(int $statusCode, string $message = "", array $headers = [], ?Throwable $previous = null, int $code = 0)
    {
        $this->statusCode = $statusCode;
        $this->headers = $headers;
        parent::__construct($message, $code, $previous);
    }

    /**
     * Get status code
     * @return int
    */
    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    /**
     * Get headers
     * @return array
    */
    public function getHeaders(): array
    {
        return $this->headers;
    }

    /**
     * Set headers
     * @param array $headers
    */
    public function setHeaders(array $headers): void
    {
        $this->headers = $headers;
    }
}