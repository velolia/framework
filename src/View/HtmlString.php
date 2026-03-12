<?php

declare(strict_types=1);

namespace Velolia\View;

use Stringable;

/**
 * Wraps a string that is already safely escaped HTML.
 * The e() helper checks for this type and skips re-escaping.
 */
class HtmlString implements Stringable
{
    public function __construct(private string $html) {}

    public function toHtml(): string
    {
        return $this->html;
    }

    public function __toString(): string
    {
        return $this->html;
    }
}
