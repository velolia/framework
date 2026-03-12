<?php

declare(strict_types=1);

namespace Velolia\Exceptions;

use Exception;

class HttpException extends Exception
{
    protected int $statusCode;

    public function __construct(int $statusCode, string $message = '', ?\Throwable $previous = null)
    {
        $this->statusCode = $statusCode;
        parent::__construct($message, $statusCode, $previous);
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }
}
