<?php

namespace App\Services;

use App\Models\Tenant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class TenantHostResolver
{
    public function resolve(Request|string $requestOrHost): TenantHostResolution
    {
        $host = $requestOrHost instanceof Request
            ? $requestOrHost->getHost()
            : $requestOrHost;

        $normalized = $this->normalizeHost($host);
        if ($normalized === null) {
            return new TenantHostResolution('', TenantHostResolution::TYPE_INVALID, reason: 'invalid_host');
        }

        if ($this->isCentralHost($normalized)) {
            return new TenantHostResolution($normalized, TenantHostResolution::TYPE_CENTRAL);
        }

        $localKey = $this->localTenantKey($normalized);
        if ($localKey !== null) {
            $tenant = Tenant::query()
                ->where(function ($query) use ($localKey, $normalized) {
                    $query->where('slug', $localKey);

                    if ($this->hasTenantColumn('subdomain')) {
                        $query->orWhere('subdomain', $localKey)
                            ->orWhere('subdomain', $normalized);
                    }
                })
                ->first();

            return new TenantHostResolution(
                $normalized,
                $tenant ? TenantHostResolution::TYPE_LOCAL_SUBDOMAIN : TenantHostResolution::TYPE_UNKNOWN,
                $tenant,
                $localKey,
                $tenant ? null : 'unknown_local_subdomain'
            );
        }

        $tenant = null;
        if ($this->hasTenantColumn('custom_domain') && $this->hasTenantColumn('domain_verified')) {
            $tenant = Tenant::query()
                ->whereRaw('LOWER(custom_domain) = ?', [$normalized])
                ->where('domain_verified', true)
                ->first();
        }

        return new TenantHostResolution(
            $normalized,
            $tenant ? TenantHostResolution::TYPE_CUSTOM_DOMAIN : TenantHostResolution::TYPE_UNKNOWN,
            $tenant,
            $normalized,
            $tenant ? null : 'unknown_or_unverified_custom_domain'
        );
    }

    public function normalizeHost(?string $host): ?string
    {
        $host = trim(Str::lower((string) $host));
        $host = preg_replace('/:\d+$/', '', $host);
        $host = rtrim((string) $host, '.');

        if ($host === '' || str_contains($host, '/') || str_contains($host, '_')) {
            return null;
        }

        if (strlen($host) > 253 || !preg_match('/^[a-z0-9.-]+$/', $host)) {
            return null;
        }

        $labels = explode('.', $host);
        foreach ($labels as $label) {
            if (!$this->isValidLabel($label)) {
                return null;
            }
        }

        return $host;
    }

    public function isCentralHost(string $host): bool
    {
        return in_array($host, $this->centralHosts(), true);
    }

    public function localTenantHost(Tenant $tenant): string
    {
        return $tenant->slug . '.' . $this->localBaseDomain();
    }

    public function preferredTenantHost(Tenant $tenant): string
    {
        $custom = $this->normalizeHost($tenant->getAttribute('custom_domain'));

        if ($custom && (bool) $tenant->getAttribute('domain_verified')) {
            return $custom;
        }

        return $this->localTenantHost($tenant);
    }

    public function validateCustomDomain(?string $host): ?string
    {
        $normalized = $this->normalizeHost($host);

        if ($normalized === null || $this->isCentralHost($normalized)) {
            return null;
        }

        if ($this->localTenantKey($normalized) !== null) {
            return null;
        }

        return $normalized;
    }

    public function localBaseDomain(): string
    {
        return $this->normalizeHost(config('tenancy.local_base_domain', 'educore.test')) ?: 'educore.test';
    }

    public function centralHosts(): array
    {
        return array_values(array_filter(array_map(
            fn ($host) => $this->normalizeHost($host),
            config('tenancy.central_hosts', [])
        )));
    }

    private function localTenantKey(string $host): ?string
    {
        $base = $this->localBaseDomain();
        $suffix = '.' . $base;

        if (!str_ends_with($host, $suffix)) {
            return null;
        }

        $key = substr($host, 0, -strlen($suffix));

        if ($key === '' || str_contains($key, '.')) {
            return null;
        }

        return $this->isValidLabel($key) ? $key : null;
    }

    private function isValidLabel(string $label): bool
    {
        return $label !== ''
            && strlen($label) <= 63
            && (bool) preg_match('/^[a-z0-9](?:[a-z0-9-]*[a-z0-9])?$/', $label);
    }

    private function hasTenantColumn(string $column): bool
    {
        return Schema::hasColumn((new Tenant())->getTable(), $column);
    }
}
