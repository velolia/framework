<?php

declare(strict_types=1);

namespace Velolia\Validation;

use RuntimeException;

class ValidationException extends RuntimeException
{
    public function __construct(string $message = 'The given data was invalid.')
    {
        parent::__construct($message, 422);
    }
}
