<?php

declare(strict_types=1);

namespace Velolia\Auth\Access;

trait Authorizable
{
    public function can(string $ability, $arguments = []): bool
    {
        return app(Gate::class)->check($ability, $arguments);
    }

    public function cannot(string $ability, $arguments = []): bool
    {
        return app(Gate::class)->denies($ability, $arguments);
    }

    public function cant(string $ability, $arguments = []): bool
    {
        return $this->cannot($ability, $arguments);
    }
}
