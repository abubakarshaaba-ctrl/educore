<?php

namespace App\Services;

use App\Models\ApiToken;
use App\Models\AuditLog;
use App\Models\Guardian;
use App\Models\PushSubscription;
use App\Models\Student;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class PortalAccountService
{
    public function createStudentAccount(
        int $tenantId,
        Student $student,
        string $email,
        string $password,
        ?User $actor = null,
        ?Request $request = null,
    ): User {
        $this->assertStudentTenant($student, $tenantId);

        return DB::transaction(function () use ($tenantId, $student, $email, $password, $actor, $request): User {
            $locked = Student::where('tenant_id', $tenantId)->whereKey($student->id)->lockForUpdate()->firstOrFail();
            if ($locked->user_id && User::where('tenant_id', $tenantId)->whereKey($locked->user_id)->exists()) {
                throw ValidationException::withMessages(['student' => 'Portal account already exists for this student.']);
            }
            if (User::where('email', $email)->exists()) {
                throw ValidationException::withMessages(['email' => 'This email address is already used by another account.']);
            }

            $user = User::create([
                'tenant_id' => $tenantId,
                'name' => $locked->full_name,
                'email' => strtolower(trim($email)),
                'password' => Hash::make($password),
                'role' => 'student',
                'is_active' => true,
            ]);
            $locked->update(['user_id' => $user->id]);
            $this->audit($tenantId, $actor, $request, 'portal.student_account.created', $user, [
                'student_id' => $locked->id,
                'email_hash' => hash('sha256', strtolower(trim($email))),
            ]);

            return $user;
        });
    }

    public function createGuardianAccount(
        int $tenantId,
        Guardian $guardian,
        string $email,
        string $password,
        ?User $actor = null,
        ?Request $request = null,
    ): User {
        abort_unless((int) $guardian->tenant_id === $tenantId, 404);

        return DB::transaction(function () use ($tenantId, $guardian, $email, $password, $actor, $request): User {
            $locked = Guardian::where('tenant_id', $tenantId)->whereKey($guardian->id)->lockForUpdate()->firstOrFail();
            if ($locked->user_id && User::where('tenant_id', $tenantId)->whereKey($locked->user_id)->exists()) {
                throw ValidationException::withMessages(['guardian' => 'Portal account already exists for this guardian.']);
            }
            if (User::where('email', $email)->exists()) {
                throw ValidationException::withMessages(['email' => 'This email address is already used by another account.']);
            }

            $user = User::create([
                'tenant_id' => $tenantId,
                'name' => $locked->full_name,
                'email' => strtolower(trim($email)),
                'password' => Hash::make($password),
                'role' => 'parent',
                'is_active' => true,
            ]);
            $locked->update(['user_id' => $user->id]);
            $this->audit($tenantId, $actor, $request, 'portal.parent_account.created', $user, [
                'guardian_id' => $locked->id,
                'email_hash' => hash('sha256', strtolower(trim($email))),
            ]);

            return $user;
        });
    }

    public function resetPassword(
        int $tenantId,
        User $portalUser,
        string $password,
        ?User $actor = null,
        ?Request $request = null,
    ): User {
        $this->assertPortalUser($portalUser, $tenantId);

        return DB::transaction(function () use ($tenantId, $portalUser, $password, $actor, $request): User {
            $locked = User::where('tenant_id', $tenantId)->whereKey($portalUser->id)->lockForUpdate()->firstOrFail();
            $this->assertPortalUser($locked, $tenantId);
            $locked->update(['password' => Hash::make($password)]);
            $this->revokeSessions($locked->id);
            $this->audit($tenantId, $actor, $request, 'portal.account.password_reset', $locked, [
                'role' => $locked->roleKey(),
                'sessions_revoked' => true,
            ]);

            return $locked->fresh();
        });
    }

    public function toggleAccess(
        int $tenantId,
        User $portalUser,
        ?User $actor = null,
        ?Request $request = null,
    ): User {
        $this->assertPortalUser($portalUser, $tenantId);

        return DB::transaction(function () use ($tenantId, $portalUser, $actor, $request): User {
            $locked = User::where('tenant_id', $tenantId)->whereKey($portalUser->id)->lockForUpdate()->firstOrFail();
            $this->assertPortalUser($locked, $tenantId);
            $before = (bool) $locked->is_active;
            $locked->update(['is_active' => !$before]);
            if ($before) {
                $this->revokeSessions($locked->id);
            }
            $this->audit($tenantId, $actor, $request, 'portal.account.access_toggled', $locked, [
                'old_active' => $before,
                'active' => !$before,
                'sessions_revoked' => $before,
            ]);

            return $locked->fresh();
        });
    }

    public function bulkCreateStudents(
        int $tenantId,
        ?User $actor = null,
        ?Request $request = null,
    ): array {
        $created = 0;
        $skipped = 0;

        Student::where('tenant_id', $tenantId)
            ->where('status', Student::STATUS_ACTIVE)
            ->whereNull('user_id')
            ->orderBy('id')
            ->chunkById(100, function ($students) use ($tenantId, $actor, $request, &$created, &$skipped): void {
                foreach ($students as $student) {
                    $email = strtolower(trim((string) $student->email));
                    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || User::where('email', $email)->exists()) {
                        $skipped++;
                        continue;
                    }

                    try {
                        // Intentionally never returned or displayed. Bulk-created
                        // users set their own password via the Forgot Password flow.
                        $password = Str::password(32, true, true, true, false);
                        $this->createStudentAccount($tenantId, $student, $email, $password, $actor, $request);
                        $created++;
                    } catch (ValidationException) {
                        $skipped++;
                    }
                }
            });

        return ['created' => $created, 'skipped' => $skipped];
    }

    public function assertPortalUser(User $user, int $tenantId): void
    {
        abort_unless(
            (int) $user->tenant_id === $tenantId
            && in_array($user->roleKey(), User::ROLES_PORTAL, true),
            404
        );
    }

    private function assertStudentTenant(Student $student, int $tenantId): void
    {
        abort_unless((int) $student->tenant_id === $tenantId, 404);
    }

    private function revokeSessions(int $userId): void
    {
        if (Schema::hasTable('api_tokens')) {
            ApiToken::query()->where('user_id', $userId)->delete();
        }
        if (Schema::hasTable('sessions') && Schema::hasColumn('sessions', 'user_id')) {
            DB::table('sessions')->where('user_id', $userId)->delete();
        }
        if (Schema::hasTable('push_subscriptions')) {
            PushSubscription::query()->where('user_id', $userId)->delete();
        }
    }

    private function audit(
        int $tenantId,
        ?User $actor,
        ?Request $request,
        string $action,
        User $portalUser,
        array $newValues,
    ): void {
        if (!Schema::hasTable('audit_logs')) {
            return;
        }

        AuditLog::create([
            'tenant_id' => $tenantId,
            'actor_user_id' => $actor?->id,
            'auditable_type' => User::class,
            'auditable_id' => $portalUser->id,
            'action' => $action,
            'old_values' => [],
            'new_values' => $newValues,
            'reason' => null,
            'ip_address' => $request?->ip(),
            'user_agent' => $request?->userAgent(),
        ]);
    }
}
