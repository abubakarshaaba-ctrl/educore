<?php

namespace App\Services\TenantPortal;

use App\Models\SchoolSetting;
use App\Models\Tenant;

class TenantBrandingService
{
    private const DEFAULT_PRIMARY = '#071E45';
    private const DEFAULT_ACCENT = '#D79A21';

    public function forTenant(Tenant $tenant): array
    {
        $settings = SchoolSetting::withoutTenantScope()
            ->where('tenant_id', $tenant->id)
            ->pluck('value', 'key');

        return [
            'name' => $tenant->name,
            'motto' => $settings->get('motto') ?: ($tenant->motto ?? null),
            'address' => $tenant->address,
            'phone' => $tenant->phone,
            'email' => $tenant->email,
            'website' => $this->safeWebsite($settings->get('website')),
            'logo_url' => $this->logoUrl($tenant),
            'primary' => $this->safeColor(
                $tenant->theme_primary
                    ?: $tenant->primary_color
                    ?: self::DEFAULT_PRIMARY,
                self::DEFAULT_PRIMARY
            ),
            'accent' => $this->safeColor(
                $tenant->theme_accent
                    ?: $tenant->secondary_color
                    ?: self::DEFAULT_ACCENT,
                self::DEFAULT_ACCENT
            ),
        ];
    }

    public function safeColor(?string $value, string $fallback): string
    {
        $value = trim((string) $value);

        return preg_match('/^#[0-9A-Fa-f]{6}$/', $value) ? $value : $fallback;
    }

    private function safeWebsite(?string $value): ?string
    {
        $value = trim((string) $value);

        if ($value === '' || !filter_var($value, FILTER_VALIDATE_URL)) {
            return null;
        }

        $scheme = parse_url($value, PHP_URL_SCHEME);

        return in_array($scheme, ['http', 'https'], true) ? $value : null;
    }

    private function logoUrl(Tenant $tenant): ?string
    {
        $path = trim((string) $tenant->logo_path);

        if ($path === '' || str_contains($path, '..') || preg_match('/^https?:\/\//i', $path)) {
            return null;
        }

        $path = ltrim($path, '/');

        if (str_starts_with($path, 'storage/')) {
            return asset($path);
        }

        return asset('storage/' . $path);
    }
}
