<?php

declare(strict_types=1);

namespace Velolia\Validation\Rules;

use Velolia\Validation\Contracts\Rule;

class ConfirmedRule implements Rule
{
    public function validate(string $field, mixed $value, array $parameters = []): bool
    {
        $data = $parameters[0] ?? [];

        $confirmationField = $field . '_confirmation';

        if (!array_key_exists($confirmationField, $data)) {
            return false;
        }

        return $value === $data[$confirmationField];
    }

    public function message(string $field, array $parameters = []): string
    {
        return 'The :attribute confirmation does not match.';
    }
}
