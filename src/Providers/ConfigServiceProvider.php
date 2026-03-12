<?php

declare(strict_types=1);

namespace Velolia\Providers;

use Velolia\Support\ServiceProvider;
use Velolia\Config\Config;

class ConfigServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton('config', function ($app) {
            $cacheFile = $app->bootstrapPath('cache/config.php');

            if (file_exists($cacheFile)) {
                return new Config(require $cacheFile);
            }

            $configPath = $app->configPath();
            $config = new Config();
            $config->load($configPath);
            return $config;
        });
    }
}