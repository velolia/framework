<?php

declare(strict_types=1);

namespace Velolia\View\Concerns;

trait CompilesEchos
{
    protected function compileEchos(string $value): string
    {
        $value = $this->compileRawEchos($value);
        $value = $this->compileEscapedEchos($value);
        $value = $this->compileRegularEchos($value);
        return $value;
    }

    protected function compileRawEchos(string $value): string
    {
        $pattern = '/\{\!!\s*(.+?)\s*\!\!\}/s';
        
        return preg_replace_callback($pattern, function ($matches) {
            $whitespace = empty($matches[2]) ? '' : $matches[2] . $matches[2];
            return "<?php echo {$matches[1]}; ?>{$whitespace}";
        }, $value);
    }

    protected function compileRegularEchos(string $value): string
    {
        $pattern = '/\{\{\s*(.+?)\s*\}\}/s';
        
        return preg_replace_callback($pattern, function ($matches) {
            return $this->compileEchoDefaults($matches[1]);
        }, $value);
    }

    protected function compileEscapedEchos(string $value): string
    {
        $pattern = '/\{\{\{\s*(.+?)\s*\}\}\}/s';
        
        return preg_replace_callback($pattern, function ($matches) {
            return "<?php echo htmlspecialchars({$matches[1]}, ENT_QUOTES, 'UTF-8', true); ?>";
        }, $value);
    }

    protected function compileEchoDefaults(string $value): string
    {
        return "<?php echo e({$value}); ?>";
    }

    protected function wrapInEchoHandler(string $value): string
    {
        return "e({$value})";
    }
}