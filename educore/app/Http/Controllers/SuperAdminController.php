<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Models\AuditLog;
use App\Models\StaffWorkHistory;
use App\Models\SubscriptionPlan;
use App\Models\Tenant;
use App\Models\User;
use App\Services\Auth\AuthAuditLogger;
use App\Services\TenantHostResolver;
use App\Services\TenantOnboardingService;
use App\Services\TenantUrlGenerator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Illuminate\Support\Str;

class SuperAdminController extends Controller
{
    private function guard(): void
    {
        abort_unless(auth()->user()?->is_super_admin, 403, 'Super Admin access required.');
    }

    // ═══════════════════════════════════════════════════════════════
    // DASHBOARD
    // ═══════════════════════════════════════════════════════════════
    public function dashboard()
    {
        $this->guard();

        $stats = [
            'tenants'           => Tenant::count(),
            'active'            => Tenant::where('status', 'active')->count(),
            'expired'           => Tenant::where('status', 'subscription_expired')->count(),
            'suspended'         => Tenant::where('status', 'suspended')->count(),
            'pending'           => Tenant::where('status', 'pending')->count(),
            'total_students'    => Student::withoutTenantScope()->count(),
            'total_users'       => User::whereNotNull('tenant_id')->count(),
            'revenue_this_month'=> DB::table('platform_payments')
                                     ->where('status', 'confirmed')
                                     ->whereMonth('paid_at', now()->month)
                                     ->whereYear('paid_at', now()->year)
                                     ->sum('amount'),
            'revenue_total'     => DB::table('platform_payments')
                                     ->where('status', 'confirmed')
                                     ->sum('amount'),
            'expiring_soon'     => Tenant::where('subscription_expires_at', '<=', now()->addDays(14))
                                         ->where('subscription_expires_at', '>=', now())
                                         ->count(),
        ];

        $recentTenants  = Tenant::with('activeSubscription.plan')->latest()->limit(8)->get();
        $recentPayments = DB::table('platform_payments')
                            ->join('tenants', 'tenants.id', '=', 'platform_payments.tenant_id')
                            ->select('platform_payments.*', 'tenants.name as school_name')
                            ->orderByDesc('platform_payments.created_at')
                            ->limit(6)->get();

        $expiringTenants = Tenant::where('subscription_expires_at', '<=', now()->addDays(14))
                                 ->where('subscription_expires_at', '>=', now())
                                 ->orderBy('subscription_expires_at')
                                 ->get();

        return view('super.dashboard', compact('stats', 'recentTenants', 'recentPayments', 'expiringTenants'));
    }

    // ═══════════════════════════════════════════════════════════════
    // SCHOOLS / TENANTS
    // ═══════════════════════════════════════════════════════════════
    public function tenants(Request $request)
    {
        $this->guard();
        $query = Tenant::withCount(['users', 'students'])->with('activeSubscription.plan')->latest();

        if ($request->filled('search'))  $query->where('name', 'like', '%'.$request->search.'%');
        if ($request->filled('status'))  $query->where('status', $request->status);
        if ($request->filled('plan'))    $query->whereHas('activeSubscription', fn($q) => $q->where('plan_id', $request->plan));

        $tenants = $query->paginate(20)->withQueryString();
        $plans   = DB::table('subscription_plans')->where('is_active', 1)->orderBy('sort_order')->get();

        return view('super.tenants', compact('tenants', 'plans'));
    }

    public function createTenant()
    {
        $this->guard();
        $plans = SubscriptionPlan::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return view('super.tenant-create', compact('plans'));
    }

