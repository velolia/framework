<?php

declare(strict_types=1);

namespace Velolia\View\Concerns;

trait CompilesLayouts
{
    protected function compileLayouts(string $value): string
    {
        $value = $this->compileExtends($value);
        $value = $this->compileSection($value);
        $value = $this->compileYield($value);
        return $value;
    }

    protected function compileExtends(string $value): string
    {
        $pattern = "/@extends\s*\(\s*['\"](.+?)['\"]\s*\)/";
        return preg_replace_callback($pattern, function ($matches) {
            return "<?php \$__blade->extends('{$matches[1]}'); ?>";
        }, $value);
    }

    protected function compileSection(string $value): string
    {
        $patternInline = "/@section\s*\(\s*['\"]([^'\"]+)['\"]\s*,\s*['\"]([^'\"]+)['\"]\s*\)/";
        $value = preg_replace_callback($patternInline, function ($matches) {
            $content = addslashes($matches[2]);
            return "<?php \$__blade->startSection('{$matches[1]}', '{$content}'); ?>";
        }, $value);

        $patternStart = "/@section\s*\(\s*['\"]([^'\"]+)['\"]\s*\)/";
        $value = preg_replace_callback($patternStart, function ($matches) {
            return "<?php \$__blade->startSection('{$matches[1]}'); ?>";
        }, $value);

        $patternEnd = "/@endsection/";
        $value = preg_replace($patternEnd, "<?php \$__blade->stopSection(); ?>", $value);

        return $value;
    }

    protected function compileYield(string $value): string
    {
        $pattern = "/@yield\s*\(\s*['\"](.+?)['\"]\s*(?:,\s*(.+?))?\s*\)/";
        
        return preg_replace_callback($pattern, function ($matches) {
            $section = $matches[1];
            
            if (isset($matches[2])) {
                $default = trim($matches[2]);
                
                if (preg_match('/^["\'](.+)["\']$/', $default, $stringMatch)) {
                    $escaped = addslashes($stringMatch[1]);
                    return "<?php echo \$__blade->yieldContent('{$section}', '{$escaped}'); ?>";
                } else {
                    return "<?php echo \$__blade->yieldContent('{$section}', {$default}); ?>";
                }
            }
            
            return "<?php echo \$__blade->yieldContent('{$section}'); ?>";
        }, $value);
    }
}