<?php

namespace App\Http\Middleware;

use App\Models\User;
use App\Services\Auth\StrictWebRbacPolicy;
use Closure;
use Illuminate\Http\Request;

/**
 * Adds the strict role-scoped module boundary to every browser route.
 *
 * The existing CheckModuleAccess middleware still performs granular route
 * authorization inside the main staff route group. This middleware closes
 * older/outlying route groups (for example Academic Repository) and keeps
 * role scope aligned with the native app.
 */
class EnforceStrictWebRbac
{
    public function __construct(private readonly StrictWebRbacPolicy $policy)
    {
    }

    public function handle(Request $request, Closure $next): mixed
    {
        /** @var User|null $user */
        $user = $request->user();

        if (! $user || $user->isSuperAdmin() || $user->isStudent() || $user->isParent()) {
            return $next($request);
        }

        $routeName = (string) ($request->route()?->getName() ?? '');
        $module = $this->policy->moduleForRoute($routeName);

        if ($module !== null && ! $this->policy->moduleAllowed($user, $module)) {
            if ($request->expectsJson()) {
                return response()->json([
                    'error' => 'Access denied.',
                    'role' => $user->roleLabel(),
                    'module' => $module,
                ], 403);
            }

            abort(403, "Your role ({$user->roleLabel()}) does not have access to this section.");
        }

        return $next($request);
    }
}
