<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Ensures an authenticated session is committed before the login response
 * reaches the browser. This covers both the unified login and tenant-host
 * login controllers and prevents the subsequent dashboard request from
 * reading a stale or missing database-backed session.
 */
class PersistAuthenticatedSession
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if ($request->isMethod('post')
            && $request->routeIs('login', 'tenant.host.login.submit', 'tenant.host.custom.login.submit')
            && auth()->check()) {
            $request->session()->save();

            $response->headers->set('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0, private');
            $response->headers->set('Pragma', 'no-cache');
            $response->headers->set('Expires', 'Thu, 01 Jan 1970 00:00:00 GMT');
            $response->headers->set('Vary', 'Cookie');
        }

        return $response;
    }
}
