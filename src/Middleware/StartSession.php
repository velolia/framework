<?php

declare(strict_types=1);

namespace Velolia\Middleware;

use Closure;
use Velolia\Http\Request;
use Velolia\Http\Response;

class StartSession implements MiddlewareInterface
{
    public function __invoke(Request $request, Closure $next): Response
    {
        app('session')->start();
        return $next($request);
    }
}