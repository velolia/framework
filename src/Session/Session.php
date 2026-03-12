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
     * Constructor.
     */
    public function __construct(protected array $config = []) {}

    /**
     * Start the session.
     * @var array
     * @throws RuntimeException
     * @return bool
     */
    public function start(array $options = []): bool
    {
        if (headers_sent() && PHP_SAPI !== 'cli') {
            throw new RuntimeException('Cannot start session, headers already sent.');
        }

        if ($this->isStarted()) {
            return true;
        }

        if (!empty($this->config['name'])) {
            session_name($this->config['name']);
        }

        $sessionPath = $this->config['files'] ?? throw new RuntimeException('Session files path not configured');

        if (!is_dir($sessionPath)) {
            if (!mkdir($sessionPath, 0777, true) && !is_dir($sessionPath)) {
                throw new RuntimeException("Failed to create session directory: {$sessionPath}");
            }
        }

        session_save_path($sessionPath);

        $options = array_merge([
            'cookie_lifetime' => ($this->config['lifetime'] ?? 120) * 60,
            'cookie_secure'   => (bool) ($this->config['secure'] ?? false),
            'cookie_httponly' => (bool) ($this->config['http_only'] ?? true),
            'cookie_samesite' => (string) ($this->config['same_site'] ?? 'Lax'),
        ], $options);

        if (class_exists(\Velolia\Session\EncryptedFileSessionHandler::class) && class_exists(\Velolia\Encryption\Encrypter::class)) {
            $encrypter = new \Velolia\Encryption\Encrypter(config('app.key', env('APP_KEY', '')));
            session_set_save_handler(new \Velolia\Session\EncryptedFileSessionHandler($sessionPath, $encrypter), true);
        }

        if (!session_start($options)) {
            throw new RuntimeException('Failed to start session.');
        }

        $this->ageFlashData();

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

    // put
    public function put(string $key, mixed $value): void
    {
        $this->set($key, $value);
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
     * Alias for delete().
     */
    public function forget(string $key): void
    {
        $this->delete($key);
    }

    /**
     * Age the flash data (move new to old, remove old).
     * Called at the start of each request.
     * @return void
     */
    protected function ageFlashData(): void
    {
        $flash = $_SESSION['_flash'] ?? ['old' => [], 'new' => []];

        foreach ($flash['old'] as $key) {
            unset($_SESSION[$key]);
        }

        $flash['old'] = $flash['new'] ?? [];
        $flash['new'] = [];

        $_SESSION['_flash'] = $flash;
    }

    /**
     * Flash data for the next request.
     * @return void
     */
    public function flash(string $key, mixed $value): void
    {
        $this->ensureStarted();

        $_SESSION[$key] = $value;

        $flash = $_SESSION['_flash'] ?? ['old' => [], 'new' => []];
        $flash['new'][] = $key;
        $_SESSION['_flash'] = $flash;
    }

    /**
     * Reflash all of the session flash data.
     * @return void
     */
    public function reflash(): void
    {
        $this->ensureStarted();

        $flash = $_SESSION['_flash'] ?? ['old' => [], 'new' => []];
        $flash['new'] = array_merge($flash['new'], $flash['old']);
        $_SESSION['_flash'] = $flash;
    }

    /**
     * Reflash a subset of the current flash data.
     * @return void
     */
    public function keep(array $keys): void
    {
        $this->ensureStarted();

        $flash = $_SESSION['_flash'] ?? ['old' => [], 'new' => []];
        foreach ($keys as $key) {
            if (in_array($key, $flash['old'])) {
                $flash['new'][] = $key;
            }
        }
        $_SESSION['_flash'] = $flash;
    }

    /**
     * Flash input for the next request.
     * @return void
     */
    public function flashInput(array $input): void
    {
        $this->flash('_old_input', $input);
    }

    /**
     * Regenerate the session ID
     */
    public function regenerate(bool $deleteOldSession = false): bool
    {
        $this->ensureStarted();
        return session_regenerate_id($deleteOldSession);
    }

    /**
     * Destroy the session
     */
    public function destroy(): bool
    {
        $this->ensureStarted();

        $_SESSION = [];

        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params['path'],
                $params['domain'],
                $params['secure'],
                $params['httponly']
            );
        }

        $this->started = false;

        return session_destroy();
    }

    /**
     * Get the session ID
     */
    public function getId(): string
    {
        $this->ensureStarted();
        return session_id();
    }

    /**
     * Set the session ID
     */
    public function setId(string $id): void
    {
        session_id($id);
    }

    /**
     * Get the session name
     */
    public function getName(): string
    {
        return session_name();
    }

    /**
     * Set the session name
     */
    public function setName(string $name): void
    {
        session_name($name);
    }
}
