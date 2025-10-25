<?php

declare(strict_types=1);

namespace Velolia\View;

class BladeCompiler
{
    use Concerns\CompilesConditionals,
        Concerns\CompilesExtensions,
        Concerns\CompilesLayouts,
        Concerns\CompilesComments,
        Concerns\CompilesEchos,
        Concerns\CompilesLoops,
        Concerns\CompilesStacks,
        Concerns\CompilesIncludes,
        Concerns\CompilesPhp;

    protected string $cachePath;
    protected ?string $layout = null;
    protected array $directives = [];
    protected array $sections = [];
    protected array $sectionStack = [];

    protected array $compilers = [
        'Comments',
        'Extensions',
        'Php',
        'Echos',
        'Layouts',
        'Stacks',
        'Includes',
        'Conditionals',
        'Loops',
    ];

    public function __construct(string $cachePath)
    {
        $this->cachePath = $cachePath;
    }

    public function compile(string $value): string
    {
        if (str_contains($value, '@verbatim')) {
            $value = $this->storeVerbatimBlocks($value);
        }

        $result = '';

        foreach (token_get_all($value) as $token) {
            if (is_array($token)) {
                [$id, $content] = $token;
                if ($id === T_INLINE_HTML) {
                    $result .= $this->compileString($content);
                } else {
                    $result .= $content;
                }
            } else {
                $result .= $token;
            }
        }

        if (str_contains($value, '@verbatim')) {
            $result = $this->restoreVerbatimBlocks($value);
        }

        return $result;
    }

    protected function compileString(string $value): string
    {
        foreach ($this->compilers as $type) {
            $value = $this->{"compile{$type}"}($value);
        }

        return $value;
    }

    public function directive(string $name, callable $handler): void
    {
        $this->directives[$name] = $handler;
    }

    protected function storeVerbatimBlocks(string $value): string
    {
        return preg_replace_callback('/(?<!@)@verbatim(.*?)@endverbatim/s', function ($matches) {
            return $this->storeRawBlock($matches[1]);
        }, $value);
    }

    protected function restoreVerbatimBlocks(string $value): string
    {
        $value = str_replace(['?><?php', '?>\n<?php'], '', $value);
        return $value;
    }

    protected function storeRawBlock(string $value): string
    {
        return '?>' . $value . '<?php';
    }

    public function extends(string $layout): void
    {
        $this->layout = $layout;
    }

    public function startSection(string $section, ?string $content = null): void
    {
        if ($content === null) {
            if (ob_start()) {
                $this->sectionStack[] = $section;
            }
        } else {
            $this->sections[$section] = $content;
        }
    }

    public function stopSection(bool $overwrite = false): string
    {
        if (empty($this->sectionStack)) {
            throw new \InvalidArgumentException('Cannot end section without first starting one.');
        }

        $last = array_pop($this->sectionStack);
        
        if ($overwrite) {
            $this->sections[$last] = ob_get_clean();
        } else {
            $this->extendSection($last, ob_get_clean());
        }

        return $last;
    }

    public function yieldSection(): string
    {
        if (empty($this->sectionStack)) {
            return '';
        }

        return $this->yieldContent($this->stopSection());
    }

    protected function extendSection(string $section, string $content): void
    {
        if (isset($this->sections[$section])) {
            $content = str_replace('@parent', $content, $this->sections[$section]);
        }

        $this->sections[$section] = $content;
    }

    public function yieldContent(string $section, string $default = ''): string
    {
        return $this->sections[$section] ?? $default;
    }

    public function getLayout(): ?string
    {
        return $this->layout;
    }

    public function clearLayout(): void
    {
        $this->layout = null;
    }
}