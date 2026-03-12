<?php

declare(strict_types=1);

namespace Velolia\View\AST;

class Lexer
{
    protected string $content;
    protected int $cursor = 0;
    protected int $length;

    const T_TEXT = 'text';
    const T_ECHO = 'echo';
    const T_ECHO_RAW = 'echo_raw';
    const T_DIRECTIVE = 'directive';

    public function __construct(string $content)
    {
        $this->content = $content;
        $this->length = strlen($content);
    }

    public function tokenize(): array
    {
        $tokens = [];

        while ($this->cursor < $this->length) {
            if ($this->isEchoRaw()) {
                $tokens[] = $this->consumeEchoRaw();
            } elseif ($this->isEcho()) {
                $tokens[] = $this->consumeEcho();
            } elseif ($this->isDirective()) {
                $tokens[] = $this->consumeDirective();
            } else {
                $tokens[] = $this->consumeText();
            }
        }

        return $tokens;
    }

    protected function isEchoRaw(): bool
    {
        return substr($this->content, $this->cursor, 3) === '{!!';
    }

    protected function consumeEchoRaw(): array
    {
        $start = $this->cursor;
        $end = strpos($this->content, '!!}', $start);

        if ($end === false) {
            $this->cursor = $this->length;
            return ['type' => self::T_TEXT, 'value' => substr($this->content, $start)];
        }

        $this->cursor = $end + 3;
        $value = substr($this->content, $start + 3, $end - $start - 3);

        return ['type' => self::T_ECHO_RAW, 'value' => trim($value)];
    }

    protected function isEcho(): bool
    {
        return substr($this->content, $this->cursor, 2) === '{{';
    }

    protected function consumeEcho(): array
    {
        $start = $this->cursor;
        $end = strpos($this->content, '}}', $start);

        if ($end === false) {
            $this->cursor = $this->length;
            return ['type' => self::T_TEXT, 'value' => substr($this->content, $start)];
        }

        $this->cursor = $end + 2;
        $value = substr($this->content, $start + 2, $end - $start - 2);

        return ['type' => self::T_ECHO, 'value' => trim($value)];
    }

    protected function isDirective(): bool
    {
        if ($this->content[$this->cursor] !== '@') {
            return false;
        }

        $nextChar = $this->content[$this->cursor + 1] ?? '';
        return preg_match('/[a-zA-Z]/', $nextChar) === 1;
    }

    protected function consumeDirective(): array
    {
        $start = $this->cursor;
        
        $nameMatch = [];
        preg_match('/^@([a-zA-Z0-9_]+)/', substr($this->content, $this->cursor), $nameMatch);
        $name = $nameMatch[1];
        
        if ($name === 'php' && ($this->content[$this->cursor + 4] ?? '') !== '(') {
            $this->cursor += 4;
            $end = strpos($this->content, '@endphp', $this->cursor);
            if ($end === false) {
                $this->cursor = $this->length;
                return ['type' => self::T_TEXT, 'value' => substr($this->content, $start)];
            }
            $value = substr($this->content, $this->cursor, $end - $this->cursor);
            $this->cursor = $end + 7;
            return [
                'type' => self::T_DIRECTIVE,
                'name' => 'php_raw',
                'args' => $value
            ];
        }

        $this->cursor += strlen($name) + 1;

        while ($this->cursor < $this->length && ctype_space($this->content[$this->cursor])) {
            $this->cursor++;
        }

        $args = null;
        if (($this->content[$this->cursor] ?? '') === '(') {
            $args = $this->consumeArguments();
        }

        return [
            'type' => self::T_DIRECTIVE,
            'name' => $name,
            'args' => $args
        ];
    }

    protected function consumeArguments(): string
    {
        $start = $this->cursor;
        $depth = 0;
        $length = strlen($this->content);

        for ($i = $start; $i < $length; $i++) {
            $char = $this->content[$i];
            if ($char === '(') $depth++;
            if ($char === ')') $depth--;

            if ($depth === 0) {
                $this->cursor = $i + 1;
                return substr($this->content, $start + 1, $i - $start - 1);
            }
        }

        $this->cursor = $length;
        return substr($this->content, $start + 1);
    }

    protected function consumeText(): array
    {
        $start = $this->cursor;
        $nextEcho = strpos($this->content, '{{', $start);
        $nextEchoRaw = strpos($this->content, '{!!', $start);
        $nextDirective = strpos($this->content, '@', $start);

        $nexts = array_filter([$nextEcho, $nextEchoRaw, $nextDirective], fn($v) => $v !== false && $v > $start);
        
        if (empty($nexts)) {
            $this->cursor = $this->length;
            return ['type' => self::T_TEXT, 'value' => substr($this->content, $start)];
        }

        $next = min($nexts);
        
        if ($next === $nextDirective) {
            $nextChar = $this->content[$next + 1] ?? '';
            if (preg_match('/[a-zA-Z]/', $nextChar) !== 1) {
                $this->cursor = $next + 1;
                $following = $this->consumeText();
                return ['type' => self::T_TEXT, 'value' => substr($this->content, $start, $next - $start + 1) . $following['value']];
            }
        }

        $this->cursor = $next;
        return ['type' => self::T_TEXT, 'value' => substr($this->content, $start, $next - $start)];
    }
}
