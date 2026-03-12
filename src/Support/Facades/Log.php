<?php

declare(strict_types=1);

namespace Velolia\Support\Facades;

use Velolia\Log\LogManager;

/**
 * @method static void emergency(string $message, array $context = [])
 * @method static void alert(string $message, array $context = [])
 * @method static void critical(string $message, array $context = [])
 * @method static void error(string $message, array $context = [])
 * @method static void warning(string $message, array $context = [])
 * @method static void notice(string $message, array $context = [])
 * @method static void info(string $message, array $context = [])
 * @method static void debug(string $message, array $context = [])
 * @method static void log(mixed $level, string $message, array $context = [])
 * 
 * @see \Velolia\Log\LogManager
 */
class Log extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'log';
    }
}
