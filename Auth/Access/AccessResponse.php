<?php

declare(strict_types=1);

namespace Velolia\Auth\Access;

class AccessResponse
{
    /**
     * Create a new response instance.
     *
     * @param  bool  $allowed
     * @param  string|null  $message
     */
    public function __construct(protected bool $allowed, protected ?string $message = null) {}

    /**
     * Create a successful response.
     *
     * @return static
     */
    public static function allow(): static
    {
        return new static(true);
    }

    /**
     * Create a failure response.
     *
     * @param  string|null  $message
     * @return static
     */
    public static function deny(?string $message = null): static
    {
        return new static(false, $message);
    }

    /**
     * Determine if the response was allowed.
     *
     * @return bool
     */
    public function allowed(): bool
    {
        return $this->allowed;
    }

    /**
     * Determine if the response was denied.
     *
     * @return bool
     */
    public function denied(): bool
    {
        return ! $this->allowed;
    }

    /**
     * Get the response message.
     *
     * @return string|null
     */
    public function message(): ?string
    {
        return $this->message;
    }

    /**
     * Convert the response to a boolean.
     *
     * @return bool
     */
    public function __toString(): string
    {
        return $this->allowed ? '1' : '0';
    }
}
