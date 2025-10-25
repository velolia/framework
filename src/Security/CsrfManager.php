<?php

declare(strict_types=1);

namespace Velolia\Security;

use Velolia\Session\Session;

class CsrfManager
{
    public function __construct(protected Session $session) {}

    public function generate(): string
    {
        if (null !== $this->session->has('_token')) {
            $this->session->set('_token', bin2hex(random_bytes(32)));
        }

        return $this->session->get('_token');
    }

    public function validate(string $token): bool
    {
        return hash_equals($this->session->get('_token') ?? '', $token);
    }

    public function token(): string
    {
        return $this->session->get('_token') ?? $this->generate();
    }
}