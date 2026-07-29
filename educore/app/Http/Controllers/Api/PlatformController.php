<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Models\Tenant;
use App\Models\User;
use App\Models\PlatformAgent;
use App\Models\ApiToken;
use App\Models\AuditLog;
use App\Services\Auth\AuthAuditLogger;
use App\Services\PricingService;
use App\Services\TenantOnboardingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class PlatformController extends Controller
{
    public function dashboard(Request $request)
    {
        $this->guard($request);

        $payments = Schema::hasTable('platform_payments')
            ? DB::table('platform_payments')->where('status', 'confirmed')
            : null;
        $recentTenants = Tenant::withCount(['users', 'students'])->latest()->limit(8)->get()
            ->map(fn (Tenant $tenant) => $this->tenantData($tenant));

        return response()->json([
            'operator' => ['name' => $request->user()->name, 'role' => 'Platform Super Admin'],
            'metrics' => [
                'schools' => Tenant::count(),
                'active_schools' => Tenant::where('status', Tenant::STATUS_ACTIVE)->count(),
                'students' => Student::withoutTenantScope()->count(),
                'platform_users' => User::whereNotNull('tenant_id')->count(),
                'monthly_revenue' => $payments ? (float) (clone $payments)->whereMonth('paid_at', now()->month)->whereYear('paid_at', now()->year)->sum('amount') : 0,
                'total_revenue' => $payments ? (float) (clone $payments)->sum('amount') : 0,
            ],
            'attention' => [
                'pending' => Tenant::where('status', Tenant::STATUS_PENDING)->count(),
                'suspended' => Tenant::where('status', Tenant::STATUS_SUSPENDED)->count(),
                'expired' => Tenant::where('status', Tenant::STATUS_SUBSCRIPTION_EXPIRED)->count(),
                'expiring_soon' => Tenant::whereBetween('subscription_expires_at', [today(), today()->addDays(14)])->count(),
            ],
            'recent_schools' => $recentTenants,
        ]);
    }

    public function tenants(Request $request)
    {
        $this->guard($request);
        $query = Tenant::withCount(['users', 'students'])->latest();
        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }
        if ($request->filled('search')) {
            $search = trim($request->string('search'));
            $query->where(fn ($item) => $item->where('name', 'like', "%{$search}%")->orWhere('slug', 'like', "%{$search}%"));
        }

        return response()->json(['tenants' => $query->limit(100)->get()->map(fn (Tenant $tenant) => $this->tenantData($tenant))]);
    }

    public function billing(Request $request)
    {
        $this->guard($request);
        if (!Schema::hasTable('platform_payments')) {
            return response()->json(['summary' => ['confirmed' => 0, 'pending' => 0, 'this_month' => 0], 'payments' => []]);
        }
        $base = DB::table('platform_payments');
        $payments = (clone $base)->join('tenants', 'tenants.id', '=', 'platform_payments.tenant_id')
            ->select('platform_payments.*', 'tenants.name as school_name')
            ->latest('platform_payments.created_at')->limit(50)->get()->map(fn ($payment) => [
                'id' => $payment->id,
                'reference' => $payment->reference,
                'school' => $payment->school_name,
                'amount' => (float) $payment->amount,
                'currency' => $payment->currency,
                'status' => $payment->status,
                'method' => $payment->payment_method,
                'paid_at' => $payment->paid_at,
            ]);

        return response()->json([
            'summary' => [
                'confirmed' => (float) (clone $base)->where('status', 'confirmed')->sum('amount'),
                'pending' => (float) (clone $base)->where('status', 'pending')->sum('amount'),
                'this_month' => (float) (clone $base)->where('status', 'confirmed')->whereMonth('paid_at', now()->month)->whereYear('paid_at', now()->year)->sum('amount'),
            ],
            'payments' => $payments,
        ]);
    }

    public function plans(Request $request)
    {
        $this->guard($request);
        $plans = collect(PricingService::tiers())->values()->map(fn (array $tier, int $index) => [
            'id' => $index + 1,
            'name' => $tier['range'],
            'rate' => $tier['rate'],
            'cycle' => $tier['cycle'],
            'active' => true,
            'features' => ['All EduCore modules', 'Role-based access', 'Unlimited staff accounts'],
        ]);
        return response()->json([
            'model' => 'Pay per active student',
            'annual_discount_percent' => 0,
            'plans' => $plans,
        ]);
    }

    public function updateTenant(
        Request $request,
        Tenant $tenant,
        TenantOnboardingService $onboarding,
        AuthAuditLogger $audit
    )
    {
        $this->guard($request);

        if ($request->has('slug')) {
            $request->merge(['slug' => Tenant::normalizeSlug($request->input('slug'))]);
        }

        $slugRules = collect(Tenant::slugRules($tenant->id))
            ->reject(fn ($rule) => $rule === 'required')
            ->prepend('sometimes')
            ->all();

        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:150'],
            'slug' => $slugRules,
            'email' => ['sometimes', 'nullable', 'email', 'max:180'],
            'phone' => ['sometimes', 'nullable', 'string', 'max:50'],
            'address' => ['sometimes', 'nullable', 'string', 'max:255'],
            'status' => ['sometimes', Rule::in([
                Tenant::STATUS_ACTIVE,
                Tenant::STATUS_PENDING,
                Tenant::STATUS_SUSPENDED,
                Tenant::STATUS_SUBSCRIPTION_EXPIRED,
            ])],
            'students_capacity' => ['sometimes', 'integer', 'min:' . PricingService::FREE_THRESHOLD, 'max:1000000'],
            'subscription_expires_at' => ['sometimes', 'nullable', 'date'],
            'reason' => ['nullable', 'string', 'max:500'],
        ]);

        $reason = $data['reason'] ?? null;
        unset($data['reason']);
        $before = $tenant->only(array_keys($data));

        if (($data['status'] ?? null) === Tenant::STATUS_ACTIVE && $tenant->status !== Tenant::STATUS_ACTIVE) {
            $readiness = $onboarding->status($tenant);
            $audit->recordForTenant(
                $tenant,
                $readiness->can_activate
                    ? 'tenant.onboarding.activation_allowed'
                    : 'tenant.onboarding.activation_overridden',
                [
                    'source' => 'platform_api',
                    'blocking_items' => $readiness->blocking_items,
                    'warning_count' => count($readiness->warning_items),
                ],
                $request,
                $readiness->can_activate ? $reason : 'platform_super_admin_override',
                $request->user()
            );
        }

        DB::transaction(function () use ($tenant, $data, $before, $request, $reason) {
            $tenant->update($data);

            if (Schema::hasTable('audit_logs')) {
                AuditLog::create([
                    'tenant_id' => $tenant->id,
                    'actor_user_id' => $request->user()->id,
                    'auditable_type' => Tenant::class,
                    'auditable_id' => $tenant->id,
                    'action' => 'tenant.updated.via_api',
                    'old_values' => $before,
                    'new_values' => $tenant->fresh()->only(array_keys($data)),
                    'reason' => $reason,
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                ]);
            }
        });

        return response()->json(['message' => 'School account updated.', 'tenant' => $this->tenantData($tenant->fresh()->loadCount(['users', 'students']))]);
    }

    public function destroyTenant(Request $request, Tenant $tenant)
    {
        $this->guard($request);

        $data = $request->validate([
            'confirmation' => ['required', 'string', Rule::in([$tenant->name])],
            'current_password' => ['required', 'string'],
            'reason' => ['required', 'string', 'min:10', 'max:500'],
        ]);

        if (!Hash::check($data['current_password'], $request->user()->password)) {
            return response()->json(['message' => 'Your current password is incorrect.'], 422);
        }

        DB::transaction(function () use ($tenant, $request, $data) {
            $userIds = User::query()->where('tenant_id', $tenant->id)->pluck('id');

            if ($userIds->isNotEmpty() && Schema::hasTable('api_tokens')) {
                ApiToken::query()->whereIn('user_id', $userIds)->delete();
            }

            User::query()->where('tenant_id', $tenant->id)->update([
                'is_active' => false,
                'status_changed_at' => now(),
            ]);

            if (Schema::hasTable('audit_logs')) {
                AuditLog::create([
                    'tenant_id' => $tenant->id,
                    'actor_user_id' => $request->user()->id,
                    'auditable_type' => Tenant::class,
                    'auditable_id' => $tenant->id,
                    'action' => 'tenant.removed.via_api',
                    'old_values' => ['name' => $tenant->name, 'slug' => $tenant->slug, 'status' => $tenant->status],
                    'new_values' => ['status' => Tenant::STATUS_SUSPENDED, 'removed_at' => now()->toIso8601String()],
                    'reason' => $data['reason'],
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                ]);
            }

            $tenant->update(['status' => Tenant::STATUS_SUSPENDED]);
            $tenant->delete();
        });

        return response()->json(['message' => 'School removed. Accounts and mobile sessions were disabled; records remain recoverable for audit.']);
    }

    public function storeTenant(Request $request, TenantOnboardingService $onboarding)
    {
        $this->guard($request);
        $request->merge(['slug' => Tenant::normalizeSlug($request->input('slug'))]);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:150'], 'slug' => Tenant::slugRules(),
            'email' => ['required', 'email'], 'phone' => ['nullable', 'string', 'max:30'],
            'subscription_expires_at' => ['required', 'date', 'after:today'],
            'admin_name' => ['required', 'string', 'max:150'],
            'admin_email' => ['required', 'email', 'unique:users,email'],
            'admin_password' => ['required', 'string', 'min:8'],
        ]);

        $tenant = DB::transaction(function () use ($data, $onboarding) {
            $tenant = Tenant::create([
                'name' => $data['name'], 'slug' => $data['slug'], 'email' => $data['email'],
                'phone' => $data['phone'] ?? null, 'status' => Tenant::STATUS_ACTIVE,
                'subscription_expires_at' => $data['subscription_expires_at'],
                'theme_primary' => '#071E45', 'theme_accent' => '#D79A21', 'theme_sidebar' => '#071E45',
            ]);
            $admin = User::create([
                'tenant_id' => $tenant->id, 'name' => $data['admin_name'], 'email' => $data['admin_email'],
                'password' => Hash::make($data['admin_password']), 'role' => 'admin', 'is_super_admin' => false,
                'is_active' => true, 'employment_status' => User::STAFF_STATUS_ACTIVE,
                'employment_started_at' => today(), 'status_changed_at' => now(),
            ]);
            $admin->assignRole('admin');
            $onboarding->createProvisioningDefaults($tenant);
            return $tenant;
        });

        return response()->json(['message' => 'School registered successfully.', 'tenant' => $this->tenantData($tenant->loadCount(['users', 'students']))], 201);
    }

    public function agents(Request $request)
    {
        $this->guard($request);
        return response()->json(['agents'=>PlatformAgent::withCount('referrals')->latest()->limit(100)->get()->map(fn($agent)=>$this->agentData($agent))]);
    }

    public function storeAgent(Request $request)
    {
        $this->guard($request);$data=$request->validate(['name'=>['required','string','max:150'],'email'=>['required','email','unique:platform_agents,email'],'phone'=>['nullable','string','max:30'],'state'=>['nullable','string','max:100'],'commission_rate'=>['required','numeric','min:1','max:50']]);
        $agent=PlatformAgent::create([...$data,'referral_code'=>strtoupper(Str::random(8)),'is_active'=>true]);
        return response()->json(['message'=>'Agent registered.','agent'=>$this->agentData($agent)],201);
    }

    public function updateAgent(Request $request, PlatformAgent $agent)
    {
        $this->guard($request);$data=$request->validate(['name'=>['sometimes','string','max:150'],'phone'=>['nullable','string','max:30'],'state'=>['nullable','string','max:100'],'commission_rate'=>['sometimes','numeric','min:1','max:50'],'is_active'=>['sometimes','boolean']]);$agent->update($data);
        return response()->json(['message'=>'Agent updated.','agent'=>$this->agentData($agent->fresh()->loadCount('referrals'))]);
    }

    private function guard(Request $request): void
    {
        abort_unless($request->user()?->isSuperAdmin(), 403, 'Platform Super Admin access required.');
    }

    private function tenantData(Tenant $tenant): array
    {
        return [
            'id' => $tenant->id,
            'name' => $tenant->name,
            'slug' => $tenant->slug,
            'email' => $tenant->email,
            'phone' => $tenant->phone,
            'address' => $tenant->address,
            'status' => $tenant->status,
            'plan' => PricingService::tierLabel((int) ($tenant->students_count ?? PricingService::activeStudentCount($tenant->id))),
            'students_capacity' => PricingService::capacityFor($tenant),
            'subscription_expires_at' => $tenant->subscription_expires_at?->toDateString(),
            'users' => $tenant->users_count ?? $tenant->users()->count(),
            'students' => $tenant->students_count ?? $tenant->students()->count(),
        ];
    }

    private function agentData(PlatformAgent $agent): array
    {
        return ['id'=>$agent->id,'name'=>$agent->name,'email'=>$agent->email,'phone'=>$agent->phone,'state'=>$agent->state,'commission_rate'=>(float)$agent->commission_rate,'referral_code'=>$agent->referral_code,'active'=>(bool)$agent->is_active,'referrals'=>$agent->referrals_count??$agent->referrals()->count(),'earned'=>(float)$agent->total_earned,'paid'=>(float)$agent->total_paid];
    }
}
