<?php

declare(strict_types=1);

namespace Velolia\View\AST;

use Velolia\View\AST\Nodes\TextNode;
use Velolia\View\AST\Nodes\EchoNode;
use Velolia\View\AST\Nodes\DirectiveNode;
use Exception;

class Parser
{
    protected array $tokens;
    protected int $cursor = 0;
    protected int $length;

    protected array $blockDirectives = [
        'if'            => 'endif',
        'foreach'       => 'endforeach',
        'section'       => 'endsection',
        'error'         => 'enderror',
        'xcomponent'    => 'xendcomponent',
    ];

    public function __construct(array $tokens)
    {
        $this->tokens = $tokens;
        $this->length = count($tokens);
    }

    public function parse(): array
    {
        return $this->parseUntil();
    }

    protected function parseUntil(?string $closingDirective = null): array
    {
        $nodes = [];

        while ($this->cursor < $this->length) {
            $token = $this->tokens[$this->cursor++];

            if ($token['type'] === Lexer::T_TEXT) {
                $nodes[] = new TextNode($token['value']);
            } elseif ($token['type'] === Lexer::T_ECHO) {
                $nodes[] = new EchoNode($token['value'], false);
            } elseif ($token['type'] === Lexer::T_ECHO_RAW) {
                $nodes[] = new EchoNode($token['value'], true);
            } elseif ($token['type'] === Lexer::T_DIRECTIVE) {
                $name = $token['name'];

                if ($closingDirective && $name === $closingDirective) {
                    return $nodes;
                }

                if (isset($this->blockDirectives[$name])) {
                    if ($name === 'section' && strpos($token['args'] ?? '', ',') !== false) {
                        $nodes[] = new DirectiveNode($name, $token['args']);
                        continue;
                    }

                    $children = $this->parseUntil($this->blockDirectives[$name]);
                    $nodes[] = new DirectiveNode($name, $token['args'], $children);
                    $nodes[] = new DirectiveNode($this->blockDirectives[$name]);
                    continue;
                }

                $nodes[] = new DirectiveNode($name, $token['args']);
            }
        }

        if ($closingDirective) {
            throw new Exception("Missing closing directive: @{$closingDirective}");
        }

        return $nodes;
    }
}
