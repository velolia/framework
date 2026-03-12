<?php

declare(strict_types=1);

namespace Velolia\Validation\Rules;

use Velolia\Validation\Contracts\Rule;

class BetweenRule implements Rule
{
    public function validate(string $field, mixed $value, array $parameters = []): bool
    {
        if (count($parameters) < 2) {
            return false;
        }

        $min = (float) $parameters[0];
        $max = (float) $parameters[1];

        if (is_string($value)) {
            $length = mb_strlen($value);
            return $length >= $min && $length <= $max;
        }

        if (is_numeric($value)) {
            $val = (float) $value;
            return $val >= $min && $val <= $max;
        }

        if (is_array($value)) {
            $count = count($value);
            return $count >= $min && $count <= $max;
        }

        return false;
    }

    public function message(string $field, array $parameters = []): string
    {
        $min = $parameters[0] ?? '?';
        $max = $parameters[1] ?? '?';
        return "The :attribute must be between {$min} and {$max}.";
    }
}
