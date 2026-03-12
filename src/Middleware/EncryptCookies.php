<?php

declare(strict_types=1);

namespace Velolia\Middleware;

use Closure;
use Exception;
use Velolia\Http\Request;
use Velolia\Http\Response;
use Velolia\Cookie\CookieJar;
use Velolia\Encryption\Encrypter;

class EncryptCookies implements MiddlewareInterface
{
    protected array $except = [
        'velolia-session',
    ];

    public function __construct(
        protected Encrypter $encrypter,
        protected CookieJar $cookieJar
    ) {}

    public function __invoke(Request $request, Closure $next): Response
    {
        return $this->encrypt($this->cookieJar, $next($this->decrypt($request)));
    }

    protected function decrypt(Request $request): Request
    {
        foreach ($_COOKIE as $key => $cookie) {
            if ($this->isDisabled($key)) {
                continue;
            }

            try {
                $_COOKIE[$key] = $this->encrypter->decrypt($cookie);
            } catch (Exception $e) {
                $_COOKIE[$key] = null;
            }
        }

        return $request;
    }

    protected function encrypt(CookieJar $jar, Response $response): Response
    {
        foreach ($jar->getQueuedCookies() as $cookie) {
            $value = $cookie['value'];

            if (!$this->isDisabled($cookie['name'])) {
                $value = $this->encrypter->encrypt($value);
            }

            setcookie(
                $cookie['name'],
                $value,
                [
                    'expires' => $cookie['expire'],
                    'path' => $cookie['path'],
                    'domain' => $cookie['domain'] ?? '',
                    'secure' => $cookie['secure'],
                    'httponly' => $cookie['httpOnly'],
                    'samesite' => $cookie['sameSite'],
                ]
            );
        }

        $jar->flushQueuedCookies();

        return $response;
    }

    protected function isDisabled(string $name): bool
    {
        return in_array($name, $this->except, true);
    }
}
