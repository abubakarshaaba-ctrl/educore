<?php

namespace App\Http\Middleware;

use App\Models\ApiToken;
use App\Services\Auth\ApiRoleAccessPolicy;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Bearer-token authentication for the mobile API.
 *
 * Sets the token's user on the default guard so auth()->user() and the
 * tenant global scope (BaseTenantModel) work exactly as on the web side.
 * Every authenticated tenant-staff request is then authorized by the same
 * explicit module contract used by the web application.
 */
class AuthenticateApiToken
{
    public function handle(Request $request, Closure $next, ApiRoleAccessPolicy $accessPolicy): Response
    {
        $plain = $request->bearerToken();

        if (! $plain) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $token = ApiToken::findValid($plain);

        if (! $token || ! $token->user) {
            return response()->json(['message' => 'Invalid or expired token.'], 401);
        }

        $user = $token->user;

        if (! $user->is_active) {
            return response()->json(['message' => 'Account deactivated.'], 403);
        }

        Auth::setUser($user);
        $request->setUserResolver(fn () => $user);
        $request->attributes->set('api_token', $token);

        if (! $accessPolicy->allows($user, $request)) {
            return response()->json([
                'message' => 'Access denied for your assigned role.',
                'role' => $user->roleLabel(),
            ], 403);
        }

        // Throttled last-used bookkeeping (at most once a minute)
        if (! $token->last_used_at || $token->last_used_at->lt(now()->subMinute())) {
            $token->forceFill(['last_used_at' => now()])->saveQuietly();
        }

        return $next($request);
    }
}
