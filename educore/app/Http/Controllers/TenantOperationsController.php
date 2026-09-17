<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class TenantOperationsController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->validate([
            'status' => ['nullable', 'string', 'max:40'],
            'q' => ['nullable', 'string', 'max:120'],
            'attention' => ['nullable', 'in:1'],
        ]);

        abort_unless(Schema::hasTable('tenants'), 503, 'Tenant registry is unavailable.');

        $query = DB::table('tenants')->select('tenants.*');
        if (Schema::hasColumn('tenants', 'deleted_at')) {
            $query->whereNull('tenants.deleted_at');
        }
        if (!empty($filters['status'])) {
            $query->where('tenants.status', $filters['status']);
        }
        if (!empty($filters['q'])) {
            $needle = '%' . trim($filters['q']) . '%';
            $query->where(function ($q) use ($needle) {
                $q->where('tenants.name', 'like', $needle)
                    ->orWhere('tenants.slug', 'like', $needle);
                if (Schema::hasColumn('tenants', 'email')) {
                    $q->orWhere('tenants.email', 'like', $needle);
                }
            });
        }

        $tenants = $query->orderBy('tenants.name')->paginate(30)->withQueryString();
        $tenantIds = collect($tenants->items())->pluck('id')->map(fn ($id) => (int) $id)->all();

        $userCounts = $this->groupCount('users', 'tenant_id', $tenantIds, function ($q) {
            if (Schema::hasColumn('users', 'deleted_at')) {
                $q->whereNull('deleted_at');
            }
        });
        $activeUserCounts = $this->groupCount('users', 'tenant_id', $tenantIds, function ($q) {
            if (Schema::hasColumn('users', 'is_active')) {
                $q->where('is_active', true);
            }
            if (Schema::hasColumn('users', 'deleted_at')) {
                $q->whereNull('deleted_at');
            }
        });
        $studentCounts = $this->groupCount('students', 'tenant_id', $tenantIds, function ($q) {
            if (Schema::hasColumn('students', 'deleted_at')) {
                $q->whereNull('deleted_at');
            }
        });

        $supportCounts = collect();
        if ($tenantIds && Schema::hasTable('platform_support_tickets') && Schema::hasColumn('platform_support_tickets', 'tenant_id')) {
            $supportCounts = DB::table('platform_support_tickets')
                ->whereIn('tenant_id', $tenantIds)
                ->when(Schema::hasColumn('platform_support_tickets', 'status'), fn ($q) => $q->whereIn('status', ['open', 'pending']))
                ->select('tenant_id', DB::raw('COUNT(*) as aggregate'))
                ->groupBy('tenant_id')->pluck('aggregate', 'tenant_id');
        }

        $unpaidInvoices = collect();
        if ($tenantIds && Schema::hasTable('platform_invoices') && Schema::hasColumn('platform_invoices', 'tenant_id')) {
            $unpaidInvoices = DB::table('platform_invoices')
                ->whereIn('tenant_id', $tenantIds)
                ->when(Schema::hasColumn('platform_invoices', 'status'), fn ($q) => $q->where('status', '!=', 'paid'))
                ->select('tenant_id', DB::raw('COUNT(*) as aggregate'))
                ->groupBy('tenant_id')->pluck('aggregate', 'tenant_id');
        }

        $recentActivity = collect();
        if ($tenantIds && Schema::hasTable('users') && Schema::hasColumn('users', 'last_login_at')) {
            $recentActivity = DB::table('users')->whereIn('tenant_id', $tenantIds)
                ->select('tenant_id', DB::raw('MAX(last_login_at) as last_activity'))
                ->groupBy('tenant_id')->pluck('last_activity', 'tenant_id');
        }

        $mobileSessions = collect();
        if ($tenantIds && Schema::hasTable('api_tokens') && Schema::hasTable('users')) {
            $mobileSessions = DB::table('api_tokens')
                ->join('users', 'users.id', '=', 'api_tokens.user_id')
                ->whereIn('users.tenant_id', $tenantIds)
                ->where(function ($q) {
                    $q->whereNull('api_tokens.expires_at')->orWhere('api_tokens.expires_at', '>', now());
                })
                ->select('users.tenant_id', DB::raw('COUNT(*) as aggregate'))
                ->groupBy('users.tenant_id')->pluck('aggregate', 'users.tenant_id');
        }

        $webSessions = collect();
        if ($tenantIds && Schema::hasTable('sessions') && Schema::hasTable('users')) {
            $cutoff = now()->subMinutes((int) config('session.lifetime', 120))->timestamp;
            $webSessions = DB::table('sessions')
                ->join('users', 'users.id', '=', 'sessions.user_id')
                ->whereIn('users.tenant_id', $tenantIds)
                ->where('sessions.last_activity', '>=', $cutoff)
                ->select('users.tenant_id', DB::raw('COUNT(*) as aggregate'))
                ->groupBy('users.tenant_id')->pluck('aggregate', 'users.tenant_id');
        }

        $rows = collect($tenants->items())->map(function ($tenant) use (
            $userCounts, $activeUserCounts, $studentCounts, $supportCounts, $unpaidInvoices,
            $recentActivity, $mobileSessions, $webSessions
        ) {
            $expiry = $tenant->subscription_expires_at ?? null;
            $expiryDate = $expiry ? \Illuminate\Support\Carbon::parse($expiry) : null;
            $daysToExpiry = $expiryDate ? (int) now()->startOfDay()->diffInDays($expiryDate->startOfDay(), false) : null;
            $attention = [];

            if (($tenant->status ?? '') !== 'active') {
                $attention[] = 'Account status: ' . ($tenant->status ?? 'unknown');
            }
            if ($daysToExpiry !== null && $daysToExpiry < 0) {
                $attention[] = 'Subscription expired';
            } elseif ($daysToExpiry !== null && $daysToExpiry <= 14) {
                $attention[] = 'Subscription expires soon';
            }
            if ((int) ($supportCounts[$tenant->id] ?? 0) > 0) {
                $attention[] = 'Open support ticket';
            }
            if ((int) ($unpaidInvoices[$tenant->id] ?? 0) > 0) {
                $attention[] = 'Unpaid invoice';
            }

            return (object) [
                'tenant' => $tenant,
                'users' => (int) ($userCounts[$tenant->id] ?? 0),
                'active_users' => (int) ($activeUserCounts[$tenant->id] ?? 0),
                'students' => (int) ($studentCounts[$tenant->id] ?? 0),
                'open_support' => (int) ($supportCounts[$tenant->id] ?? 0),
                'unpaid_invoices' => (int) ($unpaidInvoices[$tenant->id] ?? 0),
                'web_sessions' => (int) ($webSessions[$tenant->id] ?? 0),
                'mobile_sessions' => (int) ($mobileSessions[$tenant->id] ?? 0),
                'last_activity' => $recentActivity[$tenant->id] ?? null,
                'days_to_expiry' => $daysToExpiry,
                'attention' => $attention,
            ];
        });

        if (!empty($filters['attention'])) {
            $rows = $rows->filter(fn ($row) => count($row->attention) > 0)->values();
        }

        $base = DB::table('tenants');
        if (Schema::hasColumn('tenants', 'deleted_at')) {
            $base->whereNull('deleted_at');
        }

        $summary = [
            'total' => (clone $base)->count(),
            'active' => (clone $base)->where('status', 'active')->count(),
            'pending' => (clone $base)->where('status', 'pending')->count(),
            'suspended' => (clone $base)->where('status', 'suspended')->count(),
            'expired' => (clone $base)->where('status', 'subscription_expired')->count(),
            'expiring_14d' => Schema::hasColumn('tenants', 'subscription_expires_at')
                ? (clone $base)->whereBetween('subscription_expires_at', [now(), now()->addDays(14)])->count()
                : 0,
        ];

        $statuses = (clone $base)->whereNotNull('status')->distinct()->orderBy('status')->pluck('status');

        return view('super.tenant-operations', compact('tenants', 'rows', 'summary', 'statuses', 'filters'));
    }

    private function groupCount(string $table, string $tenantColumn, array $tenantIds, ?callable $scope = null)
    {
        if (!$tenantIds || !Schema::hasTable($table) || !Schema::hasColumn($table, $tenantColumn)) {
            return collect();
        }

        $query = DB::table($table)->whereIn($tenantColumn, $tenantIds);
        if ($scope) {
            $scope($query);
        }

        return $query->select($tenantColumn, DB::raw('COUNT(*) as aggregate'))
            ->groupBy($tenantColumn)->pluck('aggregate', $tenantColumn);
    }
}
