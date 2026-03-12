<?php

declare(strict_types=1);

namespace Velolia\View\AST;

class ASTCompiler
{
    public function compile(string $content): string
    {
        $content = $this->compileXComponents($content);

        $lexer = new Lexer($content);
        $tokens = $lexer->tokenize();

        $parser = new Parser($tokens);
        $nodes = $parser->parse();

        $compiled = '';
        foreach ($nodes as $node) {
            $compiled .= $node->compile();
        }

        return $compiled;
    }

    protected function compileXComponents(string $content): string
    {
        $content = preg_replace_callback(
            '/<x-([\w-]+)((?:\s+[^>]*?)?)\s*\/>/',
            function (array $m): string {
                $name = $this->resolveComponentName($m[1]);
                $props = $this->parseHtmlAttributes($m[2]);
                return "@xcomponent_inline('{$name}', {$props})";
            },
            $content
        );

        $content = preg_replace_callback(
            '/<x-([\w-]+)((?:\s+[^>]*?)?)>/',
            function (array $m): string {
                $name = $this->resolveComponentName($m[1]);
                $props = $this->parseHtmlAttributes($m[2]);
                return "@xcomponent('{$name}', {$props})";
            },
            $content
        );

        $content = preg_replace('/<\/x-[\w-]+>/', '@xendcomponent', $content);

        return $content;
    }

    protected function resolveComponentName(string $tag): string
    {
        return 'components.' . $tag;
    }

    protected function parseHtmlAttributes(string $attrString): string
    {
        $attrString = trim($attrString);
        if ($attrString === '') {
            return '[]';
        }

        preg_match_all('/([:\w-]+)\s*=\s*"([^"]*)"|([:\w-]+)\s*=\s*\'([^\']*)\'|([:\w-]+)/', $attrString, $matches, PREG_SET_ORDER);

        $pairs = [];
        foreach ($matches as $match) {
            if (!empty($match[1])) {
                $key   = ltrim($match[1], ':');
                $value = $match[2];

                if ($match[1][0] === ':') {
                    $pairs[] = "'{$key}' => {$value}";
                } else {
                    $pairs[] = "'{$key}' => " . var_export($value, true);
                }
            } elseif (!empty($match[3])) {
                $key   = ltrim($match[3], ':');
                $value = $match[4];
                if ($match[3][0] === ':') {
                    $pairs[] = "'{$key}' => {$value}";
                } else {
                    $pairs[] = "'{$key}' => " . var_export($value, true);
                }
            } elseif (!empty($match[5])) {
                $key   = ltrim($match[5], ':');
                $pairs[] = "'{$key}' => true";
            }
        }

        return '[' . implode(', ', $pairs) . ']';
    }
}
