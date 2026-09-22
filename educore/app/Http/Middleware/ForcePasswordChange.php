<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ForcePasswordChange
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (
            ! $user
            || ! $user->must_change_password
            || $user->isSuperAdmin()
            || $request->session()->has('super_admin_id')
        ) {
            return $next($request);
        }

        if ($request->routeIs('account.password-required.*') || $request->routeIs('logout')) {
            return $next($request);
        }

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'You must replace the temporary password before continuing.',
                'code' => 'password_change_required',
            ], 428);
        }

        // Keep the mandatory-recovery flow on the exact host that owns the
        // authenticated session cookie. This is essential for tenant
        // subdomains and verified custom domains; an absolute named-route
        // redirect can resolve against APP_URL and silently cross hosts.
        $recoveryUrl = rtrim($request->getSchemeAndHttpHost(), '/')
            . '/account/password-required';

        return redirect()->to($recoveryUrl);
    }
}
