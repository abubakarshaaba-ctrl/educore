@extends($platform ? 'layouts.super' : 'layouts.app')
@section('title', $platform ? 'Migration Control' : 'Migration Center')
@section('page-title', $platform ? 'Migration Control' : 'Migration Center')

@push('styles')
<style>
.migration-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:14px;margin-bottom:18px}.metric{background:#fff;border:1px solid var(--border);border-radius:12px;padding:16px}.metric strong{display:block;font-size:27px;color:#071e45}.metric span{font-size:11px;text-transform:uppercase;letter-spacing:.05em;color:#64748b}.migration-layout{display:grid;grid-template-columns:minmax(0,1.6fr) minmax(300px,.8fr);gap:18px}.card{margin-bottom:18px}.card-body{padding:16px}.migration-table{width:100%;border-collapse:collapse}.migration-table th,.migration-table td{padding:11px 13px;border-bottom:1px solid var(--border);text-align:left;font-size:12px}.migration-table th{background:#f8fafc;color:#64748b;text-transform:uppercase;font-size:10px}.badge{display:inline-flex;padding:4px 8px;border-radius:999px;background:#eef2ff;color:#3730a3;font-size:10px;font-weight:700;text-transform:capitalize}.badge.attention{background:#fff7ed;color:#9a3412}.field{margin-bottom:13px}.field label{display:block;font-size:11px;font-weight:700;margin-bottom:5px;color:#334155}.field input,.field select,.field textarea{width:100%;border:1px solid var(--border);border-radius:8px;padding:9px 10px;font:inherit}.scope-grid{display:grid;grid-template-columns:repeat(2,1fr);gap:7px;font-size:12px}.scope-grid label{display:flex;gap:6px;align-items:center}.scope-grid input{width:auto}.empty{padding:28px;text-align:center;color:#64748b}.filter{display:flex;gap:9px;align-items:end}.filter .field{margin:0;flex:1}@media(max-width:900px){.migration-grid{grid-template-columns:repeat(2,1fr)}.migration-layout{grid-template-columns:1fr}}@media(max-width:520px){.migration-grid{grid-template-columns:1fr}.table-wrap{overflow:auto}.migration-table{min-width:650px}}
</style>
@endpush

@section('content')
@if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
<div class="migration-grid">
    <div class="metric"><strong>{{ $summary['migrations']['total'] }}</strong><span>Total migrations</span></div>
    <div class="metric"><strong>{{ $summary['migrations']['in_progress'] }}</strong><span>In progress</span></div>
    <div class="metric"><strong>{{ $summary['migrations']['attention_required'] }}</strong><span>Needs attention</span></div>
    <div class="metric"><strong>{{ $summary['migrations']['completed'] }}</strong><span>Completed</span></div>
</div>

@if($platform)
<div class="card"><div class="card-header">Platform oversight</div><div class="card-body">
    <form method="GET" action="{{ route('super.migrations.index') }}" class="filter">
        <div class="field"><label>Institution</label><select name="tenant_id"><option value="">All institutions</option>@foreach($tenants as $tenant)<option value="{{ $tenant->id }}" @selected(request('tenant_id') == $tenant->id)>{{ $tenant->name }}</option>@endforeach</select></div>
        <button class="btn btn-primary">Apply filter</button>
    </form>
</div></div>
@endif

<div class="migration-layout">
<div>
    <div class="card"><div class="card-header">Migration batches</div><div class="table-wrap"><table class="migration-table">
        <thead><tr><th>Batch</th>@if($platform)<th>Institution</th>@endif<th>Direction</th><th>Scope</th><th>Status</th><th>Updated</th></tr></thead>
        <tbody>@forelse($migrations as $migration)<tr>
            <td><a href="{{ $platform ? route('super.migrations.show',$migration) : route('migrations.show',$migration) }}"><strong>{{ $migration->batch_number }}</strong></a></td>
            @if($platform)<td>{{ $migration->tenant?->name }}</td>@endif
            <td>{{ ucfirst($migration->direction) }}</td><td>{{ str($migration->migration_type)->replace('_',' ')->title() }}</td>
            <td><span class="badge {{ in_array($migration->status->value,['failed','partial','needs_review']) ? 'attention':'' }}">{{ str($migration->status->value)->replace('_',' ') }}</span></td>
            <td>{{ $migration->updated_at->diffForHumans() }}</td>
        </tr>@empty<tr><td colspan="6" class="empty">No migration batches have been created.</td></tr>@endforelse</tbody>
    </table></div></div>
    {{ $migrations->links() }}

    <div class="card"><div class="card-header">Approval queue</div><div class="table-wrap"><table class="migration-table">
        <thead><tr><th>Batch</th><th>Risk</th><th>Scope</th><th>Decision stage</th></tr></thead>
        <tbody>@forelse($requests as $approval)<tr><td>{{ $approval->migration?->batch_number }}</td><td>{{ ucfirst($approval->risk_level) }}</td><td>{{ str($approval->requested_scope)->replace('_',' ')->title() }}</td><td><span class="badge">{{ str($approval->status)->replace('_',' ') }}</span></td></tr>@empty<tr><td colspan="4" class="empty">No approval requests.</td></tr>@endforelse</tbody>
    </table></div></div>
</div>

@unless($platform)
<aside><div class="card"><div class="card-header">Request a migration</div><div class="card-body">
    <p style="font-size:12px;color:#64748b;margin:0 0 14px">Register a controlled import, export, or full institutional migration. A school approval and platform authorization will be recorded before execution.</p>
    <form method="POST" action="{{ route('migrations.store') }}">@csrf
        <div class="field"><label>Direction</label><select name="direction" required><option value="inbound">Into EduCore</option><option value="outbound">Out of EduCore</option></select></div>
        <div class="field"><label>Migration type</label><select name="migration_type" required><option value="full_migration">Full institutional migration</option><option value="standard_import">Standard import</option><option value="full_export">Full portable export</option><option value="selective_export">Selective portable export</option></select></div>
        <div class="field"><label>Source system</label><input name="source_system" value="{{ old('source_system') }}" placeholder="Existing SIS or file source"></div>
        <div class="field"><label>Data scope</label><div class="scope-grid">@foreach(['students','guardians','staff','academics','attendance','finance','configuration'] as $scope)<label><input type="checkbox" name="data_scope[]" value="{{ $scope }}"> {{ ucfirst($scope) }}</label>@endforeach</div></div>
        <div class="field"><label>Business justification</label><textarea name="business_justification" rows="4" required placeholder="Why is this migration required, and what outcome is expected?">{{ old('business_justification') }}</textarea></div>
        <button class="btn btn-primary" style="width:100%;justify-content:center">Create migration request</button>
    </form>
</div></div></aside>
@endunless
</div>
@endsection
