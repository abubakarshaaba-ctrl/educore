@extends('layouts.super')

@section('title', 'System Health')

@push('styles')
<style>
.health-header{display:flex;align-items:flex-start;justify-content:space-between;gap:16px;margin-bottom:20px}.health-header h1{font-size:22px;margin-bottom:5px}.health-header p{font-size:13px;color:var(--slate);line-height:1.5}
.health-actions{display:flex;gap:8px;flex-wrap:wrap}.health-summary{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:14px;margin-bottom:18px}.health-card,.runtime-card,.check-card{background:#fff;border:1px solid var(--border);border-radius:12px;box-shadow:0 1px 2px rgba(15,23,42,.04)}.health-card{padding:16px;min-width:0}.health-card strong{display:block;font-size:24px;margin-top:7px}.health-card span{font-size:11px;text-transform:uppercase;letter-spacing:.06em;color:var(--slate)}
.health-grid{display:grid;grid-template-columns:minmax(0,1.7fr) minmax(280px,.8fr);gap:18px}.checks{display:grid;gap:10px}.check-card{padding:15px;display:flex;align-items:flex-start;gap:12px;min-width:0}.status-dot{width:10px;height:10px;border-radius:999px;margin-top:5px;flex:0 0 auto}.status-healthy{background:#059669}.status-warning{background:#D97706}.status-critical{background:#DC2626}.check-main{min-width:0;flex:1}.check-name{display:flex;align-items:center;justify-content:space-between;gap:10px;font-size:13px;font-weight:750}.check-detail{font-size:12px;color:var(--slate);line-height:1.55;margin-top:5px;overflow-wrap:anywhere}.check-meta{font-size:11px;color:var(--slate-light);margin-top:6px;word-break:break-word}.status-label{font-size:10px;font-weight:800;text-transform:uppercase;letter-spacing:.04em;white-space:nowrap}.label-healthy{color:#059669}.label-warning{color:#D97706}.label-critical{color:#DC2626}
.runtime-card{padding:17px;min-width:0}.runtime-card h2{font-size:14px;margin-bottom:13px}.runtime-list{display:grid;gap:0}.runtime-row{display:flex;align-items:center;justify-content:space-between;gap:12px;padding:9px 0;border-bottom:1px solid #EEF2F7;font-size:12px}.runtime-row:last-child{border-bottom:0}.runtime-row span{color:var(--slate)}.runtime-row strong{text-align:right;word-break:break-word}.health-note{margin-top:14px;padding:12px;border-radius:9px;background:#F8FAFC;border:1px solid var(--border);font-size:11.5px;color:var(--slate);line-height:1.55}
@media(max-width:900px){.health-grid{grid-template-columns:1fr}}
@media(max-width:640px){
    .health-header{flex-direction:column;gap:12px;margin-bottom:14px}.health-header h1{font-size:20px}.health-header p{font-size:12px}
    .health-actions{width:100%;display:grid;grid-template-columns:repeat(2,minmax(0,1fr))}.health-actions .btn{width:100%;justify-content:center;min-height:40px;padding:8px 10px}
    .health-summary{grid-template-columns:repeat(3,minmax(0,1fr))!important;gap:8px;margin-bottom:14px}.health-card{padding:11px 10px!important;min-height:92px}.health-card strong{font-size:21px;margin-top:5px}.health-card span{font-size:9px;line-height:1.25}
    .health-grid{gap:12px}.checks{gap:8px}.check-card{padding:12px}.check-name{font-size:12.5px;align-items:flex-start}.check-detail{font-size:11.5px}.runtime-card{padding:13px}.runtime-row{font-size:11.5px;align-items:flex-start}.runtime-row strong{max-width:56%}.health-note{font-size:10.5px;padding:10px}
}
@media(max-width:420px){
    .health-summary{grid-template-columns:repeat(3,minmax(0,1fr))!important}.health-card{padding:10px 8px!important}.health-card strong{font-size:19px}.health-card span{font-size:8px;letter-spacing:.035em}.health-actions{grid-template-columns:1fr 1fr}.health-actions .btn{font-size:11px}
}
</style>
@endpush

@section('content')
<div class="health-header">
    <div>
        <h1>System Health</h1>
        <p>Live infrastructure and application checks for the EduCore platform. No secrets are displayed.</p>
    </div>
    <div class="health-actions">
        <a href="{{ route('super.system-health') }}" class="btn btn-ghost">Refresh checks</a>
        <a href="{{ route('tools.mail-health') }}" class="btn btn-primary" target="_blank" rel="noopener">Mail diagnostics</a>
    </div>
</div>

<div class="health-summary">
    <div class="health-card"><span>Healthy checks</span><strong>{{ $summary['healthy'] }}</strong></div>
    <div class="health-card"><span>Warnings</span><strong>{{ $summary['warning'] }}</strong></div>
    <div class="health-card"><span>Critical</span><strong>{{ $summary['critical'] }}</strong></div>
</div>

<div class="health-grid">
    <div class="checks">
        @foreach($checks as $check)
            <div class="check-card">
                <span class="status-dot status-{{ $check['status'] }}" aria-hidden="true"></span>
                <div class="check-main">
                    <div class="check-name">
                        <span>{{ $check['name'] }}</span>
                        <span class="status-label label-{{ $check['status'] }}">{{ $check['status'] }}</span>
                    </div>
                    <div class="check-detail">{{ $check['detail'] }}</div>
                    @if(!empty($check['meta']))
                        <div class="check-meta">
                            @foreach($check['meta'] as $key => $value)
                                {{ str_replace('_', ' ', ucfirst($key)) }}: {{ is_bool($value) ? ($value ? 'Yes' : 'No') : ($value ?? 'n/a') }}{{ !$loop->last ? ' · ' : '' }}
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        @endforeach
    </div>

    <aside class="runtime-card">
        <h2>Runtime profile</h2>
        <div class="runtime-list">
            <div class="runtime-row"><span>Environment</span><strong>{{ $runtime['environment'] }}</strong></div>
            <div class="runtime-row"><span>Laravel</span><strong>{{ $runtime['laravel'] }}</strong></div>
            <div class="runtime-row"><span>PHP</span><strong>{{ $runtime['php'] }}</strong></div>
            <div class="runtime-row"><span>Queue driver</span><strong>{{ $runtime['queue'] }}</strong></div>
            <div class="runtime-row"><span>Cache driver</span><strong>{{ $runtime['cache'] }}</strong></div>
            <div class="runtime-row"><span>Session driver</span><strong>{{ $runtime['session'] }}</strong></div>
            <div class="runtime-row"><span>Maintenance mode</span><strong>{{ $runtime['maintenance'] ? 'On' : 'Off' }}</strong></div>
            <div class="runtime-row"><span>Config cached</span><strong>{{ $runtime['config_cached'] ? 'Yes' : 'No' }}</strong></div>
            <div class="runtime-row"><span>Routes cached</span><strong>{{ $runtime['routes_cached'] ? 'Yes' : 'No' }}</strong></div>
        </div>
        <div class="health-note">
            Checked {{ $summary['checked_at']->format('d M Y, H:i:s') }}. Mail health on this page validates configuration only; use the protected mail diagnostic action when an actual delivery test is required.
        </div>
    </aside>
</div>
@endsection
