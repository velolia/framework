<?php

namespace Velolia\Validation\Rules;

use Velolia\Validation\Rule;

class NullableRule implements Rule
{
    public function validate(string $field, mixed $value, array $parameters = []): bool
    {
        return true;
    }

    public function message(string $field, array $parameters = []): string
    {
        return '';
    }
}