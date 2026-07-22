<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Models\Tenant;
use App\Models\User;
use App\Services\PricingService;
use App\Services\TenantOnboardingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Hash;
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
            'annual_discount_percent' => (int) round(PricingService::ANNUAL_DISCOUNT * 100),
            'plans' => $plans,
        ]);
    }

    public function updateTenant(Request $request, Tenant $tenant)
    {
        $this->guard($request);
        $data = $request->validate([
            'status' => ['sometimes', 'in:active,pending,suspended,subscription_expired'],
            'students_capacity' => ['sometimes', 'integer', 'min:20', 'max:1000000'],
            'subscription_expires_at' => ['sometimes', 'nullable', 'date'],
        ]);
        $tenant->update($data);
        return response()->json(['message' => 'School account updated.', 'tenant' => $this->tenantData($tenant->fresh()->loadCount(['users', 'students']))]);
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
            'status' => $tenant->status,
            'plan' => PricingService::tierLabel((int) ($tenant->students_count ?? PricingService::activeStudentCount($tenant->id))),
            'students_capacity' => PricingService::capacityFor($tenant),
            'subscription_expires_at' => $tenant->subscription_expires_at?->toDateString(),
            'users' => $tenant->users_count ?? $tenant->users()->count(),
            'students' => $tenant->students_count ?? $tenant->students()->count(),
        ];
    }
}
