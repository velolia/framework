<?php

declare(strict_types=1);

namespace Velolia\Log;

use Psr\Log\AbstractLogger;
use Psr\Log\LogLevel;
use DateTime;

class FileLogger extends AbstractLogger
{
    protected string $path;

    public function __construct(string $path)
    {
        $this->path = $path;
        $this->ensureDirectoryExists();
    }

    public function log($level, string|\Stringable $message, array $context = []): void
    {
        $date = new DateTime();
        $timestamp = $date->format('Y-m-d H:i:s');
        $fileName = 'velolia.log';
        $filePath = $this->path . DIRECTORY_SEPARATOR . $fileName;

        $formattedMessage = sprintf(
            "[%s] %s: %s %s\n",
            $timestamp,
            strtoupper((string) $level),
            (string) $message,
            json_encode($context)
        );

        file_put_contents($filePath, $formattedMessage, FILE_APPEND);
    }

    protected function ensureDirectoryExists(): void
    {
        if (!is_dir($this->path)) {
            mkdir($this->path, 0777, true);
        }
    }
}
