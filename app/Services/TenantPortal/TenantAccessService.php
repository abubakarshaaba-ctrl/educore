<?php

namespace App\Services\TenantPortal;

use App\Models\Tenant;
use App\Services\TenantAccessService as ApplicationTenantAccessService;

class TenantAccessService
{
    public function __construct(private readonly ApplicationTenantAccessService $applicationAccess)
    {
    }

    public function normalizeSlug(string $slug): string
    {
        return Tenant::normalizeSlug($slug);
    }

    public function resolveBySlug(string $slug): ?Tenant
    {
        $normalized = $this->normalizeSlug($slug);

        if ($normalized === '') {
            return null;
        }

        return Tenant::query()
            ->where('slug', $normalized)
            ->first();
    }

    public function isPubliclyAccessible(?Tenant $tenant): bool
    {
        return $this->applicationAccess->applicationAccess($tenant)->allowed;
    }

    public function unavailableMessage(): string
    {
        return $this->applicationAccess->genericUnavailableMessage();
    }
}
