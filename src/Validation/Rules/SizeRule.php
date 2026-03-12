<?php

declare(strict_types=1);

namespace Velolia\Validation\Rules;

use Velolia\Validation\Contracts\Rule;

class SizeRule implements Rule
{
    public function validate(string $field, mixed $value, array $parameters = []): bool
    {
        if (empty($parameters)) {
            return false;
        }

        $size = (float) $parameters[0];

        if (is_string($value)) {
            return mb_strlen($value) == $size;
        }

        if (is_numeric($value)) {
            return (float) $value == $size;
        }

        if (is_array($value)) {
            return count($value) == $size;
        }

        return false;
    }

    public function message(string $field, array $parameters = []): string
    {
        $size = $parameters[0] ?? '?';
        return "The :attribute must be exactly {$size}.";
    }
}
