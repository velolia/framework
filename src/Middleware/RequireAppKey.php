<?php

declare(strict_types=1);

namespace Velolia\Middleware;

use Closure;
use RuntimeException;
use Velolia\Http\Request;
use Velolia\Http\Response;

class RequireAppKey implements MiddlewareInterface
{
    public function __invoke(Request $request, Closure $next): Response
    {
        $key = app()->getAppKey();

        if (empty($key)) {
            if (function_exists('abort')) {
                abort(500, 'Application Key is missing. Please generate an app key.');
            }
            throw new RuntimeException('Application Key is missing. Please generate an app key.');
        }

        if (str_starts_with((string)$key, 'base64:')) {
            $key = base64_decode(substr((string)$key, 7));
        }

        if (strlen((string)$key) !== 32) {
            if (function_exists('abort')) {
                abort(500, 'App key must be 256 bits (32 characters).');
            }
            throw new RuntimeException('App key must be 256 bits (32 characters).');
        }

        return $next($request);
    }
}
