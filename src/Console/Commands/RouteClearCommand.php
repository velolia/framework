<?php

declare(strict_types=1);

namespace Velolia\Console\Commands;

use Velolia\Console\Command;

class RouteClearCommand extends Command
{
    protected string $signature = 'route:clear';
    protected string $description = 'Remove the route cache file';

    public function handle(array $args): int
    {
        $cacheFile = $this->app->basePath('storage/framework/routes.php');

        if (file_exists($cacheFile)) {
            unlink($cacheFile);
            $this->info('Route cache cleared successfully!');
        } else {
            $this->info('No route cache found.');
        }

        return 0;
    }
}
