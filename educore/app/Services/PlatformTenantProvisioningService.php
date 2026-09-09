<?php

namespace App\Services;

use App\Models\StaffWorkHistory;
use App\Models\Tenant;
use App\Models\User;
use App\Services\Auth\AuthAuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class PlatformTenantProvisioningService
{
    public function __construct(
        private readonly TenantOnboardingService $onboarding,
        private readonly AuthAuditLogger $audit,
    ) {
    }

    /**
     * Create a tenant and its first administrator as one platform transaction.
     * The caller must validate uniqueness and authorize the Super Admin boundary.
     *
     * @return array{tenant: Tenant, admin: User}
     */
    public function provision(array $data, User $actor, ?Request $request = null): array
    {
        $result = DB::transaction(function () use ($data, $actor, $request): array {
            $tenant = Tenant::create([
                'name' => trim($data['name']),
                'slug' => $data['slug'],
                'subdomain' => $data['subdomain'] ?? null,
                'email' => strtolower(trim($data['email'])),
                'phone' => $data['phone'] ?? null,
                'address' => $data['address'] ?? null,
                'status' => Tenant::STATUS_ACTIVE,
                'subscription_expires_at' => null,
                'theme_primary' => '#071E45',
                'theme_accent' => '#D79A21',
                'theme_sidebar' => '#071E45',
            ]);

            $this->audit->recordForTenant($tenant, 'tenant.provisioning.started', [
                'slug' => $tenant->slug,
                'subdomain' => $tenant->subdomain,
                'source' => $data['source'] ?? 'platform',
            ], $request, null, $actor);

            $admin = User::create([
                'tenant_id' => $tenant->id,
                'name' => trim($data['admin_name']),
                'email' => strtolower(trim($data['admin_email'])),
                'password' => Hash::make($data['admin_password']),
                'role' => 'admin',
                'is_super_admin' => false,
                'is_active' => true,
                'employment_status' => User::STAFF_STATUS_ACTIVE,
                'employment_started_at' => $data['admin_employment_started_at'],
                'status_changed_at' => now(),
            ]);

            if (Schema::hasTable('roles') && Schema::hasTable('model_has_roles')) {
                $admin->assignRole('admin');
            }

            if (Schema::hasTable('staff_work_histories')) {
                StaffWorkHistory::create([
                    'tenant_id' => $tenant->id,
                    'user_id' => $admin->id,
                    'position_title' => 'School Administrator',
                    'functional_role' => 'admin',
                    'employment_type' => 'full_time',
                    'appointment_type' => 'initial_admin',
                    'start_date' => $data['admin_employment_started_at'],
                    'change_type' => 'appointment',
                    'reason' => 'Initial tenant administrator provisioned.',
                    'recorded_by' => $actor->id,
                    'approved_by' => $actor->id,
                    'approved_at' => now(),
                ]);
            }

            $this->audit->recordForTenant($tenant, 'tenant.provisioning.tenant_created', [
                'slug' => $tenant->slug,
                'status' => $tenant->status,
                'source' => $data['source'] ?? 'platform',
            ], $request, null, $actor);
            $this->audit->recordForTenant($tenant, 'tenant.provisioning.administrator_created', [
                'admin_user_id' => $admin->id,
                'admin_email_hash' => hash('sha256', strtolower($admin->email)),
            ], $request, null, $actor);

            $this->onboarding->createProvisioningDefaults($tenant);
            $this->audit->recordForTenant($tenant, 'tenant.provisioning.default_settings_created', [
                'defaults' => ['school_settings', 'admission_portal_settings', 'skills'],
            ], $request, null, $actor);

            return ['tenant' => $tenant, 'admin' => $admin];
        });

        try {
            $result['tenant']->notifyAdmins(new \App\Notifications\Tenant\TenantWelcomeNotification(
                $result['tenant'],
                $result['tenant']->subscription_expires_at?->format('d M Y')
            ));
        } catch (\Throwable $error) {
            Log::error('Tenant welcome notification failed after provisioning.', [
                'tenant_id' => $result['tenant']->id,
                'error' => $error->getMessage(),
            ]);
        }

        return $result;
    }
}
