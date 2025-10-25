<?php

declare(strict_types=1);

namespace Velolia\Http;

use Throwable;
use Velolia\ErrorHandler\ErrorHandler;
use Velolia\Foundation\Application;

class Kernel
{
    protected array $middleware = [
        \Velolia\Middleware\CsrfMiddleware::class,
    ];

    public function __construct(protected Application $app) {}

    public function handle($request)
    {
        try {
            $this->app->withRouting();
            $response = (new Pipeline($this->app))
                ->send($request)
                ->through($this->middleware ?? [])
                ->then($this->resolveRouter());
            return $response;
        } catch (Throwable $e) {
            return $this->handleException($e);
        }
    }

    /**
     * Resolve the router.
     * @return callable
    */
    protected function resolveRouter()
    {
        return function ($request) {
            return $this->app->make('router')->dispatch($request);
        };
    }

    /**
     * Handle the given exception.
     * @param Throwable $e
     * @throws Throwable
    */
    protected function handleException(Throwable $e)
    {
        $handler = app(ErrorHandler::class);
        return $handler->handleException($e);
    }
}