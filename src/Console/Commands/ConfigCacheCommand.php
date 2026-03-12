<?php

declare(strict_types=1);

namespace Velolia\Console\Commands;

use Velolia\Console\Command;
use Exception;

class ConfigCacheCommand extends Command
{
    protected string $signature = 'config:cache';
    protected string $description = 'Create a config cache file for faster application booting';

    public function handle(array $args): int
    {
        $configPath = $this->app->configPath();
        $cacheFile = $this->app->bootstrapPath('cache/config.php');

        if (!is_dir($this->app->bootstrapPath('cache'))) {
            mkdir($this->app->bootstrapPath('cache'), 0755, true);
        }

        try {
            $files = glob($configPath . '/*.php');
            $items = [];

            foreach ($files as $file) {
                $key = basename($file, '.php');
                $config = include $file;

                if (is_array($config)) {
                    $items[$key] = $config;
                }
            }

            $content = "<?php\n\nreturn " . var_export($items, true) . ";\n";

            if (file_put_contents($cacheFile, $content) === false) {
                throw new Exception("Failed to write cache file to {$cacheFile}");
            }

            $this->info('Configuration cached successfully!');
            return 0;
        } catch (Exception $e) {
            $this->error('Failed to cache configuration: ' . $e->getMessage());
            return 1;
        }
    }
}
