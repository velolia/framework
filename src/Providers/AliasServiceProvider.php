<?php

declare(strict_types=1);

namespace Velolia\Providers;

use Velolia\Database\Manager;
use Velolia\Http\Response;
use Velolia\Support\ServiceProvider;

class AliasServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton('session', function ($app) {
            return new \Velolia\Session\Session($app->make('config')->get('session'));
        });

        $this->app->singleton(\Velolia\Cookie\CookieJar::class, \Velolia\Cookie\CookieJar::class);

        $this->app->singleton(\Velolia\Encryption\Encrypter::class, function () {
            return new \Velolia\Encryption\Encrypter($this->app->getAppKey());
        });

        $this->app->alias(Manager::class, 'db');

        $this->app->alias(Response::class, 'response');
    }
}
