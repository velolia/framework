<?php

namespace Velolia\Middleware;

use Closure;
use Velolia\Http\Request;
use Velolia\Http\Response;

class TrimStrings implements MiddlewareInterface
{
    public function __invoke(Request $request, Closure $next): Response
    {
        if ($request->isMethod('GET')) {
            return $next($request);
        }

        $input = $request->all();

        foreach ($input as $key => $value) {
            if (is_string($value)) {
                $input[$key] = trim($value);
            }
        }

        $request->merge($input);

        return $next($request);
    }
}
