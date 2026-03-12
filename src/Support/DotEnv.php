<?php

declare(strict_types=1);

namespace Velolia\Support;

use RuntimeException;

class DotEnv
{
    /**
     * Parsed environment variables cache
     */
    protected static array $cache = [];

    /**
     * Whether the .env has been loaded
     */
    protected static bool $loaded = false;

    /**
     * Load .env file
     */
    public static function load(string $path, bool $overwrite = false): void
    {
        if (self::$loaded && !$overwrite) {
            return;
        }

        $envFile = rtrim($path, '/') . '/.env';

        if (!file_exists($envFile)) {
            throw new RuntimeException("Environment file not found: {$envFile}");
        }

        $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        
        if ($lines === false) {
            throw new RuntimeException("Failed to read environment file: {$envFile}");
        }

        foreach ($lines as $line) {
            self::parseLine($line, $overwrite);
        }

        self::$loaded = true;
    }

    /**
     * Parse a single line from .env file
     */
    protected static function parseLine(string $line, bool $overwrite): void
    {
        $line = trim($line);

        if ($line === '' || str_starts_with($line, '#')) {
            return;
        }

        if (!str_contains($line, '=')) {
            return;
        }

        [$key, $value] = explode('=', $line, 2);
        $key = trim($key);
        $value = trim($value);

        if ($key === '' || !self::isValidKey($key)) {
            return;
        }

        $value = self::processValue($value);

        if ($overwrite || !isset($_ENV[$key])) {
            $_ENV[$key] = $value;
            $_SERVER[$key] = $value;
            putenv("{$key}={$value}");
            self::$cache[$key] = $value;
        }
    }

    /**
     * Process and clean the value
     */
    protected static function processValue(string $value): string
    {
        if (!str_starts_with($value, '"') && !str_starts_with($value, "'")) {
            if (str_contains($value, ' #')) {
                $value = explode(' #', $value)[0];
                $value = trim($value);
            }
        }

        if ((str_starts_with($value, '"') && str_ends_with($value, '"')) ||
            (str_starts_with($value, "'") && str_ends_with($value, "'"))) {
            $value = substr($value, 1, -1);
            
            if (str_starts_with($value, '"')) {
                $value = str_replace('\"', '"', $value);
            }
        }

        $value = self::expandVariables($value);

        return $value;
    }

    /**
     * Expand variables in the value
     */
    protected static function expandVariables(string $value): string
    {
        $value = preg_replace_callback('/\$\{([A-Z0-9_]+)\}/', function ($matches) {
            return self::get($matches[1], '');
        }, $value);

        $value = preg_replace_callback('/\$([A-Z0-9_]+)/', function ($matches) {
            return self::get($matches[1], '');
        }, $value);

        return $value;
    }

    /**
     * Check if key is valid
     */
    protected static function isValidKey(string $key): bool
    {
        return preg_match('/^[A-Z_][A-Z0-9_]*$/', $key) === 1;
    }

    /**
     * Get environment variable with type casting
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        if (isset(self::$cache[$key]) && !is_string(self::$cache[$key])) {
            return self::$cache[$key];
        }

        $value = $_ENV[$key] ?? $_SERVER[$key] ?? getenv($key);

        if ($value === false) {
            return $default;
        }

        // Handle quoted strings - don't cast them
        if (is_string($value) && strlen($value) > 1 && 
            (($value[0] === '"' && $value[strlen($value)-1] === '"') || 
             ($value[0] === "'" && $value[strlen($value)-1] === "'"))) {
            $value = substr($value, 1, -1);
        } else {
            switch (strtolower((string) $value)) {
                case 'true':
                case '(true)':
                    $value = true;
                    break;
                case 'false':
                case '(false)':
                    $value = false;
                    break;
                case 'empty':
                case '(empty)':
                    $value = '';
                    break;
                case 'null':
                case '(null)':
                    $value = null;
                    break;
            }
        }

        self::$cache[$key] = $value;

        return $value;
    }

    /**
     * Get as string
     */
    public static function string(string $key, string $default = ''): string
    {
        return (string) self::get($key, $default);
    }

    /**
     * Get as integer
     */
    public static function int(string $key, int $default = 0): int
    {
        $value = self::get($key, $default);
        return is_numeric($value) ? (int) $value : $default;
    }

    /**
     * Get as float
     */
    public static function float(string $key, float $default = 0.0): float
    {
        $value = self::get($key, $default);
        return is_numeric($value) ? (float) $value : $default;
    }

    /**
     * Get as boolean
     */
    public static function bool(string $key, bool $default = false): bool
    {
        $value = self::get($key);

        if ($value === null) {
            return $default;
        }

        $value = strtolower((string) $value);

        return match ($value) {
            'true', '1', 'yes', 'on' => true,
            'false', '0', 'no', 'off', '' => false,
            default => $default,
        };
    }

    /**
     * Get as array (comma-separated values)
     */
    public static function array(string $key, array $default = []): array
    {
        $value = self::get($key);

        if ($value === null || $value === '') {
            return $default;
        }

        return array_map('trim', explode(',', (string) $value));
    }

    /**
     * Check if environment variable exists
     */
    public static function has(string $key): bool
    {
        return self::get($key) !== null;
    }

    /**
     * Require environment variable (throw if not exists)
     */
    public static function require(string $key): string
    {
        $value = self::get($key);

        if ($value === null) {
            throw new RuntimeException("Required environment variable [{$key}] is not set.");
        }

        return (string) $value;
    }

    /**
     * Validate required environment variables
     */
    public static function validate(array $required): void
    {
        $missing = [];

        foreach ($required as $key) {
            if (!self::has($key)) {
                $missing[] = $key;
            }
        }

        if (!empty($missing)) {
            throw new RuntimeException(
                'Missing required environment variables: ' . implode(', ', $missing)
            );
        }
    }

    /**
     * Set environment variable (useful for testing)
     */
    public static function set(string $key, string $value): void
    {
        $_ENV[$key] = $value;
        $_SERVER[$key] = $value;
        putenv("{$key}={$value}");
        self::$cache[$key] = $value;
    }

    /**
     * Clear cache
     */
    public static function clearCache(): void
    {
        self::$cache = [];
    }

    /**
     * Get all environment variables
     */
    public static function all(): array
    {
        return $_ENV;
    }

    /**
     * Reload .env file
     */
    public static function reload(string $path): void
    {
        self::$loaded = false;
        self::clearCache();
        self::load($path, true);
    }
}