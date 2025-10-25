<?php

declare(strict_types=1);

namespace Velolia\Middleware;

use Closure;
use Velolia\Http\Request;
use Velolia\Security\CsrfManager;
use Velolia\Security\Exception\CsrfTokenMismatchException;

class CsrfMiddleware
{
    public function __construct(protected CsrfManager $csrf) {}

    public function __invoke(Request $request, Closure $next)
    {
        if (in_array($request->getMethod(), ['GET', 'HEAD', 'OPTIONS'])) {
            return $next($request);
        }

        $token = $request->input('_token')
            ?? $request->header('X-CSRF-TOKEN')
            ?? $request->header('X-XSRF-TOKEN');

        if (!$token || !$this->csrf->validate($token)) {
            throw new CsrfTokenMismatchException();
        }

        return $next($request);
    }
}