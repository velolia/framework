<?php

declare(strict_types=1);

namespace Velolia\Providers;

use Velolia\Support\DotEnv;
use Velolia\Support\ServiceProvider;

class DotEnvServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton('env', function ($app) {
            DotEnv::load($app->basePath(), true);
            return new DotEnv($app);
        });
    }
}