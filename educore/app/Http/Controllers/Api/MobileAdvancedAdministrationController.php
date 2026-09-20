<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\DataMigration;
use App\Models\MigrationRequest as EnterpriseMigrationRequest;
use App\Models\Tenant;
use App\Services\DataMigration\ImmutableSourceStorage;
use App\Services\DataMigration\MigrationBatchService;
use App\Services\DataMigration\MigrationEnterpriseControlService;
use App\Services\DataMigration\MigrationIngestionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Validation\Rule;
use Throwable;

class MobileAdvancedAdministrationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $actor = $request->user();
        $this->authorizeActor($actor);

        $platform = $actor->isSuperAdmin();
        $tenantId = $platform ? ($request->integer('tenant_id') ?: null) : (int) $actor->tenant_id;

        $migrations = DataMigration::query()
            ->with(['tenant:id,name'])
            ->withCount(['files', 'datasets', 'issues'])
            ->when($tenantId, fn ($query) => $query->where('tenant_id', $tenantId))
            ->latest('id')
            ->limit(20)
            ->get()
            ->map(fn (DataMigration $migration) => [
                'id' => $migration->id,
                'batch_number' => $migration->batch_number,
                'tenant_id' => $migration->tenant_id,
                'tenant_name' => $migration->tenant?->name,
                'direction' => $migration->direction,
                'migration_type' => $migration->migration_type,
                'source_system' => $migration->source_system,
                'status' => $migration->status?->value ?? (string) $migration->status,
                'files_count' => $migration->files_count,
                'datasets_count' => $migration->datasets_count,
                'issues_count' => $migration->issues_count,
                'total_source_rows' => (int) $migration->total_source_rows,
                'total_created' => (int) $migration->total_created,
                'total_updated' => (int) $migration->total_updated,
                'total_failed' => (int) $migration->total_failed,
                'created_at' => $migration->created_at?->toIso8601String(),
                'completed_at' => $migration->completed_at?->toIso8601String(),
            ]);

        $requests = EnterpriseMigrationRequest::query()
            ->with(['migration:id,batch_number,status'])
            ->when($tenantId, fn ($query) => $query->where('tenant_id', $tenantId))
            ->latest('id')
            ->limit(20)
            ->get()
            ->map(fn (EnterpriseMigrationRequest $item) => [
                'id' => $item->id,
                'migration_id' => $item->migration_id,
                'batch_number' => $item->migration?->batch_number,
                'tenant_id' => $item->tenant_id,
                'status' => $item->status,
                'risk_level' => $item->risk_level,
                'business_justification' => $item->business_justification,
                'data_scope' => $item->data_scope ?? [],
                'decision_reason' => $item->decision_reason,
                'created_at' => $item->created_at?->toIso8601String(),
            ]);

        $auditBase = AuditLog::query()
            ->when($tenantId, fn ($query) => $query->where('tenant_id', $tenantId))
            ->where('created_at', '>=', now()->subDays(7));

        $auditStats = [
            'events_7d' => (clone $auditBase)->count(),
            'unique_actors_7d' => (clone $auditBase)
                ->whereNotNull('actor_user_id')
                ->distinct('actor_user_id')
                ->count('actor_user_id'),
            'security_signals_7d' => (clone $auditBase)
                ->where(function ($query) {
                    foreach (['failed', 'denied', 'invalid', 'security', 'password', 'token', 'locked', 'blocked', 'revoked', 'deleted'] as $index => $word) {
                        $method = $index === 0 ? 'where' : 'orWhere';
                        $query->{$method}('action', 'like', "%{$word}%");
                    }
                })
                ->count(),
        ];

        $audit = AuditLog::query()
            ->with(['actor:id,name,email', 'tenant:id,name'])
            ->when($tenantId, fn ($query) => $query->where('tenant_id', $tenantId))
            ->latest('id')
            ->limit(30)
            ->get()
            ->map(fn (AuditLog $log) => [
                'id' => $log->id,
                'action' => $log->action,
                'actor' => $log->actor?->name,
                'tenant' => $log->tenant?->name,
                'reason' => $log->reason,
                'ip_address' => $log->ip_address,
                'created_at' => $log->created_at?->toIso8601String(),
            ]);

        return response()->json([
            'contract_version' => 1,
            'scope' => $platform ? 'platform' : 'tenant',
            'selected_tenant_id' => $tenantId,
            'capabilities' => [
                'create_migration' => true,
                'ingest_migration' => true,
                'verify_migration' => true,
                'reconstruct_blueprint' => true,
                'approve_school' => ! $platform,
                'approve_platform' => $actor->isMigrationAdmin(),
                'run_backup' => $platform,
            ],
            'tenants' => $platform
                ? Tenant::query()->orderBy('name')->get(['id', 'name'])
                : [],
            'migrations' => $migrations,
            'migration_requests' => $requests,
            'audit_stats' => $auditStats,
            'audit' => $audit,
            'backups' => $platform ? $this->backupFiles() : [],
            'generated_at' => now()->toIso8601String(),
        ]);
    }

    public function storeMigration(
        Request $request,
        MigrationBatchService $batches,
        MigrationEnterpriseControlService $controls,
        ImmutableSourceStorage $storage,
    ): JsonResponse {
        $actor = $request->user();
        $this->authorizeActor($actor);
        $platform = $actor->isSuperAdmin();

        $data = $request->validate([
            'tenant_id' => [$platform ? 'required' : 'nullable', 'integer', 'exists:tenants,id'],
            'direction' => ['required', Rule::in(['inbound', 'outbound'])],
            'migration_type' => ['required', Rule::in(['full_migration', 'standard_import', 'full_export', 'selective_export'])],
            'source_platform' => ['required', 'string', 'max:120'],
            'source_system_other' => ['nullable', 'string', 'max:120'],
            'destination_system' => ['nullable', 'string', 'max:120'],
            'business_justification' => ['required', 'string', 'min:20', 'max:2000'],
            'data_scope' => ['required', 'array', 'min:1'],
            'data_scope.*' => ['string', Rule::in(['students', 'guardians', 'staff', 'academics', 'attendance', 'finance', 'configuration'])],
            'source_files' => ['required', 'array', 'min:1', 'max:20'],
            'source_files.*' => ['required', 'file', 'max:524288'],
        ]);

        $tenant = Tenant::query()->findOrFail($platform ? (int) $data['tenant_id'] : (int) $actor->tenant_id);
        $sourceSystem = trim((string) $data['source_platform']);

        if ($sourceSystem === '') {
            return response()->json(['message' => 'Enter the source platform name.'], 422);
        }

        $migration = $batches->create(
            $tenant,
            $actor,
            $data['direction'],
            $data['migration_type'],
            $sourceSystem,
            $data['destination_system'] ?? 'EduCore',
        );

        foreach ($request->file('source_files', []) as $sourceFile) {
            $storage->preserve($migration, $sourceFile, $actor);
        }

        $migrationRequest = $controls->request(
            $migration,
            $actor,
            $data['business_justification'],
            $data['data_scope'],
        );

        return response()->json([
            'message' => 'Migration source files uploaded securely and approval workflow started.',
            'migration_id' => $migration->id,
            'batch_number' => $migration->batch_number,
            'request_id' => $migrationRequest->id,
            'request_status' => $migrationRequest->status,
        ], 201);
    }

    public function ingest(Request $request, DataMigration $migration, MigrationIngestionService $ingestion): JsonResponse
    {
        $actor = $request->user();
        $this->authorizeMigration($actor, $migration);

        try {
            $ingestion->ingest($migration, $actor);
        } catch (Throwable $error) {
            return response()->json(['message' => $error->getMessage()], 422);
        }

        return response()->json(['message' => 'Migration source files inspected and staged successfully.']);
    }

    public function verify(Request $request, DataMigration $migration): JsonResponse
    {
        $actor = $request->user();
        $this->authorizeMigration($actor, $migration);

        $status = Artisan::call('data-migration:verify', ['migration' => $migration->batch_number]);
        return response()->json([
            'ok' => $status === 0,
            'message' => trim(Artisan::output()) ?: ($status === 0 ? 'Migration integrity verified.' : 'Migration verification reported issues.'),
        ], $status === 0 ? 200 : 422);
    }

    public function reconstructBlueprint(Request $request, DataMigration $migration): JsonResponse
    {
        $actor = $request->user();
        $this->authorizeMigration($actor, $migration);
        $request->validate(['confirmation' => ['required', 'in:RECONSTRUCT']]);

        $status = Artisan::call('data-migration:blueprint', [
            'migration' => $migration->batch_number,
            '--actor' => $actor->id,
        ]);

        return response()->json([
            'ok' => $status === 0,
            'message' => trim(Artisan::output()) ?: ($status === 0 ? 'Staged school blueprint reconstructed.' : 'Blueprint reconstruction failed.'),
        ], $status === 0 ? 200 : 422);
    }

    public function approve(
        Request $request,
        EnterpriseMigrationRequest $migrationRequest,
        MigrationEnterpriseControlService $controls,
    ): JsonResponse {
        $actor = $request->user();
        $this->authorizeActor($actor);
        $data = $request->validate(['reason' => ['required', 'string', 'min:10', 'max:1000']]);

        $updated = $migrationRequest->status === 'awaiting_school_approval'
            ? $controls->approveBySchool($migrationRequest, $actor, $data['reason'])
            : $controls->approveByPlatform($migrationRequest, $actor, $data['reason']);

        return response()->json([
            'message' => 'Migration approval recorded.',
            'status' => $updated->status,
        ]);
    }

    public function reject(
        Request $request,
        EnterpriseMigrationRequest $migrationRequest,
        MigrationEnterpriseControlService $controls,
    ): JsonResponse {
        $actor = $request->user();
        $this->authorizeActor($actor);
        $data = $request->validate(['reason' => ['required', 'string', 'min:10', 'max:1000']]);
        $updated = $controls->reject($migrationRequest, $actor, $data['reason']);

        return response()->json([
            'message' => 'Migration request rejected.',
            'status' => $updated->status,
        ]);
    }

    public function backup(Request $request): JsonResponse
    {
        $actor = $request->user();
        abort_unless($actor && $actor->isSuperAdmin(), 403);
        $request->validate(['confirmation' => ['required', 'in:BACKUP']]);

        $status = Artisan::call('backup:database', ['--prune-days' => 14]);
        $files = $this->backupFiles();

        return response()->json([
            'ok' => $status === 0,
            'message' => trim(Artisan::output()) ?: ($status === 0 ? 'Database backup completed.' : 'Database backup failed.'),
            'backup' => $files[0] ?? null,
        ], $status === 0 ? 200 : 422);
    }

    private function authorizeActor($actor): void
    {
        abort_unless($actor && ($actor->isSuperAdmin() || ($actor->isAdmin() && $actor->tenant_id)), 403);
    }

    private function authorizeMigration($actor, DataMigration $migration): void
    {
        $this->authorizeActor($actor);
        abort_unless(
            $actor->isSuperAdmin() || (int) $actor->tenant_id === (int) $migration->tenant_id,
            403,
        );
    }

    private function backupFiles(): array
    {
        $directory = storage_path('app/backups');
        if (! File::isDirectory($directory)) {
            return [];
        }

        return collect(File::files($directory))
            ->filter(fn ($file) => strtolower($file->getExtension()) === 'sql')
            ->sortByDesc(fn ($file) => $file->getMTime())
            ->take(10)
            ->map(fn ($file) => [
                'name' => $file->getFilename(),
                'size' => $file->getSize(),
                'created_at' => date(DATE_ATOM, $file->getMTime()),
            ])
            ->values()
            ->all();
    }
}
