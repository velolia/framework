<?php

declare(strict_types=1);

namespace Velolia\Foundation;

use Velolia\Config\Config;
use Velolia\Console\Console;
use Velolia\Database\DatabaseManager;
use Velolia\Database\Schema\SchemaBuilder;
use Velolia\DI\Container;
use Velolia\Http\Kernel;
use Velolia\Http\Request;
use Velolia\Http\Response;
use Velolia\Routing\Router;
use Velolia\Routing\Url;
use Velolia\View\BladeEngine;

class Application extends Container
{
    protected string $basePath;
    protected array $loadedRoutes = [];

    public function __construct()
    {
        $this->registerBaseBindings();
        $this->registerSingleletons();
    }

    protected function registerBaseBindings(): void
    {
        static::setInstance($this);
        $this->instance(Application::class, $this);
        $this->instance(Container::class, $this);
    }

    protected function registerSingleletons(): void
    {
        $this->singleton(Kernel::class, Kernel::class);
        $this->singleton('config', function () {
            return (new Config())->load(base_path('config'));
        });
        $this->singleton('router', Router::class);
        $this->singleton('request', Request::class);
        $this->singleton('response', Response::class);
        $this->singleton('view', BladeEngine::class);
        $this->singleton('url', Url::class);
        $this->singleton('db', DatabaseManager::class);
        $this->singleton('db.schema', SchemaBuilder::class);
    }

    public function handleRequest(Request $request)
    {
        $kernel = $this->make(Kernel::class);
        $kernel->handle($request)->send();
    }

    public function withRouting(): void
    {
        $router = base_path('routes');

        $files = [
            $router . '/web.php',
            $router . '/api.php',
        ];

        foreach ($files as $file) {
            if (is_file($file) && !isset($this->loadedRoutes[$file])) {
                $this->loadedRoutes[$file] = true;
                require $file;
            }
        }
    }

    public function handleCommand(array $argv): int
    {
        $handleCommand = $this->make(Console::class);
        return $handleCommand->handle($argv);
    }

    public function basePath(string $path = ''): string
    {
        return $this->basePath ?? base_path($path);
    }
}