@extends('layouts.app')
@section('title', 'Permissions — '.$staff->name)
@section('page-title', 'Staff Permissions')

@push('styles')
<style>
.permissions-head{display:flex;align-items:center;justify-content:space-between;margin-bottom:18px;flex-wrap:wrap;gap:10px;min-width:0}
.permissions-head-main{display:flex;align-items:center;gap:10px;min-width:0}
.permissions-head-copy{min-width:0}
.permissions-title{font-size:16px;font-weight:800;color:var(--midnight);overflow-wrap:anywhere}
.permissions-subtitle{font-size:11px;color:var(--slate-light);overflow-wrap:anywhere;line-height:1.45}
.pg{background:white;border:1px solid var(--border);border-radius:12px;overflow:hidden;margin-bottom:20px;box-shadow:0 1px 4px rgba(0,0,0,.04);min-width:0}
.pgh{padding:14px 18px;border-bottom:1px solid var(--border);background:#F8FAFC;font-size:13px;font-weight:700;color:var(--midnight)}
.pm-row{display:grid;grid-template-columns:minmax(0,1fr) auto;align-items:center;padding:11px 18px;border-bottom:1px solid #F8FAFC;gap:16px;min-width:0}
.pm-row:last-child{border-bottom:none}
.pm-row>div:first-child{min-width:0}
.pm-label{font-size:13px;font-weight:600;color:var(--midnight);overflow-wrap:anywhere}
.pm-sublabel{font-size:11px;color:var(--slate-light);margin-top:1px;overflow-wrap:anywhere;word-break:break-word}
.radio-group{display:flex;gap:4px;flex-wrap:wrap;justify-content:flex-end}
.radio-opt input{display:none}
.radio-opt label{display:inline-flex;align-items:center;justify-content:center;gap:4px;padding:6px 12px;min-height:34px;border-radius:7px;font-size:11px;font-weight:700;cursor:pointer;border:1.5px solid var(--border);color:var(--slate);transition:all 120ms;white-space:nowrap}
.radio-opt input:checked + label.opt-inherit{background:#F1F5F9;border-color:#CBD5E1;color:#475569}
.radio-opt input:checked + label.opt-grant{background:#ECFDF5;border-color:#A7F3D0;color:#059669}
.radio-opt input:checked + label.opt-deny{background:#FEF2F2;border-color:#FECACA;color:#DC2626}
.radio-opt label:hover{background:#F8FAFC}
.btn{display:inline-flex;align-items:center;justify-content:center;gap:5px;padding:9px 18px;min-height:40px;font-size:13px;font-weight:600;font-family:inherit;border-radius:8px;border:none;cursor:pointer;text-decoration:none;transition:all 150ms;max-width:100%}
.btn-p{background:var(--indigo);color:white}
.btn-g{background:#F1F5F9;color:var(--slate);border:1px solid var(--border)}
.legend{display:flex;gap:14px;margin-bottom:16px;flex-wrap:wrap}
.leg-item{display:flex;align-items:center;gap:6px;font-size:12px;color:var(--slate);min-width:0}
.leg-dot{width:10px;height:10px;border-radius:50%;flex:0 0 10px}
.permissions-actions{display:flex;gap:10px;flex-wrap:wrap}
.permissions-success{background:#ECFDF5;border:1px solid #A7F3D0;border-radius:8px;padding:10px 14px;font-size:13px;color:#059669;margin-bottom:16px;overflow-wrap:anywhere}
@media(max-width:700px){
    .permissions-head-main{width:100%;align-items:flex-start;flex-direction:column}
    .permissions-head-main>.btn{width:100%}
    .pm-row{grid-template-columns:1fr;align-items:start;padding:12px 14px;gap:9px}
    .radio-group{justify-content:flex-start;width:100%}
    .radio-opt{flex:1 1 96px}
    .radio-opt label{width:100%;padding-inline:8px}
    .pgh{padding:12px 14px}
    .legend{gap:9px 14px}
    .permissions-actions{display:grid;grid-template-columns:1fr 1fr;width:100%}
    .permissions-actions .btn{width:100%}
}
@media(max-width:420px){
    .permissions-actions{grid-template-columns:1fr}
    .legend{display:grid;grid-template-columns:1fr}
    .permissions-title{font-size:15px}
    .pm-label{font-size:12px}
    .radio-opt label{font-size:10.5px}
}
</style>
@endpush

@section('content')
<div class="permissions-head">
    <div class="permissions-head-main">
        <a href="{{ route('staff.show', $staff) }}" class="btn btn-g">← {{ $staff->name }}</a>
        <div class="permissions-head-copy">
            <div class="permissions-title">Module Permissions</div>
            <div class="permissions-subtitle">{{ $staff->roleLabel() }} · Custom access overrides for this staff member</div>
        </div>
    </div>
</div>

<div class="legend">
    <div class="leg-item"><div class="leg-dot" style="background:#CBD5E1"></div> Inherit from role (default)</div>
    <div class="leg-item"><div class="leg-dot" style="background:#059669"></div> Grant (allow even if role doesn't have it)</div>
    <div class="leg-item"><div class="leg-dot" style="background:#DC2626"></div> Deny (block even if role normally allows it)</div>
</div>

@if(session('success'))
<div class="permissions-success">✓ {{ session('success') }}</div>
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

<div class="permissions-actions">
    <button type="submit" class="btn btn-p">💾 Save Permissions</button>
    <a href="{{ route('staff.show', $staff) }}" class="btn btn-g">Cancel</a>
</div>
</form>
@endsection
