<?php

declare(strict_types=1);

namespace Velolia\Providers;

use Velolia\Auth\AuthManager;
use Velolia\Auth\Aura\AuraManager;
use Velolia\Support\ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton('auth', function ($app) {
            $session = $app->make('session');
            $cookieJar = $app->make(\Velolia\Cookie\CookieJar::class);
            $modelClass = config('auth.providers.users.model', '\\App\\Models\\User');
            return new AuthManager($session, $cookieJar, $modelClass);
        });

        $this->app->alias('auth', AuthManager::class);

        $this->app->singleton('aura', function ($app) {
            $modelClass = config('auth.providers.users.model', '\\App\\Models\\User');
            return new AuraManager($modelClass);
        });

        $this->app->alias('aura', AuraManager::class);

        $this->app->singleton(\Velolia\Auth\Access\Gate::class, function ($app) {
            return new \Velolia\Auth\Access\Gate($app);
        });
    }

    public function boot(): void
    {
        $gate = $this->app->make(\Velolia\Auth\Access\Gate::class);
        // $gate->policy(\App\Models\Pengaturan::class, \App\Policies\SettingPolicy::class);
    }
}
