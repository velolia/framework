<?php

declare(strict_types=1);

namespace Velolia\Validation\Rules;

use Velolia\Validation\Contracts\Rule;

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
