<?php

namespace App\Http\Controllers;

use App\Models\DataMigration;
use App\Models\MigrationRequest as EnterpriseMigrationRequest;
use App\Models\Tenant;
use App\Services\DataMigration\ImmutableSourceStorage;
use App\Services\DataMigration\MigrationBatchService;
use App\Services\DataMigration\MigrationEnterpriseControlService;
use App\Services\DataMigration\MigrationEnterpriseDashboardService;
use App\Services\DataMigration\MigrationIngestionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DataMigrationController extends Controller
{
    public function index(Request $request, MigrationEnterpriseDashboardService $dashboard): View
    {
        $actor = $request->user();
        $platform = $actor->isSuperAdmin();
        abort_unless($platform || ($actor->isAdmin() && $actor->tenant_id), 403);

        $tenantId = $platform ? ($request->integer('tenant_id') ?: null) : (int) $actor->tenant_id;
        $query = DataMigration::query()
            ->with('tenant:id,name')
            ->when($tenantId, fn ($builder) => $builder->where('tenant_id', $tenantId));

        return view('data-migrations.index', [
            'platform' => $platform,
            'summary' => $dashboard->summary($actor, $tenantId),
            'migrations' => $query->latest()->paginate(20)->withQueryString(),
            'requests' => EnterpriseMigrationRequest::query()
                ->with(['migration:id,batch_number,status'])
                ->when($tenantId, fn ($builder) => $builder->where('tenant_id', $tenantId))
                ->latest()->limit(20)->get(),
            'tenants' => $platform ? Tenant::query()->orderBy('name')->get(['id', 'name']) : collect(),
        ]);
    }

    public function store(Request $request, MigrationBatchService $batches, MigrationEnterpriseControlService $controls, ImmutableSourceStorage $storage): RedirectResponse
    {
        $actor = $request->user();
        $platform = $actor->isSuperAdmin();
        abort_unless($platform || ($actor->isAdmin() && $actor->tenant_id), 403);
        $data = $request->validate([
            'tenant_id' => [$platform ? 'required' : 'nullable', 'integer', 'exists:tenants,id'],
            'direction' => ['required', 'in:inbound,outbound'],
            'migration_type' => ['required', 'in:full_migration,standard_import,full_export,selective_export'],
            'source_platform' => ['required', 'string', 'max:120'],
            'source_system_other' => ['nullable', 'string', 'max:120'],
            'destination_system' => ['nullable', 'string', 'max:120'],
            'business_justification' => ['required', 'string', 'min:20', 'max:2000'],
            'data_scope' => ['required', 'array', 'min:1'],
            'data_scope.*' => ['string', 'in:students,guardians,staff,academics,attendance,finance,configuration'],
            'source_files' => ['required', 'array', 'min:1', 'max:20'],
            'source_files.*' => ['required', 'file', 'max:524288'],
        ]);

        $tenant = Tenant::query()->findOrFail($platform ? $data['tenant_id'] : $actor->tenant_id);
        $sourceSystem = $data['source_platform'] === 'other'
            ? trim((string) ($data['source_system_other'] ?? ''))
            : $data['source_platform'];
        if ($sourceSystem === '') {
            return back()->withErrors(['source_system_other' => 'Enter the source platform name.'])->withInput();
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
        $controls->request($migration, $actor, $data['business_justification'], $data['data_scope']);

        $route = $platform ? 'super.migrations.show' : 'migrations.show';

        return redirect()->route($route, $migration)->with('success', 'Source files uploaded securely. Inspect and stage the batch, then complete the approval workflow.');
    }

    public function ingest(Request $request, DataMigration $migration, MigrationIngestionService $ingestion): RedirectResponse
    {
        $actor = $request->user();
        abort_unless($actor->isSuperAdmin() || ($actor->isAdmin() && (int) $actor->tenant_id === (int) $migration->tenant_id), 403);

        try {
            $ingestion->ingest($migration, $actor);
        } catch (\Throwable $exception) {
            return back()->withErrors(['ingestion' => $exception->getMessage()]);
        }

        return back()->with('success', 'Files inspected successfully and source rows staged for mapping.');
    }

    public function schoolApprove(Request $request, EnterpriseMigrationRequest $migrationRequest, MigrationEnterpriseControlService $controls): RedirectResponse
    {
        $data = $request->validate(['reason' => ['required', 'string', 'min:10', 'max:1000']]);
        $controls->approveBySchool($migrationRequest, $request->user(), $data['reason']);

        return back()->with('success', 'School approval recorded. The platform Migration Administrator has been notified.');
    }

    public function platformApprove(Request $request, EnterpriseMigrationRequest $migrationRequest, MigrationEnterpriseControlService $controls): RedirectResponse
    {
        $data = $request->validate(['reason' => ['required', 'string', 'min:10', 'max:1000']]);
        $controls->approveByPlatform($migrationRequest, $request->user(), $data['reason']);

        return back()->with('success', 'Platform execution approval recorded.');
    }

    public function reject(Request $request, EnterpriseMigrationRequest $migrationRequest, MigrationEnterpriseControlService $controls): RedirectResponse
    {
        $data = $request->validate(['reason' => ['required', 'string', 'min:10', 'max:1000']]);
        $controls->reject($migrationRequest, $request->user(), $data['reason']);

        return back()->with('success', 'Migration request rejected with an auditable reason.');
    }

    public function show(Request $request, DataMigration $migration, MigrationEnterpriseDashboardService $dashboard): View
    {
        $platform = $request->user()->isSuperAdmin();

        return view('data-migrations.show', [
            'platform' => $platform,
            'migration' => $migration->load(['tenant:id,name', 'files', 'datasets']),
            'report' => $dashboard->report($migration, $request->user()),
            'migrationRequest' => EnterpriseMigrationRequest::query()->where('migration_id', $migration->id)->first(),
        ]);
    }
}
