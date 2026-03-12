<?php

declare(strict_types=1);

namespace Velolia\Console\Commands;

use Velolia\Console\Command;

class ConfigClearCommand extends Command
{
    protected string $signature = 'config:clear';
    protected string $description = 'Remove the config cache file';

    public function handle(array $args): int
    {
        $cacheFile = $this->app->bootstrapPath('cache/config.php');

        if (file_exists($cacheFile)) {
            unlink($cacheFile);
            $this->info('Configuration cache cleared!');
        } else {
            $this->info('No configuration cache found.');
        }

        return 0;
    }
}
