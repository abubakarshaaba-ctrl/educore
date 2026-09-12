<?php

namespace App\Http\Middleware;

use App\Models\ApiToken;
use App\Services\Auth\ApiRoleAccessPolicy;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Throwable;
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
    public function __construct(private readonly ApiRoleAccessPolicy $accessPolicy)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        $plain = $request->bearerToken();

        if (! $plain) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        try {
            $token = ApiToken::findValid($plain);
        } catch (Throwable $exception) {
            report($exception);
            return response()->json(['message' => 'The secure mobile session could not be verified. Please sign in again.'], 401);
        }

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

        try {
            $allowed = $this->accessPolicy->allows($user, $request);
        } catch (Throwable $exception) {
            report($exception);
            $path = trim($request->path(), '/');
            $path = preg_replace('#^api/v1/#', '', $path) ?? $path;
            $allowed = in_array($path, ['bootstrap', 'auth/logout', 'me'], true);
        }

        if (! $allowed) {
            return response()->json([
                'message' => 'Access denied for your assigned role.',
                'role' => $user->roleLabel(),
            ], 403);
        }

        try {
            // api_tokens predates last_used_at on a small number of upgraded
            // installations. Do not issue an invalid UPDATE while the repair
            // migration is being rolled out.
            if (
                array_key_exists('last_used_at', $token->getAttributes())
                && (! $token->last_used_at || $token->last_used_at->lt(now()->subMinute()))
            ) {
                $token->forceFill(['last_used_at' => now()])->saveQuietly();
            }
        } catch (Throwable $exception) {
            report($exception);
        }

        try {
            return $next($request);
        } catch (Throwable $exception) {
            report($exception);

            $path = trim($request->path(), '/');
            $path = preg_replace('#^api/v1/#', '', $path) ?? $path;
            if ($path !== 'bootstrap') {
                throw $exception;
            }

            // Never fabricate an allowed workspace after an unexpected bootstrap
            // exception. Return a traceable service error so access and module
            // authority remain server-owned.
            $reference = 'BOOT-' . strtoupper(Str::random(8));
            return response()->json([
                'message' => 'EduCore could not load the school workspace. Please try again.',
                'request_id' => $reference,
            ], 503)->header('X-EduCore-Request-Id', $reference);
        }
    }
}
