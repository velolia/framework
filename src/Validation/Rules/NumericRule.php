<?php

declare(strict_types=1);

namespace Velolia\Validation\Rules;

use Velolia\Validation\Contracts\Rule;

class NumericRule implements Rule
{
    public function validate(string $field, mixed $value, array $parameters = []): bool
    {
        return is_numeric($value);
    }

    public function message(string $field, array $parameters = []): string
    {
        return 'The :attribute must be a number.';
    }
}
