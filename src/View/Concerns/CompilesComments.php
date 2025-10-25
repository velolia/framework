<?php

declare(strict_types=1);

namespace Velolia\View\Concerns;

trait CompilesComments
{
    protected function compileComments(string $value): string
    {
        $pattern = '/{{--(.*?)--}}/s';
        return preg_replace($pattern, '', $value);
    }
}