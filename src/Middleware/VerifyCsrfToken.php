<?php

namespace Velolia\Middleware;

use Closure;
use Velolia\Security\CsrfManager;
use Velolia\Http\Request;
use Velolia\Http\Response;

class VerifyCsrfToken implements MiddlewareInterface
{
    public function __construct(protected CsrfManager $csrf) {}

    public function __invoke(Request $request, Closure $next): Response
    {
        if (in_array($request->method(), ['GET', 'HEAD', 'OPTIONS'])) {
            return $next($request);
        }

        $token = $request->input('_token') ?? $request->header('X-CSRF-TOKEN') ?? $request->header('X-XSRF-TOKEN');

        if (!$token || !$this->csrf->validate($token)) {
            abort(419);
        }

        return $next($request);
    }
}