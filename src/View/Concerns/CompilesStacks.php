<?php

declare(strict_types=1);

namespace Velolia\View\Concerns;

trait CompilesStacks
{
    protected array $stacks = [];

    protected function compileStacks(string $value): string
    {
        $value = $this->compilePush($value);
        $value = $this->compileStack($value);
        
        return $value;
    }

    protected function compilePush(string $value): string
    {
        $pattern = "/@push\s*\(\s*['\"](.+?)['\"]\s*\)/";
        $value = preg_replace_callback($pattern, function ($matches) {
            return "<?php \$__blade->startPush('{$matches[1]}'); ?>";
        }, $value);

        return preg_replace("/@endpush/", "<?php \$__blade->stopPush(); ?>", $value);
    }

    protected function compileStack(string $value): string
    {
        $pattern = "/@stack\s*\(\s*['\"](.+?)['\"]\s*\)/";
        return preg_replace_callback($pattern, function ($matches) {
            return "<?php echo \$__blade->yieldPushContent('{$matches[1]}'); ?>";
        }, $value);
    }

    public function startPush(string $stack, bool $prepend = false): void
    {
        if (!isset($this->stacks[$stack])) {
            $this->stacks[$stack] = [];
        }

        if (ob_start()) {
            $this->sectionStack[] = [$stack, $prepend];
        }
    }

    public function stopPush(): void
    {
        if (empty($this->sectionStack)) {
            throw new \InvalidArgumentException('Cannot end push without first starting one.');
        }

        [$stack, $prepend] = array_pop($this->sectionStack);
        $content = ob_get_clean();

        if ($prepend) {
            array_unshift($this->stacks[$stack], $content);
        } else {
            $this->stacks[$stack][] = $content;
        }
    }

    public function yieldPushContent(string $stack): string
    {
        if (!isset($this->stacks[$stack])) {
            return '';
        }

        return implode('', $this->stacks[$stack]);
    }
}