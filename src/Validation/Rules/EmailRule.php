<?php

declare(strict_types=1);

namespace Velolia\Validation\Rules;

use Velolia\Validation\Contracts\Rule;

class EmailRule implements Rule
{
    public function validate(string $field, mixed $value, array $parameters = []): bool
    {
        if (!is_string($value)) {
            return false;
        }

        return filter_var($value, FILTER_VALIDATE_EMAIL) !== false;
    }

    public function message(string $field, array $parameters = []): string
    {
        return 'The :attribute must be a valid email address.';
    }
}