    public function storeTenant(Request $request, TenantOnboardingService $onboarding, AuthAuditLogger $audit)
    {
        $this->guard();
        $request->merge([
            'slug' => Tenant::normalizeSlug($request->input('slug')),
            'subdomain' => Tenant::normalizeSlug($request->input('subdomain')),
        ]);

        $validated = $request->validate([
            'name'                    => ['required', 'string', 'max:150'],
            'slug'                    => Tenant::slugRules(),
            'subdomain'               => ['nullable', 'string', 'max:80', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', Rule::unique('tenants', 'subdomain')],
            'email'                   => ['required', 'email'],
            'phone'                   => ['nullable', 'string'],
            'address'                 => ['nullable', 'string'],
            'plan_id'                 => [
                'required',
                Rule::exists('subscription_plans', 'id')->where(fn ($query) => $query->where('is_active', 1)),
            ],
            'billing_cycle'           => ['required', 'in:monthly,annual'],
            'subscription_expires_at' => ['required', 'date', 'after:today'],
            'admin_name'              => ['required', 'string'],
            'admin_email'             => ['required', 'email', 'unique:users,email'],
            'admin_password'          => ['required', 'string', 'min:8'],
            'admin_employment_started_at' => ['required', 'date', 'before_or_equal:today'],
        ]);

        try {
            $tenant = DB::transaction(function () use ($validated, $onboarding, $audit) {
                $tenant = Tenant::create([
                    'name'                    => $validated['name'],
                    'slug'                    => $validated['slug'],
                    'subdomain'               => $validated['subdomain'] ?: null,
                    'email'                   => $validated['email'],
                    'phone'                   => $validated['phone'] ?? null,
                    'address'                 => $validated['address'] ?? null,
                    'status'                  => Tenant::STATUS_ACTIVE,
                    'subscription_expires_at' => $validated['subscription_expires_at'],
                    'theme_primary'           => '#071E45',
                    'theme_accent'            => '#D79A21',
                    'theme_sidebar'           => '#071E45',
                ]);

                $audit->recordForTenant($tenant, 'tenant.provisioning.started', [
                    'slug' => $tenant->slug,
                    'subdomain' => $tenant->subdomain,
                ], request(), null, auth()->user());

                $plan = SubscriptionPlan::query()
                    ->where('is_active', true)
                    ->findOrFail($validated['plan_id']);
                $amount = $validated['billing_cycle'] === 'annual' ? $plan->annual_price : $plan->monthly_price;

                DB::table('tenant_subscriptions')->insert([
                    'tenant_id'          => $tenant->id,
                    'plan_id'            => $validated['plan_id'],
                    'status'             => 'active',
                    'billing_cycle'      => $validated['billing_cycle'],
                    'amount_paid'        => $amount,
                    'starts_at'          => now()->toDateString(),
                    'expires_at'         => $validated['subscription_expires_at'],
                    'next_billing_date'  => $validated['subscription_expires_at'],
                    'created_by'         => auth()->id(),
                    'created_at'         => now(),
                    'updated_at'         => now(),
                ]);

                $this->creditReferralCommission($tenant, (float) $amount);

                $admin = User::create([
                    'tenant_id'  => $tenant->id,
                    'name'       => $validated['admin_name'],
                    'email'      => $validated['admin_email'],
                    'password'   => Hash::make($validated['admin_password']),
                    'role'       => 'admin',
                    'is_super_admin' => false,
                    'is_active'  => true,
                    'employment_status' => User::STAFF_STATUS_ACTIVE,
                    'employment_started_at' => $validated['admin_employment_started_at'],
                    'status_changed_at' => now(),
                ]);
                $admin->assignRole('admin');

                if (Schema::hasTable('staff_work_histories')) {
                    StaffWorkHistory::create([
                        'tenant_id' => $tenant->id,
                        'user_id' => $admin->id,
                        'position_title' => 'School Administrator',
                        'functional_role' => 'admin',
                        'employment_type' => 'full_time',
                        'appointment_type' => 'initial_admin',
                        'start_date' => $validated['admin_employment_started_at'],
                        'change_type' => 'appointment',
                        'reason' => 'Initial tenant administrator provisioned.',
                        'recorded_by' => auth()->id(),
                        'approved_by' => auth()->id(),
                        'approved_at' => now(),
                    ]);
                }

                $audit->recordForTenant($tenant, 'tenant.provisioning.tenant_created', [
                    'slug' => $tenant->slug,
                    'status' => $tenant->status,
                ], request(), null, auth()->user());
                $audit->recordForTenant($tenant, 'tenant.provisioning.administrator_created', [
                    'admin_user_id' => $admin->id,
                    'admin_email_hash' => hash('sha256', strtolower($admin->email)),
                ], request(), null, auth()->user());

                $onboarding->createProvisioningDefaults($tenant);
                $audit->recordForTenant($tenant, 'tenant.provisioning.default_settings_created', [
                    'defaults' => ['school_settings', 'admission_portal_settings'],
                ], request(), null, auth()->user());

                return $tenant;
            });
        } catch (\Throwable $e) {
            if (Schema::hasTable('audit_logs')) {
                AuditLog::create([
                    'tenant_id' => null,
                    'actor_user_id' => auth()->id(),
                    'auditable_type' => Tenant::class,
                    'auditable_id' => 0,
                    'action' => 'tenant.provisioning.failed',
                    'old_values' => [],
                    'new_values' => [
                        'slug' => $validated['slug'] ?? null,
                        'admin_email_hash' => isset($validated['admin_email']) ? hash('sha256', strtolower($validated['admin_email'])) : null,
                    ],
                    'reason' => class_basename($e),
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                ]);
            }

            throw $e;
        }

        try {
            $tenant->notifyAdmins(new \App\Notifications\Tenant\TenantWelcomeNotification(
                $tenant,
                $tenant->subscription_expires_at?->format('d M Y')
            ));
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('Tenant welcome notification failed: ' . $e->getMessage());
        }

        return redirect()
            ->route('super.tenant.show', $tenant)
            ->with('success', 'School provisioned successfully. The school portal and login links are now available below.');
    }

    public function showTenant(Tenant $tenant, TenantOnboardingService $onboarding)
    {
        $this->guard();
        $tenant->load(['users', 'students']);
        $subscriptions = DB::table('tenant_subscriptions')
                           ->join('subscription_plans', 'subscription_plans.id', '=', 'tenant_subscriptions.plan_id')
                           ->select('tenant_subscriptions.*', 'subscription_plans.name as plan_name')
                           ->where('tenant_id', $tenant->id)
                           ->orderByDesc('created_at')->get();
        $payments = DB::table('platform_payments')->where('tenant_id', $tenant->id)
                      ->orderByDesc('created_at')->get();
        $plans = DB::table('subscription_plans')->where('is_active', 1)->orderBy('sort_order')->get();
        $onboardingStatus = $onboarding->status($tenant);

        return view('super.tenant-show', compact('tenant', 'subscriptions', 'payments', 'plans', 'onboardingStatus'));
    }

    public function editTenant(Tenant $tenant, TenantUrlGenerator $urls, TenantHostResolver $hosts)
    {
        $this->guard();

        return view('super.tenant-edit', [
            'tenant' => $tenant,
            'statuses' => $this->tenantStatusValues(),
            'urls' => $this->tenantManagementUrls($tenant, $urls, $hosts),
        ]);
    }

    public function updateTenant(
        Request $request,
        Tenant $tenant,
        TenantHostResolver $hosts,
        TenantUrlGenerator $urls,
        TenantOnboardingService $onboarding,
        AuthAuditLogger $audit
    ) {
        $this->guard();

        $request->merge([
            'slug' => Tenant::normalizeSlug($request->input('slug')),
            'subdomain' => $request->filled('subdomain') ? Tenant::normalizeSlug($request->input('subdomain')) : null,
            'custom_domain' => $request->filled('custom_domain')
                ? strtolower(trim((string) $request->input('custom_domain')))
                : null,
        ]);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'slug' => Tenant::slugRules($tenant->id),
            'subdomain' => [
                'nullable',
                'string',
                'max:80',
                'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                Rule::unique('tenants', 'subdomain')->ignore($tenant->id),
            ],
            'email' => ['nullable', 'email', 'max:150'],
            'phone' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string', 'max:255'],
            'status' => ['required', Rule::in($this->tenantStatusValues())],
            'subscription_expires_at' => ['nullable', 'date'],
            'motto' => ['nullable', 'string', 'max:255'],
            'logo_path' => ['nullable', 'string', 'max:255', function ($attribute, $value, $fail) {
                $path = strtolower((string) $value);
                if (str_contains($path, '<') || str_contains($path, '>') || str_contains($path, 'javascript:') || str_contains($path, 'data:')) {
                    $fail('The logo path must be a safe relative asset path.');
                }
            }],
            'custom_domain' => ['nullable', 'string', 'max:200', function ($attribute, $value, $fail) use ($hosts, $tenant) {
                if (!$value) {
                    return;
                }

                $normalized = $hosts->validateCustomDomain($value);
                if (!$normalized) {
                    $fail('Enter a valid local custom domain that is not a central EduCore host.');
                    return;
                }

                $taken = Tenant::query()
                    ->whereRaw('LOWER(custom_domain) = ?', [$normalized])
                    ->whereKeyNot($tenant->id)
                    ->exists();

                if ($taken) {
                    $fail('This custom domain is already assigned to another school.');
                }
            }],
        ]);

        if (!empty($validated['custom_domain'])) {
            $validated['custom_domain'] = $hosts->validateCustomDomain($validated['custom_domain']);
        }

        if (($validated['status'] ?? null) === Tenant::STATUS_ACTIVE && $tenant->status !== Tenant::STATUS_ACTIVE) {
            $status = $onboarding->status($tenant);
            $audit->recordForTenant($tenant, 'tenant.onboarding.activation_attempted', [
                'source' => 'super_tenant_edit',
                'blocking_count' => count($status->blocking_items),
                'warning_count' => count($status->warning_items),
            ], $request, null, auth()->user());

            if (!$status->can_activate) {
                $audit->recordForTenant($tenant, 'tenant.onboarding.activation_denied', [
                    'source' => 'super_tenant_edit',
                    'blocking_items' => $status->blocking_items,
                ], $request, 'readiness_blocking_items', auth()->user());

                return back()
                    ->withInput()
                    ->withErrors(['status' => 'This tenant cannot be activated until onboarding blocking items are resolved.']);
            }
        }

