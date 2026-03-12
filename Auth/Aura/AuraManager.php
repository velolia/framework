<?php

declare(strict_types=1);

namespace Velolia\Auth\Aura;

use Velolia\Http\Request;
use Velolia\Database\Model;

/**
 * Velolia Aura - Manager
 *
 * Responsible for resolving Bearer tokens from HTTP requests
 * and returning the authenticated user model.
 */
class AuraManager
{
    protected ?Model $user = null;
    protected ?PersonalAccessToken $token = null;
    protected string $modelClass;

    public function __construct(string $modelClass)
    {
        $this->modelClass = $modelClass;
    }

    /**
     * Attempt to authenticate the given request via its Bearer token.
     * Returns the user model on success, or null on failure.
     */
    public function authenticateRequest(Request $request): ?Model
    {
        $bearerToken = $this->parseBearerToken($request);

        if ($bearerToken === null) {
            return null;
        }

        $token = $this->resolveToken($bearerToken);

        if ($token === null || $token->isExpired()) {
            return null;
        }

        $user = $this->modelClass::find($token->tokenable_id);

        if ($user === null) {
            return null;
        }

        try {
            $token->touchLastUsed();
        } catch (\Throwable) {
            // non-critical
        }

        if (method_exists($user, 'withToken')) {
            $user->withToken($token);
        }

        $this->user  = $user;
        $this->token = $token;

        return $user;
    }

    /**
     * Resolve a plain-text token string to its PersonalAccessToken record.
     *
     * Format expected: "{id}|{plain_random}"
     */
    public function resolveToken(string $plainTextToken): ?PersonalAccessToken
    {
        $parts = explode('|', $plainTextToken, 2);

        if (count($parts) !== 2) {
            return null;
        }

        [$id, $plain] = $parts;

        if (!is_numeric($id) || empty(trim($plain))) {
            return null;
        }

        $hash = hash('sha256', $plain);

        return PersonalAccessToken::query()
            ->where('id', (int) $id)
            ->where('token', $hash)
            ->first();
    }

    /**
     * Return the currently authenticated user (if any).
     */
    public function user(): ?Model
    {
        return $this->user;
    }

    /**
     * Return the currently active PersonalAccessToken (if any).
     */
    public function token(): ?PersonalAccessToken
    {
        return $this->token;
    }

    /**
     * Check if the current request is authenticated.
     */
    public function check(): bool
    {
        return $this->user !== null;
    }

    /**
     * Check if the current request is not authenticated.
     */
    public function guest(): bool
    {
        return !$this->check();
    }

    /**
     * Return the authenticated user's primary key value.
     */
    public function id(): mixed
    {
        return $this->user?->id;
    }

    /**
     * Extract the Bearer token string from the Authorization header.
     * Supports both "Authorization: Bearer ..." and "X-Aura-Token: ..." headers.
     */
    protected function parseBearerToken(Request $request): ?string
    {
        $header = $request->header('authorization', '');

        if (str_starts_with($header, 'Bearer ')) {
            return trim(substr($header, 7));
        }

        $custom = $request->header('x-aura-token', '');
        if (!empty($custom)) {
            return trim($custom);
        }

        return null;
    }
}
