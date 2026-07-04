<?php

namespace App\Models\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Facades\Auth;

class TenantScope implements Scope
{
    /**
     * Apply the scope to a given Eloquent query builder.
     * Automatically appends WHERE tenant_id = ? to every query
     * for any model that boots this scope.
     */
    public function apply(Builder $builder, Model $model): void
    {
        // Super-admins bypass tenant filtering — they see all tenants
        if (Auth::check() && Auth::user()->is_super_admin) {
            return;
        }

        // Resolve tenant_id from the authenticated user
        if (Auth::check() && Auth::user()->tenant_id !== null) {
            $builder->where($model->getTable() . '.tenant_id', Auth::user()->tenant_id);
            return;
        }

        // If no authenticated user (e.g. during seeding/CLI), allow explicit
        // tenant context to be set via the TenantContext helper
        if (TenantContext::has()) {
            $builder->where($model->getTable() . '.tenant_id', TenantContext::get());
        }
    }
}
