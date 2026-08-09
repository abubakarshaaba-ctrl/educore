@extends($platform ? 'layouts.super' : 'layouts.app')
@section('title','Migration '.$migration->batch_number)
@section('page-title','Migration Report')
@push('styles')
<style>
.topline{display:flex;justify-content:space-between;gap:16px;align-items:flex-start;margin-bottom:18px}.batch{font-size:22px;font-weight:800;color:#071e45}.muted{font-size:12px;color:#64748b}.stats{display:grid;grid-template-columns:repeat(5,1fr);gap:12px;margin-bottom:18px}.stat,.panel{background:#fff;border:1px solid var(--border);border-radius:12px}.stat{padding:14px}.stat strong{display:block;font-size:22px}.stat span{font-size:10px;text-transform:uppercase;color:#64748b}.panel{margin-bottom:16px;overflow:hidden}.panel h3{font-size:13px;padding:13px 16px;margin:0;background:#f8fafc;border-bottom:1px solid var(--border)}.panel-body{padding:16px}.detail-grid{display:grid;grid-template-columns:repeat(2,1fr);gap:12px}.detail-grid div{padding:10px;background:#f8fafc;border-radius:8px;font-size:12px}.detail-grid b{display:block;color:#64748b;font-size:10px;text-transform:uppercase;margin-bottom:3px}.actions{display:grid;grid-template-columns:1fr 1fr;gap:12px}.actions textarea{width:100%;border:1px solid var(--border);border-radius:8px;padding:9px;margin-bottom:8px}.report-table{width:100%;border-collapse:collapse}.report-table th,.report-table td{padding:9px 12px;border-bottom:1px solid var(--border);font-size:12px;text-align:left}@media(max-width:760px){.stats{grid-template-columns:repeat(2,1fr)}.actions,.detail-grid{grid-template-columns:1fr}}
</style>
@endpush
@section('content')
@if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
<div class="topline"><div><div class="batch">{{ $migration->batch_number }}</div><div class="muted">{{ $migration->tenant?->name }} · {{ str($migration->migration_type)->replace('_',' ')->title() }} · {{ ucfirst($migration->direction) }}</div></div><a class="btn btn-secondary" href="{{ $platform ? route('super.migrations.index') : route('migrations.index') }}">Back to Migration Center</a></div>
<div class="stats">
@foreach(['total_source_rows'=>'Source rows','total_valid_rows'=>'Valid','total_created'=>'Created','total_updated'=>'Updated','total_failed'=>'Failed'] as $key=>$label)<div class="stat"><strong>{{ number_format($report['migration'][$key] ?? 0) }}</strong><span>{{ $label }}</span></div>@endforeach
</div>

<div class="panel"><h3>Control record</h3><div class="panel-body detail-grid">
    <div><b>Status</b>{{ str($migration->status->value)->replace('_',' ')->title() }}</div><div><b>Source</b>{{ $migration->source_system ?: 'Not specified' }}</div>
    <div><b>Business justification</b>{{ $migrationRequest?->business_justification ?: 'Awaiting request record' }}</div><div><b>Approved data scope</b>{{ implode(', ', $migrationRequest?->data_scope ?? []) ?: 'Not recorded' }}</div>
</div></div>

@if($migrationRequest && in_array($migrationRequest->status,['awaiting_school_approval','awaiting_platform_approval']))
<div class="panel"><h3>Required decision</h3><div class="panel-body actions">
    @if(!$platform && $migrationRequest->status === 'awaiting_school_approval')<form method="POST" action="{{ route('migrations.school-approve',$migrationRequest) }}">@csrf<textarea name="reason" required placeholder="School approval reason"></textarea><button class="btn btn-primary">Approve for the school</button></form>@endif
    @if($platform && $migrationRequest->status === 'awaiting_platform_approval')<form method="POST" action="{{ route('super.migrations.approve',$migrationRequest) }}">@csrf<textarea name="reason" required placeholder="Platform authorization reason"></textarea><button class="btn btn-primary">Authorize execution</button></form>@endif
    <form method="POST" action="{{ $platform ? route('super.migrations.reject',$migrationRequest) : route('migrations.reject',$migrationRequest) }}">@csrf<textarea name="reason" required placeholder="Mandatory rejection reason"></textarea><button class="btn btn-danger">Reject request</button></form>
</div></div>
@endif

<div class="panel"><h3>Approval history</h3><table class="report-table"><thead><tr><th>Level</th><th>Decision</th><th>Reason</th><th>Decided</th></tr></thead><tbody>@forelse($report['approvals'] as $approval)<tr><td>{{ str($approval['approval_type'])->replace('_',' ')->title() }}</td><td>{{ ucfirst($approval['decision']) }}</td><td>{{ $approval['reason'] }}</td><td>{{ $approval['decided_at'] }}</td></tr>@empty<tr><td colspan="4">No decisions recorded yet.</td></tr>@endforelse</tbody></table></div>
<div class="panel"><h3>Reconciliation evidence</h3><table class="report-table"><thead><tr><th>Scope</th><th>Source</th><th>Destination</th><th>Status</th></tr></thead><tbody>@forelse($report['reconciliations'] as $item)<tr><td>{{ $item['scope'] }}</td><td>{{ $item['source_count'] ?? $item['source_total'] }}</td><td>{{ $item['destination_count'] ?? $item['destination_total'] }}</td><td>{{ ucfirst($item['status']) }}</td></tr>@empty<tr><td colspan="4">Reconciliation has not run.</td></tr>@endforelse</tbody></table></div>
@endsection
