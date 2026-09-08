<?php

namespace App\Services;

use App\Models\Admission;
use App\Models\Tenant;
use Illuminate\Support\Facades\Schema;

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

    public function admissionForm(Tenant $tenant): string
    {
        return $this->url($tenant, '/apply/form');
    }

    public function admissionStatus(Tenant $tenant): string
    {
        return $this->url($tenant, '/apply/status');
    }

    public function admissionSuccess(Tenant $tenant, string $applicationNumber, ?string $portalToken = null): string
    {
        // Existing callers historically supplied only the application number.
        // Resolve the already-generated portal token when the admission exists so
        // the success page is not an application-number-only public lookup.
        // Schema guard keeps this helper safe in onboarding/tests before the
        // admissions table exists.
        if (($portalToken === null || $portalToken === '') && Schema::hasTable('admissions')) {
            $portalToken = Admission::withoutTenantScope()
                ->where('tenant_id', $tenant->id)
                ->where('application_number', $applicationNumber)
                ->value('portal_token');
        }

        $path = '/apply/success/' . rawurlencode($applicationNumber);
        if (is_string($portalToken) && $portalToken !== '') {
            $path .= '?token=' . rawurlencode($portalToken);
        }

        return $this->url($tenant, $path);
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