        $auditFields = $this->tenantAuditFields();
        $before = $tenant->only($auditFields);

        DB::transaction(function () use ($tenant, $validated, $auditFields, $request, $before) {
            $updates = collect($validated)
                ->only($auditFields)
                ->map(fn ($value) => $value === '' ? null : $value)
                ->all();

            $customDomainChanged = array_key_exists('custom_domain', $updates)
                && ($updates['custom_domain'] ?? null) !== $tenant->custom_domain;

            if ($customDomainChanged) {
                $updates['domain_verified'] = false;
            }

            $tenant->update($updates);
            $tenant->refresh();

            if (Schema::hasTable('audit_logs')) {
                AuditLog::create([
                    'tenant_id' => $tenant->id,
                    'actor_user_id' => auth()->id(),
                    'auditable_type' => Tenant::class,
                    'auditable_id' => $tenant->id,
                    'action' => 'tenant.updated',
                    'old_values' => $before,
                    'new_values' => $tenant->only($auditFields),
                    'reason' => 'super_admin_tenant_edit',
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                ]);
            }
        });

        $tenant->refresh();

        return redirect()
            ->route('super.tenant.edit', $tenant)
            ->with('success', 'School details updated. The portal links below reflect the current slug, subdomain and verified domain state.')
            ->with('tenant_urls', $this->tenantManagementUrls($tenant, $urls, $hosts));
    }

    // ═══════════════════════════════════════════════════════════════
    // SUBSCRIPTION MANAGEMENT
    // ═══════════════════════════════════════════════════════════════
    public function subscriptions(Request $request)
    {
        $this->guard();
        $query = DB::table('tenant_subscriptions')
                   ->join('tenants', 'tenants.id', '=', 'tenant_subscriptions.tenant_id')
                   ->join('subscription_plans', 'subscription_plans.id', '=', 'tenant_subscriptions.plan_id')
                   ->select('tenant_subscriptions.*', 'tenants.name as school_name', 'subscription_plans.name as plan_name')
                   ->orderByDesc('tenant_subscriptions.created_at');

        if ($request->filled('status')) $query->where('tenant_subscriptions.status', $request->status);
        $subscriptions = $query->paginate(20)->withQueryString();

        return view('super.subscriptions', compact('subscriptions'));
    }

    public function renewSubscription(Request $request, Tenant $tenant)
    {
        $this->guard();
        $validated = $request->validate([
            'plan_id'       => ['required', 'exists:subscription_plans,id'],
            'billing_cycle' => ['required', 'in:monthly,annual'],
            'expires_at'    => ['required', 'date', 'after:today'],
            'amount_paid'   => ['required', 'numeric', 'min:0'],
            'payment_method'=> ['required', 'string'],
            'notes'         => ['nullable', 'string'],
        ]);

        DB::transaction(function () use ($tenant, $validated) {
            // Create new subscription
            $subId = DB::table('tenant_subscriptions')->insertGetId([
                'tenant_id'      => $tenant->id,
                'plan_id'        => $validated['plan_id'],
                'status'         => 'active',
                'billing_cycle'  => $validated['billing_cycle'],
                'amount_paid'    => $validated['amount_paid'],
                'starts_at'      => now()->toDateString(),
                'expires_at'     => $validated['expires_at'],
                'payment_method' => $validated['payment_method'],
                'notes'          => $validated['notes'] ?? null,
                'created_by'     => auth()->id(),
                'created_at'     => now(),
                'updated_at'     => now(),
            ]);

            // Record payment
            if ($validated['amount_paid'] > 0) {
                DB::table('platform_payments')->insert([
                    'tenant_id'       => $tenant->id,
                    'subscription_id' => $subId,
                    'reference'       => 'PAY-'.strtoupper(Str::random(10)),
                    'amount'          => $validated['amount_paid'],
                    'status'          => 'confirmed',
                    'payment_method'  => $validated['payment_method'],
                    'description'     => 'Subscription renewal - '.$validated['billing_cycle'],
                    'confirmed_by'    => auth()->id(),
                    'paid_at'         => now(),
                    'created_at'      => now(),
                    'updated_at'      => now(),
                ]);
            }

            $this->creditReferralCommission($tenant, (float) $validated['amount_paid']);

            // Update tenant expiry and status
            $tenant->update([
                'status'                  => 'active',
                'subscription_expires_at' => $validated['expires_at'],
            ]);
        });

        return back()->with('success', 'Subscription renewed and payment recorded.');
    }

    // ═══════════════════════════════════════════════════════════════
    // SUBSCRIPTION PLANS
    // ═══════════════════════════════════════════════════════════════
    public function plans()
    {
        $this->guard();
        $plans = \App\Models\SubscriptionPlan::orderBy('sort_order')->get();
        return view('super.plans', compact('plans'));
    }

    public function storePlan(Request $request)
    {
        $this->guard();
        $validated = $request->validate([
            'name'          => ['required', 'string', 'max:100'],
            'slug'          => ['required', 'string', 'max:100', 'unique:subscription_plans,slug', 'regex:/^[a-z0-9-]+$/'],
            'monthly_price' => ['required', 'numeric', 'min:0'],
            'annual_price'  => ['required', 'numeric', 'min:0'],
            'max_students'  => ['required', 'integer', 'min:1'],
            'max_staff'     => ['required', 'integer', 'min:1'],
            'description'   => ['nullable', 'string', 'max:500'],
            'features'      => ['nullable', 'array'],
            'features.*'    => ['string'],
        ]);

        $features = $validated['features'] ?? [];
        // Derive legacy boolean flags from features array
        $hasCbt = in_array('cbt', $features, true);
        $hasSms = in_array('sms', $features, true);

        DB::table('subscription_plans')->insert([
            'name'          => $validated['name'],
            'slug'          => $validated['slug'],
            'description'   => $validated['description'] ?? null,
            'monthly_price' => $validated['monthly_price'],
            'annual_price'  => $validated['annual_price'],
            'max_students'  => $validated['max_students'],
            'max_staff'     => $validated['max_staff'],
            'has_cbt'       => $hasCbt,
            'has_sms'       => $hasSms,
            'features'      => json_encode($features),
            'is_active'     => true,
            'sort_order'    => DB::table('subscription_plans')->count() + 1,
            'created_at'    => now(),
            'updated_at'    => now(),
        ]);

        return back()->with('success', "Plan \"{$validated['name']}\" created successfully.");
    }

    public function updatePlan(Request $request, $planId)
    {
        $this->guard();
        $validated = $request->validate([
            'name'          => ['required', 'string', 'max:100'],
            'monthly_price' => ['required', 'numeric', 'min:0'],
            'annual_price'  => ['required', 'numeric', 'min:0'],
            'max_students'  => ['required', 'integer', 'min:1'],
            'max_staff'     => ['required', 'integer', 'min:1'],
            'description'   => ['nullable', 'string', 'max:500'],
            'is_active'     => ['boolean'],
            'features'      => ['nullable', 'array'],
            'features.*'    => ['string'],
        ]);

        $features = $validated['features'] ?? [];
        $hasCbt   = in_array('cbt', $features, true);
        $hasSms   = in_array('sms', $features, true);

        DB::table('subscription_plans')->where('id', $planId)->update([
            'name'          => $validated['name'],
            'description'   => $validated['description'] ?? null,
            'monthly_price' => $validated['monthly_price'],
            'annual_price'  => $validated['annual_price'],
            'max_students'  => $validated['max_students'],
            'max_staff'     => $validated['max_staff'],
            'has_cbt'       => $hasCbt,
            'has_sms'       => $hasSms,
            'features'      => json_encode($features),
            'is_active'     => $request->boolean('is_active', true),
            'updated_at'    => now(),
        ]);

        return back()->with('success', "Plan updated successfully.");
    }

    // ═══════════════════════════════════════════════════════════════
    // PAYMENTS / REVENUE
    // ═══════════════════════════════════════════════════════════════
    public function payments(Request $request)
    {
        $this->guard();
        $query = DB::table('platform_payments')
                   ->join('tenants', 'tenants.id', '=', 'platform_payments.tenant_id')
                   ->select('platform_payments.*', 'tenants.name as school_name')
                   ->orderByDesc('platform_payments.created_at');

        if ($request->filled('status')) $query->where('platform_payments.status', $request->status);

        $payments = $query->paginate(25)->withQueryString();

        $revenue = [
            'today'     => DB::table('platform_payments')->where('status','confirmed')->whereDate('paid_at', today())->sum('amount'),
            'this_month'=> DB::table('platform_payments')->where('status','confirmed')->whereMonth('paid_at', now()->month)->sum('amount'),
            'this_year' => DB::table('platform_payments')->where('status','confirmed')->whereYear('paid_at', now()->year)->sum('amount'),
            'total'     => DB::table('platform_payments')->where('status','confirmed')->sum('amount'),
        ];

        return view('super.payments', compact('payments', 'revenue'));
    }

    // ═══════════════════════════════════════════════════════════════
    // DELETE TENANT
    // ═══════════════════════════════════════════════════════════════
    public function destroyTenant(Tenant $tenant)
    {
        $this->guard();

        DB::transaction(function () use ($tenant) {
            // Delete all related data
            DB::table('tenant_subscriptions')->where('tenant_id', $tenant->id)->delete();
            DB::table('platform_payments')->where('tenant_id', $tenant->id)->delete();
            DB::table('platform_invoices')->where('tenant_id', $tenant->id)->delete();
            DB::table('audit_logs')->where('tenant_id', $tenant->id)->delete();

            // Delete users belonging to this tenant (cascades model_has_roles via observer or manually)
            $userIds = DB::table('users')->where('tenant_id', $tenant->id)->pluck('id');
            if ($userIds->isNotEmpty()) {
                DB::table('model_has_roles')->whereIn('model_id', $userIds)->where('model_type', 'App\\Models\\User')->delete();
            }
            DB::table('users')->where('tenant_id', $tenant->id)->delete();

            // Hard delete the tenant (uses SoftDeletes, forceDelete removes permanently)
            $tenant->forceDelete();
        });

        return redirect()->route('super.tenants')->with('success', 'School and all associated data have been permanently deleted.');
    }

    // ═══════════════════════════════════════════════════════════════
    // TOGGLE + IMPERSONATE
    // ═══════════════════════════════════════════════════════════════
    public function toggleTenant(Request $request, Tenant $tenant, TenantOnboardingService $onboarding, AuthAuditLogger $audit)
    {
        $this->guard();
        $request->validate(['status' => ['required', 'in:active,suspended,subscription_expired']]);

        if ($request->status === Tenant::STATUS_ACTIVE) {
            $status = $onboarding->status($tenant);
            $audit->recordForTenant($tenant, 'tenant.onboarding.activation_attempted', [
                'blocking_count' => count($status->blocking_items),
                'warning_count' => count($status->warning_items),
            ], $request, null, auth()->user());

            if (!$status->can_activate) {
                $audit->recordForTenant($tenant, 'tenant.onboarding.activation_denied', [
                    'blocking_items' => $status->blocking_items,
                ], $request, 'readiness_blocking_items', auth()->user());

                return back()->withErrors(['status' => 'This tenant cannot be activated until onboarding blocking items are resolved.']);
            }

            $audit->recordForTenant($tenant, 'tenant.onboarding.activation_allowed', [
                'warning_count' => count($status->warning_items),
            ], $request, null, auth()->user());
        }

        $previousStatus = $tenant->status;
        $tenant->update(['status' => $request->status]);

        if ($request->status === Tenant::STATUS_SUSPENDED && $previousStatus !== Tenant::STATUS_SUSPENDED) {
            try {
                $tenant->notifyAdmins(new \App\Notifications\Tenant\TenantSuspendedNotification($tenant, reactivated: false));
            } catch (\Throwable $e) {
                \Log::error("Tenant suspended notification failed for tenant {$tenant->id}: " . $e->getMessage());
            }
        } elseif ($request->status === Tenant::STATUS_ACTIVE && $previousStatus === Tenant::STATUS_SUSPENDED) {
            try {
                $tenant->notifyAdmins(new \App\Notifications\Tenant\TenantSuspendedNotification($tenant, reactivated: true));
            } catch (\Throwable $e) {
                \Log::error("Tenant reactivated notification failed for tenant {$tenant->id}: " . $e->getMessage());
            }
        }

        return back()->with('success', "Status updated to: {$request->status}");
    }

    private function tenantStatusValues(): array
    {
        return [
            Tenant::STATUS_PENDING,
            Tenant::STATUS_ACTIVE,
            Tenant::STATUS_SUSPENDED,
            Tenant::STATUS_SUBSCRIPTION_EXPIRED,
        ];
    }

    private function tenantAuditFields(): array
    {
        return [
            'name',
            'slug',
            'subdomain',
            'email',
            'phone',
            'address',
            'status',
            'subscription_expires_at',
            'motto',
            'logo_path',
            'custom_domain',
            'domain_verified',
        ];
    }

    private function tenantManagementUrls(Tenant $tenant, TenantUrlGenerator $urls, TenantHostResolver $hosts): array
    {
        $scheme = config('tenancy.scheme', 'http');
        $localKey = $tenant->subdomain ?: $tenant->slug;
        $localHost = $localKey ? $localKey . '.' . $hosts->localBaseDomain() : null;

        return [
            'school_portal' => $urls->landing($tenant),
            'school_login' => $urls->login($tenant),
            'admissions' => $urls->apply($tenant),
            'local_subdomain' => $localHost ? "{$scheme}://{$localHost}" : null,
            'local_subdomain_login' => $localHost ? "{$scheme}://{$localHost}/login" : null,
            'account_status' => $urls->accountStatus($tenant),
            'preferred_login' => $urls->login($tenant),
            'custom_domain' => $tenant->custom_domain && $tenant->domain_verified
                ? $urls->landing($tenant)
                : null,
        ];
    }

    public function impersonate(Tenant $tenant)
    {
        $this->guard();
        $admin = User::where('tenant_id', $tenant->id)
            ->whereIn('role', User::roleAliasesFor('admin'))
            ->first();
        if (!$admin) return back()->withErrors(['error' => 'No admin found for this school.']);

        session(['impersonating_tenant_id' => $tenant->id, 'super_admin_id' => auth()->id()]);
        auth()->login($admin);
        return redirect()->route('dashboard')->with('info', 'Now viewing as: '.$tenant->name);
    }

    public function stopImpersonating()
    {
        $superAdminId = session('super_admin_id');
        session()->forget(['impersonating_tenant_id', 'super_admin_id']);
        if ($superAdminId) auth()->loginUsingId($superAdminId);
        return redirect()->route('super.dashboard');
    }

    // ═══════════════════════════════════════════════════════════════
    // PLATFORM SETTINGS
    // ═══════════════════════════════════════════════════════════════
    public function paymentGateways()
    {
        $this->guard();
        $keys = [
            'paystack_public_key', 'paystack_secret_key', 'paystack_is_live',
            'monnify_api_key', 'monnify_secret_key', 'monnify_contract_code', 'monnify_is_live',
            'flutterwave_public_key', 'flutterwave_secret_key', 'flutterwave_is_live',
        ];
        $settings = DB::table('platform_settings')
            ->whereIn('key', $keys)
            ->pluck('value', 'key');

        return view('super.payment-gateways', compact('settings'));
    }

    public function savePaymentGateways(Request $request)
    {
        $this->guard();

        $allowed = [
            'paystack_public_key', 'paystack_secret_key', 'paystack_is_live',
            'monnify_api_key', 'monnify_secret_key', 'monnify_contract_code', 'monnify_is_live',
            'flutterwave_public_key', 'flutterwave_secret_key', 'flutterwave_is_live',
        ];

        foreach ($request->input('settings', []) as $key => $value) {
            if (!in_array($key, $allowed, true)) continue;
            // Only update secret keys if a non-empty value was submitted (avoid wiping with blank)
            if (in_array($key, ['paystack_secret_key', 'monnify_secret_key', 'flutterwave_secret_key'])
                && ($value === '' || $value === null)) {
                continue;
            }
            DB::table('platform_settings')->updateOrInsert(
                ['key' => $key],
                ['value' => $value ?? '', 'updated_at' => now()]
            );
        }

        // Handle checkboxes (unchecked = not submitted = set to 0)
        foreach (['paystack_is_live', 'monnify_is_live', 'flutterwave_is_live'] as $flag) {
            DB::table('platform_settings')->updateOrInsert(
                ['key' => $flag],
                ['value' => $request->has("settings.{$flag}") ? '1' : '0', 'updated_at' => now()]
            );
        }

        return back()->with('success', 'Payment gateway settings saved successfully.');
    }

    public function settings()
    {
        $this->guard();
        try {
            $settings = DB::table('platform_settings')
                ->orderBy('group')->orderBy('key')
                ->get()->keyBy('key');
        } catch (\Exception $e) {
            $settings = collect(); // table may not exist yet
        }
        return view('super.settings', compact('settings'));
    }

    public function saveSettings(Request $request)
    {
        $this->guard();
        foreach ($request->settings ?? [] as $key => $value) {
            DB::table('platform_settings')->updateOrInsert(
                ['key' => $key],
                ['value' => $value, 'updated_at' => now()]
            );
        }
        return back()->with('success', 'Settings saved.');
    }

    // ═══════════════════════════════════════════════════════════════
    // EXTEND SUBSCRIPTION (legacy)
    // ═══════════════════════════════════════════════════════════════
    public function extendSubscription(Request $request, Tenant $tenant)
    {
        $this->guard();
        $request->validate(['expires_at' => ['required', 'date', 'after:today']]);
        $tenant->update(['subscription_expires_at' => $request->expires_at, 'status' => 'active']);
        return back()->with('success', 'Subscription extended.');
    }

    // ── Super Admin Analytics ─────────────────────────────────────
    public function analytics()
    {
        $this->guard();
        $tenants = \App\Models\Tenant::withCount([
            'users',
        ])->with('activeSubscription.plan')->latest()->get();

        $growth = \App\Models\Tenant::selectRaw('MONTH(created_at) as month, YEAR(created_at) as year, COUNT(*) as count')
            ->whereYear('created_at', date('Y'))
            ->groupBy('year','month')->orderBy('year')->orderBy('month')->get();

        $planDist = \App\Models\TenantSubscription::where('status','active')
            ->join('subscription_plans','subscription_plans.id','=','tenant_subscriptions.plan_id')
            ->selectRaw('subscription_plans.name as plan, COUNT(*) as count')
            ->groupBy('subscription_plans.name')->get();

        $revenue = \App\Models\TenantSubscription::selectRaw('MONTH(created_at) as month, YEAR(created_at) as year, SUM(amount_paid) as total')
            ->whereYear('created_at', date('Y'))
            ->groupBy('year','month')->orderBy('year')->orderBy('month')->get();

        return view('super.analytics', compact('tenants','growth','planDist','revenue'));
    }

    // ── Send Renewal Reminders ────────────────────────────────────
    public function sendRenewalReminders()
    {
        $this->guard();
        $expiring = \App\Models\TenantSubscription::where('status','active')
            ->where('expires_at','<=', now()->addDays(30))
            ->where('expires_at','>=', now())
            ->with(['tenant'])
            ->get();

        $sent = 0;
        foreach ($expiring as $sub) {
            if (!$sub->tenant) {
                continue;
            }

            $days = (int) now()->diffInDays($sub->expires_at);

            try {
                $sub->tenant->notifyAdmins(new \App\Notifications\Tenant\SubscriptionExpiringNotification(
                    $sub->tenant,
                    $sub->expires_at->format('d M Y'),
                    $days
                ));
                $sent++;
            } catch (\Throwable $e) {
                \Log::error("Renewal reminder failed for tenant {$sub->tenant->id}: " . $e->getMessage());
            }
        }

        return back()->with('success', "Renewal reminders sent to {$sent} schools.");
    }

    public function extendTenant(Request $request, \App\Models\Tenant $tenant)
    {
        abort_unless(auth()->user()?->isSuperAdmin(), 403);
        $data = $request->validate(['months' => ['required','integer','min:1','max:24']]);
        $current = $tenant->subscription_expires_at ?? now();
        $newExpiry = \Carbon\Carbon::parse($current)->addMonths($data['months']);
        $tenant->update(['subscription_expires_at' => $newExpiry]);

        try {
            $tenant->notifyAdmins(new \App\Notifications\Tenant\SubscriptionRenewedNotification($tenant, $newExpiry->format('d M Y')));
        } catch (\Throwable $e) {
            \Log::error("Subscription renewed notification failed for tenant {$tenant->id}: " . $e->getMessage());
        }

        return back()->with('success', "Subscription extended by {$data['months']} month(s).");
    }

    public function renewTenant(Request $request, \App\Models\Tenant $tenant)
    {
        $data = $request->validate(['plan_id' => ['required','exists:subscription_plans,id']]);
        $plan = \App\Models\SubscriptionPlan::findOrFail($data['plan_id']);
        $newExpiry = now()->addMonths($plan->duration_months);
        $tenant->update([
            'subscription_expires_at' => $newExpiry,
            'is_active' => true,
        ]);
        \App\Models\TenantSubscription::create([
            'tenant_id' => $tenant->id, 'plan_id' => $plan->id,
            'starts_at' => now(), 'ends_at' => $newExpiry,
            'amount_paid' => $plan->price, 'status' => 'active',
        ]);

        try {
            $tenant->notifyAdmins(new \App\Notifications\Tenant\SubscriptionRenewedNotification($tenant, $newExpiry->format('d M Y'), (float) $plan->price));
        } catch (\Throwable $e) {
            \Log::error("Subscription renewed notification failed for tenant {$tenant->id}: " . $e->getMessage());
        }

        return back()->with('success', "Subscription renewed for {$plan->duration_months} month(s).");
    }

    // ═══════════════════════════════════════════════════════════════
    // BILLING & INVOICING
    // ═══════════════════════════════════════════════════════════════
    public function billingInvoices(Request $request)
    {
        $this->guard();
        $invoices = DB::table('platform_invoices')
            ->join('tenants','tenants.id','=','platform_invoices.tenant_id')
            ->join('subscription_plans','subscription_plans.id','=','platform_invoices.plan_id')
            ->select('platform_invoices.*','tenants.name as school_name','subscription_plans.name as plan_name')
            ->when($request->filled('status'), fn($q) => $q->where('platform_invoices.status', $request->status))
            ->when($request->filled('tenant_id'), fn($q) => $q->where('platform_invoices.tenant_id', $request->tenant_id))
            ->orderByDesc('platform_invoices.created_at')
            ->paginate(20)->withQueryString();

        $tenants = Tenant::orderBy('name')->get();
        $stats = [
            'total_invoiced' => DB::table('platform_invoices')->sum('amount'),
            'total_paid'     => DB::table('platform_invoices')->where('status','paid')->sum('amount'),
            'total_overdue'  => DB::table('platform_invoices')->where('status','overdue')->sum('amount'),
            'pending_count'  => DB::table('platform_invoices')->where('status','pending')->count(),
        ];

        return view('super.billing', compact('invoices','tenants','stats'));
    }

    public function generateInvoice(Request $request)
    {
        $this->guard();
        $data = $request->validate([
            'tenant_id'     => ['required','exists:tenants,id'],
            'plan_id'       => ['required','exists:subscription_plans,id'],
            'billing_cycle' => ['required','in:monthly,annual'],
            'due_date'      => ['required','date'],
            'notes'         => ['nullable','string'],
        ]);

        $tenant  = Tenant::findOrFail($data['tenant_id']);
        $plan    = DB::table('subscription_plans')->find($data['plan_id']);
        $amount  = $data['billing_cycle'] === 'annual' ? $plan->annual_price : $plan->monthly_price;
        $ref     = 'INV-'.strtoupper(Str::random(8));

        DB::table('platform_invoices')->insert([
            'tenant_id'      => $data['tenant_id'],
            'plan_id'        => $data['plan_id'],
            'invoice_number' => $ref,
            'amount'         => $amount,
            'billing_cycle'  => $data['billing_cycle'],
            'status'         => 'pending',
            'due_date'       => $data['due_date'],
            'notes'          => $data['notes'],
            'created_at'     => now(),
            'updated_at'     => now(),
        ]);

        return back()->with('success', "Invoice {$ref} generated for {$tenant->name} — ₦".number_format($amount).".");
    }

    public function markInvoicePaid(Request $request, $invoiceId)
    {
        $this->guard();
        $data = $request->validate([
            'payment_method' => ['required','string'],
            'payment_ref'    => ['nullable','string'],
        ]);

        $invoice = DB::table('platform_invoices')->find($invoiceId);
        if (!$invoice) abort(404);

        DB::table('platform_invoices')->where('id',$invoiceId)->update([
            'status'         => 'paid',
            'paid_at'        => now(),
            'payment_method' => $data['payment_method'],
            'payment_ref'    => $data['payment_ref'],
            'updated_at'     => now(),
        ]);

        // Record in platform_payments too
        DB::table('platform_payments')->insert([
            'tenant_id'       => $invoice->tenant_id,
            'reference'       => $data['payment_ref'] ?? 'PAY-'.strtoupper(Str::random(8)),
            'amount'          => $invoice->amount,
            'status'          => 'confirmed',
            'payment_method'  => $data['payment_method'],
            'description'     => 'Invoice '.$invoice->invoice_number.' payment',
            'confirmed_by'    => auth()->id(),
            'paid_at'         => now(),
            'created_at'      => now(),
            'updated_at'      => now(),
        ]);

        // Extend tenant subscription
        $tenant = Tenant::find($invoice->tenant_id);
        if ($tenant) {
            $months = $invoice->billing_cycle === 'annual' ? 12 : 1;
            $from   = max(now(), \Carbon\Carbon::parse($tenant->subscription_expires_at));
            $newExpiry = $from->addMonths($months);
            $tenant->update([
                'subscription_expires_at' => $newExpiry,
                'status' => 'active',
            ]);

            $this->creditReferralCommission($tenant, (float) $invoice->amount);

            try {
                $tenant->notifyAdmins(new \App\Notifications\Tenant\SubscriptionRenewedNotification($tenant, $newExpiry->format('d M Y'), (float) $invoice->amount));
            } catch (\Throwable $e) {
                \Log::error("Subscription renewed notification failed for tenant {$tenant->id}: " . $e->getMessage());
            }
        }

        return back()->with('success', 'Invoice marked as paid and subscription extended.');
    }

    public function invoicePdf($invoiceId)
    {
        $this->guard();
        $invoice = DB::table('platform_invoices')
            ->join('tenants','tenants.id','=','platform_invoices.tenant_id')
            ->join('subscription_plans','subscription_plans.id','=','platform_invoices.plan_id')
            ->select('platform_invoices.*','tenants.name as school_name','tenants.address as school_address','tenants.email as school_email','subscription_plans.name as plan_name')
            ->where('platform_invoices.id',$invoiceId)->first();

        if (!$invoice) abort(404);

        $superSettings = DB::table('platform_settings')->pluck('value','key');

        return view('super.invoice-pdf', compact('invoice','superSettings'));
    }
    // ── Tenant Self-Service Subscription Payment ─────────────────────
    // School admin sees their invoice and pays online via Paystack
    public function tenantPayInitiate($invoiceId)
    {
        $invoice = DB::table('platform_invoices')->where('id', $invoiceId)->first();
        if (!$invoice) abort(404);

        // Only the tenant or super admin can pay this invoice
        $user = auth()->user();
        if (!$user->is_super_admin && $user->tenant_id != $invoice->tenant_id) abort(403);

        if ($invoice->status === 'paid') {
            return back()->withErrors(['error' => 'This invoice is already paid.']);
        }

        // Platform-level gateway keys (stored in platform_settings key-value)
        $keys = DB::table('platform_settings')
            ->whereIn('key', [
                'paystack_public_key', 'paystack_secret_key', 'paystack_is_live',
                'monnify_api_key', 'monnify_secret_key', 'monnify_contract_code', 'monnify_is_live',
            ])
            ->pluck('value', 'key');

        $publicKey       = $keys['paystack_public_key'] ?? null;
        $paystackEnabled = !empty($publicKey);
        $monnifyEnabled  = !empty($keys['monnify_api_key'])
            && !empty($keys['monnify_secret_key'])
            && !empty($keys['monnify_contract_code']);

        if (!$paystackEnabled && !$monnifyEnabled) {
            return back()->withErrors(['error' => 'Online payment is not configured. Add Paystack or Monnify credentials in Super Admin → Settings.']);
        }

        $settings  = (object) [
            'paystack_public_key' => $publicKey,
            'paystack_secret_key' => $keys['paystack_secret_key'] ?? null,
        ];

        $tenant    = Tenant::find($invoice->tenant_id);
        $reference = 'SUB-' . strtoupper(\Illuminate\Support\Str::random(10));
        $email     = optional($tenant->users()->whereIn('role', User::roleAliasesFor('admin'))->first())->email ?? $user->email ?? 'admin@school.ng';
        $amount    = $invoice->amount;

        // Store reference on invoice
        DB::table('platform_invoices')->where('id', $invoiceId)
            ->update(['payment_reference' => $reference, 'updated_at' => now()]);

        return view('super.pay-subscription', compact('invoice', 'tenant', 'reference', 'amount', 'email', 'settings', 'paystackEnabled', 'monnifyEnabled'));
    }

    public function tenantPayCallback(\Illuminate\Http\Request $request)
    {
        $reference = $request->get('reference');
        $invoice   = DB::table('platform_invoices')->where('payment_reference', $reference)->first();

        if (!$invoice) {
            return redirect()->route('super.billing')->withErrors(['error' => 'Payment reference not found.']);
        }

        // Verify with Paystack using platform keys
        $secretKey = optional(DB::table('platform_settings')->where('key','paystack_secret_key')->first())->value;
        $verified  = false;

        if ($secretKey) {
            $response = \Illuminate\Support\Facades\Http::withToken($secretKey)
                ->get("https://api.paystack.co/transaction/verify/{$reference}");
            $verified = $response->successful() && $response->json('data.status') === 'success';
        }

        if ($verified) {
            $tenant = $this->creditInvoicePayment($invoice, $reference, 'paystack_online');
            return redirect()->route('super.billing')
                ->with('success', "Payment confirmed! {$tenant->name} subscription extended to {$tenant->subscription_expires_at->format('d M Y')}.");
        }

        return redirect()->route('super.billing')
            ->withErrors(['error' => 'Payment could not be verified. Please try again or contact support.']);
    }

    /**
     * Mark an invoice paid, record the payment, and extend the tenant's subscription.
     * Shared by the Paystack and Monnify verification callbacks.
     */
    private function creditInvoicePayment($invoice, string $reference, string $method): Tenant
    {
        $existingPayment = DB::table('platform_payments')
            ->where('invoice_id', $invoice->id)
            ->where('tenant_id', $invoice->tenant_id)
            ->exists();

        if ($existingPayment) {
            return Tenant::find($invoice->tenant_id);
        }

        DB::table('platform_invoices')->where('id', $invoice->id)
            ->update(['status' => 'paid', 'paid_at' => now(), 'updated_at' => now()]);

        DB::table('platform_payments')->insert([
            'tenant_id'  => $invoice->tenant_id,
            'invoice_id' => $invoice->id,
            'amount'     => $invoice->amount,
            'reference'  => $reference,
            'method'     => $method,
            'status'     => 'success',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $tenant = Tenant::find($invoice->tenant_id);
        $days   = $invoice->billing_cycle === 'annual' ? 365 : 30;
        $expiry = $tenant->subscription_expires_at && $tenant->subscription_expires_at->isFuture()
                  ? $tenant->subscription_expires_at->copy()->addDays($days)
                  : now()->addDays($days);

        $tenant->update([
            'status'                  => 'active',
            'subscription_expires_at' => $expiry,
        ]);

        // Deactivate any existing active subscriptions so the new plan wins ordering.
        DB::table('tenant_subscriptions')
            ->where('tenant_id', $tenant->id)
            ->where('status', 'active')
            ->update(['status' => 'expired', 'updated_at' => now()]);

        DB::table('tenant_subscriptions')->insert([
            'tenant_id'         => $tenant->id,
            'plan_id'           => $invoice->plan_id,
            'status'            => 'active',
            'billing_cycle'     => $invoice->billing_cycle,
            'amount_paid'       => $invoice->amount,
            'starts_at'         => now()->toDateString(),
            'expires_at'        => $expiry->toDateString(),
            'next_billing_date' => $expiry->toDateString(),
            'payment_method'    => $method,
            'notes'             => 'Auto-created from paid subscription invoice',
            'created_by'        => auth()->id(),
            'created_at'        => now(),
            'updated_at'        => now(),
        ]);

        $this->creditReferralCommission($tenant, (float) $invoice->amount);

        try {
            $tenant->notifyAdmins(new \App\Notifications\Tenant\SubscriptionRenewedNotification($tenant, $expiry->format('d M Y'), (float) $invoice->amount));
        } catch (\Throwable $e) {
            \Log::error("Subscription renewed notification failed for tenant {$tenant->id}: " . $e->getMessage());
        }

        return $tenant;
    }

    private function creditReferralCommission(Tenant $tenant, float $amount): void
    {
        if ($amount <= 0) {
            return;
        }

        AgentController::recordReferralCommission($tenant->id, $amount);
    }

    /**
     * Read Monnify credentials and resolve the correct API base URL (sandbox vs live).
     */
    private function monnifyConfig(): ?object
    {
        $s = DB::table('platform_settings')
            ->whereIn('key', ['monnify_api_key', 'monnify_secret_key', 'monnify_contract_code', 'monnify_is_live'])
            ->pluck('value', 'key');

        if (empty($s['monnify_api_key']) || empty($s['monnify_secret_key']) || empty($s['monnify_contract_code'])) {
            return null;
        }

        return (object) [
            'apiKey'   => $s['monnify_api_key'],
            'secret'   => $s['monnify_secret_key'],
            'contract' => $s['monnify_contract_code'],
            'base'     => (($s['monnify_is_live'] ?? '0') == '1')
                            ? 'https://api.monnify.com'
                            : 'https://sandbox.monnify.com',
        ];
    }

    private function monnifyToken(object $cfg): ?string
    {
        $auth = \Illuminate\Support\Facades\Http::withBasicAuth($cfg->apiKey, $cfg->secret)
            ->post("{$cfg->base}/api/v1/auth/login");

        return $auth->successful() ? $auth->json('responseBody.accessToken') : null;
    }

    /**
     * Start a Monnify checkout for an invoice and redirect the payer to Monnify.
     */
    public function monnifyPayInitiate($invoiceId)
    {
        $invoice = DB::table('platform_invoices')->where('id', $invoiceId)->first();
        if (!$invoice) abort(404);

        $user = auth()->user();
        if (!$user->is_super_admin && $user->tenant_id != $invoice->tenant_id) abort(403);
        if ($invoice->status === 'paid') {
            return back()->withErrors(['error' => 'This invoice is already paid.']);
        }

        $cfg = $this->monnifyConfig();
        if (!$cfg) {
            return back()->withErrors(['error' => 'Monnify is not configured. Add the API key, secret and contract code in Super Admin → Settings.']);
        }

        $token = $this->monnifyToken($cfg);
        if (!$token) {
            return back()->withErrors(['error' => 'Could not authenticate with Monnify. Please check the API key and secret.']);
        }

        $tenant    = Tenant::find($invoice->tenant_id);
        $reference = 'SUB-' . strtoupper(\Illuminate\Support\Str::random(10));
        $email     = optional($tenant->users()->whereIn('role', User::roleAliasesFor('admin'))->first())->email ?? $user->email ?? 'admin@school.ng';

        DB::table('platform_invoices')->where('id', $invoiceId)
            ->update(['payment_reference' => $reference, 'updated_at' => now()]);

        $init = \Illuminate\Support\Facades\Http::withToken($token)
            ->post("{$cfg->base}/api/v1/merchant/transactions/init-transaction", [
                'amount'             => (float) $invoice->amount,
                'customerName'       => $tenant->name,
                'customerEmail'      => $email,
                'paymentReference'   => $reference,
                'paymentDescription' => 'Subscription — ' . $tenant->name,
                'currencyCode'       => 'NGN',
                'contractCode'       => $cfg->contract,
                'redirectUrl'        => route('super.billing.pay.monnify.callback', ['reference' => $reference]),
                'paymentMethods'     => ['CARD', 'ACCOUNT_TRANSFER'],
            ]);

        $checkoutUrl = $init->successful() ? $init->json('responseBody.checkoutUrl') : null;
        if (!$checkoutUrl) {
            return back()->withErrors(['error' => 'Could not start the Monnify checkout. Please try again.']);
        }

        return redirect()->away($checkoutUrl);
    }

    /**
     * Monnify redirects back here after payment; verify the transaction then credit it.
     */
    public function monnifyPayCallback(\Illuminate\Http\Request $request)
    {
        $reference = $request->get('paymentReference') ?: $request->get('reference');
        $invoice   = DB::table('platform_invoices')->where('payment_reference', $reference)->first();

        if (!$invoice) {
            return redirect()->route('super.billing')->withErrors(['error' => 'Payment reference not found.']);
        }
        if ($invoice->status === 'paid') {
            return redirect()->route('super.billing')->with('success', 'Payment already confirmed.');
        }

        $cfg      = $this->monnifyConfig();
        $verified = false;

        if ($cfg && ($token = $this->monnifyToken($cfg))) {
            $query = \Illuminate\Support\Facades\Http::withToken($token)
                ->get("{$cfg->base}/api/v1/merchant/transactions/query", ['paymentReference' => $reference]);
            $verified = $query->successful() && $query->json('responseBody.paymentStatus') === 'PAID';
        }

        if ($verified) {
            $tenant = $this->creditInvoicePayment($invoice, $reference, 'monnify_online');
            return redirect()->route('super.billing')
                ->with('success', "Payment confirmed! {$tenant->name} subscription extended to {$tenant->subscription_expires_at->format('d M Y')}.");
        }

        return redirect()->route('super.billing')
            ->withErrors(['error' => 'Payment could not be verified yet. If you completed payment, please refresh in a moment.']);
    }

    // ── Support Tickets (school → super admin) ────────────────────
    public function supportInbox(Request $request)
    {
        $this->guard();
        $status  = $request->get('status', 'open');
        $tickets = DB::table('platform_support_tickets')
            ->leftJoin('tenants', 'tenants.id', '=', 'platform_support_tickets.tenant_id')
            ->leftJoin('users', 'users.id', '=', 'platform_support_tickets.user_id')
            ->select('platform_support_tickets.*', 'tenants.name as school_name', 'users.name as sender_name')
            ->when($status !== 'all', fn($q) => $q->where('platform_support_tickets.status', $status))
            ->orderByDesc('platform_support_tickets.created_at')
            ->paginate(25);

        return view('super.support', compact('tickets', 'status'));
    }

    public function replyTicket(Request $request, $ticketId)
    {
        $this->guard();
        $data = $request->validate(['reply' => ['required', 'string', 'max:3000']]);

        DB::table('platform_support_tickets')->where('id', $ticketId)->update([
            'admin_reply' => $data['reply'],
            'replied_by'  => auth()->id(),
            'replied_at'  => now(),
            'status'      => 'answered',
            'updated_at'  => now(),
        ]);

        return back()->with('success', 'Reply sent to school.');
    }

    public function closeTicket($ticketId)
    {
        $this->guard();
        DB::table('platform_support_tickets')->where('id', $ticketId)->update([
            'status'     => 'closed',
            'updated_at' => now(),
        ]);
        return back()->with('success', 'Ticket closed.');
    }

    // ── Broadcasts (super admin → schools) ───────────────────────
    public function broadcasts()
    {
        $this->guard();
        $broadcasts = DB::table('platform_broadcasts')
            ->leftJoin('users', 'users.id', '=', 'platform_broadcasts.created_by')
            ->select('platform_broadcasts.*', 'users.name as creator_name')
            ->orderByDesc('platform_broadcasts.created_at')
            ->paginate(20);

        return view('super.broadcasts', compact('broadcasts'));
    }

    public function storeBroadcast(Request $request)
    {
        $this->guard();
        $data = $request->validate([
            'title'      => ['required', 'string', 'max:150'],
            'body'       => ['required', 'string', 'max:5000'],
            'target'     => ['required', 'in:all,trial,active,expired'],
            'expires_at' => ['nullable', 'date', 'after:today'],
        ]);

        DB::table('platform_broadcasts')->insert([
            'title'      => $data['title'],
            'body'       => $data['body'],
            'target'     => $data['target'],
            'created_by' => auth()->id(),
            'expires_at' => isset($data['expires_at']) ? $data['expires_at'] : null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return back()->with('success', 'Broadcast sent to schools.');
    }

    public function deleteBroadcast($id)
    {
        $this->guard();
        DB::table('platform_broadcast_dismissals')->where('broadcast_id', $id)->delete();
        DB::table('platform_broadcasts')->where('id', $id)->delete();
        return back()->with('success', 'Broadcast deleted.');
    }

}
