<?php

declare(strict_types=1);

namespace Velolia\Middleware;

use Closure;
use Velolia\Auth\Access\Gate;
use Velolia\Auth\Access\AuthorizationException;
use Velolia\Http\Request;
use Velolia\Http\Response;

class AuthorizeMiddleware
{
    public function __construct(protected Gate $gate) {}

    public function __invoke(Request $request, Closure $next, string $ability, ...$models): Response
    {
        $arguments = [];

        if (!empty($models)) {
            $model = $models[0];
            $fallback = $models[1] ?? $model;

            if ($request->attribute($model)) {
                $arguments = [$request->attribute($model)];
            } else {
                $arguments = [$fallback];
            }
        }

        $response = $this->gate->inspect($ability, $arguments);

        if ($response->denied()) {
            throw new AuthorizationException($response->message() ?? "This action is unauthorized.");
        }

        return $next($request);
    }
}
