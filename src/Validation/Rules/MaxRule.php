<?php

declare(strict_types=1);

namespace Velolia\Validation\Rules;

use Velolia\Validation\Contracts\Rule;

class MaxRule implements Rule
{
    public function validate(string $field, mixed $value, array $parameters = []): bool
    {
        if (empty($parameters)) {
            return false;
        }

        $max = (float) $parameters[0];

        if (is_string($value)) {
            return mb_strlen($value) <= $max;
        }

        if (is_numeric($value)) {
            return (float) $value <= $max;
        }

        if (is_array($value)) {
            return count($value) <= $max;
        }

        return false;
    }

    public function message(string $field, array $parameters = []): string
    {
        $max = $parameters[0] ?? '?';
        return "The :attribute must not be greater than {$max}.";
    }
}
