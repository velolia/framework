<?php

declare(strict_types=1);

namespace Velolia\Console;

class CommandLoader
{
    private string $commandsPath;
    private array $commands = [];

    public function __construct()
    {
        $this->commandsPath = __DIR__ . '/Commands';
    }

    public function loadCommands(): array
    {
        if (!is_dir($this->commandsPath)) {
            return [];
        }

        $files = glob($this->commandsPath . '/*Command.php');

        foreach ($files as $file) {
            $this->loadCommandFromFile($file);
        }

        return $this->commands;
    }

    private function loadCommandFromFile(string $file): void
    {
        $className = $this->getClassNameFromFile($file);
        
        if (!$className || !class_exists($className)) {
            return;
        }

        $reflection = new \ReflectionClass($className);
        
        if ($reflection->isAbstract() || !$reflection->isSubclassOf(Command::class)) {
            return;
        }

        $instance = $reflection->newInstance();
        $commandName = $instance->getName();
        
        $this->commands[$commandName] = $className;
    }

    private function getClassNameFromFile(string $file): ?string
    {
        $content = file_get_contents($file);
        
        if (!preg_match('/namespace\s+([^;]+);/', $content, $namespaceMatch)) {
            return null;
        }
        
        if (!preg_match('/class\s+(\w+)\s+extends/', $content, $classMatch)) {
            return null;
        }

        $fullClassName = trim($namespaceMatch[1]) . '\\' . trim($classMatch[1]);
        
        return $fullClassName;
    }

    public function getCommands(): array
    {
        return $this->commands;
    }

    public function hasCommand(string $name): bool
    {
        return isset($this->commands[$name]);
    }

    public function getCommand(string $name): ?string
    {
        return $this->commands[$name] ?? null;
    }
}
