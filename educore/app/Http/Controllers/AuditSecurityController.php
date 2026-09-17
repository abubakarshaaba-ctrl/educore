<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AuditSecurityController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->validate([
            'tenant_id' => ['nullable', 'integer', 'min:1'],
            'action' => ['nullable', 'string', 'max:120'],
            'period' => ['nullable', 'in:24h,7d,30d,all'],
        ]);

        $period = $filters['period'] ?? '7d';
        $since = match ($period) {
            '24h' => now()->subDay(),
            '7d' => now()->subDays(7),
            '30d' => now()->subDays(30),
            default => null,
        };

        $hasAudit = Schema::hasTable('audit_logs');
        $hasSessions = Schema::hasTable('sessions');
        $hasTokens = Schema::hasTable('api_tokens');
        $hasUsers = Schema::hasTable('users');
        $hasTenants = Schema::hasTable('tenants');

        $auditQuery = $hasAudit
            ? AuditLog::query()->with(['actor:id,name,email', 'tenant:id,name,slug'])
            : null;

        if ($auditQuery && $since) {
            $auditQuery->where('created_at', '>=', $since);
        }
        if ($auditQuery && !empty($filters['tenant_id'])) {
            $auditQuery->where('tenant_id', (int) $filters['tenant_id']);
        }
        if ($auditQuery && !empty($filters['action'])) {
            $auditQuery->where('action', 'like', '%' . trim($filters['action']) . '%');
        }

        $recentLogs = $auditQuery
            ? (clone $auditQuery)->latest('id')->paginate(30)->withQueryString()
            : collect();

        $privilegedKeywords = [
            'super', 'admin', 'tenant', 'security', 'password', 'token', 'settings',
            'payment', 'delete', 'remove', 'suspend', 'activate', 'override', 'permission',
        ];
        $signalKeywords = [
            'failed', 'denied', 'invalid', 'security', 'password', 'token', 'suspend',
            'locked', 'blocked', 'revoked', 'removed', 'deleted',
        ];

        $stats = [
            'audit_events' => 0,
            'privileged_events' => 0,
            'security_signals' => 0,
            'unique_actors' => 0,
            'active_web_sessions' => 0,
            'active_mobile_tokens' => 0,
            'stale_mobile_tokens' => 0,
            'expiring_mobile_tokens' => 0,
        ];

        $privilegedLogs = collect();
        $securitySignals = collect();

        if ($hasAudit) {
            $base = AuditLog::query();
            if ($since) {
                $base->where('created_at', '>=', $since);
            }
            if (!empty($filters['tenant_id'])) {
                $base->where('tenant_id', (int) $filters['tenant_id']);
            }

            $stats['audit_events'] = (clone $base)->count();
            $stats['unique_actors'] = (clone $base)->whereNotNull('actor_user_id')->distinct('actor_user_id')->count('actor_user_id');

            $privileged = $this->keywordQuery(clone $base, $privilegedKeywords);
            $signals = $this->keywordQuery(clone $base, $signalKeywords);
            $stats['privileged_events'] = (clone $privileged)->count();
            $stats['security_signals'] = (clone $signals)->count();

            $privilegedLogs = $privileged->with(['actor:id,name,email', 'tenant:id,name,slug'])
                ->latest('id')->limit(12)->get();
            $securitySignals = $signals->with(['actor:id,name,email', 'tenant:id,name,slug'])
                ->latest('id')->limit(12)->get();
        }

        if ($hasSessions) {
            $cutoff = now()->subMinutes((int) config('session.lifetime', 120))->timestamp;
            $sessions = DB::table('sessions')->where('last_activity', '>=', $cutoff);
            if (!empty($filters['tenant_id']) && $hasUsers) {
                $sessions->whereIn('user_id', function ($q) use ($filters) {
                    $q->select('id')->from('users')->where('tenant_id', (int) $filters['tenant_id']);
                });
            }
            $stats['active_web_sessions'] = $sessions->count();
        }

        $tokenRows = collect();
        if ($hasTokens) {
            $tokens = DB::table('api_tokens')
                ->leftJoin('users', 'users.id', '=', 'api_tokens.user_id')
                ->select([
                    'api_tokens.id', 'api_tokens.user_id', 'api_tokens.device',
                    'api_tokens.last_used_at', 'api_tokens.expires_at', 'api_tokens.created_at',
                    'users.name as user_name', 'users.email as user_email', 'users.tenant_id',
                ])
                ->where(function ($q) {
                    $q->whereNull('api_tokens.expires_at')->orWhere('api_tokens.expires_at', '>', now());
                });

            if (!empty($filters['tenant_id'])) {
                $tokens->where('users.tenant_id', (int) $filters['tenant_id']);
            }

            $stats['active_mobile_tokens'] = (clone $tokens)->count();
            $stats['stale_mobile_tokens'] = (clone $tokens)
                ->where(function ($q) {
                    $q->whereNull('api_tokens.last_used_at')->orWhere('api_tokens.last_used_at', '<', now()->subDays(30));
                })->count();
            $stats['expiring_mobile_tokens'] = (clone $tokens)
                ->whereNotNull('api_tokens.expires_at')
                ->whereBetween('api_tokens.expires_at', [now(), now()->addDays(7)])
                ->count();

            $tokenRows = $tokens->orderByDesc('api_tokens.last_used_at')->limit(20)->get();
        }

        $twoFactor = [
            'available' => false,
            'super_admins' => 0,
            'super_admins_enabled' => 0,
            'users_enabled' => 0,
        ];

        if ($hasUsers) {
            $twoFactorColumn = collect(['two_factor_secret', 'two_factor_confirmed_at'])
                ->first(fn ($column) => Schema::hasColumn('users', $column));

            if ($twoFactorColumn) {
                $twoFactor['available'] = true;
                $enabled = DB::table('users')->whereNotNull($twoFactorColumn);
                $twoFactor['users_enabled'] = (clone $enabled)->count();
                $twoFactor['super_admins'] = DB::table('users')->where('is_super_admin', true)->count();
                $twoFactor['super_admins_enabled'] = (clone $enabled)->where('is_super_admin', true)->count();
            }
        }

        $tenants = $hasTenants
            ? DB::table('tenants')->select('id', 'name')->orderBy('name')->get()
            : collect();

        return view('super.audit-security', compact(
            'stats', 'recentLogs', 'privilegedLogs', 'securitySignals', 'tokenRows',
            'twoFactor', 'tenants', 'filters', 'period', 'hasAudit', 'hasSessions', 'hasTokens'
        ));
    }

    private function keywordQuery($query, array $keywords)
    {
        return $query->where(function ($q) use ($keywords) {
            foreach ($keywords as $index => $keyword) {
                $method = $index === 0 ? 'where' : 'orWhere';
                $q->{$method}('action', 'like', '%' . $keyword . '%');
            }
        });
    }
}
