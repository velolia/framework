<?php

declare(strict_types=1);

namespace Velolia\Providers;

use Velolia\Log\LogManager;
use Velolia\Support\ServiceProvider;

class LogServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton('log', function ($app) {
            return new LogManager($app);
        });
    }

    public function boot(): void
    {
        //
    }
}
