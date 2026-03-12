<?php

declare(strict_types=1);

namespace Velolia\Auth\Aura;

use JsonSerializable;

/**
 * Velolia Aura - New Access Token Value Object
 *
 * Holds the freshly-created PersonalAccessToken model alongside
 * the plain-text token string that must be shown to the user exactly once.
 *
 * Format: "{id}|{random_string}"  (identical to Laravel Sanctum)
 */
class NewAccessToken implements JsonSerializable
{
    public function __construct(
        public readonly PersonalAccessToken $accessToken,
        public readonly string $plainTextToken
    ) {}

    /**
     * Serialize for JSON responses — exposes the plain-text token.
     */
    public function jsonSerialize(): array
    {
        return [
            'id'           => $this->accessToken->id,
            'name'         => $this->accessToken->name,
            'abilities'    => $this->accessToken->getAbilities(),
            'token'        => $this->plainTextToken,
            'expires_at'   => $this->accessToken->expires_at,
            'created_at'   => $this->accessToken->created_at,
        ];
    }
}
