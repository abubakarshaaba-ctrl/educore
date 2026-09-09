<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class MobilePlatformGroupController extends Controller
{
    public function show(Request $request, int $group): JsonResponse
    {
        $this->guard($request);
        $record = DB::table('school_groups')->where('id', $group)->first();
        abort_unless($record, 404);

        $members = DB::table('school_group_members')
            ->join('tenants', 'tenants.id', '=', 'school_group_members.tenant_id')
            ->where('school_group_members.group_id', $group)
            ->orderByDesc('school_group_members.role')
            ->orderBy('tenants.name')
            ->get([
                'school_group_members.tenant_id',
                'school_group_members.role',
                'tenants.name',
                'tenants.slug',
                'tenants.status',
                'tenants.subscription_expires_at',
            ])
            ->map(fn ($member): array => [
                'tenant_id' => (int) $member->tenant_id,
                'name' => $member->name,
                'slug' => $member->slug,
                'status' => $member->status,
                'role' => $member->role,
                'subscription_expires_at' => $member->subscription_expires_at,
            ]);

        $memberIds = $members->pluck('tenant_id');
        $available = Tenant::query()
            ->whereNotIn('id', DB::table('school_group_members')->pluck('tenant_id'))
            ->orderBy('name')
            ->limit(200)
            ->get(['id', 'name', 'slug', 'status'])
            ->map(fn (Tenant $tenant): array => [
                'id' => (int) $tenant->id,
                'name' => $tenant->name,
                'slug' => $tenant->slug,
                'status' => $tenant->status,
            ]);

        return response()->json([
            'group' => [
                'id' => (int) $record->id,
                'name' => $record->name,
                'slug' => $record->slug ?? null,
                'description' => $record->description ?? null,
                'owner_name' => $record->owner_name ?? null,
                'owner_email' => $record->owner_email ?? null,
                'member_count' => $memberIds->count(),
            ],
            'members' => $members,
            'available_schools' => $available,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $user = $this->guard($request);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:1000'],
            'owner_name' => ['nullable', 'string', 'max:100'],
            'owner_email' => ['nullable', 'email', 'max:150'],
        ]);

        $id = DB::transaction(function () use ($data, $request, $user): int {
            $id = DB::table('school_groups')->insertGetId([
                'name' => trim($data['name']),
                'slug' => Str::slug($data['name']).'-'.Str::lower(Str::random(4)),
                'description' => $data['description'] ?? null,
                'owner_name' => $data['owner_name'] ?? null,
                'owner_email' => $data['owner_email'] ?? null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $this->audit($request, $user, 'platform.group.created', $id, [], ['name' => $data['name']]);
            return $id;
        });

        return response()->json(['message' => 'School group created.', 'id' => $id], 201);
    }

    public function addMember(Request $request, int $group): JsonResponse
    {
        $user = $this->guard($request);
        $data = $request->validate([
            'tenant_id' => ['required', 'integer', Rule::exists('tenants', 'id')->whereNull('deleted_at')],
            'role' => ['nullable', Rule::in(['member', 'lead'])],
        ]);
        $tenantId = (int) $data['tenant_id'];

        try {
            DB::transaction(function () use ($group, $tenantId, $data, $request, $user): void {
                $groupRecord = DB::table('school_groups')->where('id', $group)->lockForUpdate()->first();
                abort_unless($groupRecord, 404);
                Tenant::query()->whereKey($tenantId)->lockForUpdate()->firstOrFail();

                $existingMembership = DB::table('school_group_members')
                    ->where('tenant_id', $tenantId)
                    ->first();
                if ($existingMembership && (int) $existingMembership->group_id !== $group) {
                    throw ValidationException::withMessages(['tenant_id' => 'This school already belongs to another school group.']);
                }

                $role = $data['role'] ?? 'member';
                if ($role === 'lead') {
                    DB::table('school_group_members')->where('group_id', $group)
                        ->update(['role' => 'member', 'updated_at' => now()]);
                }
                DB::table('school_group_members')->updateOrInsert(
                    ['group_id' => $group, 'tenant_id' => $tenantId],
                    ['role' => $role, 'created_at' => now(), 'updated_at' => now()]
                );
                $this->audit($request, $user, 'platform.group.member_added', $group, [], [
                    'tenant_id' => $tenantId,
                    'role' => $role,
                ]);
            });
        } catch (QueryException $error) {
            if ($this->isUniqueViolation($error)) {
                throw ValidationException::withMessages([
                    'tenant_id' => 'This school already belongs to another school group. Refresh the group and try again.',
                ]);
            }
            throw $error;
        }

        return response()->json(['message' => 'School added to group.']);
    }

    public function removeMember(Request $request, int $group, int $tenant): JsonResponse
    {
        $user = $this->guard($request);

        DB::transaction(function () use ($group, $tenant, $request, $user): void {
            $groupRecord = DB::table('school_groups')->where('id', $group)->lockForUpdate()->first();
            abort_unless($groupRecord, 404);
            Tenant::query()->whereKey($tenant)->lockForUpdate()->firstOrFail();

            $member = DB::table('school_group_members')
                ->where('group_id', $group)
                ->where('tenant_id', $tenant)
                ->lockForUpdate()
                ->first();
            abort_unless($member, 404);

            if ($member->role === 'lead' && DB::table('school_group_members')->where('group_id', $group)->where('tenant_id', '!=', $tenant)->exists()) {
                throw ValidationException::withMessages(['tenant' => 'Choose another lead campus before removing the current lead.']);
            }

            DB::table('school_group_members')->where('id', $member->id)->delete();
            $this->audit($request, $user, 'platform.group.member_removed', $group, [
                'tenant_id' => $tenant,
                'role' => $member->role,
            ], []);
        });

        return response()->json(['message' => 'School removed from group.']);
    }

    public function setLead(Request $request, int $group, int $tenant): JsonResponse
    {
        $user = $this->guard($request);

        DB::transaction(function () use ($group, $tenant, $request, $user): void {
            $groupRecord = DB::table('school_groups')->where('id', $group)->lockForUpdate()->first();
            abort_unless($groupRecord, 404);
            Tenant::query()->whereKey($tenant)->lockForUpdate()->firstOrFail();

            $member = DB::table('school_group_members')
                ->where('group_id', $group)
                ->where('tenant_id', $tenant)
                ->lockForUpdate()
                ->first();
            abort_unless($member, 404, 'The selected school is not a member of this group.');

            $oldLead = DB::table('school_group_members')->where('group_id', $group)->where('role', 'lead')->value('tenant_id');
            DB::table('school_group_members')->where('group_id', $group)
                ->update(['role' => 'member', 'updated_at' => now()]);
            DB::table('school_group_members')->where('group_id', $group)->where('tenant_id', $tenant)
                ->update(['role' => 'lead', 'updated_at' => now()]);
            $this->audit($request, $user, 'platform.group.lead_changed', $group,
                ['tenant_id' => $oldLead ? (int) $oldLead : null],
                ['tenant_id' => $tenant]
            );
        });

        return response()->json(['message' => 'Lead campus updated.']);
    }

    private function isUniqueViolation(QueryException $error): bool
    {
        $sqlState = (string) ($error->errorInfo[0] ?? $error->getCode());
        $driverCode = (string) ($error->errorInfo[1] ?? '');

        return in_array($sqlState, ['23000', '23505'], true)
            || $driverCode === '1062'
            || str_contains(strtolower($error->getMessage()), 'unique constraint');
    }

    private function guard(Request $request): User
    {
        /** @var User|null $user */
        $user = $request->user();
        abort_unless($user, 401);
        abort_unless($user->isSuperAdmin(), 403, 'Platform Super Admin access required.');
        abort_unless(Schema::hasTable('school_groups') && Schema::hasTable('school_group_members'), 503, 'School-group storage is unavailable.');

        return $user;
    }

    private function audit(Request $request, User $user, string $action, int $groupId, array $oldValues, array $newValues): void
    {
        if (!Schema::hasTable('audit_logs')) {
            return;
        }
        AuditLog::create([
            'tenant_id' => null,
            'actor_user_id' => $user->id,
            'auditable_type' => 'school_group',
            'auditable_id' => $groupId,
            'action' => $action,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'reason' => null,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);
    }
}
