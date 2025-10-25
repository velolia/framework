<?php

declare(strict_types=1);

namespace Velolia\Validation\Rules;

use Velolia\Validation\Rule;

class RequiredRule implements Rule
{
    public function validate(string $field, mixed $value, array $parameters = []): bool
    {
        return ! is_null($value) && trim((string) $value) !== '';
    }

    public function message(string $field, array $parameters = []): string
    {
        return "The {$field} field is required.";
    }
}