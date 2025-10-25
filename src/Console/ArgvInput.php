<?php

declare(strict_types=1);

namespace Velolia\Console;

class ArgvInput
{
    private array $arguments = [];
    private array $options = [];
    private string $commandName = '';

    public function __construct(array $argv)
    {
        $this->parse($argv);
    }

    private function parse(array $argv): void
    {
        array_shift($argv);

        if (empty($argv)) {
            return;
        }

        if (!str_starts_with($argv[0], '-')) {
            $this->commandName = array_shift($argv);
        }

        foreach ($argv as $token) {
            if (str_starts_with($token, '--')) {
                $this->parseLongOption($token);
            } elseif (str_starts_with($token, '-') && strlen($token) > 1) {
                $this->parseShortOptions($token);
            } else {
                $this->arguments[] = $token;
            }
        }
    }

    private function parseLongOption(string $token): void
    {
        $option = substr($token, 2);
        
        if (str_contains($option, '=')) {
            [$key, $value] = explode('=', $option, 2);
            $this->options[$key] = $value;
        } else {
            $this->options[$option] = true;
        }
    }

    private function parseShortOptions(string $token): void
    {
        $option = substr($token, 1);

        if (preg_match('/^([a-zA-Z0-9])=(.+)$/', $option, $matches)) {
            $this->options[$matches[1]] = $matches[2];
            return;
        }

        $chars = str_split(substr($token, 1));
        
        foreach ($chars as $char) {
            $this->options[$char] = true;
        }
    }

    public function getCommandName(): string
    {
        return $this->commandName;
    }

    public function getArguments(): array
    {
        return $this->arguments;
    }

    public function getArgument(int $index, mixed $default = null): mixed
    {
        return $this->arguments[$index] ?? $default;
    }

    public function getOptions(): array
    {
        return $this->options;
    }

    public function hasOption(string $name): bool
    {
        return isset($this->options[$name]);
    }

    public function getOption(string $name, mixed $default = null): mixed
    {
        return $this->options[$name] ?? $default;
    }
}