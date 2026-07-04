<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequireTwoFactor
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && $user->hasTwoFactorEnabled() && !$request->session()->get('2fa_verified')) {
            // Allow the challenge and verify routes through to avoid a redirect loop
            if (!$request->routeIs('2fa.challenge', '2fa.verify')) {
                return redirect()->route('2fa.challenge');
            }
        }

        return $next($request);
    }
}
