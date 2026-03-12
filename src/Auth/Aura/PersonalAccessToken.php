<?php

declare(strict_types=1);

namespace Velolia\Auth\Aura;

use Velolia\Database\Model;

/**
 * Velolia Aura - Personal Access Token Model
 *
 * @property int         $id
 * @property string      $tokenable_type
 * @property int         $tokenable_id
 * @property string      $name
 * @property string      $token
 * @property array|null  $abilities
 * @property string|null $last_used_at
 * @property string|null $expires_at
 * @property string|null $created_at
 * @property string|null $updated_at
 */
class PersonalAccessToken extends Model
{
    protected string $table = 'personal_access_tokens';

    protected array $fillable = [
        'tokenable_type',
        'tokenable_id',
        'name',
        'token',
        'abilities',
        'last_used_at',
        'expires_at',
    ];

    protected array $casts = [
        'abilities' => 'array',
    ];

    /**
     * Check whether this token has the given ability.
     * A wildcard ability '*' grants all permissions.
     */
    public function can(string $ability): bool
    {
        $abilities = $this->getAbilities();

        return in_array('*', $abilities, true) || in_array($ability, $abilities, true);
    }

    /**
     * Check whether this token is missing the given ability.
     */
    public function cant(string $ability): bool
    {
        return !$this->can($ability);
    }

    /**
     * Resolve the model that owns this token (polymorphic).
     */
    public function tokenable(): ?Model
    {
        $class = $this->tokenable_type;

        if (!class_exists($class)) {
            return null;
        }

        return $class::find($this->tokenable_id);
    }

    /**
     * Check if the token has expired.
     */
    public function isExpired(): bool
    {
        if ($this->expires_at === null) {
            return false;
        }

        return strtotime($this->expires_at) < time();
    }

    /**
     * Mark the token as last used right now.
     */
    public function touchLastUsed(): void
    {
        $this->last_used_at = date('Y-m-d H:i:s');
        $this->save();
    }

    /**
     * Get abilities as a guaranteed array.
     */
    public function getAbilities(): array
    {
        $raw = $this->abilities;

        if (is_array($raw)) {
            return $raw;
        }

        if (is_string($raw)) {
            $decoded = json_decode($raw, true);
            return is_array($decoded) ? $decoded : ['*'];
        }

        return ['*'];
    }
}
