<?php

namespace Velolia\Middleware;

use Closure;
use Velolia\Http\Request;
use Velolia\Http\Response;

class GuestMiddleware implements MiddlewareInterface
{
    public function __invoke(Request $request, Closure $next): Response
    {
        if (auth()->check()) {
            return redirect('/');
        }

        return $next($request);
    }
}