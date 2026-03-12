<?php

declare(strict_types=1);

namespace Velolia\Security;

use Velolia\Session\Session;

class CsrfManager
{
    /**
     * Session instance
     */
    protected Session $session;

    /**
     * Application key for token signing
     */
    protected ?string $appKey = null;

    /**
     * Token lifetime in seconds (default: 2 hours)
     */
    protected int $lifetime = 7200;

    /**
     * Constructor
     */
    public function __construct(Session $session, ?string $appKey = null)
    {
        $this->session = $session;
        $this->appKey = $appKey;
    }

    /**
     * Generate a new CSRF token
     * 
     * @return string
     */
    public function generate(): string
    {
        $token = bin2hex(random_bytes(32));

        if ($this->appKey) {
            $payload = [
                'token' => $token,
                'session_id' => $this->session->getId(),
                'timestamp' => time(),
            ];

            $token = $this->signToken($payload);
        }

        $this->session->set('_token', $token);

        return $token;
    }

    /**
     * Validate a CSRF token
     * 
     * @param string $token
     * @return bool
     */
    public function validate(string $token): bool
    {
        $storedToken = $this->session->get('_token');

        if (!$storedToken) {
            return false;
        }

        if ($this->appKey && $this->isSigned($token)) {
            return $this->validateSignedToken($token, $storedToken);
        }

        return hash_equals($storedToken, $token);
    }

    /**
     * Get the current CSRF token (generate if not exists)
     * 
     * @return string
     */
    public function token(): string
    {
        if (!$this->session->has('_token')) {
            return $this->generate();
        }

        $token = $this->session->get('_token');

        if ($this->appKey && $this->isSigned($token) && $this->isExpired($token)) {
            return $this->generate();
        }

        return $token;
    }

    /**
     * Regenerate the CSRF token
     * 
     * @return string
     */
    public function regenerate(): string
    {
        $this->session->delete('_token');
        return $this->generate();
    }

    /**
     * Sign a token with APP_KEY
     * 
     * @param array $payload
     * @return string
     */
    protected function signToken(array $payload): string
    {
        $json = json_encode($payload);
        $encoded = base64_encode($json);
        $signature = hash_hmac('sha256', $encoded, $this->appKey);

        return $encoded . '.' . $signature;
    }

    /**
     * Validate a signed token
     * 
     * @param string $token
     * @param string $storedToken
     * @return bool
     */
    protected function validateSignedToken(string $token, string $storedToken): bool
    {
        if (!hash_equals($storedToken, $token)) {
            return false;
        }

        if (!$this->verifySignature($token)) {
            return false;
        }

        if ($this->isExpired($token)) {
            return false;
        }

        $payload = $this->extractPayload($token);

        if (!$payload) {
            return false;
        }

        return hash_equals($payload['session_id'], $this->session->getId());
    }

    /**
     * Verify token signature
     * 
     * @param string $token
     * @return bool
     */
    protected function verifySignature(string $token): bool
    {
        $parts = explode('.', $token, 2);

        if (count($parts) !== 2) {
            return false;
        }

        [$encoded, $signature] = $parts;
        $calculated = hash_hmac('sha256', $encoded, $this->appKey);

        return hash_equals($calculated, $signature);
    }

    /**
     * Extract payload from signed token
     * 
     * @param string $token
     * @return array|null
     */
    protected function extractPayload(string $token): ?array
    {
        $parts = explode('.', $token, 2);

        if (count($parts) !== 2) {
            return null;
        }

        $decoded = base64_decode($parts[0], true);

        if ($decoded === false) {
            return null;
        }

        $payload = json_decode($decoded, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return null;
        }

        return $payload;
    }

    /**
     * Check if token is signed
     * 
     * @param string $token
     * @return bool
     */
    protected function isSigned(string $token): bool
    {
        return str_contains($token, '.');
    }

    /**
     * Check if token is expired
     * 
     * @param string $token
     * @return bool
     */
    protected function isExpired(string $token): bool
    {
        $payload = $this->extractPayload($token);

        if (!$payload || !isset($payload['timestamp'])) {
            return true;
        }

        return (time() - $payload['timestamp']) > $this->lifetime;
    }

    /**
     * Set token lifetime
     * 
     * @param int $seconds
     * @return void
     */
    public function setLifetime(int $seconds): void
    {
        $this->lifetime = $seconds;
    }

    /**
     * Get token lifetime
     * 
     * @return int
     */
    public function getLifetime(): int
    {
        return $this->lifetime;
    }

    /**
     * Set application key
     * 
     * @param string $appKey
     * @return void
     */
    public function setAppKey(string $appKey): void
    {
        $this->appKey = $appKey;
    }
}