@extends('layouts.app')
@section('title', 'Permissions — '.$staff->name)
@section('page-title', 'Staff Permissions')

@push('styles')
<style>
.pg{background:white;border:1px solid var(--border);border-radius:12px;overflow:hidden;margin-bottom:20px;box-shadow:0 1px 4px rgba(0,0,0,.04)}
.pgh{padding:14px 18px;border-bottom:1px solid var(--border);background:#F8FAFC;font-size:13px;font-weight:700;color:var(--midnight)}
.pm-row{display:grid;grid-template-columns:1fr auto;align-items:center;padding:11px 18px;border-bottom:1px solid #F8FAFC;gap:16px}
.pm-row:last-child{border-bottom:none}
.pm-label{font-size:13px;font-weight:600;color:var(--midnight)}
.pm-sublabel{font-size:11px;color:var(--slate-light);margin-top:1px}
.radio-group{display:flex;gap:4px}
.radio-opt input{display:none}
.radio-opt label{display:inline-flex;align-items:center;gap:4px;padding:5px 12px;border-radius:7px;font-size:11px;font-weight:700;cursor:pointer;border:1.5px solid var(--border);color:var(--slate);transition:all 120ms}
.radio-opt input:checked + label.opt-inherit{background:#F1F5F9;border-color:#CBD5E1;color:#475569}
.radio-opt input:checked + label.opt-grant{background:#ECFDF5;border-color:#A7F3D0;color:#059669}
.radio-opt input:checked + label.opt-deny{background:#FEF2F2;border-color:#FECACA;color:#DC2626}
.radio-opt label:hover{background:#F8FAFC}
.btn{display:inline-flex;align-items:center;gap:5px;padding:9px 18px;font-size:13px;font-weight:600;font-family:inherit;border-radius:8px;border:none;cursor:pointer;text-decoration:none;transition:all 150ms}
.btn-p{background:var(--indigo);color:white}
.btn-g{background:#F1F5F9;color:var(--slate);border:1px solid var(--border)}
.legend{display:flex;gap:14px;margin-bottom:16px;flex-wrap:wrap}
.leg-item{display:flex;align-items:center;gap:6px;font-size:12px;color:var(--slate)}
.leg-dot{width:10px;height:10px;border-radius:50%}

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

<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:18px;flex-wrap:wrap;gap:10px">
    <div style="display:flex;align-items:center;gap:10px">
        <a href="{{ route('staff.show', $staff) }}" class="btn btn-g">← {{ $staff->name }}</a>
        <div>
            <div style="font-size:16px;font-weight:800;color:var(--midnight)">Module Permissions</div>
            <div style="font-size:11px;color:var(--slate-light)">{{ $staff->roleLabel() }} · Custom access overrides for this staff member</div>
        </div>
    </div>
</div>

<div class="legend">
    <div class="leg-item"><div class="leg-dot" style="background:#CBD5E1"></div> Inherit from role (default)</div>
    <div class="leg-item"><div class="leg-dot" style="background:#059669"></div> Grant (allow even if role doesn't have it)</div>
    <div class="leg-item"><div class="leg-dot" style="background:#DC2626"></div> Deny (block even if role normally allows it)</div>
</div>

@if(session('success'))
<div style="background:#ECFDF5;border:1px solid #A7F3D0;border-radius:8px;padding:10px 14px;font-size:13px;color:#059669;margin-bottom:16px">
    ✓ {{ session('success') }}
</div>
@endif

<form method="POST" action="{{ route('staff.permissions.update', $staff) }}">
@csrf @method('PUT')

<div class="pg">
    <div class="pgh">Module Access Permissions</div>
    @foreach($modules as $module => $label)
    @php $current = optional($permissions->get($module))->type ?? 'inherit'; @endphp
    <div class="pm-row">
        <div>
            <div class="pm-label">{{ $label }}</div>
            <div class="pm-sublabel">{{ $module }}</div>
        </div>
        <div class="radio-group">
            <div class="radio-opt">
                <input type="radio" name="permissions[{{ $module }}]" value="inherit" id="p_{{ $module }}_inherit" {{ $current==='inherit'?'checked':'' }}>
                <label for="p_{{ $module }}_inherit" class="opt-inherit">Inherit</label>
            </div>
            <div class="radio-opt">
                <input type="radio" name="permissions[{{ $module }}]" value="grant" id="p_{{ $module }}_grant" {{ $current==='grant'?'checked':'' }}>
                <label for="p_{{ $module }}_grant" class="opt-grant">✓ Grant</label>
            </div>
            <div class="radio-opt">
                <input type="radio" name="permissions[{{ $module }}]" value="deny" id="p_{{ $module }}_deny" {{ $current==='deny'?'checked':'' }}>
                <label for="p_{{ $module }}_deny" class="opt-deny">✗ Deny</label>
            </div>
        </div>
    </div>
    @endforeach
</div>

<div style="display:flex;gap:10px">
    <button type="submit" class="btn btn-p">💾 Save Permissions</button>
    <a href="{{ route('staff.show', $staff) }}" class="btn btn-g">Cancel</a>
</div>
</form>

@endsection
