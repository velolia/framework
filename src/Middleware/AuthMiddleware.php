<?php

namespace Velolia\Middleware;

use Closure;
use Velolia\Http\Request;
use Velolia\Http\Response;

class AuthMiddleware implements MiddlewareInterface
{
    public function __invoke(Request $request, Closure $next): Response
    {   
        if (auth()->guest()) {
            if (!$request->expectsJson()) {
                session()->put('url.intended', $request->getPathInfo());
            }
            return redirect(route('login'));
        }

        return $next($request);
    }
}