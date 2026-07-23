<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * Demonstrates how route middleware can guard a feature boundary.
 *
 * Laravel's built-in auth / auth:sanctum middleware performs the main
 * authentication. This additional project-specific boundary is a convenient
 * place for future role or permission checks.
 */
class EnsureAuthenticatedUser
{
    /**
     * Pass authenticated requests to the next middleware in the pipeline.
     *
     * @param  Closure(Request): Response  $next
     *
     * @throws AuthenticationException
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user() === null) {
            Log::warning('Unauthenticated user-management access attempt.', [
                'path' => $request->path(),
                'ip' => $request->ip(),
            ]);

            throw new AuthenticationException('Unauthenticated.');
        }

        return $next($request);
    }
}
