<?php

declare(strict_types=1);

namespace Velolia\View\Concerns;

trait CompilesConditionals
{
    protected function compileConditionals(string $value): string
    {
        $value = $this->compileIf($value);
        $value = $this->compileElse($value);
        $value = $this->compileEndif($value);
        return $value;
    }

    protected function compileIf(string $value): string
    {
        $pattern = "/@if\s*\((.*?)\)/";
        return preg_replace($pattern, "<?php if($1): ?>", $value);
    }

    protected function compileElse(string $value): string
    {
        return preg_replace("/@else/", "<?php else: ?>", $value);
    }

    protected function compileEndif(string $value): string
    {
        return preg_replace("/@endif/", "<?php endif; ?>", $value);
    }
}