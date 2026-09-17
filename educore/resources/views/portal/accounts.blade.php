@extends('layouts.app')
@section('title','Portal Account Management')
@section('page-title','Portal Accounts')

@push('styles')
<style>
.tabs{display:flex;gap:4px;background:white;border:1px solid var(--border);border-radius:10px;padding:4px;margin-bottom:20px;width:fit-content;max-width:100%;overflow-x:auto;-webkit-overflow-scrolling:touch}
.tab{padding:7px 16px;border-radius:7px;font-size:13px;font-weight:500;color:var(--slate);cursor:pointer;border:none;background:none;font-family:inherit;white-space:nowrap;flex:0 0 auto}
.tab.active{background:var(--indigo);color:white}
.card{background:white;border:1px solid var(--border);border-radius:12px;overflow:hidden;margin-bottom:16px;min-width:0}
.ch{padding:12px 18px;border-bottom:1px solid var(--border);background:#F8FAFC;font-size:13px;font-weight:700;color:var(--midnight);display:flex;align-items:center;justify-content:space-between;min-width:0}
.account-table-wrap{width:100%;max-width:100%;overflow-x:auto;-webkit-overflow-scrolling:touch;overscroll-behavior-inline:contain}
table{width:100%;min-width:760px;border-collapse:collapse;font-size:13px}
th{padding:9px 14px;background:#F8FAFC;border-bottom:1px solid var(--border);font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:var(--muted,#94A3B8);text-align:left;white-space:nowrap}
td{padding:9px 14px;border-bottom:1px solid var(--border);color:var(--midnight);vertical-align:top}
tr:hover td{background:#FAFBFF}
.badge{display:inline-flex;font-size:10px;font-weight:700;padding:2px 9px;border-radius:20px;max-width:240px;overflow-wrap:anywhere;word-break:break-word;white-space:normal}
.b-active{background:#ECFDF5;color:#059669}.b-inactive{background:#FEF2F2;color:#DC2626}
.b-has{background:#EFF6FF;color:#2563EB}.b-none{background:#F1F5F9;color:#64748B}
.btn{display:inline-flex;align-items:center;justify-content:center;gap:5px;padding:6px 12px;font-size:12px;font-weight:600;font-family:inherit;border-radius:7px;border:none;cursor:pointer;text-decoration:none;transition:all 150ms}
.btn-p{background:var(--indigo);color:white}.btn-ghost{background:#F1F5F9;color:var(--slate);border:1px solid var(--border)}
.btn-g{background:#059669;color:white}.btn-warn{background:#FFFBEB;color:#D97706;border:1px solid #FDE68A}
.btn-danger{background:#FEF2F2;color:#DC2626;border:1px solid #FECACA}
.alert-s{background:#ECFDF5;border:1px solid #A7F3D0;border-radius:8px;padding:10px 14px;font-size:13px;color:#059669;margin-bottom:14px;overflow-wrap:anywhere}
.alert-e{background:#FEF2F2;border:1px solid #FECACA;border-radius:8px;padding:10px 14px;font-size:13px;color:#DC2626;margin-bottom:14px;overflow-wrap:anywhere}
.inline-form{display:flex;gap:6px;align-items:center;min-width:0}
.fc-sm{padding:6px 10px;font-size:12px;font-family:inherit;border:1.5px solid var(--border);border-radius:7px;background:#F8FAFC;outline:none;min-width:160px;max-width:100%}
.fc-sm:focus{border-color:var(--indigo)}
.info-box{background:#EFF6FF;border:1px solid #BFDBFE;border-radius:10px;padding:14px 18px;font-size:13px;color:#1D4ED8;margin-bottom:18px;line-height:1.6;overflow-wrap:anywhere}
.bulk-actions{padding:14px 18px;display:flex;gap:10px;flex-wrap:wrap;align-items:center}
.account-actions{display:flex;gap:5px;flex-wrap:wrap}
.reset-modal{display:none;position:fixed;inset:0;background:rgba(0,0,0,0.5);z-index:999;align-items:center;justify-content:center;padding:18px;overflow-y:auto}
.reset-dialog{background:white;border-radius:14px;padding:28px;max-width:380px;width:100%;max-height:calc(100vh - 36px);overflow-y:auto;overflow-wrap:anywhere}
.reset-buttons{display:flex;gap:8px;flex-wrap:wrap}
@media(max-width:640px){
    .tabs{width:100%;margin-bottom:14px}
    .tab{flex:1 0 auto;min-height:42px}
    .info-box{padding:12px 14px;font-size:12px}
    .bulk-actions{padding:12px 14px;align-items:stretch}
    .bulk-actions form,.bulk-actions .btn{width:100%}
    table{min-width:700px;font-size:12px}
    th,td{padding:8px 10px}
    .inline-form{flex-direction:column;align-items:stretch;min-width:190px}
    .inline-form .fc-sm,.inline-form .btn{width:100%;min-width:0;min-height:38px}
    .account-actions{min-width:160px}
    .account-actions form,.account-actions .btn{flex:1 1 auto}
    .reset-dialog{padding:20px 16px}
    .reset-dialog .fc-sm{font-size:16px}
    .reset-buttons{flex-direction:column}
    .reset-buttons .btn{width:100%;min-height:42px}
}
@media(max-width:380px){
    table{min-width:660px}
    .tab{padding:7px 12px;font-size:12px}
}
</style>
@endpush

@section('content')
@if(session('success'))<div class="alert-s">✓ {{ session('success') }}</div>@endif
@if($errors->any())<div class="alert-e">{{ $errors->first() }}</div>@endif

<div class="info-box">
    🔐 <strong>Portal Account Management</strong><br>
    Create login accounts for <strong>students</strong> and <strong>parents/guardians</strong> so they can access their respective portals.
    Students log in at <code>/student/dashboard</code>, parents at <code>/parent/dashboard</code>.
    Default student password = their <strong>Admission Number</strong>.
</div>

{{-- Bulk action --}}
<div class="card" style="margin-bottom:16px">
    <div class="ch">⚡ Bulk Actions</div>
    <div class="bulk-actions">
        <form method="POST" action="{{ route('portal-accounts.bulk-students') }}">
            @csrf
            <button type="submit" class="btn btn-g"
                    onclick="return confirm('Create student portal accounts for ALL active students with an email address and no existing account?')">
                🎓 Bulk Create Student Accounts
            </button>
        </form>
        <span style="font-size:12px;color:var(--slate)">Creates accounts for students with an email address who don't yet have portal access.</span>
    </div>
</div>

{{-- Tabs --}}
<div class="tabs" id="portalTabs">
    <button class="tab active" onclick="showTab('students')">🎓 Students ({{ $students->count() }})</button>
    <button class="tab" onclick="showTab('parents')">👪 Parents / Guardians ({{ $guardians->count() }})</button>
</div>

{{-- Students tab --}}
<div id="tab-students">
<div class="card">
    <div class="ch">🎓 Student Portal Accounts</div>
    <div class="account-table-wrap">
    <table>
        <thead>
            <tr>
                <th>Student</th>
                <th>Class</th>
                <th>Admission No.</th>
                <th>Portal Account</th>
                <th>Status</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
        @foreach($students as $s)
        @php $account = $studentAccountMap[$s->id] ?? null; @endphp
        <tr>
            <td style="font-weight:600">{{ $s->full_name }}</td>
            <td style="font-size:12px;color:var(--slate)">{{ optional(optional($s->currentClassArm)->classLevel)->name }} {{ optional($s->currentClassArm)->name }}</td>
            <td style="font-family:monospace;font-size:12px">{{ $s->admission_number }}</td>
            <td>
                @if($account)
                    <span class="badge b-has">✓ {{ $account->email }}</span>
                @else
                    <span class="badge b-none">No Account</span>
                @endif
            </td>
            <td>
                @if($account)
                <span class="badge {{ $account->is_active ? 'b-active':'b-inactive' }}">{{ $account->is_active ? 'Active':'Disabled' }}</span>
                @else <span style="color:var(--slate);font-size:12px">—</span> @endif
            </td>
            <td>
                @if($account)
                <div class="account-actions">
                    <form method="POST" action="{{ route('portal-accounts.toggle', $account->id) }}">
                        @csrf @method('PATCH')
                        <button class="btn {{ $account->is_active ? 'btn-danger':'btn-g' }}" style="padding:4px 8px;font-size:11px">
                            {{ $account->is_active ? 'Disable':'Enable' }}
                        </button>
                    </form>
                    <button class="btn btn-ghost" style="padding:4px 8px;font-size:11px"
                            onclick="showReset({{ $account->id }}, '{{ addslashes($s->full_name) }}')">Reset Password</button>
                </div>
                @else
                <form method="POST" action="{{ route('portal-accounts.students.create', $s->id) }}" class="inline-form">
                    @csrf
                    <input type="email" name="email" class="fc-sm" placeholder="student@email.com"
                           value="{{ $s->email ?? '' }}" required>
                    <button type="submit" class="btn btn-p" style="padding:5px 10px;font-size:11px">+ Create</button>
                </form>
                @endif
            </td>
        </tr>
        @endforeach
        </tbody>
    </table>
    </div>
</div>
</div>

{{-- Parents tab --}}
<div id="tab-parents" style="display:none">
<div class="card">
    <div class="ch">👪 Parent / Guardian Portal Accounts</div>
    <div class="account-table-wrap">
    <table>
        <thead>
            <tr>
                <th>Guardian</th>
                <th>Phone</th>
                <th>Children</th>
                <th>Portal Account</th>
                <th>Status</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
        @foreach($guardians as $g)
        @php $account = $guardianAccountMap[$g->id] ?? null; @endphp
        <tr>
            <td style="font-weight:600">{{ $g->full_name }}<div style="font-size:11px;color:var(--slate)">{{ $g->relationship }}</div></td>
            <td style="font-size:12px">{{ $g->phone ?? '—' }}</td>
            <td style="font-size:12px">
                @foreach($g->students->take(2) as $sc)
                <div>{{ $sc->full_name }}</div>
                @endforeach
                @if($g->students->count() > 2)<div style="color:var(--muted)">+{{ $g->students->count()-2 }} more</div>@endif
            </td>
            <td>
                @if($account)
                    <span class="badge b-has">✓ {{ $account->email }}</span>
                @else
                    <span class="badge b-none">No Account</span>
                @endif
            </td>
            <td>
                @if($account)
                <span class="badge {{ $account->is_active ? 'b-active':'b-inactive' }}">{{ $account->is_active ? 'Active':'Disabled' }}</span>
                @else <span style="color:var(--slate);font-size:12px">—</span> @endif
            </td>
            <td>
                @if($account)
                <div class="account-actions">
                    <form method="POST" action="{{ route('portal-accounts.toggle', $account->id) }}">
                        @csrf @method('PATCH')
                        <button class="btn {{ $account->is_active ? 'btn-danger':'btn-g' }}" style="padding:4px 8px;font-size:11px">
                            {{ $account->is_active ? 'Disable':'Enable' }}
                        </button>
                    </form>
                    <button class="btn btn-ghost" style="padding:4px 8px;font-size:11px"
                            onclick="showReset({{ $account->id }}, '{{ addslashes($g->full_name) }}')">Reset Password</button>
                </div>
                @else
                <form method="POST" action="{{ route('portal-accounts.guardians.create', $g->id) }}" class="inline-form">
                    @csrf
                    <input type="email" name="email" class="fc-sm" placeholder="parent@email.com"
                           value="{{ $g->email ?? '' }}" required>
                    <button type="submit" class="btn btn-p" style="padding:5px 10px;font-size:11px">+ Create</button>
                </form>
                @endif
            </td>
        </tr>
        @endforeach
        </tbody>
    </table>
    </div>
</div>
</div>

{{-- Reset password modal --}}
<div id="resetModal" class="reset-modal">
    <div class="reset-dialog">
        <h3 style="font-size:15px;font-weight:700;margin-bottom:14px">🔑 Reset Password — <span id="resetName"></span></h3>
        <form method="POST" id="resetForm" action="">
            @csrf
            <div style="margin-bottom:12px">
                <label style="font-size:11px;font-weight:700;color:var(--slate);text-transform:uppercase;display:block;margin-bottom:5px">New Password</label>
                <input type="text" name="password" class="fc-sm" style="width:100%;padding:9px 12px"
                       placeholder="Min 6 characters" required minlength="6">
            </div>
            <div class="reset-buttons">
                <button type="submit" class="btn btn-p">Reset Password</button>
                <button type="button" class="btn btn-ghost" onclick="document.getElementById('resetModal').style.display='none'">Cancel</button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
function showTab(tab) {
    document.querySelectorAll('.tabs .tab').forEach((t,i) => t.classList.toggle('active', (tab==='students' ? i===0 : i===1)));
    document.getElementById('tab-students').style.display = tab === 'students' ? '' : 'none';
    document.getElementById('tab-parents').style.display  = tab === 'parents'  ? '' : 'none';
}
function showReset(userId, name) {
    document.getElementById('resetName').textContent = name;
    document.getElementById('resetForm').action = '/portal-accounts/reset/' + userId;
    const modal = document.getElementById('resetModal');
    modal.style.display = 'flex';
}
document.getElementById('resetModal').addEventListener('click', function(e) {
    if (e.target === this) this.style.display = 'none';
});
</script>
@endpush
