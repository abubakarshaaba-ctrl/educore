@extends('layouts.super')
@section('title','School Groups')
@section('page-title','School Groups & Chains')

@push('styles')
<style>
.kpi{display:grid;grid-template-columns:repeat(3,1fr);gap:14px;margin-bottom:20px}
.kc{background:white;border:1px solid var(--border);border-radius:12px;padding:16px 18px}
.kv{font-size:22px;font-weight:800}.kl{font-size:11px;color:#64748B;text-transform:uppercase;letter-spacing:.06em;margin-top:4px}
.card{background:white;border:1px solid var(--border);border-radius:12px;overflow:hidden;margin-bottom:16px}
.ch{padding:12px 18px;border-bottom:1px solid var(--border);background:#F8FAFC;font-size:13px;font-weight:700;display:flex;align-items:center;justify-content:space-between}
.two{display:grid;grid-template-columns:1fr 360px;gap:16px}
table{width:100%;border-collapse:collapse;font-size:13px}
th{padding:9px 14px;background:#F8FAFC;border-bottom:1px solid var(--border);font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:#94A3B8;text-align:left}
td{padding:10px 14px;border-bottom:1px solid var(--border);color:#0F172A}
tr:hover td{background:#F8FAFC}
.btn{display:inline-flex;align-items:center;gap:5px;padding:7px 13px;font-size:12px;font-weight:600;font-family:inherit;border-radius:7px;border:none;cursor:pointer;text-decoration:none;transition:all 150ms}
.btn-p{background:#D79A21;color:white}.btn-danger{background:#FEF2F2;color:#DC2626;border:1px solid #FECACA}
.btn-ghost{background:#F1F5F9;color:#475569;border:1px solid #E2E8F0}
.fg{display:flex;flex-direction:column;gap:4px;margin-bottom:12px}
.fl{font-size:11px;font-weight:700;color:#475569;text-transform:uppercase;letter-spacing:.05em}
.fc{padding:8px 12px;font-size:13px;font-family:inherit;border:1.5px solid #E2E8F0;border-radius:8px;background:#F8FAFC;outline:none;width:100%}
.fc:focus{border-color:#2563EB}
.alert-s{background:#ECFDF5;border:1px solid #A7F3D0;border-radius:8px;padding:10px 14px;font-size:13px;color:#059669;margin-bottom:14px}
.group-card{background:white;border:1px solid var(--border);border-radius:12px;padding:18px;margin-bottom:12px;display:flex;align-items:center;justify-content:space-between;gap:14px;transition:box-shadow 150ms}
.group-card:hover{box-shadow:0 4px 12px rgba(0,0,0,0.07)}
.g-icon{width:46px;height:46px;background:linear-gradient(135deg,var(--midnight),var(--navy));border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:20px;flex-shrink:0}
.g-name{font-size:14px;font-weight:700;color:#0F172A}
.g-meta{font-size:12px;color:#64748B;margin-top:2px}

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
@if(session('success'))<div class="alert-s">✓ {{ session('success') }}</div>@endif

<div style="margin-bottom:18px">
    <h2 style="font-size:15px;font-weight:700;color:#0F172A">🏫 School Groups & Chains</h2>
    <p style="font-size:13px;color:#64748B;margin-top:3px">Group multiple schools under one management umbrella for consolidated reporting</p>
</div>

<div class="two">
    <div>
        @forelse($groups as $g)
        <div class="group-card">
            <div class="g-icon">🏫</div>
            <div style="flex:1">
                <div class="g-name">{{ $g->name }}</div>
                <div class="g-meta">
                    {{ $g->member_count }} school{{ $g->member_count != 1 ? 's':'' }}
                    @if($g->owner_name) · Owner: {{ $g->owner_name }} @endif
                    @if($g->description) · {{ Str::limit($g->description, 60) }} @endif
                </div>
            </div>
            <div style="display:flex;gap:6px">
                <a href="{{ route('super.groups.show',$g->id) }}" class="btn btn-ghost">Manage</a>
                <a href="{{ route('super.groups.report',$g->id) }}" class="btn btn-p">Report</a>
                <form method="POST" action="{{ route('super.groups.destroy',$g->id) }}">
                    @csrf @method('DELETE')
                    <button class="btn btn-danger" onclick="return confirm('Delete group?')">✕</button>
                </form>
            </div>
        </div>
        @empty
        <div style="background:white;border:1px dashed var(--border);border-radius:12px;padding:50px;text-align:center;color:#94A3B8">
            <div style="font-size:36px;margin-bottom:10px">🏫</div>
            <div style="font-size:14px;font-weight:600;color:#64748B">No groups yet</div>
            <div style="font-size:12px;margin-top:4px">Create a group to manage multiple schools together</div>
        </div>
        @endforelse
        <div style="margin-top:8px">{{ $groups->links() }}</div>
    </div>

    <div class="card">
        <div class="ch">➕ New School Group</div>
        <div style="padding:18px">
        <form method="POST" action="{{ route('super.groups.store') }}">
            @csrf
            <div class="fg"><label class="fl">Group Name *</label>
                <input name="name" class="fc" required placeholder="e.g. Greenfield Schools Group"></div>
            <div class="fg"><label class="fl">Description</label>
                <textarea name="description" class="fc" rows="2" placeholder="Brief description..."></textarea></div>
            <div class="fg"><label class="fl">Group Owner / Proprietor</label>
                <input name="owner_name" class="fc" placeholder="Name"></div>
            <div class="fg"><label class="fl">Owner Email</label>
                <input name="owner_email" type="email" class="fc" placeholder="owner@email.com"></div>
            <button type="submit" class="btn btn-p" style="width:100%;justify-content:center">Create Group</button>
        </form>
        </div>
    </div>
</div>
@endsection
