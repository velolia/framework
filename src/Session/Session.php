<?php

declare(strict_types=1);

namespace Velolia\Session;

use RuntimeException;

class Session
{
    /**
     * Whether the session has been started.
     * @var bool
    */
    protected bool $started = false;

    /**
     * Start the session.
     * @var array
     * @throws RuntimeException
     * @return bool
    */
    public function start(array $options = []): bool
    {
        if (headers_sent()) {
            throw new \RuntimeException('Cannot start session, headers already sent.');
        }

        if ($this->isStarted()) {
            return true;
        }

        if ($name = config('session.name')) {
            session_name($name);
        }

        session_save_path(config('session.files'));

        $options = array_merge([
            'cookie_lifetime' => config('session.lifetime') * 60,
            'cookie_secure'   => config('session.secure'),
            'cookie_httponly' => config('session.http_only'),
            'cookie_samesite' => config('session.same_site'),
        ], $options);

        if (!session_start($options)) {
            throw new RuntimeException('Failed to start session.');
        }

        $this->initFlashData();

        $this->started = true;

        return true;
    }

    /**
     * Ensure the session has been started.
     * @return void
    */
    protected function ensureStarted(): void
    {
        if (!$this->isStarted()) {
            $this->start();
        }
    }

    /**
     * Check if the session has been started.
     * @return bool
    */
    public function isStarted(): bool
    {
        return session_status() === PHP_SESSION_ACTIVE;
    }

    /**
     * Get all of the session data.
     * @return array
    */
    public function all(): array
    {
        $this->ensureStarted();
        return $_SESSION ?? [];
    }

    /**
     * Get a session value.
     * @return mixed
    */
    public function get(string $key, mixed $default = null): mixed
    {
        $this->ensureStarted();
        return $_SESSION[$key] ?? $default;
    }

    /**
     * Set a session value.
     * @return void
    */
    public function set(string $key, mixed $value): void
    {
        $this->ensureStarted();
        $_SESSION[$key] = $value;
    }

    /**
     * Check if a session value exists.
     * @return bool
    */
    public function has(string $key): bool
    {
        $this->ensureStarted();
        return isset($_SESSION[$key]);
    }

    /**
     * Pull a session value.
     * @return mixed
    */
    public function pull(string $key, mixed $default = null): mixed
    {
        $this->ensureStarted();
        $value = $this->get($key, $default);
        $this->delete($key);
        return $value;
    }

    /**
     * Delete a session value.
     * @return void
    */
    public function delete(string $key): void
    {
        $this->ensureStarted();
        unset($_SESSION[$key]);
    }

    /**
     * Initialize the flash data.
     * @return void
    */
    public function initFlashData()
    {
        $flash = $this->get('_flash', ['old' => [], 'new' => []]);
        $flash['old'] = $flash['new'] ?? [];
        $flash['new'] = [];
        $this->set('_flash', $flash);
    }

    /**
     * Flash a session value.
     * @return mixed
    */
    public function flash(string $key, mixed $value = null): mixed
    {
        $this->ensureStarted();
        if (is_null($value)) {
            $flash = $this->get('_flash');
            $value = $flash['old'][$key] ?? null;
            unset($flash['old'][$key]);
            $this->set('_flash', $flash);
            return $value;
        }

        $flash = $this->get('_flash');
        $flash['new'][$key] = $value;
        $this->set('_flash', $flash);

        return null;
    }

    /**
     * Check if a session value has been flashed.
     * @return bool
    */
    public function hasFlash(string $key): bool
    {
        $this->ensureStarted();
        $flash = $this->get('_flash');
        return isset($flash['old'][$key]);
    }

    /**
     * Get a flashed session value.
     * @return mixed
    */
    public function getFlash(string $key, mixed $default = null): mixed
    {
        $this->ensureStarted();
        $flash = $this->get('_flash');
        return $flash['old'][$key] ?? $default;
    }
}