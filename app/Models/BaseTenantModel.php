<?php

namespace App\Models;

use App\Models\Scopes\TenantScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

/**
 * BaseTenantModel
 *
 * All tenant-scoped models in this system extend this class instead of
 * the default Illuminate\Database\Eloquent\Model.
 *
 * What it does automatically:
 *  1. Boots TenantScope — every SELECT query gets WHERE tenant_id = ?
 *  2. Auto-fills tenant_id on CREATE — no need to set it manually
 *  3. Provides a withoutTenantScope() escape hatch for super-admin queries
 */
abstract class BaseTenantModel extends Model
{
    /**
     * Boot the model and register the global TenantScope.
     */
    protected static function booted(): void
    {
        static::addGlobalScope(new TenantScope());

        // Auto-assign tenant_id when creating a new record
        static::creating(function (self $model) {
            if (empty($model->tenant_id)) {
                if (Auth::check() && Auth::user()->tenant_id) {
                    $model->tenant_id = Auth::user()->tenant_id;
                } elseif (\App\Models\Scopes\TenantContext::has()) {
                    $model->tenant_id = \App\Models\Scopes\TenantContext::get();
                }
            }
        });
    }

    /**
     * Remove the TenantScope for this query only.
     * Use sparingly — only in super-admin contexts.
     *
     * Example:
     *   Student::withoutTenantScope()->where('status', 'active')->get();
     */
    public static function withoutTenantScope(): \Illuminate\Database\Eloquent\Builder
    {
        return static::withoutGlobalScope(TenantScope::class);
    }
}
