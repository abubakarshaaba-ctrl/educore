<?php

namespace App\Services\DataMigration;

use App\Enums\DataMigrationStatus;
use App\Models\DataMigration;
use App\Models\MigrationApproval;
use App\Models\MigrationNotification;
use App\Models\MigrationRequest;
use App\Models\User;
use App\Services\LifecycleAuditLogger;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\UnauthorizedException;
use InvalidArgumentException;

class MigrationEnterpriseControlService
{
    public function __construct(
        private readonly LifecycleAuditLogger $audit,
        private readonly MigrationStateMachine $states,
    ) {}

    public function request(DataMigration $migration, User $actor, string $justification, array $dataScope): MigrationRequest
    {
        $this->requireTenantAdmin($actor, $migration->tenant_id);

        if (trim($justification) === '' || $dataScope === []) {
            throw new InvalidArgumentException('A business justification and explicit data scope are required.');
        }

        return DB::transaction(function () use ($migration, $actor, $justification, $dataScope): MigrationRequest {
            $request = MigrationRequest::query()->create([
                'migration_id' => $migration->id,
                'tenant_id' => $migration->tenant_id,
                'requested_by' => $actor->id,
                'status' => 'awaiting_school_approval',
                'requested_scope' => $migration->migration_type,
                'business_justification' => trim($justification),
                'data_scope' => array_values(array_unique($dataScope)),
                'risk_level' => $migration->migration_type === 'full_migration' ? 'critical' : 'high',
            ]);

            $this->notifyTenantAdmins($migration, 'migration.requested', ['request_id' => $request->id]);
            $this->audit->record($migration->tenant_id, $actor, $request, 'data_migration.enterprise_request_created', [], $request->only([
                'migration_id', 'requested_scope', 'data_scope', 'risk_level', 'status',
            ]), $justification);

            return $request;
        });
    }

    public function approveBySchool(MigrationRequest $request, User $actor, string $reason): MigrationRequest
    {
        $this->requireTenantAdmin($actor, $request->tenant_id);
        $this->requireStatus($request, 'awaiting_school_approval');

        return DB::transaction(function () use ($request, $actor, $reason): MigrationRequest {
            $this->recordApproval($request, $actor, 'school_admin', $reason);
            $request->update([
                'status' => 'awaiting_platform_approval',
                'school_approved_by' => $actor->id,
                'school_approved_at' => now(),
                'decision_reason' => $reason,
            ]);
            $this->notifyMigrationAdmins($request->migration_id, $request->tenant_id, 'migration.school_approved', ['request_id' => $request->id]);
            $this->auditDecision($request, $actor, 'data_migration.school_approved', $reason);

            return $request->refresh();
        });
    }

    public function approveByPlatform(MigrationRequest $request, User $actor, string $reason): MigrationRequest
    {
        if (! $actor->isMigrationAdmin()) {
            throw new UnauthorizedException('A Migration Administrator is required.');
        }
        $this->requireStatus($request, 'awaiting_platform_approval');

        return DB::transaction(function () use ($request, $actor, $reason): MigrationRequest {
            $this->recordApproval($request, $actor, 'platform_execution', $reason);
            $request->update([
                'status' => 'approved',
                'platform_approved_by' => $actor->id,
                'platform_approved_at' => now(),
                'decision_reason' => $reason,
            ]);

            $migration = DataMigration::query()->findOrFail($request->migration_id);
            if ($migration->status === DataMigrationStatus::AwaitingApproval) {
                $this->states->transition($migration, DataMigrationStatus::Approved, $actor, $reason);
            }

            $this->notifyTenantAdmins($migration, 'migration.platform_approved', ['request_id' => $request->id]);
            $this->auditDecision($request, $actor, 'data_migration.platform_approved', $reason);

            return $request->refresh();
        });
    }

    public function reject(MigrationRequest $request, User $actor, string $reason): MigrationRequest
    {
        if ($request->status === 'awaiting_school_approval') {
            $this->requireTenantAdmin($actor, $request->tenant_id);
            $approvalType = 'school_admin';
        } elseif ($request->status === 'awaiting_platform_approval' && $actor->isMigrationAdmin()) {
            $approvalType = 'platform_execution';
        } else {
            throw new UnauthorizedException('This user cannot reject the migration request at its current stage.');
        }

        if (trim($reason) === '') {
            throw new InvalidArgumentException('A rejection reason is required.');
        }

        return DB::transaction(function () use ($request, $actor, $reason, $approvalType): MigrationRequest {
            MigrationApproval::query()->create([
                'migration_id' => $request->migration_id,
                'tenant_id' => $request->tenant_id,
                'approval_type' => $approvalType,
                'decision' => 'rejected',
                'decided_by' => $actor->id,
                'reason' => $reason,
                'approved_snapshot' => $this->snapshot($request),
                'decided_at' => now(),
            ]);
            $request->update(['status' => 'rejected', 'decision_reason' => $reason]);
            $migration = DataMigration::query()->findOrFail($request->migration_id);
            $this->notifyTenantAdmins($migration, 'migration.rejected', ['request_id' => $request->id, 'reason' => $reason]);
            $this->auditDecision($request, $actor, 'data_migration.request_rejected', $reason);

            return $request->refresh();
        });
    }

    private function requireTenantAdmin(User $actor, int $tenantId): void
    {
        if (! $actor->isAdmin() || (int) $actor->tenant_id !== $tenantId) {
            throw new UnauthorizedException('A School Administrator for this institution is required.');
        }
    }

    private function requireStatus(MigrationRequest $request, string $status): void
    {
        if ($request->status !== $status) {
            throw new InvalidArgumentException("Request must be {$status}.");
        }
    }

    private function recordApproval(MigrationRequest $request, User $actor, string $type, string $reason): void
    {
        MigrationApproval::query()->create([
            'migration_id' => $request->migration_id,
            'tenant_id' => $request->tenant_id,
            'approval_type' => $type,
            'decision' => 'approved',
            'decided_by' => $actor->id,
            'reason' => $reason,
            'approved_snapshot' => $this->snapshot($request),
            'decided_at' => now(),
        ]);
    }

    private function snapshot(MigrationRequest $request): array
    {
        return $request->only(['migration_id', 'tenant_id', 'requested_scope', 'business_justification', 'data_scope', 'risk_level']);
    }

    private function notifyTenantAdmins(DataMigration $migration, string $event, array $payload): void
    {
        User::query()->where('tenant_id', $migration->tenant_id)->where('role', 'admin')
            ->each(fn (User $user) => $this->notification($migration->id, $migration->tenant_id, $user->id, $event, $payload));
    }

    private function notifyMigrationAdmins(int $migrationId, int $tenantId, string $event, array $payload): void
    {
        User::query()->where(fn ($query) => $query->where('is_migration_admin', true)->orWhere('is_super_admin', true))
            ->each(fn (User $user) => $this->notification($migrationId, $tenantId, $user->id, $event, $payload));
    }

    private function notification(int $migrationId, int $tenantId, int $recipientId, string $event, array $payload): void
    {
        MigrationNotification::query()->create([
            'migration_id' => $migrationId,
            'tenant_id' => $tenantId,
            'recipient_user_id' => $recipientId,
            'event' => $event,
            'payload' => $payload,
            'status' => 'pending',
        ]);
    }

    private function auditDecision(MigrationRequest $request, User $actor, string $action, string $reason): void
    {
        $this->audit->record($request->tenant_id, $actor, $request, $action, [], [
            'status' => $request->status,
            'decision_reason' => $reason,
        ], $reason);
    }
}
