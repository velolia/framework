<?php

declare(strict_types=1);

namespace Velolia\Auth\Access;

use Exception;

class AuthorizationException extends Exception
{
    public function __construct(string $message = "This action is unauthorized.", int $code = 403, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
