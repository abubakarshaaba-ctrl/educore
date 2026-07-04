@extends('layouts.app')
@section('title','Generate Payroll')
@section('page-title','Generate Payroll')
@push('styles')
<style>
.card{background:white;border:1px solid var(--border);border-radius:12px;overflow:hidden;margin-bottom:16px}
.ch{padding:14px 20px;border-bottom:1px solid var(--border);background:#F8FAFC;font-size:14px;font-weight:700;color:var(--midnight)}
.cb{padding:20px}
.fr{display:grid;grid-template-columns:1fr 1fr;gap:14px}
.fg{display:flex;flex-direction:column;gap:5px;margin-bottom:14px}
.fl{font-size:11px;font-weight:600;color:var(--slate);text-transform:uppercase;letter-spacing:.05em}
.fc{padding:9px 12px;font-size:13px;font-family:inherit;border:1px solid var(--border);border-radius:8px;background:#F8FAFC;outline:none;width:100%}
.btn{display:inline-flex;align-items:center;gap:6px;padding:9px 18px;font-size:13px;font-weight:600;font-family:inherit;border-radius:8px;border:none;cursor:pointer;transition:all 150ms}
.btn-p{background:var(--indigo);color:white}.btn-ghost{background:white;color:var(--midnight);border:1px solid var(--border)}
.info-box{background:#EFF6FF;border:1px solid #BFDBFE;border-radius:8px;padding:12px 16px;font-size:13px;color:var(--indigo);margin-bottom:16px}
.back{font-size:13px;color:var(--indigo);text-decoration:none;display:inline-flex;align-items:center;gap:4px;margin-bottom:16px}

@media (max-width: 1024px) {
    .two-col { grid-template-columns: 1fr !important; }
    .stats-row, .stat-row { grid-template-columns: repeat(2, 1fr) !important; }
    .kpi { grid-template-columns: repeat(2, 1fr) !important; }
}
@media (max-width: 640px) {
    .two, .fr { grid-template-columns: 1fr !important; }
}
@media (max-width: 480px) {
    .fr3 { grid-template-columns: 1fr !important; }
}
</style>
@endpush
@section('content')
<a href="{{ route('payroll.index') }}" class="back">← Back to Payroll</a>
<div class="info-box">
    ℹ️ Payroll will be auto-calculated from salary settings. Go to <a href="{{ route('payroll.salary') }}" style="font-weight:700">Salary Settings</a> to configure individual staff salaries first.
</div>
<form method="POST" action="{{ route('payroll.generate') }}">
@csrf
<div class="card">
    <div class="ch">New Payroll Period</div>
    <div class="cb">
        <div class="fg"><label class="fl">Payroll Title *</label><input type="text" name="title" class="fc" required placeholder="e.g. January 2026 Staff Salary"></div>
        <div class="fr">
            <div class="fg"><label class="fl">Period Start *</label><input type="date" name="period_start" class="fc" required value="{{ date('Y-m-01') }}"></div>
            <div class="fg"><label class="fl">Period End *</label><input type="date" name="period_end" class="fc" required value="{{ date('Y-m-t') }}"></div>
        </div>
    </div>
</div>
<div style="display:flex;gap:10px">
    <button type="submit" class="btn btn-p">Generate Payroll</button>
    <a href="{{ route('payroll.index') }}" class="btn btn-ghost">Cancel</a>
</div>
</form>
@endsection