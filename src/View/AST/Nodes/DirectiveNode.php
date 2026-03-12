<?php

declare(strict_types=1);

namespace Velolia\View\AST\Nodes;

class DirectiveNode extends Node
{
    /**
     * @param Node[] $children
     */
    public function __construct(
        protected string $name,
        protected ?string $args = null,
        protected array $children = []
    ) {}

    public function compile(): string
    {
        $content = '';
        foreach ($this->children as $child) {
            $content .= $child->compile();
        }

        return match ($this->name) {
            'if' => "<?php if({$this->args}): ?>{$content}",
            'elseif' => "<?php elseif({$this->args}): ?>{$content}",
            'else' => "<?php else: ?>{$content}",
            'endif' => "<?php endif; ?>",
            
            'foreach' => $this->compileForeach($content),
            'endforeach' => "<?php \$loop->next(); endforeach; \$factory->popLoop(); \$loop = \$factory->getLastLoop(); ?>",

            'extends' => "<?php \$factory->extend({$this->args}); ?>",
            'yield' => "<?php echo \$factory->yieldSection({$this->args}); ?>",
            'section' => $this->compileSection($content),
            'endsection' => "<?php \$factory->endSection(); ?>",

            'include' => "<?php echo view({$this->args})->render(); ?>",
            'csrf' => "<?php echo csrf_field(); ?>",
            'method' => "<input type=\"hidden\" name=\"_method\" value=\"<?php echo {$this->args}; ?>\">",
            
            'error' => "<?php if(has_error({$this->args})): \$message = error({$this->args}); ?>{$content}",
            'enderror' => "<?php endif; ?>",

            'ultrawire' => $this->compileUltraWire(),
            'ultrawireScripts' => "<script src=\"/js/morphdom.js\"></script><script src=\"/js/ultrawire.js\"></script>",
            'ultrawireStyles' => "<style>[uw\\:loading] { display: none; }</style>",

            'xcomponent'        => $this->compileComponent($content),
            'xendcomponent'     => '',
            'xcomponent_inline' => "<?php echo \$factory->renderComponentInline({$this->args}); ?>",

            'php_raw' => "<?php {$this->args} ?>",

            default => $content
        };
    }


    protected function compileUltraWire(): string
    {
        $name = trim((string)$this->args);
        return "<?php echo \\Velolia\\UltraWire\\UltraWire::renderComponent({$name}, []); ?>";
    }

    protected function compileComponent(string $slotContent): string
    {
        $args = (string)$this->args;

        return implode('', [
            "<?php \$factory->startComponent({$args}); ?>",
            $slotContent,
            "<?php echo \$factory->endComponent(); ?>",
        ]);
    }

    protected function compileForeach(string $content): string
    {
        preg_match('/(.+?)\s+as\s+(.+)/', (string)$this->args, $matches);
        $items = $matches[1] ?? '$items';
        $item = $matches[2] ?? '$item';

        return "<?php \$factory->addLoop({$items}); foreach({$items} as {$item}): \$loop = \$factory->getLastLoop(); ?>{$content}";
    }

    protected function compileSection(string $content): string
    {
        if (strpos((string)$this->args, ',') !== false) {
            [$name, $value] = explode(',', $this->args, 2);
            $name = trim($name);
            return "<?php \$factory->inlineSection({$name}, {$value}); ?>";
        }

        $name = trim($this->args);
        return "<?php \$factory->startSection({$name}); ?>{$content}";
    }
}
