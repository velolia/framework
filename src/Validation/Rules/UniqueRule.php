<?php

declare(strict_types=1);

namespace Velolia\Validation\Rules;

use Velolia\Support\Facades\DB;
use Velolia\Validation\Contracts\Rule;

class UniqueRule implements Rule
{
    public function validate(string $field, mixed $value, array $parameters = []): bool
    {
        if ($value === null || $value === '' || (is_string($value) && trim($value) === '')) {
            return true;
        }

        // Format:
        // unique:table,column,except,idColumn
        $table     = $parameters[0] ?? null;
        $column    = $parameters[1] ?? $field;
        $exceptId  = $parameters[2] ?? null;
        $idColumn  = $parameters[3] ?? 'id';

        if (!$table) {
            return true;
        }

        $query = DB::table($table)->where($column, '=', $value);

        if ($exceptId !== null) {
            $query->where($idColumn, '!=', $exceptId);
        }

        return $query->count() === 0;
    }

    public function message(string $field, array $parameters = []): string
    {
        return 'The :attribute has already been taken.';
    }
}
