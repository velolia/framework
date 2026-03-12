<?php

declare(strict_types=1);

namespace Velolia\Auth;

use Velolia\Session\Session;
use Velolia\Database\Model;
use Velolia\Cookie\CookieJar;
use Velolia\Support\Str;

class AuthManager
{
    protected Session $session;
    protected CookieJar $cookieJar;
    protected ?Model $user = null;
    protected string $modelClass;

    public function __construct(Session $session, CookieJar $cookieJar, string $modelClass)
    {
        $this->session = $session;
        $this->cookieJar = $cookieJar;
        $this->modelClass = $modelClass;
    }

    public function attempt(array $credentials, bool $remember = false): bool
    {
        $password = $credentials['password'] ?? null;
        unset($credentials['password']);

        $query = $this->modelClass::query();
        foreach ($credentials as $key => $value) {
            $query->where($key, $value);
        }

        $user = $query->first();

        if ($user && $password) {
            if (method_exists($user, 'verifyPassword')) {
                $check = $user->verifyPassword($password);
                
                if ($check) {
                    $this->login($user, $remember);
                    return true;
                }
            } elseif (password_verify($password, $user->password)) {
                $this->login($user, $remember);
                return true;
            }
        }

        return false;
    }

    public function login(Model $user, bool $remember = false): void
    {
        $this->user = $user;
        
        $this->session->put('auth_user_id', $user->id);

        if ($remember) {
            $this->ensureRememberTokenIsSet($user);
            $this->queueRememberCookie($user);
        }
    }

    protected function ensureRememberTokenIsSet(Model $user): void
    {
        if (empty($user->remember_token)) {
            $user->remember_token = Str::random(60);
            $user->save();
        }
    }

    protected function queueRememberCookie(Model $user): void
    {
        $value = $user->id . '|' . $user->remember_token;
        $this->cookieJar->queue($this->getRememberCookieName(), $value, 2628000);
    }

    protected function getRememberCookieName(): string
    {
        return 'remember_web_' . sha1(static::class);
    }

    public function logout(): void
    {
        if ($user = $this->user()) {
            $user->remember_token = null;
            $user->save();
        }

        $this->user = null;
        $this->session->delete('auth_user_id');
        $this->session->regenerate();

        $this->cookieJar->queue($this->getRememberCookieName(), '', -1);
    }

    public function user(): ?Model
    {
        if ($this->user !== null) {
            return $this->user;
        }

        $id = $this->session->get('auth_user_id');

        if ($id) {
            $this->user = $this->modelClass::find($id);
            return $this->user;
        }

        return $this->user = $this->recallUserFromCookie();
    }

    protected function recallUserFromCookie(): ?Model
    {
        $cookie = $_COOKIE[$this->getRememberCookieName()] ?? null;

        if ($cookie) {
            $segments = explode('|', $cookie);

            if (count($segments) === 2) {
                [$id, $token] = $segments;
                $user = $this->modelClass::query()
                    ->where('id', $id)
                    ->where('remember_token', $token)
                    ->first();

                if ($user) {
                    $this->login($user);
                    return $user;
                }
            }
        }

        return null;
    }

    public function check(): bool
    {
        return $this->user() !== null;
    }

    public function guest(): bool
    {
        return !$this->check();
    }

    public function id()
    {
        return $this->user() ? $this->user()->id : null;
    }
}
