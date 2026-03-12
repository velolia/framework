<?php

declare(strict_types=1);

namespace Velolia\Console\Commands;

use Velolia\Console\Command;

class OptimizeClearCommand extends Command
{
    protected string $signature = 'optimize:clear';
    protected string $description = 'Remove the route and view cache files (clears all caches)';

    public function handle(array $args): int
    {
        $this->info('Clearing cached bootstrap files...');

        $viewClear = new ViewClearCommand($this->app);
        $routeClear = new RouteClearCommand($this->app);

        $viewClear->handle([]);
        $routeClear->handle([]);

        $this->info('All caches cleared successfully!');

        return 0;
    }
}
