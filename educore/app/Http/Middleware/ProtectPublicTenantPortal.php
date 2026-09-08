<?php

namespace App\Http\Middleware;

use App\Models\Admission;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\Response;

/**
 * Security boundary for unauthenticated tenant-host admission/recruitment pages.
 *
 * Keeps public forms usable while protecting high-cost upload/message endpoints
 * from abuse and ensuring an admission success page cannot be opened with an
 * application number alone.
 */
class ProtectPublicTenantPortal
{
    private const LIMITED_ROUTES = [
        'tenant.host.apply.submit' => [6, 60],
        'tenant.host.custom.apply.submit' => [6, 60],
        'tenant.host.apply.status' => [10, 60],
        'tenant.host.custom.apply.status' => [10, 60],
        'tenant.host.careers.apply' => [6, 60],
        'tenant.host.custom.careers.apply' => [6, 60],
        'tenant.host.careers.track.reply' => [12, 60],
        'tenant.host.custom.careers.track.reply' => [12, 60],
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $routeName = (string) ($request->route()?->getName() ?? '');

        if (in_array($routeName, [
            'tenant.host.apply.success',
            'tenant.host.custom.apply.success',
        ], true)) {
            $this->protectAdmissionSuccess($request);
        }

        if (isset(self::LIMITED_ROUTES[$routeName])) {
            [$maxAttempts, $decaySeconds] = self::LIMITED_ROUTES[$routeName];
            $this->enforceRateLimit($request, $routeName, $maxAttempts, $decaySeconds);
        }

        return $next($request);
    }

    private function protectAdmissionSuccess(Request $request): void
    {
        $tenant = $request->attributes->get('resolved_tenant');
        $applicationNumber = (string) $request->route('app');
        $presentedToken = (string) $request->query('token', '');

        if (!$tenant || $applicationNumber === '' || $presentedToken === '') {
            abort(404);
        }

        $storedToken = Admission::withoutTenantScope()
            ->where('tenant_id', $tenant->id)
            ->where('application_number', $applicationNumber)
            ->value('portal_token');

        if (!is_string($storedToken)
            || $storedToken === ''
            || !hash_equals($storedToken, $presentedToken)) {
            abort(404);
        }
    }

    private function enforceRateLimit(
        Request $request,
        string $routeName,
        int $maxAttempts,
        int $decaySeconds,
    ): void {
        $tenantId = $request->attributes->get('resolved_tenant')?->id ?? 'unknown';
        $key = 'public-tenant-portal:' . $tenantId . ':' . $routeName . ':' . $request->ip();

        if (RateLimiter::tooManyAttempts($key, $maxAttempts)) {
            abort(429, 'Too many requests. Please try again shortly.');
        }

        RateLimiter::hit($key, $decaySeconds);
    }
}
