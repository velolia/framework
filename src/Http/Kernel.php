<?php

declare(strict_types=1);

namespace Velolia\Http;

use Throwable;
use Velolia\Core\Application;
use Velolia\Exceptions\Handler;

class Kernel
{
    protected array $middleware = [
        \Velolia\Middleware\RequireAppKey::class,
    ];

    protected array $middlewareGroups = [
        'web' => [
            \Velolia\Middleware\EncryptCookies::class,
            \Velolia\Middleware\StartSession::class,
            \Velolia\Middleware\TrimStrings::class,
            \Velolia\Middleware\ConvertEmptyStringsToNull::class,
            \Velolia\Middleware\VerifyCsrfToken::class,
        ],
        'api' => [
            \Velolia\Middleware\TrimStrings::class,
            \Velolia\Middleware\ConvertEmptyStringsToNull::class,
        ],
    ];

    protected array $routeMiddleware = [
        'auth'      => \Velolia\Middleware\AuthMiddleware::class,
        'guest'     => \Velolia\Middleware\GuestMiddleware::class,
        'can'       => \Velolia\Middleware\AuthorizeMiddleware::class,
        'auth.aura' => \Velolia\Middleware\AuraMiddleware::class,
    ];

    protected array $resolvedMiddleware = [];

    public function __construct(protected Application $app) {}

    public function handle(Request $request)
    {
        try {
            return (new Pipeline($this->app))
                ->send($request)
                ->through($this->middleware)
                ->then(function ($request) {
                    $router = $this->app->make('router');
                    $router->setMiddlewareGroups($this->middlewareGroups);
                    $router->setRouteMiddleware($this->routeMiddleware);

                    return $router->dispatch($request);
                });
        } catch (Throwable $e) {
            return $this->handleException($request, $e);
        }
    }

    public function pushMiddleware(string $middleware): self
    {
        if (!in_array($middleware, $this->middleware, true)) {
            $this->middleware[] = $middleware;
        }

        return $this;
    }

    public function prependMiddleware(string $middleware): self
    {
        if (!in_array($middleware, $this->middleware, true)) {
            array_unshift($this->middleware, $middleware);
        }

        return $this;
    }

    protected function handleException(Request $request, Throwable $e)
    {
        $handler = $this->app->make(Handler::class);
        $handler->report($e);

        return $handler->render($request, $e);
    }
}
