<?php

declare(strict_types=1);

namespace Velolia\Cookie;

class CookieJar
{
    protected array $queued = [];

    public function queue(
        string $name,
        string $value,
        int $minutes = 0,
        ?string $path = '/',
        ?string $domain = null,
        ?bool $secure = null,
        bool $httpOnly = true,
        string $sameSite = 'Lax'
    ): void {
        $expire = ($minutes === 0) ? 0 : time() + ($minutes * 60);

        $this->queued[$name] = [
            'name' => $name,
            'value' => $value,
            'expire' => $expire,
            'path' => $path,
            'domain' => $domain,
            'secure' => $secure ?? config('session.secure', false),
            'httpOnly' => $httpOnly,
            'sameSite' => $sameSite,
        ];
    }

    public function getQueuedCookies(): array
    {
        return $this->queued;
    }

    public function flushQueuedCookies(): void
    {
        $this->queued = [];
    }
}
