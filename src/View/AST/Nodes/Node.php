<?php

declare(strict_types=1);

namespace Velolia\View\AST\Nodes;

abstract class Node
{
    abstract public function compile(): string;
}
