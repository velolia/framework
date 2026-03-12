<?php

declare(strict_types=1);

namespace Velolia\Middleware;

use Closure;
use Velolia\Http\Request;
use Velolia\Http\Response;
use Velolia\Auth\Aura\AuraManager;

/**
 * Velolia Aura - API Authentication Middleware
 *
 * Usage in routes: Route::middleware('auth.aura')->group(...)
 *
 * Reads the Authorization: Bearer <token> header, resolves the token
 * via AuraManager, and injects the authenticated user into request
 * attributes. Returns 401 JSON if no valid token is provided.
 */
class AuraMiddleware implements MiddlewareInterface
{
    public function __invoke(Request $request, Closure $next): Response
    {
        /** @var AuraManager $aura */
        $aura = app('aura');

        $user = $aura->authenticateRequest($request);

        if ($user === null) {
            /** @var Response $response */
            $response = app(Response::class);
            return $response->json([
                'message' => 'Unauthenticated.',
                'error'   => 'No valid API token was provided.',
            ], 401);
        }

        $request->attribute('aura_user', $user);
        $request->attribute('aura_token', $aura->token());

        return $next($request);
    }
}
