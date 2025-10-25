<?php

declare(strict_types=1);

namespace Velolia\View\Concerns;

trait CompilesPhp
{
    protected function compilePhp(string $value): string
    {
        $value = $this->compilePhpBlock($value);
        $value = $this->compilePhpInline($value);
        return $value;
    }

    protected function compilePhpBlock(string $value): string
    {
        $pattern = '/@php(.*?)@endphp/s';
        return preg_replace_callback($pattern, function ($matches) {
            return "<?php{$matches[1]}?>";
        }, $value);
    }

    protected function compilePhpInline(string $value): string
    {
        $pattern = '/@php\s*\((.*?)\)/';
        return preg_replace($pattern, "<?php $1; ?>", $value);
    }
}