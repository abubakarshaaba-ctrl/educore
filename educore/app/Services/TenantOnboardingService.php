<?php

namespace App\Services;

use App\Models\AcademicSession;
use App\Models\AdmissionPortalSetting;
use App\Models\ClassArm;
use App\Models\ClassLevel;
use App\Models\GradingSystem;
use App\Models\SchoolSetting;
use App\Models\Subject;
use App\Models\Tenant;
use App\Models\Term;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class TenantOnboardingService
{
    private const STEP_ROUTES = [
        'identity' => 'tenant.onboarding.profile',
        'administrator' => 'tenant.onboarding.index',
        'profile' => 'tenant.onboarding.profile',
        'branding' => 'tenant.onboarding.branding',
        'calendar' => 'tenant.onboarding.session',
        'classes' => 'tenant.onboarding.classes',
        'subjects' => 'tenant.onboarding.subjects',
        'settings' => 'tenant.onboarding.settings',
        'portals' => 'tenant.onboarding.portals',
        'review' => 'tenant.onboarding.review',
    ];

    public function __construct(
        private readonly TenantAccessService $access,
        private readonly TenantUrlGenerator $urls
    ) {
    }

    public function status(Tenant $tenant): TenantOnboardingStatus
    {
        $tenant->refresh();

        $steps = [
            'identity' => $this->identityStep($tenant),
            'administrator' => $this->administratorStep($tenant),
            'profile' => $this->profileStep($tenant),
            'branding' => $this->brandingStep($tenant),
            'calendar' => $this->calendarStep($tenant),
            'classes' => $this->classesStep($tenant),
            'subjects' => $this->subjectsStep($tenant),
            'settings' => $this->settingsStep($tenant),
            'portals' => $this->portalsStep($tenant),
            'review' => $this->accessStep($tenant),
        ];

        foreach ($steps as $key => $step) {
            $steps[$key]['key'] = $key;
            $steps[$key]['route'] = self::STEP_ROUTES[$key] ?? 'tenant.onboarding.index';
            $steps[$key]['status'] = $this->resolveStepStatus($step);
        }

        // The final review/activation gate cannot read as complete while any earlier
        // setup step is still blocking — activation depends on the rest of onboarding.
        // Without this, the school could see "Review and activation: complete" sitting
        // next to a blocking "Academic session and term", which is contradictory.
        $upstreamBlocking = collect($steps)
            ->reject(fn ($step, $key) => $key === 'review')
            ->contains(fn ($step) => $step['status'] === 'blocking');

        if ($upstreamBlocking && ($steps['review']['status'] ?? null) !== 'blocking') {
            $steps['review']['blocking'][] = 'Resolve the blocking steps above before the school can be activated.';
            $steps['review']['status'] = 'blocking';
        }

        $completed = collect($steps)
            ->filter(fn ($step) => $step['status'] === 'complete')
            ->map(fn ($step) => $step['label'])
            ->values()
            ->all();
        $blocking = collect($steps)->flatMap(fn ($step) => $step['blocking'])->values()->all();
        $warnings = collect($steps)->flatMap(fn ($step) => $step['warnings'])->values()->all();
        $progress = (int) round((count($completed) / max(1, count($steps))) * 100);
        $nextStep = collect($steps)->first(fn ($step) => $step['status'] === 'blocking')
            ?: collect($steps)->first(fn ($step) => $step['status'] === 'warning');

        $complete = $blocking === [];

        return new TenantOnboardingStatus(
            complete: $complete,
            progress_percentage: $progress,
            current_step: $nextStep['key'] ?? null,
            next_step: $nextStep['route'] ?? null,
            blocking_items: $blocking,
            warning_items: $warnings,
            completed_items: $completed,
            can_activate: $complete,
            can_access_operations: $complete,
            steps: $steps,
            urls: $this->tenantUrls($tenant),
            environment: $this->environmentReadiness()
        );
    }

    public function createProvisioningDefaults(Tenant $tenant): void
    {
        if (Schema::hasTable('school_settings')) {
            foreach ([
                'currency' => ['value' => 'NGN', 'group' => 'finance'],
                'timezone' => ['value' => config('app.timezone', 'Africa/Lagos'), 'group' => 'general'],
                'onboarding_defaults_version' => ['value' => '11I', 'group' => 'system'],
            ] as $key => $payload) {
                SchoolSetting::withoutTenantScope()->updateOrCreate(
                    ['tenant_id' => $tenant->id, 'key' => $key],
                    $payload
                );
            }
        }

        if (Schema::hasTable('admission_portal_settings')) {
            AdmissionPortalSetting::withoutTenantScope()->firstOrCreate(
                ['tenant_id' => $tenant->id],
                [
                    'is_open' => false,
                    'application_fee' => 0,
                    'welcome_message' => 'Admissions setup is pending.',
                    'notify_guardian_sms' => false,
                    'notify_guardian_email' => true,
                ]
            );
        }
    }

    public function activeAdminCount(Tenant $tenant): int
    {
        return User::query()
            ->where('tenant_id', $tenant->id)
            ->where('is_super_admin', false)
            ->where('is_active', true)
            ->where(function ($query) {
                $query->whereNull('employment_status')
                    ->orWhere('employment_status', User::STAFF_STATUS_ACTIVE);
            })
            ->whereIn('role', User::STAFF_ADMIN_CONTINUITY_ROLES)
            ->count();
    }

    private function identityStep(Tenant $tenant): array
    {
        $blocking = [];
        $warnings = [];

        foreach (['name' => 'School name', 'slug' => 'School slug', 'email' => 'School email'] as $field => $label) {
            if (blank($tenant->{$field})) {
                $blocking[] = "{$label} is required.";
            }
        }

        if (blank($tenant->phone)) {
            $warnings[] = 'School phone number is not set.';
        }

        if (blank($tenant->address)) {
            $warnings[] = 'School address is not set.';
        }

        return $this->step('School identity', $blocking, $warnings);
    }

    private function administratorStep(Tenant $tenant): array
    {
        $blocking = [];

        if ($this->activeAdminCount($tenant) < 1) {
            $blocking[] = 'At least one active tenant administrator is required.';
        }

        $superAdminContamination = User::query()
            ->where('tenant_id', $tenant->id)
            ->where('is_super_admin', true)
            ->exists();

        if ($superAdminContamination) {
            $blocking[] = 'A tenant user is incorrectly marked as Super Admin.';
        }

        return $this->step('Primary administrator', $blocking);
    }

    private function profileStep(Tenant $tenant): array
    {
        $warnings = [];

        foreach (['motto' => 'School motto', 'logo_path' => 'School logo'] as $field => $label) {
            if (blank($tenant->{$field})) {
                $warnings[] = "{$label} is not configured.";
            }
        }

        return $this->step('School profile', [], $warnings);
    }

    private function brandingStep(Tenant $tenant): array
    {
        $blocking = [];
        $warnings = [];

        foreach (['theme_primary' => 'Primary colour', 'theme_accent' => 'Accent colour'] as $field => $label) {
            $value = $tenant->{$field};
            if (blank($value)) {
                $warnings[] = "{$label} is using the EduCore default.";
                continue;
            }

            if (!$this->isSafeHexColor($value)) {
                $blocking[] = "{$label} must be a safe hex colour.";
            }
        }

        return $this->step('Branding', $blocking, $warnings);
    }

    private function calendarStep(Tenant $tenant): array
    {
        if (!Schema::hasTable('academic_sessions') || !Schema::hasTable('terms')) {
            return $this->step('Academic session and term', ['Academic calendar tables are unavailable.']);
        }

        $blocking = [];
        $sessionCount = AcademicSession::withoutTenantScope()
            ->where('tenant_id', $tenant->id)
            ->where('is_current', true)
            ->count();
        $termCount = Term::withoutTenantScope()
            ->where('tenant_id', $tenant->id)
            ->where('is_current', true)
            ->count();

        if ($sessionCount !== 1) {
            $blocking[] = $sessionCount === 0
                ? 'Exactly one current academic session is required.'
                : 'Only one academic session may be marked current.';
        }

        if ($termCount !== 1) {
            $blocking[] = $termCount === 0
                ? 'Exactly one current term is required.'
                : 'Only one term may be marked current.';
        }

        $invalidTerm = Term::withoutTenantScope()
            ->where('tenant_id', $tenant->id)
            ->whereColumn('end_date', '<', 'start_date')
            ->exists();

        if ($invalidTerm) {
            $blocking[] = 'One or more terms have invalid date ranges.';
        }

        return $this->step('Academic session and term', $blocking);
    }

    private function classesStep(Tenant $tenant): array
    {
        $blocking = [];

        if (!Schema::hasTable('class_levels') || ClassLevel::withoutTenantScope()->where('tenant_id', $tenant->id)->count() < 1) {
            $blocking[] = 'At least one class level is required.';
        }

        if (!Schema::hasTable('class_arms') || ClassArm::withoutTenantScope()->where('tenant_id', $tenant->id)->count() < 1) {
            $blocking[] = 'At least one class arm is required.';
        }

        return $this->step('Classes and arms', $blocking);
    }

    private function subjectsStep(Tenant $tenant): array
    {
        $blocking = [];

        if (!Schema::hasTable('subjects') || Subject::withoutTenantScope()->where('tenant_id', $tenant->id)->where('is_active', true)->count() < 1) {
            $blocking[] = 'At least one active subject is required.';
        }

        if (!Schema::hasTable('grading_systems') || GradingSystem::withoutTenantScope()->where('tenant_id', $tenant->id)->count() < 1) {
            $blocking[] = 'At least one grading rule is required.';
        }

        return $this->step('Subjects and grading', $blocking);
    }

    private function settingsStep(Tenant $tenant): array
    {
        $warnings = [];

        if (!Schema::hasTable('admission_portal_settings')
            || !AdmissionPortalSetting::withoutTenantScope()->where('tenant_id', $tenant->id)->exists()) {
            $warnings[] = 'Admission portal settings have not been reviewed.';
        }

        if (!Schema::hasTable('school_settings')
            || !SchoolSetting::withoutTenantScope()->where('tenant_id', $tenant->id)->exists()) {
            $warnings[] = 'School settings defaults have not been created.';
        }

        if (blank(config('mail.default')) || config('mail.default') === 'array') {
            $warnings[] = 'Mail transport is not production-ready; password reset and invitation delivery may need setup.';
        }

        return $this->step('Operational settings', [], $warnings);
    }

    private function portalsStep(Tenant $tenant): array
    {
        $warnings = [];

        if ($tenant->getAttribute('custom_domain') && !$tenant->getAttribute('domain_verified')) {
            $warnings[] = 'Custom domain is present but not verified, so it is not used.';
        }

        return $this->step('Portals and hostname', [], $warnings);
    }

    private function accessStep(Tenant $tenant): array
    {
        $decision = $this->access->applicationAccess($tenant);
        $blocking = [];
        $warnings = [];

        if ($decision->isDenied()) {
            $blocking[] = $decision->message;
        } elseif ($decision->isWarning()) {
            $warnings[] = $decision->message;
        }

        return $this->step('Review and activation', $blocking, $warnings);
    }

    private function tenantUrls(Tenant $tenant): array
    {
        return [
            'slug_landing' => route('tenant.portal.landing', $tenant->slug),
            'slug_login' => route('tenant.login', $tenant->slug),
            'slug_forgot_password' => route('tenant.password.request', $tenant->slug),
            'admissions' => route('portal.landing', $tenant->slug),
            'local_subdomain' => $this->urls->landing($tenant),
            'local_subdomain_login' => $this->urls->login($tenant),
            'account_status' => $this->urls->accountStatus($tenant),
            'custom_domain' => $tenant->getAttribute('custom_domain') && $tenant->getAttribute('domain_verified')
                ? $this->urls->landing($tenant)
                : null,
        ];
    }

    private function environmentReadiness(): array
    {
        return [
            'app_key' => filled(config('app.key')),
            'mail_transport' => filled(config('mail.default')) && config('mail.default') !== 'array',
            'storage_writable' => is_writable(storage_path()),
            'public_disk_configured' => (bool) config('filesystems.disks.public.root'),
            'storage_link_present' => is_link(public_path('storage')) || is_dir(public_path('storage')),
            'queue_connection' => config('queue.default'),
        ];
    }

    private function step(string $label, array $blocking = [], array $warnings = []): array
    {
        return [
            'label' => $label,
            'blocking' => array_values($blocking),
            'warnings' => array_values($warnings),
        ];
    }

    private function resolveStepStatus(array $step): string
    {
        if ($step['blocking'] !== []) {
            return 'blocking';
        }

        if ($step['warnings'] !== []) {
            return 'warning';
        }

        return 'complete';
    }

    private function isSafeHexColor(?string $value): bool
    {
        return is_string($value) && preg_match('/^#[0-9A-Fa-f]{6}$/', $value);
    }
}
