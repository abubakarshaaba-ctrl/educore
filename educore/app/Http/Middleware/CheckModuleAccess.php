<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Fail-closed module authorization for every authenticated staff web route.
 *
 * ROLE_ACCESS remains the default role contract. Per-staff deny overrides are
 * enforced before role access, while explicit grants can extend a role. Routes
 * that are not mapped to an explicitly allowed module are denied rather than
 * silently becoming available.
 */
class CheckModuleAccess
{
    private const ADMISSION_OFFICER_DEFAULT_DENIES = [
        'students', 'transfers', 'classes', 'subjects', 'curriculum',
        'academic-cycle', 'scores', 'scores.entry', 'scores.view',
        'reports', 'reports.view', 'reports.remarks', 'attendance',
        'timetable', 'timetable.view', 'skills', 'cbt', 'gradebook',
        'academic-repository', 'lesson-planner',
    ];

    public function handle(Request $request, Closure $next): mixed
    {
        /** @var User|null $user */
        $user = Auth::user();

        if (! $user) {
            return redirect()->route('login');
        }

        if ($user->isStudent() || $user->isParent()) {
            return redirect()->route(
                $user->isStudent() ? 'student.portal.dashboard' : 'parent.dashboard'
            );
        }

        if ($user->isSuperAdmin()) {
            return $next($request);
        }

        $routeName = trim((string) ($request->route()?->getName() ?? ''));

        if ($routeName === 'logout') {
            return $next($request);
        }

        // Authenticated staff routes must be named so authorization can be
        // resolved deterministically. Unknown routes fail closed.
        if ($routeName === '') {
            return $this->deny($request, $user, 'unnamed route');
        }

        $matchingModules = $this->matchingModules($routeName);

        // Profile is the only universal staff workspace.
        if (str_starts_with($routeName, 'profile')) {
            return $next($request);
        }

        // Respect explicit per-staff denies and grants on the web, not only in
        // the sidebar/mobile bootstrap contract.
        foreach ($matchingModules as $module) {
            if ($user->hasDeniedPermission($module)) {
                return $this->deny($request, $user, $routeName);
            }
        }

        foreach ($matchingModules as $module) {
            if ($user->hasGrantedPermission($module)) {
                return $next($request);
            }
        }

        // Admission is an operational role, not an academic role. Legacy
        // student/transfer grants are not treated as default authority. An
        // administrator can still make an intentional per-staff grant.
        if ($user->roleKey() === 'admission_officer') {
            foreach ($matchingModules as $module) {
                if (in_array($module, self::ADMISSION_OFFICER_DEFAULT_DENIES, true)) {
                    return $this->deny($request, $user, $routeName);
                }
            }
        }

        if (! $user->canAccessRoute($routeName)) {
            return $this->deny($request, $user, $routeName);
        }

        return $next($request);
    }

    private function matchingModules(string $routeName): array
    {
        $matches = [];

        foreach (User::MODULE_ROUTES as $module => $prefixes) {
            foreach ($prefixes as $prefix) {
                if ($routeName === $prefix || str_starts_with($routeName, $prefix.'.')) {
                    $matches[] = $module;
                    break;
                }
            }
        }

        return array_values(array_unique($matches));
    }

    private function deny(Request $request, User $user, string $route): mixed
    {
        if ($request->expectsJson()) {
            return response()->json([
                'error' => 'Access denied.',
                'role' => $user->roleLabel(),
                'route' => $route,
            ], 403);
        }

        abort(403, "Your role ({$user->roleLabel()}) does not have access to this section.");
    }
}
