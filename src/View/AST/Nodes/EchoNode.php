<?php

declare(strict_types=1);

namespace Velolia\View\AST\Nodes;

class EchoNode extends Node
{
    public function __construct(protected string $expression, protected bool $raw = false) {}

    public function compile(): string
    {
        if ($this->raw) {
            return "<?php echo {$this->expression}; ?>";
        }
        
        return "<?php echo e({$this->expression}); ?>";
    }
}
