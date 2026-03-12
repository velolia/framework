<?php

declare(strict_types=1);

namespace Velolia\View\AST\Nodes;

class TextNode extends Node
{
    public function __construct(protected string $content) {}

    public function compile(): string
    {
        return $this->content;
    }
}
