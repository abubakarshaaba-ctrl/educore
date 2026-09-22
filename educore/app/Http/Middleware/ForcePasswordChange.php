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

        return redirect()->route('account.password-required.edit');
    }
}
