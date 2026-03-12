<?php

declare(strict_types=1);

namespace Velolia\Middleware;

use Closure;
use Velolia\Http\Request;
use Velolia\Http\Response;
use Velolia\Debug\Toolbar;

class InjectDebugToolbar implements MiddlewareInterface
{
    public function __invoke(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $isDebug = app('config')->get('app.debug') ?? false;

        if ($isDebug && class_exists(Toolbar::class)) {
            Toolbar::inject($response);
        }

        return $response;
    }
}