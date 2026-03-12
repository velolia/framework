<?php

declare(strict_types=1);

namespace Velolia\Core;

use Velolia\DI\Container;
use Velolia\Http\Kernel;
use Velolia\Http\Request;
use Velolia\Support\ServiceProvider;

class Application extends Container
{
    public const VERSION = '1.0.0';

    protected string $basePath;
    protected bool $booted = false;
    protected array $serviceProviders = [];
    protected array $loadedProviders = [];
    protected array $deferredServices = [];
    protected ?string $appKey = null;

    public function __construct(?string $basePath = '')
    {
        $this->basePath = rtrim($basePath, '\/');
        $this->registerBaseBindings();
        $this->registerCoreProviders();
    }

    protected function registerBaseBindings(): void
    {
        static::setInstance($this);
        $this->instance(Application::class, $this);
        $this->instance(Container::class, $this);
        $this->singleton(Kernel::class, Kernel::class);
    }

    protected function registerCoreProviders(): void
    {
        $this->register(\Velolia\Providers\DotEnvServiceProvider::class);
        $this->register(\Velolia\Providers\ConfigServiceProvider::class);
        $this->register(\Velolia\Providers\AliasServiceProvider::class);
        $this->register(\Velolia\Providers\RoutingServiceProvider::class);
        $this->register(\Velolia\Providers\DatabaseServiceProvider::class);
        $this->register(\Velolia\Providers\ViewServiceProvider::class);
        $this->register(\Velolia\Providers\DebugServiceProvider::class);
        $this->register(\Velolia\Providers\LogServiceProvider::class);
        $this->register(\Velolia\Providers\AuthServiceProvider::class);
        $this->register(\Velolia\UltraWire\UltraWireServiceProvider::class);
    }

    public function register($provider, bool $force = false): ?ServiceProvider
    {
        if (is_string($provider)) {
            $provider = new $provider($this);
        }

        if (array_key_exists(get_class($provider), $this->loadedProviders)) {
            return $provider;
        }

        if ($provider->isDeferred() && !$force) {
            foreach ($provider->provides() as $service) {
                $this->deferredServices[$service] = get_class($provider);
            }
            return $provider;
        }

        $provider->register();
        $this->serviceProviders[] = $provider;
        $this->loadedProviders[get_class($provider)] = true;

        if ($this->booted) {
            $provider->boot();
        }

        return $provider;
    }

    public function boot(): void
    {
        if ($this->booted) {
            return;
        }

        foreach ($this->serviceProviders as $provider) {
            $provider->boot();
        }

        $this->booted = true;
    }

    public function getLoadedProviders(): array
    {
        return array_keys($this->loadedProviders);
    }

    public function handleRequest(Request $request)
    {
        $this->boot();
        $this->make(Kernel::class)->handle($request)->send();
    }

    public function basePath(string $path = ''): string
    {
        return $this->basePath . ($path ? DIRECTORY_SEPARATOR . $path : '');
    }

    public function configPath(string $path = ''): string
    {
        return $this->basePath('config' . ($path ? DIRECTORY_SEPARATOR . $path : ''));
    }

    public function storagePath(string $path = ''): string
    {
        return $this->basePath('storage' . ($path ? DIRECTORY_SEPARATOR . $path : ''));
    }

    public function publicPath(string $path = ''): string
    {
        return $this->basePath('public' . ($path ? DIRECTORY_SEPARATOR . $path : ''));
    }

    public function bootstrapPath(string $path = ''): string
    {
        return $this->basePath('bootstrap' . ($path ? DIRECTORY_SEPARATOR . $path : ''));
    }

    public function getAppKey(): string
    {
        if ($this->appKey === null) {
            $this->appKey = (string) config('app.key', env('APP_KEY', ''));
        }

        return $this->appKey;
    }
}
