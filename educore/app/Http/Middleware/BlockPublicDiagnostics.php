<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Prevent internal filesystem/debug endpoints from being reachable through the
 * public web stack, even if a legacy route remains present during deployment.
 */
class BlockPublicDiagnostics
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->is('download/app/_debug')) {
            abort(404);
        }

        return $next($request);
    }
}
