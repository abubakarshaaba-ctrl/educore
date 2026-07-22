<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Models\SubscriptionPlan;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PlatformController extends Controller
{
    public function dashboard(Request $request)
    {
        $this->guard($request);

        $payments = DB::table('platform_payments')->where('status', 'confirmed');
        $recentTenants = Tenant::with('activeSubscription.plan')->latest()->limit(8)->get()
            ->map(fn (Tenant $tenant) => $this->tenantData($tenant));

        return response()->json([
            'operator' => ['name' => $request->user()->name, 'role' => 'Platform Super Admin'],
            'metrics' => [
                'schools' => Tenant::count(),
                'active_schools' => Tenant::where('status', Tenant::STATUS_ACTIVE)->count(),
                'students' => Student::withoutTenantScope()->count(),
                'platform_users' => User::whereNotNull('tenant_id')->count(),
                'monthly_revenue' => (float) (clone $payments)->whereMonth('paid_at', now()->month)->whereYear('paid_at', now()->year)->sum('amount'),
                'total_revenue' => (float) (clone $payments)->sum('amount'),
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
        $query = Tenant::with(['activeSubscription.plan'])->withCount(['users', 'students'])->latest();
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
        $plans = SubscriptionPlan::withCount(['subscriptions as subscribers_count' => fn ($query) => $query->whereIn('status', ['active', 'trial'])])
            ->orderBy('sort_order')->get()->map(fn (SubscriptionPlan $plan) => [
                'id' => $plan->id,
                'name' => $plan->name,
                'slug' => $plan->slug,
                'monthly_price' => (float) $plan->monthly_price,
                'annual_price' => (float) $plan->annual_price,
                'max_students' => $plan->max_students,
                'max_staff' => $plan->max_staff,
                'subscribers' => $plan->subscribers_count,
                'active' => (bool) $plan->is_active,
                'features' => $plan->features ?? [],
            ]);
        return response()->json(['plans' => $plans]);
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
            'plan' => $tenant->activeSubscription?->plan?->name ?? 'No active plan',
            'subscription_expires_at' => $tenant->subscription_expires_at?->toDateString(),
            'users' => $tenant->users_count ?? $tenant->users()->count(),
            'students' => $tenant->students_count ?? $tenant->students()->count(),
        ];
    }
}
