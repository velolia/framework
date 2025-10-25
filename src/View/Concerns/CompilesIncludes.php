<?php

declare(strict_types=1);

namespace Velolia\View\Concerns;

trait CompilesIncludes
{
    protected function compileIncludes(string $value): string
    {
        $value = $this->compileInclude($value);
        $value = $this->compileIncludeIf($value);
        return $value;
    }

    protected function compileInclude(string $value): string
    {
        $pattern = "/@include\s*\(\s*['\"](.+?)['\"]\s*(?:,\s*(\[.*?\]|\$\w+))?\s*\)/";
        return preg_replace_callback($pattern, function ($matches) {
            $view = $matches[1];
            $data = $matches[2] ?? '[]';
            return "<?php echo \$__blade->render('{$view}', array_merge(get_defined_vars(), {$data})); ?>";
        }, $value);
    }

    protected function compileIncludeIf(string $value): string
    {
        $pattern = "/@includeIf\s*\(\s*['\"](.+?)['\"]\s*(?:,\s*(\[.*?\]|\$\w+))?\s*\)/";
        return preg_replace_callback($pattern, function ($matches) {
            $view = $matches[1];
            $data = $matches[2] ?? '[]';
            return "<?php if(\$__blade->exists('{$view}')): echo \$__blade->render('{$view}', array_merge(get_defined_vars(), {$data})); endif; ?>";
        }, $value);
    }
}