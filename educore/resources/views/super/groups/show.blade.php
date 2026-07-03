@extends('layouts.super')
@section('title','{{ $group->name }}')
@section('page-title','School Group')

@push('styles')
<style>
.card{background:white;border:1px solid var(--border);border-radius:12px;overflow:hidden;margin-bottom:16px}
.ch{padding:12px 18px;border-bottom:1px solid var(--border);background:#F8FAFC;font-size:13px;font-weight:700;display:flex;align-items:center;justify-content:space-between}
.two{display:grid;grid-template-columns:1fr 340px;gap:16px}
table{width:100%;border-collapse:collapse;font-size:13px}
th{padding:9px 14px;background:#F8FAFC;border-bottom:1px solid var(--border);font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:#94A3B8;text-align:left}
td{padding:10px 14px;border-bottom:1px solid var(--border);color:#0F172A}
tr:last-child td{border:none}tr:hover td{background:#F8FAFC}
.badge{display:inline-flex;font-size:10px;font-weight:600;padding:2px 8px;border-radius:20px}
.b-active{background:#ECFDF5;color:#059669}.b-expired{background:#FEF2F2;color:#DC2626}
.btn{display:inline-flex;align-items:center;gap:5px;padding:7px 13px;font-size:12px;font-weight:600;font-family:inherit;border-radius:7px;border:none;cursor:pointer;text-decoration:none;transition:all 150ms}
.btn-p{background:#D79A21;color:white}.btn-danger{background:#FEF2F2;color:#DC2626;border:1px solid #FECACA}
.btn-ghost{background:#F1F5F9;color:#475569;border:1px solid #E2E8F0}
.stat-row{display:grid;grid-template-columns:repeat(4,1fr);gap:10px;margin-bottom:16px}
.sc{background:white;border:1px solid var(--border);border-radius:10px;padding:13px 16px}
.sv{font-size:20px;font-weight:800}.sl{font-size:10px;color:#64748B;text-transform:uppercase;letter-spacing:.06em;margin-top:3px}
.fg{display:flex;flex-direction:column;gap:4px;margin-bottom:12px}
.fl{font-size:11px;font-weight:700;color:#475569;text-transform:uppercase;letter-spacing:.05em}
.fc{padding:8px 12px;font-size:13px;font-family:inherit;border:1.5px solid #E2E8F0;border-radius:8px;background:#F8FAFC;outline:none;width:100%}
.fc:focus{border-color:#2563EB}
.alert-s{background:#ECFDF5;border:1px solid #A7F3D0;border-radius:8px;padding:10px 14px;font-size:13px;color:#059669;margin-bottom:14px}
.back{font-size:13px;color:#2563EB;text-decoration:none;display:inline-flex;align-items:center;gap:4px;margin-bottom:16px}
</style>
@endpush

@section('content')
<a href="{{ route('super.groups.index') }}" class="back">← All Groups</a>
@if(session('success'))<div class="alert-s">✓ {{ session('success') }}</div>@endif

<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;flex-wrap:wrap;gap:10px">
    <div>
        <h2 style="font-size:17px;font-weight:800;color:#0F172A">🏫 {{ $group->name }}</h2>
        @if($group->owner_name)<div style="font-size:13px;color:#64748B">Owner: {{ $group->owner_name }}@if($group->owner_email) · {{ $group->owner_email }}@endif</div>@endif
    </div>
    <a href="{{ route('super.groups.report',$group->id) }}" class="btn btn-p">📊 Group Report</a>
</div>

<div class="stat-row">
    <div class="sc"><div class="sv" style="color:#2563EB">{{ number_format($stats['total_students']) }}</div><div class="sl">Total Students</div></div>
    <div class="sc"><div class="sv">{{ number_format($stats['total_staff']) }}</div><div class="sl">Total Staff</div></div>
    <div class="sc"><div class="sv" style="color:#059669">₦{{ number_format($stats['total_revenue']) }}</div><div class="sl">Total Revenue</div></div>
    <div class="sc"><div class="sv" style="color:#059669">{{ $stats['active_count'] }}</div><div class="sl">Active Schools</div></div>
</div>

<div class="two">
    <div class="card">
        <div class="ch"><span>Member Schools ({{ $members->count() }})</span></div>
        <div class="tbl"><table>
            <thead><tr><th>School</th><th>Status</th><th>Expires</th><th>Role</th><th></th></tr></thead>
            <tbody>
            @forelse($members as $m)
            <tr>
                <td>
                    <div style="font-weight:600">{{ $m->name }}</div>
                    <div style="font-size:11px;color:#94A3B8">{{ $m->email }}</div>
                </td>
                <td><span class="badge b-{{ $m->status === 'active' ? 'active':'expired' }}">{{ ucfirst($m->status) }}</span></td>
                <td style="font-size:12px;color:{{ $m->subscription_expires_at && \Carbon\Carbon::parse($m->subscription_expires_at)->isPast() ? '#DC2626':'#64748B' }}">
                    {{ $m->subscription_expires_at ? \Carbon\Carbon::parse($m->subscription_expires_at)->format('d M Y') : '—' }}
                </td>
                <td><span style="font-size:11px;text-transform:capitalize;color:#64748B">{{ $m->role ?? 'member' }}</span></td>
                <td>
                    <div style="display:flex;gap:6px">
                        <a href="{{ route('super.tenant.show',$m->tenant_id) }}" class="btn btn-ghost" style="padding:4px 8px;font-size:11px">View</a>
                        <form method="POST" action="{{ route('super.groups.members.remove',[$group->id,$m->tenant_id]) }}">
                            @csrf @method('DELETE')
                            <button class="btn btn-danger" style="padding:4px 8px;font-size:11px" onclick="return confirm('Remove?')">✕</button>
                        </form>
                    </div>
                </td>
            </tr>
            @empty
            <tr><td colspan="5" style="text-align:center;padding:30px;color:#94A3B8">No schools added yet.</td></tr>
            @endforelse
            </tbody>
        </table></div>
    </div>

    <div class="card">
        <div class="ch">➕ Add School</div>
        <div style="padding:18px">
        @if($availableTenants->isEmpty())
            <div style="text-align:center;color:#94A3B8;padding:20px;font-size:13px">All schools are already in this group.</div>
        @else
        <form method="POST" action="{{ route('super.groups.members.add',$group->id) }}">
            @csrf
            <div class="fg"><label class="fl">School *</label>
                <select name="tenant_id" class="fc" required>
                    <option value="">Select school...</option>
                    @foreach($availableTenants as $t)
                    <option value="{{ $t->id }}">{{ $t->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="fg"><label class="fl">Role in Group</label>
                <select name="role" class="fc">
                    <option value="member">Member</option>
                    <option value="lead">Lead School</option>
                </select>
            </div>
            <button type="submit" class="btn btn-p" style="width:100%;justify-content:center">Add to Group</button>
        </form>
        @endif
        </div>
    </div>
</div>
@endsection
