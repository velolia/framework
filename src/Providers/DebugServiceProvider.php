<?php

declare(strict_types=1);

namespace Velolia\Providers;

use Velolia\Http\Kernel;
use Velolia\Middleware\InjectDebugToolbar;
use Velolia\Support\ServiceProvider;

class DebugServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        $kernel = $this->app->make(Kernel::class);
        $kernel->pushMiddleware(InjectDebugToolbar::class);
    }
}