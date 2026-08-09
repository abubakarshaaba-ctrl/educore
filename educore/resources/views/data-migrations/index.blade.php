@extends($platform ? 'layouts.super' : 'layouts.app')
@section('title', $platform ? 'Migration Control' : 'Migration Center')
@section('page-title', $platform ? 'Migration Control' : 'Migration Center')

@push('styles')
<style>
.migration-hero{position:relative;overflow:hidden;background:linear-gradient(120deg,#071e45,#123d73);color:#fff;border-radius:18px;padding:25px 28px;margin-bottom:18px}.migration-hero:after{content:"";position:absolute;width:240px;height:240px;border-radius:50%;right:-70px;top:-110px;background:rgba(255,255,255,.08)}.migration-hero h2{font-size:23px;margin:0 0 7px}.migration-hero p{max-width:720px;font-size:13px;color:#dbeafe;margin:0}.workflow{display:grid;grid-template-columns:repeat(5,1fr);gap:0;margin-top:21px;position:relative;z-index:1}.workflow div{border-top:2px solid rgba(255,255,255,.28);padding:10px 8px 0 0;font-size:11px;color:#bfdbfe}.workflow b{display:block;color:#fff;font-size:12px;margin-bottom:2px}.migration-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:14px;margin-bottom:18px}.metric{background:#fff;border:1px solid var(--border);border-radius:13px;padding:16px;box-shadow:0 5px 18px rgba(15,23,42,.04)}.metric strong{display:block;font-size:27px;color:#071e45}.metric span{font-size:11px;text-transform:uppercase;letter-spacing:.05em;color:#64748b}.migration-layout{display:grid;grid-template-columns:minmax(0,1.45fr) minmax(350px,.85fr);gap:18px}.card{margin-bottom:18px;box-shadow:0 7px 24px rgba(15,23,42,.04)}.card-body{padding:18px}.migration-table{width:100%;border-collapse:collapse}.migration-table th,.migration-table td{padding:11px 13px;border-bottom:1px solid var(--border);text-align:left;font-size:12px}.migration-table th{background:#f8fafc;color:#64748b;text-transform:uppercase;font-size:10px}.badge{display:inline-flex;padding:4px 8px;border-radius:999px;background:#eef2ff;color:#3730a3;font-size:10px;font-weight:700;text-transform:capitalize}.badge.attention{background:#fff7ed;color:#9a3412}.field{margin-bottom:14px}.field label{display:block;font-size:11px;font-weight:700;margin-bottom:5px;color:#334155}.field input,.field select,.field textarea{width:100%;border:1px solid var(--border);border-radius:9px;padding:10px 11px;font:inherit;background:#fff}.field input:focus,.field select:focus,.field textarea:focus{outline:2px solid #bfdbfe;border-color:#2563eb}.upload-zone{border:2px dashed #93c5fd;background:#eff6ff;border-radius:12px;padding:18px;text-align:center}.upload-zone strong{display:block;color:#0f3b70;font-size:13px}.upload-zone small{display:block;color:#64748b;margin:5px 0 10px}.scope-grid{display:grid;grid-template-columns:repeat(2,1fr);gap:7px;font-size:12px}.scope-grid label{display:flex;gap:6px;align-items:center;padding:7px;background:#f8fafc;border-radius:7px}.scope-grid input{width:auto}.form-title{display:flex;align-items:center;gap:10px;margin-bottom:5px}.form-title span{display:grid;place-items:center;width:31px;height:31px;border-radius:9px;background:#dbeafe;color:#1d4ed8;font-weight:800}.empty{padding:28px;text-align:center;color:#64748b}.filter{display:flex;gap:9px;align-items:end}.filter .field{margin:0;flex:1}.help-note{padding:10px 12px;border-radius:9px;background:#f0fdf4;color:#166534;font-size:11px;margin-bottom:13px}@media(max-width:900px){.migration-grid{grid-template-columns:repeat(2,1fr)}.migration-layout{grid-template-columns:1fr}.workflow{grid-template-columns:1fr}.workflow div{border-top:0;border-left:2px solid rgba(255,255,255,.28);padding:4px 0 7px 10px}}@media(max-width:520px){.migration-grid{grid-template-columns:1fr}.table-wrap{overflow:auto}.migration-table{min-width:650px}.migration-hero{padding:20px}.scope-grid{grid-template-columns:1fr}}
</style>
@endpush

@section('content')
@if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
@if(isset($errors) && $errors->any())<div class="alert alert-danger"><strong>Please correct the migration request:</strong><ul style="margin:6px 0 0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
<section class="migration-hero">
    <h2>Move school data into EduCore with confidence.</h2>
    <p>Upload exports from another school platform, spreadsheets, database dumps, or a complete ZIP package. EduCore preserves the originals, verifies every file, stages the records, and keeps approvals separate from execution.</p>
    <div class="workflow"><div><b>1. Select source</b>Institution and former platform</div><div><b>2. Upload</b>Files or portable package</div><div><b>3. Inspect</b>Verify and stage records</div><div><b>4. Approve</b>School and platform control</div><div><b>5. Migrate</b>Execute and reconcile</div></div>
</section>
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

<aside><div class="card"><div class="card-header"><div class="form-title"><span>01</span><div>Start a migration<small style="display:block;color:#64748b;font-weight:400">Upload data from another platform</small></div></div></div><div class="card-body">
    <div class="help-note">Accepted sources: CSV, Excel, JSON, XML, SQL dumps, and ZIP migration packages. Up to 20 files per batch.</div>
    <form method="POST" enctype="multipart/form-data" action="{{ $platform ? route('super.migrations.store') : route('migrations.store') }}">@csrf
        @if($platform)<div class="field"><label>Destination institution</label><select name="tenant_id" required><option value="">Choose the school receiving this data</option>@foreach($tenants as $tenant)<option value="{{ $tenant->id }}" @selected(old('tenant_id')==$tenant->id)>{{ $tenant->name }}</option>@endforeach</select></div>@endif
        <div class="field"><label>Direction</label><select name="direction" required><option value="inbound">Into EduCore</option><option value="outbound">Out of EduCore</option></select></div>
        <div class="field"><label>Migration type</label><select name="migration_type" required><option value="full_migration">Full institutional migration</option><option value="standard_import">Standard import</option><option value="full_export">Full portable export</option><option value="selective_export">Selective portable export</option></select></div>
        <div class="field"><label>Source platform</label><select name="source_platform" required><option value="">Select the former platform</option>@foreach(['SchoolCloud','Edves','SAFSMS','FlexiSAF','PowerSchool','Google Classroom','Microsoft School Data Sync','Custom Excel / CSV','Legacy database','other'] as $source)<option value="{{ $source }}" @selected(old('source_platform')===$source)>{{ ucfirst($source) }}</option>@endforeach</select></div>
        <div class="field"><label>Other platform name <span style="font-weight:400;color:#94a3b8">(when Other is selected)</span></label><input name="source_system_other" value="{{ old('source_system_other') }}" placeholder="Name of the existing system"></div>
        <div class="field"><label>Source files or migration package</label><div class="upload-zone"><strong>Choose exports from the former platform</strong><small>CSV, XLSX, XLS, JSON, XML, SQL, TXT or ZIP</small><input type="file" name="source_files[]" multiple required accept=".csv,.xlsx,.xls,.json,.xml,.sql,.txt,.zip"></div></div>
        <div class="field"><label>Data scope</label><div class="scope-grid">@foreach(['students','guardians','staff','academics','attendance','finance','configuration'] as $scope)<label><input type="checkbox" name="data_scope[]" value="{{ $scope }}"> {{ ucfirst($scope) }}</label>@endforeach</div></div>
        <div class="field"><label>Business justification</label><textarea name="business_justification" rows="4" required placeholder="Why is this migration required, and what outcome is expected?">{{ old('business_justification') }}</textarea></div>
        <button class="btn btn-primary" style="width:100%;justify-content:center">Upload files and create batch</button>
    </form>
</div></div></aside>
</div>
@endsection
