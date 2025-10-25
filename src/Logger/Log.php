<?php

declare(strict_types=1);

namespace Velolia\Logger;

use SplFileObject;

class Log
{
    /**
     * Log file
     * @var string
    */
    protected $logFile;

    /**
     * Date format
     * @var string
    */
    protected $dateFormat = 'Y-m-d H:i:s';

    /**
     * Log constructor.
     * @param string $logFile
    */
    public function __construct($logFile = null)
    {
        if (!file_exists(app()->basePath() . DIRECTORY_SEPARATOR . 'storage/logs')) {
            mkdir(app()->basePath() . DIRECTORY_SEPARATOR . 'storage/logs');
        }
        $this->logFile = $logFile ?? app()->basePath() . DIRECTORY_SEPARATOR . 'storage/logs/velolia.log';
    }

    /**
     * Error log
     * @param string $message
     * @param array $context
    */
    public function error($message, array $context = [])
    {
        $this->log('ERROR', $message, $context);
    }

    /**
     * Warning log
     * @param string $message
     * @param array $context
    */
    public function warning($message, array $context = [])
    {
        $this->log('WARNING', $message, $context);
    }

    /**
     * Info log
     * @param string $message
     * @param array $context
    */
    public function info($message, array $context = [])
    {
        $this->log('INFO', $message, $context);
    }

    /**
     * Debug log
     * @param string $message
     * @param array $context
    */
    public function debug($message, array $context = [])
    {
        $this->log('DEBUG', $message, $context);
    }

    /**
     * Log message
     * @param string $level
     * @param string $message
     * @param array $context
    */
    protected function log($level, $message, array $context = [])
    {
        $date = date($this->dateFormat);
        $contextString = !empty($context) ? json_encode($context) : '';
        $logMessage = "[$date] [$level] $message $contextString" . PHP_EOL;

        file_put_contents($this->logFile, $logMessage, FILE_APPEND);
    }

    /**
     * Get log contents
     * @param int $lines
     * @return string
    */
    public function getLogContents($lines = 100)
    {
        if (!file_exists($this->logFile)) {
            return "Log file not found.";
        }

        $file = new SplFileObject($this->logFile, 'r');
        $file->seek(PHP_INT_MAX);
        $lastLine = $file->key();

        $output = [];
        for ($i = max(0, $lastLine - $lines); $i <= $lastLine; $i++) {
            $file->seek($i);
            $output[] = $file->current();
        }

        return implode('', $output);
    }
}