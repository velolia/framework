<?php

declare(strict_types=1);

namespace Velolia\Validation\Rules;

use Velolia\Validation\Contracts\Rule;

class MinRule implements Rule
{
    public function validate(string $field, mixed $value, array $parameters = []): bool
    {
        if (empty($parameters)) {
            return false;
        }

        $min = (float) $parameters[0];

        if (is_string($value)) {
            return mb_strlen($value) >= $min;
        }

        if (is_numeric($value)) {
            return (float) $value >= $min;
        }

        if (is_array($value)) {
            return count($value) >= $min;
        }

        return false;
    }

    public function message(string $field, array $parameters = []): string
    {
        $min = $parameters[0] ?? '?';
        return "The :attribute must be at least {$min}.";
    }
}
