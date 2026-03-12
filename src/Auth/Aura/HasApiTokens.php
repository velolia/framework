<?php

declare(strict_types=1);

namespace Velolia\Auth\Aura;

use Velolia\Support\Str;

/**
 * Velolia Aura - HasApiTokens Trait
 *
 * Add this trait to any authenticatable Model (e.g. User) to give it
 * the ability to issue and manage personal access tokens.
 */
trait HasApiTokens
{
    /**
     * The currently active token for this request (injected by AuraMiddleware).
     */
    protected ?PersonalAccessToken $currentAccessToken = null;

    /**
     * Create a new personal access token for this model.
     *
     * @param  string  $name       A human-readable label (e.g. "mobile-app", "postman")
     * @param  array   $abilities  Scopes for this token (default: all)
     * @param  \DateTimeInterface|null  $expiresAt  Optional expiry
     * @return NewAccessToken
     */
    public function createToken(
        string $name,
        array $abilities = ['*'],
        ?\DateTimeInterface $expiresAt = null
    ): NewAccessToken {
        $plainText = Str::random(40);

        $hash = hash('sha256', $plainText);

        $record = PersonalAccessToken::create([
            'tokenable_type' => static::class,
            'tokenable_id'   => $this->id,
            'name'           => $name,
            'token'          => $hash,
            'abilities'      => json_encode($abilities),
            'expires_at'     => $expiresAt?->format('Y-m-d H:i:s'),
        ]);

        $plainTextToken = $record->id . '|' . $plainText;

        return new NewAccessToken($record, $plainTextToken);
    }

    /**
     * Get all tokens belonging to this model.
     *
     * @return PersonalAccessToken[]
     */
    public function tokens(): array
    {
        return PersonalAccessToken::query()
            ->where('tokenable_type', static::class)
            ->where('tokenable_id', $this->id)
            ->get()
            ->all();
    }

    /**
     * Revoke (delete) a specific token by its DB id.
     */
    public function revokeToken(int $tokenId): bool
    {
        $token = PersonalAccessToken::query()
            ->where('id', $tokenId)
            ->where('tokenable_type', static::class)
            ->where('tokenable_id', $this->id)
            ->first();

        if ($token) {
            return $token->delete();
        }

        return false;
    }

    /**
     * Revoke all tokens belonging to this model.
     */
    public function revokeAllTokens(): void
    {
        $tokens = $this->tokens();
        foreach ($tokens as $token) {
            $token->delete();
        }
    }

    /**
     * Set the currently active token (called by AuraMiddleware).
     */
    public function withToken(PersonalAccessToken $token): static
    {
        $this->currentAccessToken = $token;
        return $this;
    }

    /**
     * Get the currently active token for this request.
     */
    public function currentToken(): ?PersonalAccessToken
    {
        return $this->currentAccessToken;
    }

    /**
     * Check if the current token has a given ability.
     */
    public function tokenCan(string $ability): bool
    {
        return $this->currentAccessToken?->can($ability) ?? false;
    }
}
