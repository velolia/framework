<?php

namespace Velolia\Console;

class ConsoleOutput
{
    private const COLOR_RESET = "\033[0m";
    private const COLOR_GREEN = "\033[32m";
    private const COLOR_RED = "\033[31m";
    private const COLOR_YELLOW = "\033[33m";
    private const COLOR_CYAN = "\033[36m";
    private const COLOR_GRAY = "\033[90m";

    public function success(string $message): void
    {
        $this->writeln(self::COLOR_GREEN . '✓ ' . $message . self::COLOR_RESET);
    }

    public function error(string $message): void
    {
        $this->writeln(self::COLOR_RED . '✗ ' . $message . self::COLOR_RESET);
    }

    public function info(string $message): void
    {
        $this->writeln(self::COLOR_CYAN . 'ℹ ' . $message . self::COLOR_RESET);
    }

    public function warning(string $message): void
    {
        $this->writeln(self::COLOR_YELLOW . '⚠ ' . $message . self::COLOR_RESET);
    }

    public function line(string $message = ''): void
    {
        $this->writeln($message);
    }

    public function comment(string $message): void
    {
        $this->writeln(self::COLOR_GRAY . $message . self::COLOR_RESET);
    }

    public function newLine(int $count = 1): void
    {
        echo str_repeat(PHP_EOL, $count);
    }

    public function title(string $title): void
    {
        $this->newLine();
        $this->writeln(self::COLOR_CYAN . $title . self::COLOR_RESET);
        $this->writeln(str_repeat('=', strlen($title)));
    }

    private function writeln(string $message): void
    {
        echo $message . PHP_EOL;
    }
}
