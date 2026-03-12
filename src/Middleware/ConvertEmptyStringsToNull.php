<?php

namespace Velolia\Middleware;

use Closure;
use Velolia\Http\Request;
use Velolia\Http\Response;

class ConvertEmptyStringsToNull implements MiddlewareInterface
{
    public function __invoke(Request $request, Closure $next): Response
    {
        $input = $request->all();

        array_walk_recursive($input, function (&$value) {
            if ($value === '') {
                $value = null;
            }
        });

        $request->merge($input);
        
        return $next($request);
    }
}