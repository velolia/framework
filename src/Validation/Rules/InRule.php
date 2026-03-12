<?php

declare(strict_types=1);

namespace Velolia\Validation\Rules;

use Velolia\Validation\Contracts\Rule;

class InRule implements Rule
{
    public function validate(string $field, mixed $value, array $parameters = []): bool
    {
        if (empty($parameters)) {
            return false;
        }

        $valueStr = (string) $value;

        return in_array($valueStr, $parameters, true);
    }

    public function message(string $field, array $parameters = []): string
    {
        $options = implode(', ', $parameters);
        return "The :attribute must be one of: {$options}.";
    }
}
