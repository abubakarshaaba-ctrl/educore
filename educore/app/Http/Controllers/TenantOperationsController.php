<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Throwable;

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
        if (!empty($filters['status']) && Schema::hasColumn('tenants', 'status')) {
            $query->where('tenants.status', $filters['status']);
        }
        if (!empty($filters['q'])) {
            $needle = '%' . trim($filters['q']) . '%';
            $query->where(function ($q) use ($needle) {
                $q->where('tenants.name', 'like', $needle);
                if (Schema::hasColumn('tenants', 'slug')) {
                    $q->orWhere('tenants.slug', 'like', $needle);
                }
                if (Schema::hasColumn('tenants', 'email')) {
                    $q->orWhere('tenants.email', 'like', $needle);
                }
            });
        }

        if (!empty($filters['attention'])) {
            $this->applyAttentionFilter($query);
        }

        $tenants = $query->orderBy('tenants.name')->paginate(30)->withQueryString();
        $tenantIds = collect($tenants->items())->pluck('id')->map(fn ($id) => (int) $id)->all();

        $userCounts = $this->safeMetric('user_counts', function () use ($tenantIds) {
            return $this->groupCount('users', 'tenant_id', $tenantIds, function ($q) {
                if (Schema::hasColumn('users', 'deleted_at')) {
                    $q->whereNull('users.deleted_at');
                }
            });
        });

        $activeUserCounts = $this->safeMetric('active_user_counts', function () use ($tenantIds) {
            return $this->groupCount('users', 'tenant_id', $tenantIds, function ($q) {
                if (Schema::hasColumn('users', 'is_active')) {
                    $q->where('users.is_active', true);
                }
                if (Schema::hasColumn('users', 'deleted_at')) {
                    $q->whereNull('users.deleted_at');
                }
            });
        });

        $studentCounts = $this->safeMetric('student_counts', function () use ($tenantIds) {
            return $this->groupCount('students', 'tenant_id', $tenantIds, function ($q) {
                if (Schema::hasColumn('students', 'deleted_at')) {
                    $q->whereNull('students.deleted_at');
                }
            });
        });

        $supportCounts = $this->safeMetric('support_counts', function () use ($tenantIds) {
            if (!$tenantIds || !Schema::hasTable('platform_support_tickets') || !Schema::hasColumn('platform_support_tickets', 'tenant_id')) {
                return collect();
            }

            return DB::table('platform_support_tickets')
                ->whereIn('tenant_id', $tenantIds)
                ->when(
                    Schema::hasColumn('platform_support_tickets', 'status'),
                    fn ($q) => $q->whereIn('status', ['open', 'pending'])
                )
                ->select('tenant_id', DB::raw('COUNT(*) as aggregate'))
                ->groupBy('tenant_id')
                ->pluck('aggregate', 'tenant_id');
        });

        $unpaidInvoices = $this->safeMetric('unpaid_invoice_counts', function () use ($tenantIds) {
            if (!$tenantIds || !Schema::hasTable('platform_invoices') || !Schema::hasColumn('platform_invoices', 'tenant_id')) {
                return collect();
            }

            return DB::table('platform_invoices')
                ->whereIn('tenant_id', $tenantIds)
                ->when(
                    Schema::hasColumn('platform_invoices', 'status'),
                    fn ($q) => $q->where(function ($status) {
                        $status->whereNull('status')->orWhere('status', '!=', 'paid');
                    })
                )
                ->select('tenant_id', DB::raw('COUNT(*) as aggregate'))
                ->groupBy('tenant_id')
                ->pluck('aggregate', 'tenant_id');
        });

        $recentActivity = $this->safeMetric('recent_activity', function () use ($tenantIds) {
            if (
                !$tenantIds
                || !Schema::hasTable('users')
                || !Schema::hasColumn('users', 'tenant_id')
                || !Schema::hasColumn('users', 'last_login_at')
            ) {
                return collect();
            }

            return DB::table('users')
                ->whereIn('tenant_id', $tenantIds)
                ->select('tenant_id', DB::raw('MAX(last_login_at) as last_activity'))
                ->groupBy('tenant_id')
                ->pluck('last_activity', 'tenant_id');
        });

        $mobileSessions = $this->safeMetric('mobile_sessions', function () use ($tenantIds) {
            if (
                !$tenantIds
                || !Schema::hasTable('api_tokens')
                || !Schema::hasTable('users')
                || !Schema::hasColumn('api_tokens', 'user_id')
                || !Schema::hasColumn('users', 'id')
                || !Schema::hasColumn('users', 'tenant_id')
            ) {
                return collect();
            }

            $mobileQuery = DB::table('api_tokens')
                ->join('users', 'users.id', '=', 'api_tokens.user_id')
                ->whereIn('users.tenant_id', $tenantIds);

            if (Schema::hasColumn('api_tokens', 'expires_at')) {
                $mobileQuery->where(function ($q) {
                    $q->whereNull('api_tokens.expires_at')
                        ->orWhere('api_tokens.expires_at', '>', now());
                });
            }

            return $mobileQuery
                ->select('users.tenant_id', DB::raw('COUNT(*) as aggregate'))
                ->groupBy('users.tenant_id')
                ->pluck('aggregate', 'users.tenant_id');
        });

        $webSessions = $this->safeMetric('web_sessions', function () use ($tenantIds) {
            if (
                !$tenantIds
                || !Schema::hasTable('sessions')
                || !Schema::hasTable('users')
                || !Schema::hasColumn('sessions', 'user_id')
                || !Schema::hasColumn('sessions', 'last_activity')
                || !Schema::hasColumn('users', 'id')
                || !Schema::hasColumn('users', 'tenant_id')
            ) {
                return collect();
            }

            $cutoff = now()->subMinutes((int) config('session.lifetime', 120))->timestamp;

            return DB::table('sessions')
                ->join('users', 'users.id', '=', 'sessions.user_id')
                ->whereIn('users.tenant_id', $tenantIds)
                ->where('sessions.last_activity', '>=', $cutoff)
                ->select('users.tenant_id', DB::raw('COUNT(*) as aggregate'))
                ->groupBy('users.tenant_id')
                ->pluck('aggregate', 'users.tenant_id');
        });

        $rows = collect($tenants->items())->map(function ($tenant) use (
            $userCounts,
            $activeUserCounts,
            $studentCounts,
            $supportCounts,
            $unpaidInvoices,
            $recentActivity,
            $mobileSessions,
            $webSessions
        ) {
            [$expiryDate, $daysToExpiry] = $this->subscriptionExpiry($tenant->subscription_expires_at ?? null);
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
                'expiry_date' => $expiryDate,
                'days_to_expiry' => $daysToExpiry,
                'attention' => $attention,
            ];
        });

        $base = DB::table('tenants');
        if (Schema::hasColumn('tenants', 'deleted_at')) {
            $base->whereNull('deleted_at');
        }

        $summary = [
            'total' => (clone $base)->count(),
            'active' => Schema::hasColumn('tenants', 'status') ? (clone $base)->where('status', 'active')->count() : 0,
            'pending' => Schema::hasColumn('tenants', 'status') ? (clone $base)->where('status', 'pending')->count() : 0,
            'suspended' => Schema::hasColumn('tenants', 'status') ? (clone $base)->where('status', 'suspended')->count() : 0,
            'expired' => Schema::hasColumn('tenants', 'status') ? (clone $base)->where('status', 'subscription_expired')->count() : 0,
            'expiring_14d' => Schema::hasColumn('tenants', 'subscription_expires_at')
                ? (clone $base)->whereBetween('subscription_expires_at', [now()->toDateString(), now()->addDays(14)->toDateString()])->count()
                : 0,
        ];

        $statuses = Schema::hasColumn('tenants', 'status')
            ? (clone $base)->whereNotNull('status')->distinct()->orderBy('status')->pluck('status')
            : collect();

        return view('super.tenant-operations', compact('tenants', 'rows', 'summary', 'statuses', 'filters'));
    }

    private function applyAttentionFilter($query): void
    {
        $hasStatus = Schema::hasColumn('tenants', 'status');
        $hasExpiry = Schema::hasColumn('tenants', 'subscription_expires_at');
        $hasSupport = Schema::hasTable('platform_support_tickets')
            && Schema::hasColumn('platform_support_tickets', 'tenant_id');
        $hasInvoices = Schema::hasTable('platform_invoices')
            && Schema::hasColumn('platform_invoices', 'tenant_id');

        if (!$hasStatus && !$hasExpiry && !$hasSupport && !$hasInvoices) {
            $query->whereRaw('1 = 0');

            return;
        }

        $query->where(function ($attention) use ($hasStatus, $hasExpiry, $hasSupport, $hasInvoices) {
            if ($hasStatus) {
                $attention->orWhereNull('tenants.status')
                    ->orWhere('tenants.status', '!=', 'active');
            }

            if ($hasExpiry) {
                $attention->orWhere(function ($expiry) {
                    $expiry->whereNotNull('tenants.subscription_expires_at')
                        ->where('tenants.subscription_expires_at', '<=', now()->addDays(14)->toDateString());
                });
            }

            if ($hasSupport) {
                $attention->orWhereExists(function ($subquery) {
                    $subquery->selectRaw('1')
                        ->from('platform_support_tickets')
                        ->whereColumn('platform_support_tickets.tenant_id', 'tenants.id');

                    if (Schema::hasColumn('platform_support_tickets', 'status')) {
                        $subquery->whereIn('platform_support_tickets.status', ['open', 'pending']);
                    }
                });
            }

            if ($hasInvoices) {
                $attention->orWhereExists(function ($subquery) {
                    $subquery->selectRaw('1')
                        ->from('platform_invoices')
                        ->whereColumn('platform_invoices.tenant_id', 'tenants.id');

                    if (Schema::hasColumn('platform_invoices', 'status')) {
                        $subquery->where(function ($status) {
                            $status->whereNull('platform_invoices.status')
                                ->orWhere('platform_invoices.status', '!=', 'paid');
                        });
                    }
                });
            }
        });
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
            ->groupBy($tenantColumn)
            ->pluck('aggregate', $tenantColumn);
    }

    private function safeMetric(string $metric, callable $callback)
    {
        try {
            return $callback();
        } catch (Throwable $exception) {
            Log::warning('Tenant Operations optional metric unavailable.', [
                'metric' => $metric,
                'exception' => $exception::class,
                'message' => $exception->getMessage(),
            ]);

            return collect();
        }
    }

    private function subscriptionExpiry(mixed $value): array
    {
        if (!$value) {
            return [null, null];
        }

        try {
            $expiry = Carbon::parse($value)->startOfDay();
            $days = (int) now()->startOfDay()->diffInDays($expiry, false);

            return [$expiry, $days];
        } catch (Throwable $exception) {
            Log::warning('Tenant Operations encountered an invalid subscription expiry value.', [
                'value' => is_scalar($value) ? (string) $value : gettype($value),
                'exception' => $exception::class,
            ]);

            return [null, null];
        }
    }
}
