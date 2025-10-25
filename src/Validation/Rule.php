<?php

declare(strict_types=1);

namespace Velolia\Validation;

interface Rule
{
    public function validate(string $field, mixed $value, array $parameters = []): bool;
    public function message(string $field, array $parameters = []): string;
}