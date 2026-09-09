<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\PlatformAgent;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class MobilePlatformAgentController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $user = $this->guard($request);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'email' => ['required', 'email', Rule::unique('platform_agents', 'email')],
            'phone' => ['nullable', 'string', 'max:30'],
            'state' => ['nullable', 'string', 'max:100'],
            'commission_rate' => ['required', 'numeric', 'min:1', 'max:50'],
            'reason' => ['required', 'string', 'min:5', 'max:500'],
        ]);

        $agent = DB::transaction(function () use ($data, $request, $user): PlatformAgent {
            $agent = PlatformAgent::create([
                'name' => trim($data['name']),
                'email' => strtolower(trim($data['email'])),
                'phone' => $data['phone'] ?? null,
                'state' => $data['state'] ?? null,
                'commission_rate' => (float) $data['commission_rate'],
                'referral_code' => strtoupper(Str::random(10)),
                'is_active' => true,
            ]);
            $this->audit($request, $user, $agent, 'platform.agent.created', [], [
                'name' => $agent->name,
                'email_hash' => hash('sha256', strtolower($agent->email)),
                'commission_rate' => $agent->commission_rate,
                'is_active' => true,
            ], trim($data['reason']));
            return $agent;
        });

        return response()->json(['message' => 'Platform agent registered.', 'agent' => $this->agentData($agent)], 201);
    }

    public function update(Request $request, PlatformAgent $agent): JsonResponse
    {
        $user = $this->guard($request);
        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:150'],
            'phone' => ['sometimes', 'nullable', 'string', 'max:30'],
            'state' => ['sometimes', 'nullable', 'string', 'max:100'],
            'commission_rate' => ['sometimes', 'numeric', 'min:1', 'max:50'],
            'is_active' => ['sometimes', 'boolean'],
            'reason' => ['required', 'string', 'min:5', 'max:500'],
        ]);
        $reason = trim($data['reason']);
        unset($data['reason']);
        $before = $agent->only(array_keys($data));

        DB::transaction(function () use ($agent, $data, $before, $request, $user, $reason): void {
            $agent->update($data);
            $this->audit($request, $user, $agent, 'platform.agent.updated', $before, $agent->fresh()->only(array_keys($data)), $reason);
        });

        return response()->json([
            'message' => 'Platform agent updated.',
            'agent' => $this->agentData($agent->fresh()->loadCount('referrals')),
        ]);
    }

    private function guard(Request $request): User
    {
        /** @var User|null $user */
        $user = $request->user();
        abort_unless($user, 401);
        abort_unless($user->isSuperAdmin(), 403, 'Platform Super Admin access required.');
        abort_unless(Schema::hasTable('platform_agents'), 503, 'Platform agent storage is unavailable.');
        return $user;
    }

    private function audit(Request $request, User $user, PlatformAgent $agent, string $action, array $old, array $new, string $reason): void
    {
        if (!Schema::hasTable('audit_logs')) return;
        AuditLog::create([
            'tenant_id' => null,
            'actor_user_id' => $user->id,
            'auditable_type' => PlatformAgent::class,
            'auditable_id' => $agent->id,
            'action' => $action,
            'old_values' => $old,
            'new_values' => $new,
            'reason' => $reason,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);
    }

    private function agentData(PlatformAgent $agent): array
    {
        return [
            'id' => (int) $agent->id,
            'name' => $agent->name,
            'email' => $agent->email,
            'phone' => $agent->phone,
            'state' => $agent->state,
            'commission_rate' => (float) $agent->commission_rate,
            'referral_code' => $agent->referral_code,
            'active' => (bool) $agent->is_active,
            'referrals' => (int) ($agent->referrals_count ?? $agent->referrals()->count()),
            'earned' => (float) $agent->total_earned,
            'paid' => (float) $agent->total_paid,
        ];
    }
}
