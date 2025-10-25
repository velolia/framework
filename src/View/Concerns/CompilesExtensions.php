<?php

declare(strict_types=1);

namespace Velolia\View\Concerns;

trait CompilesExtensions
{
    protected function compileExtensions(string $value): string
    {
        foreach ($this->directives as $name => $compiler) {
            $value = $this->compileCustomDirective($name, $value, $compiler);
        }

        return $value;
    }

    protected function compileCustomDirective(string $name, string $value, callable $compiler): string
    {
        $pattern = sprintf('/(?<!\\w)@%s\\s*(?:\\((.*)\\))?/s', $name);
        
        return preg_replace_callback($pattern, function ($matches) use ($compiler) {
            $expression = $matches[1] ?? '';
            return call_user_func($compiler, $expression);
        }, $value);
    }
}