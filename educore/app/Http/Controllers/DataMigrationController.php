<?php

namespace App\Http\Controllers;

use App\Models\DataMigration;
use App\Models\MigrationRequest as EnterpriseMigrationRequest;
use App\Models\Tenant;
use App\Services\DataMigration\MigrationBatchService;
use App\Services\DataMigration\MigrationEnterpriseControlService;
use App\Services\DataMigration\MigrationEnterpriseDashboardService;
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

    public function store(Request $request, MigrationBatchService $batches, MigrationEnterpriseControlService $controls): RedirectResponse
    {
        $actor = $request->user();
        abort_unless($actor->isAdmin() && $actor->tenant_id, 403);
        $data = $request->validate([
            'direction' => ['required', 'in:inbound,outbound'],
            'migration_type' => ['required', 'in:full_migration,standard_import,full_export,selective_export'],
            'source_system' => ['nullable', 'string', 'max:120'],
            'destination_system' => ['nullable', 'string', 'max:120'],
            'business_justification' => ['required', 'string', 'min:20', 'max:2000'],
            'data_scope' => ['required', 'array', 'min:1'],
            'data_scope.*' => ['string', 'in:students,guardians,staff,academics,attendance,finance,configuration'],
        ]);

        $tenant = Tenant::query()->findOrFail($actor->tenant_id);
        $migration = $batches->create(
            $tenant,
            $actor,
            $data['direction'],
            $data['migration_type'],
            $data['source_system'] ?? null,
            $data['destination_system'] ?? 'EduCore',
        );
        $controls->request($migration, $actor, $data['business_justification'], $data['data_scope']);

        return redirect()->route('migrations.show', $migration)->with('success', 'Migration request created and submitted for school approval.');
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
            'migration' => $migration->load('tenant:id,name'),
            'report' => $dashboard->report($migration, $request->user()),
            'migrationRequest' => EnterpriseMigrationRequest::query()->where('migration_id', $migration->id)->first(),
        ]);
    }
}
