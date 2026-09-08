<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;

/**
 * Defense-in-depth for tenant-owned foreign keys accepted by legacy web forms.
 *
 * Eloquent route models already use TenantScope, but raw validation rules such
 * as "exists:class_arms,id" can otherwise accept an id from another school.
 */
class EnforceTenantFormReferences
{
    private const SINGLE_REFERENCES = [
        'admissions.store' => [
            'applying_for_class_level_id' => 'class_levels',
        ],
        'admissions.status' => [
            'class_arm_id' => 'class_arms',
        ],
        'recruitment.applicants.interview' => [
            'interviewer_id' => 'users',
        ],
    ];

    private const ARRAY_REFERENCES = [
        'admissions.bulk-status' => [
            'ids' => 'admissions',
        ],
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();
        $tenantId = $user?->tenant_id;
        if (!$tenantId || $user->isSuperAdmin()) {
            return $next($request);
        }

        $routeName = (string) ($request->route()?->getName() ?? '');

        foreach (self::SINGLE_REFERENCES[$routeName] ?? [] as $field => $table) {
            $value = $request->input($field);
            if ($value === null || $value === '') {
                continue;
            }
            if (!$this->belongsToTenant($table, $value, (int) $tenantId)) {
                throw ValidationException::withMessages([
                    $field => 'The selected record is not available for this school.',
                ]);
            }
        }

        foreach (self::ARRAY_REFERENCES[$routeName] ?? [] as $field => $table) {
            $values = array_values(array_unique(array_filter(
                (array) $request->input($field, []),
                fn ($value) => $value !== null && $value !== ''
            )));
            if ($values === []) {
                continue;
            }

            $ownedCount = DB::table($table)
                ->where('tenant_id', $tenantId)
                ->whereIn('id', $values)
                ->count();

            if ($ownedCount !== count($values)) {
                throw ValidationException::withMessages([
                    $field => 'One or more selected records are not available for this school.',
                ]);
            }
        }

        return $next($request);
    }

    private function belongsToTenant(string $table, mixed $value, int $tenantId): bool
    {
        return DB::table($table)
            ->where('tenant_id', $tenantId)
            ->where('id', $value)
            ->exists();
    }
}
