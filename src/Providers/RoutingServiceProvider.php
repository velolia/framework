<?php

declare(strict_types=1);

namespace Velolia\Providers;

use Velolia\Routing\Router;
use Velolia\Support\ServiceProvider;

class RoutingServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton('router', Router::class);
    }

    public function boot(): void
    {
        $cacheFile = $this->app->basePath('storage/framework/routes.php');

        if (file_exists($cacheFile)) {
            $router = $this->app->make('router');
            $router->importCache(require $cacheFile);
        } else {
            $this->loadRoutes();
        }
    }

    protected function loadRoutes(): void
    {
        $router = $this->app->make('router');

        $router->group([
            'middleware' => ['web']
        ], function ($router) {
            $this->loadWebRoutes($router);
        });

        $router->group([
            'prefix' => 'api',
            'middleware' => ['api']
        ], function ($router) {
            $this->loadApiRoutes($router);
        });
    }

    protected function loadWebRoutes(): void
    {
        $webRoutes = $this->app->basePath('routes/web.php');

        if (file_exists($webRoutes)) {
            require $webRoutes;
        }
    }

    protected function loadApiRoutes(): void
    {
        $apiRoutes = $this->app->basePath('routes/api.php');

        if (file_exists($apiRoutes)) {
            require $apiRoutes;
        }
    }
}
