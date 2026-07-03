<?php

namespace App\Services;

use App\Models\Tenant;

class TenantUrlGenerator
{
    public function __construct(private readonly TenantHostResolver $hosts)
    {
    }

    public function landing(Tenant $tenant): string
    {
        return $this->url($tenant, '/');
    }

    public function login(Tenant $tenant): string
    {
        return $this->url($tenant, '/login');
    }

    public function forgotPassword(Tenant $tenant): string
    {
        return $this->url($tenant, '/forgot-password');
    }

    public function resetPassword(Tenant $tenant, string $token, string $email): string
    {
        return $this->url($tenant, '/reset-password/' . rawurlencode($token) . '?email=' . rawurlencode($email));
    }

    public function apply(Tenant $tenant): string
    {
        return $this->url($tenant, '/apply');
    }

    public function accountStatus(Tenant $tenant): string
    {
        return $this->url($tenant, '/account-status');
    }

    public function url(Tenant $tenant, string $path = '/'): string
    {
        $path = '/' . ltrim($path, '/');
        $scheme = config('tenancy.scheme', 'http');

        return $scheme . '://' . $this->hosts->preferredTenantHost($tenant) . $path;
    }
}
