<?php

namespace Velolia\Middleware;

use Closure;
use Velolia\Http\Request;
use Velolia\Http\Response;

interface MiddlewareInterface
{
    public function __invoke(Request $request, Closure $next): Response;
}