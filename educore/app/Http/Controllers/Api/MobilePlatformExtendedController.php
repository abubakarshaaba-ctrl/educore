<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PlatformSetting;
use App\Models\Student;
use App\Models\Tenant;
use App\Models\User;
use App\Services\PricingService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class MobilePlatformExtendedController extends Controller
{
    public function analytics(Request $request): JsonResponse
    {
        $this->guard($request);
        $tenants = Tenant::query()->latest('id')->get(['id', 'status', 'created_at']);

        $studentCounts = collect();
        if (Schema::hasTable('students')) {
            $students = DB::table('students');
            if (Schema::hasColumn('students', 'status')) {
                $students->where('status', Student::STATUS_ACTIVE);
            }
            if (Schema::hasColumn('students', 'deleted_at')) {
                $students->whereNull('deleted_at');
            }
            $studentCounts = $students
                ->selectRaw('tenant_id, COUNT(*) as cnt')
                ->groupBy('tenant_id')
                ->pluck('cnt', 'tenant_id');
        }

        $distribution = collect(['Free plan' => 0, 'Paid plan' => 0]);
        foreach ($tenants as $tenant) {
            $count = (int) ($studentCounts[$tenant->id] ?? 0);
            $label = PricingService::isFree($count) ? 'Free plan' : 'Paid plan';
            $distribution->put($label, (int) $distribution->get($label, 0) + 1);
        }

        $year = (int) now()->year;
        $growth = $tenants
            ->filter(fn (Tenant $tenant): bool => $tenant->created_at?->year === $year)
            ->groupBy(fn (Tenant $tenant): int => (int) $tenant->created_at->month)
            ->map(fn ($items, $month): array => [
                'month' => (int) $month,
                'year' => $year,
                'count' => $items->count(),
            ])
            ->sortBy('month')
            ->values();

        $planDistribution = $distribution
            ->map(fn (int $count, string $plan): array => ['plan' => $plan, 'count' => $count])
            ->values();
        $top = $planDistribution->sortByDesc('count')->first();

        return response()->json([
            'metrics' => [
                'schools' => $tenants->count(),
                'active_subscriptions' => $tenants->where('status', Tenant::STATUS_ACTIVE)->count(),
                'students' => $studentCounts->sum(),
                'top_tier' => ($top['count'] ?? 0) > 0 ? $top['plan'] : 'None',
            ],
            'growth' => $growth,
            'plan_distribution' => $planDistribution,
        ]);
    }

    public function groups(Request $request): JsonResponse
    {
        $this->guard($request);
        if (!Schema::hasTable('school_groups')) {
            return response()->json(['groups' => []]);
        }

        $query = DB::table('school_groups')->orderBy('name');
        if (Schema::hasTable('school_group_members')) {
            $query->select('school_groups.*')->selectSub(function ($sub): void {
                $sub->from('school_group_members')
                    ->selectRaw('COUNT(*)')
                    ->whereColumn('school_group_members.group_id', 'school_groups.id');
            }, 'member_count');
        } else {
            $query->select('school_groups.*')->selectRaw('0 as member_count');
        }

        $groups = $query->limit(100)->get()->map(fn ($group): array => [
            'id' => (int) $group->id,
            'name' => $group->name,
            'slug' => $group->slug ?? null,
            'description' => $group->description ?? null,
            'owner_name' => $group->owner_name ?? null,
            'owner_email' => $group->owner_email ?? null,
            'member_count' => (int) ($group->member_count ?? 0),
            'created_at' => $group->created_at ?? null,
        ]);

        return response()->json(['groups' => $groups]);
    }

    public function support(Request $request): JsonResponse
    {
        $this->guard($request);
        if (!Schema::hasTable('platform_support_tickets')) {
            return response()->json([
                'summary' => ['open' => 0, 'replied' => 0, 'closed' => 0],
                'tickets' => [],
            ]);
        }

        $base = DB::table('platform_support_tickets');
        $tickets = (clone $base)
            ->leftJoin('tenants', 'tenants.id', '=', 'platform_support_tickets.tenant_id')
            ->leftJoin('users', 'users.id', '=', 'platform_support_tickets.user_id')
            ->select(
                'platform_support_tickets.*',
                'tenants.name as school_name',
                'users.name as requester_name'
            )
            ->orderByDesc('platform_support_tickets.created_at')
            ->limit(100)
            ->get()
            ->map(fn ($ticket): array => [
                'id' => (int) $ticket->id,
                'tenant_id' => (int) $ticket->tenant_id,
                'school' => $ticket->school_name,
                'requester' => $ticket->requester_name,
                'subject' => $ticket->subject,
                'body' => $ticket->body,
                'status' => $ticket->status,
                'admin_reply' => $ticket->admin_reply,
                'replied_at' => $ticket->replied_at,
                'created_at' => $ticket->created_at,
            ]);

        return response()->json([
            'summary' => [
                'open' => (clone $base)->where('status', 'open')->count(),
                'replied' => (clone $base)->where('status', 'replied')->count(),
                'closed' => (clone $base)->where('status', 'closed')->count(),
            ],
            'tickets' => $tickets,
        ]);
    }

    public function broadcasts(Request $request): JsonResponse
    {
        $this->guard($request);
        if (!Schema::hasTable('platform_broadcasts')) {
            return response()->json(['broadcasts' => []]);
        }

        $broadcasts = DB::table('platform_broadcasts')
            ->leftJoin('users', 'users.id', '=', 'platform_broadcasts.created_by')
            ->select('platform_broadcasts.*', 'users.name as creator_name')
            ->orderByDesc('platform_broadcasts.created_at')
            ->limit(100)
            ->get()
            ->map(fn ($item): array => [
                'id' => (int) $item->id,
                'title' => $item->title,
                'body' => $item->body,
                'target' => $item->target,
                'creator' => $item->creator_name,
                'expires_at' => $item->expires_at,
                'created_at' => $item->created_at,
                'active' => $item->expires_at === null || Carbon::parse($item->expires_at)->isFuture(),
            ]);

        return response()->json(['broadcasts' => $broadcasts]);
    }

    public function settings(Request $request): JsonResponse
    {
        $this->guard($request);
        if (!Schema::hasTable('platform_settings')) {
            return response()->json(['settings' => []]);
        }

        $settings = PlatformSetting::query()
            ->whereNotIn('key', PlatformSetting::SECRET_KEYS)
            ->orderBy('group')
            ->orderBy('key')
            ->get()
            ->map(fn (PlatformSetting $setting): array => [
                'key' => $setting->key,
                'label' => $setting->label ?: str($setting->key)->headline()->toString(),
                'group' => $setting->group ?: 'general',
                'type' => $setting->type ?: 'string',
                'value' => $setting->typed_value,
            ])
            ->values();

        return response()->json(['settings' => $settings]);
    }

    public function gateways(Request $request): JsonResponse
    {
        $this->guard($request);

        $providers = [
            'paystack' => [
                'public_key' => 'paystack_public_key',
                'secret_key' => 'paystack_secret_key',
                'live' => 'paystack_is_live',
            ],
            'monnify' => [
                'public_key' => 'monnify_api_key',
                'secret_key' => 'monnify_secret_key',
                'contract_code' => 'monnify_contract_code',
                'live' => 'monnify_is_live',
            ],
            'flutterwave' => [
                'public_key' => 'flutterwave_public_key',
                'secret_key' => 'flutterwave_secret_key',
                'live' => 'flutterwave_is_live',
            ],
        ];

        $result = collect($providers)->map(function (array $keys, string $provider): array {
            $public = PlatformSetting::valueFor($keys['public_key']);
            $secret = PlatformSetting::valueFor($keys['secret_key']);
            $contract = isset($keys['contract_code']) ? PlatformSetting::valueFor($keys['contract_code']) : null;

            return [
                'provider' => $provider,
                'public_identifier' => $this->maskIdentifier($public),
                'contract_code' => $contract ? $this->maskIdentifier($contract) : null,
                'secret_configured' => filled($secret),
                'live' => (bool) PlatformSetting::valueFor($keys['live'], false),
                'configured' => filled($public) && filled($secret),
            ];
        })->values();

        return response()->json(['gateways' => $result]);
    }

    private function maskIdentifier(mixed $value): ?string
    {
        $text = trim((string) ($value ?? ''));
        if ($text === '') {
            return null;
        }
        if (strlen($text) <= 8) {
            return str_repeat('•', max(4, strlen($text)));
        }

        return substr($text, 0, 4).'••••'.substr($text, -4);
    }

    private function guard(Request $request): User
    {
        /** @var User|null $user */
        $user = $request->user();
        abort_unless($user, 401);
        abort_unless($user->isSuperAdmin(), 403, 'Platform Super Admin access required.');

        return $user;
    }
}
