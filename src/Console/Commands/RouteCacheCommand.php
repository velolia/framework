<?php

declare(strict_types=1);

namespace Velolia\Console\Commands;

use Velolia\Console\Command;
use Exception;

class RouteCacheCommand extends Command
{
    protected string $signature = 'route:cache';
    protected string $description = 'Create a route cache file for faster route registration';

    public function handle(array $args): int
    {
        $router = $this->app->make('router');
        $cacheFile = $this->app->basePath('storage/framework/routes.php');

        try {
            $this->loadRoutesForCaching($router);

            $routes = $router->exportRoutes();

            $content = "<?php\n\nreturn " . var_export($routes, true) . ";\n";

            file_put_contents($cacheFile, $content);

            $this->info('Routes cached successfully!');
            return 0;
        } catch (Exception $e) {
            $this->error('Failed to cache routes: ' . $e->getMessage());
            return 1;
        }
    }

    protected function loadRoutesForCaching($router): void
    {
        $router->group(['middleware' => ['web']], function ($router) {
            $webRoutes = $this->app->basePath('routes/web.php');
            if (file_exists($webRoutes)) require $webRoutes;
        });

        $router->group(['prefix' => 'api', 'middleware' => ['api']], function ($router) {
            $apiRoutes = $this->app->basePath('routes/api.php');
            if (file_exists($apiRoutes)) require $apiRoutes;
        });
    }
}
