<?php

declare(strict_types=1);

namespace Velolia\Database\Exception;

use RuntimeException;

class MassAssignmentException extends RuntimeException
{
    public function __construct(string $key, string $model)
    {
        parent::__construct(
            "Add [{$key}] to fillable property to allow mass assignment on [{$model}]."
        );
    }
}
