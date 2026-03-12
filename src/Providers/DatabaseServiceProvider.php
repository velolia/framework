<?php

declare(strict_types=1);

namespace Velolia\Providers;

use Velolia\Database\Manager;
use Velolia\Support\ServiceProvider;

class DatabaseServiceProvider extends ServiceProvider
{
    protected bool $defer = true;

    public function register(): void
    {
        $this->app->singleton('db', function ($app) {
            return new Manager($app);
        });
    }

    public function provides(): array
    {
        return ['db'];
    }
}
