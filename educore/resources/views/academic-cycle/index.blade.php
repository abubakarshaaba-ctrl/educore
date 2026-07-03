@extends('layouts.app')
@section('title','Academic Session')
@section('page-title','Academic Session')
@push('styles')
<style>
/* ── Layout ──────────────────────────────────────────────────────── */
.ac-banner{background:linear-gradient(135deg,var(--midnight) 0%,#1a3a6b 100%);border-radius:14px;padding:20px 24px;color:white;margin-bottom:20px;display:grid;grid-template-columns:1fr 1fr;gap:16px}
@@media(max-width:640px){.ac-banner{grid-template-columns:1fr}}
.ac-status-label{font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.07em;opacity:.65;margin-bottom:4px}
.ac-status-value{font-size:18px;font-weight:800;line-height:1.2}
.ac-status-sub{font-size:11px;opacity:.65;margin-top:3px}
.ac-status-none{font-size:14px;font-weight:600;color:#F87171}
.ac-grid{display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:20px}
@@media(max-width:900px){.ac-grid{grid-template-columns:1fr}}
.ac-card{background:white;border:1px solid var(--border);border-radius:12px;overflow:hidden;margin-bottom:16px}
.ac-card-head{padding:13px 18px;border-bottom:1px solid var(--border);background:#F8FAFC;display:flex;align-items:center;justify-content:space-between}
.ac-card-title{font-size:14px;font-weight:800;color:var(--midnight)}
.ac-card-body{padding:16px 18px}
.ac-form .fg{margin-bottom:12px}
.ac-form label{display:block;font-size:11px;font-weight:700;color:var(--slate-light);text-transform:uppercase;letter-spacing:.04em;margin-bottom:4px}
.ac-form input,.ac-form select{width:100%;padding:9px 12px;border:1px solid var(--border);border-radius:8px;font-size:13px;font-family:inherit;outline:none;background:#F8FAFC;transition:border 150ms}
.ac-form input:focus,.ac-form select:focus{border-color:var(--indigo);background:white}
.ac-form .fg-row{display:grid;grid-template-columns:1fr 1fr;gap:10px}
.ac-check{display:flex;align-items:center;gap:8px;font-size:12px;font-weight:600;color:var(--midnight);margin-bottom:12px;cursor:pointer}
.ac-check input{width:16px;height:16px;accent-color:var(--indigo);cursor:pointer}
.btn-ac-save{width:100%;padding:10px;background:var(--indigo);color:white;border:none;border-radius:8px;font-size:13px;font-weight:700;cursor:pointer;font-family:inherit}
.btn-ac-save:hover{background:#1D4ED8}
.ac-table{width:100%;border-collapse:collapse}
.ac-table thead th{font-size:9px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:var(--slate-light);padding:8px 10px;background:#F8FAFC;border-bottom:1px solid var(--border);text-align:left}
.ac-table tbody td{padding:9px 10px;border-bottom:1px solid var(--border);font-size:12.5px;vertical-align:middle}
.ac-table tbody tr:last-child td{border-bottom:none}
.badge-curr{background:#ECFDF5;color:#059669;border-radius:20px;padding:2px 9px;font-size:10px;font-weight:700;white-space:nowrap}
.badge-inactive{background:#FFF7ED;color:#D97706;border-radius:20px;padding:2px 9px;font-size:10px;font-weight:700}
.ac-actions{display:flex;gap:5px;flex-wrap:wrap;align-items:center}
.btn-sm-act{padding:4px 10px;border-radius:6px;font-size:11px;font-weight:700;border:none;cursor:pointer;font-family:inherit;display:inline-flex;align-items:center;gap:3px}
.btn-activate{background:#059669;color:white}
.btn-close{background:#F1F5F9;color:#475569;border:1px solid #E2E8F0}
.btn-edit{background:#EFF6FF;color:#2563EB;border:1px solid #BFDBFE}
.btn-del{background:#FEF2F2;color:#DC2626;border:1px solid #FECACA}
.edit-row{display:none}
.edit-row.open{display:table-row}
.edit-row td{padding:10px;background:#F0F4FF;border-top:1px solid #BFDBFE}
.edit-inline-form{display:flex;gap:8px;align-items:center;flex-wrap:wrap}
.edit-inline-form input{padding:7px 10px;border:1.5px solid #BFDBFE;border-radius:7px;font-size:12px;font-family:inherit;outline:none;background:white}
.edit-inline-form input:focus{border-color:var(--indigo)}
.alert-s{background:#ECFDF5;border:1px solid #A7F3D0;border-radius:8px;padding:12px 16px;font-size:13px;color:#065F46;margin-bottom:16px}
.alert-e{background:#FEF2F2;border:1px solid #FCA5A5;border-radius:8px;padding:12px 16px;font-size:13px;color:#991B1B;margin-bottom:16px}
.ac-bottom{display:flex;gap:10px;flex-wrap:wrap}
.btn-ac-link{display:inline-flex;align-items:center;gap:6px;padding:9px 16px;border:1px solid var(--border);border-radius:8px;background:white;color:var(--midnight);font-size:12px;font-weight:600;text-decoration:none}
.btn-ac-link:hover{border-color:var(--indigo);color:var(--indigo)}
.btn-ac-link.primary{background:var(--indigo);color:white;border-color:var(--indigo)}
</style>
@endpush
@section('content')
@if(session('success'))<div class="alert-s">&#10003; {{ session('success') }}</div>@endif
@if($errors->has('error'))<div class="alert-e">&#9888; {{ $errors->first('error') }}</div>@endif
@if($errors->any() && !$errors->has('error'))<div class="alert-e">@foreach($errors->all() as $err)<div>{{ $err }}</div>@endforeach</div>@endif

<div class="ac-banner">
    <div>
        <div class="ac-status-label">Current Session</div>
        @if($currentSession)
            <div class="ac-status-value">{{ $currentSession->name }}</div>
            <div class="ac-status-sub">{{ $sessions->count() }} session(s) total</div>
        @else
            <div class="ac-status-none">No active session</div>
            <div class="ac-status-sub">Create a session below and activate it</div>
        @endif
    </div>
    <div>
        <div class="ac-status-label">Current Term</div>
        @if($currentTerm)
            <div class="ac-status-value">{{ $currentTerm->name }}</div>
            <div class="ac-status-sub">{{ optional($currentTerm->start_date)->format('d M Y') }} &rarr; {{ optional($currentTerm->end_date)->format('d M Y') }}</div>
        @else
            <div class="ac-status-none">No active term</div>
            <div class="ac-status-sub">@if($currentSession)Create a term for {{ $currentSession->name }}@else Activate a session first@endif</div>
        @endif
    </div>
</div>

<div class="ac-grid">

{{-- SESSIONS --}}
<div>
<div class="ac-card">
    <div class="ac-card-head"><span class="ac-card-title">&#128197; New Academic Session</span></div>
    <div class="ac-card-body">
        <form method="POST" action="{{ route('academic-cycle.sessions.store') }}" class="ac-form">
            @csrf
            <div class="fg">
                <label>Session Name *</label>
                <input name="name" value="{{ old('name') }}" placeholder="e.g. 2026/2027" required autocomplete="off">
            </div>
            <label class="ac-check">
                <input type="checkbox" name="activate" value="1" {{ old('activate') ? 'checked' : '' }}>
                Set as current session immediately
            </label>
            <button type="submit" class="btn-ac-save">+ Create Session</button>
        </form>
    </div>
</div>

<div class="ac-card">
    <div class="ac-card-head">
        <span class="ac-card-title">All Sessions</span>
        <span style="font-size:11px;color:var(--slate-light)">{{ $sessions->count() }} total</span>
    </div>
    @if($sessions->isEmpty())
    <div style="padding:28px;text-align:center;color:var(--slate-light);font-size:13px">No academic sessions yet.</div>
    @else
    <div style="overflow-x:auto">
    <table class="ac-table">
        <thead><tr><th>Session</th><th>Terms</th><th>Status</th><th>Actions</th></tr></thead>
        <tbody>
        @foreach($sessions as $session)
        <tr>
            <td style="font-weight:700">{{ $session->name }}</td>
            <td style="text-align:center"><span style="background:#EFF6FF;color:#2563EB;border-radius:20px;padding:2px 9px;font-size:11px;font-weight:700">{{ $session->terms_count }}</span></td>
            <td>@if($session->is_current)<span class="badge-curr">&#9679; Current</span>@else<span class="badge-inactive">Inactive</span>@endif</td>
            <td>
                <div class="ac-actions">
                    @if(!$session->is_current)
                    <form method="POST" action="{{ route('academic-cycle.sessions.activate', $session) }}" style="display:inline">@csrf<button type="submit" class="btn-sm-act btn-activate">&#10003; Activate</button></form>
                    @else
                    <form method="POST" action="{{ route('academic-cycle.sessions.close', $session) }}" style="display:inline" onsubmit="return confirm('Close this session?')">@csrf<button type="submit" class="btn-sm-act btn-close">&#10005; Close</button></form>
                    @endif
                    <button type="button" class="btn-sm-act btn-edit" onclick="toggleEdit('session',{{ $session->id }})">&#9998; Edit</button>
                    @if(!$session->is_current)
                    <form method="POST" action="{{ route('academic-cycle.sessions.destroy', $session) }}" style="display:inline" onsubmit="return confirm('Delete {{ addslashes($session->name) }}? Cannot be undone.')">@csrf @method('DELETE')<button type="submit" class="btn-sm-act btn-del">&#128465;</button></form>
                    @endif
                </div>
            </td>
        </tr>
        <tr class="edit-row" id="session-edit-{{ $session->id }}">
            <td colspan="4">
                <form method="POST" action="{{ route('academic-cycle.sessions.update', $session) }}" class="edit-inline-form">
                    @csrf @method('PATCH')
                    <input type="text" name="name" value="{{ $session->name }}" required style="min-width:180px">
                    <button type="submit" class="btn-sm-act btn-activate">Save</button>
                    <button type="button" class="btn-sm-act btn-close" onclick="toggleEdit('session',{{ $session->id }})">Cancel</button>
                </form>
            </td>
        </tr>
        @endforeach
        </tbody>
    </table>
    </div>
    @endif
</div>
</div>

{{-- TERMS --}}
<div>
<div class="ac-card">
    <div class="ac-card-head">
        <span class="ac-card-title">&#128203; New Term</span>
        @if(!$currentSession)<span style="font-size:11px;color:#D97706;font-weight:600">&#9888; Activate a session first</span>@endif
    </div>
    <div class="ac-card-body">
        <form method="POST" action="{{ route('academic-cycle.terms.store') }}" class="ac-form">
            @csrf
            <div class="fg">
                <label>Session *</label>
                <select name="session_id" required>
                    <option value="">Select session</option>
                    @foreach($sessions as $s)
                    <option value="{{ $s->id }}" {{ (old('session_id', optional($currentSession)->id) == $s->id) ? 'selected' : '' }}>{{ $s->name }}{{ $s->is_current ? ' (current)' : '' }}</option>
                    @endforeach
                </select>
            </div>
            <div class="fg">
                <label>Term Name *</label>
                <input name="name" value="{{ old('name') }}" placeholder="e.g. First Term" required autocomplete="off">
            </div>
            <div class="fg-row">
                <div class="fg"><label>Start Date *</label><input type="date" name="start_date" value="{{ old('start_date') }}" required></div>
                <div class="fg"><label>End Date *</label><input type="date" name="end_date" value="{{ old('end_date') }}" required></div>
            </div>
            <label class="ac-check">
                <input type="checkbox" name="activate" value="1" {{ old('activate') ? 'checked' : '' }}>
                Set as current term immediately
            </label>
            <button type="submit" class="btn-ac-save">+ Create Term</button>
        </form>
    </div>
</div>

<div class="ac-card">
    <div class="ac-card-head">
        <span class="ac-card-title">All Terms</span>
        <span style="font-size:11px;color:var(--slate-light)">{{ $terms->count() }} total</span>
    </div>
    @if($terms->isEmpty())
    <div style="padding:28px;text-align:center;color:var(--slate-light);font-size:13px">No terms yet. Create a session first, then add terms.</div>
    @else
    <div style="overflow-x:auto">
    <table class="ac-table">
        <thead><tr><th>Term</th><th>Session</th><th>Dates</th><th>Status</th><th>Actions</th></tr></thead>
        <tbody>
        @foreach($terms as $term)
        <tr>
            <td style="font-weight:700;white-space:nowrap">{{ $term->name }}</td>
            <td style="font-size:11px;color:var(--slate-light)">{{ optional($term->session)->name }}</td>
            <td style="font-size:11px;white-space:nowrap">{{ optional($term->start_date)->format('d M Y') }}<br>&rarr; {{ optional($term->end_date)->format('d M Y') }}</td>
            <td>@if($term->is_current)<span class="badge-curr">&#9679; Current</span>@else<span class="badge-inactive">Inactive</span>@endif</td>
            <td>
                <div class="ac-actions">
                    @if(!$term->is_current)
                    <form method="POST" action="{{ route('academic-cycle.terms.activate', $term) }}" style="display:inline">@csrf<button type="submit" class="btn-sm-act btn-activate" title="Activate">&#10003;</button></form>
                    @else
                    <form method="POST" action="{{ route('academic-cycle.terms.close', $term) }}" style="display:inline" onsubmit="return confirm('Close {{ addslashes($term->name) }}?')">@csrf<button type="submit" class="btn-sm-act btn-close" title="Close">&#10005;</button></form>
                    @endif
                    <button type="button" class="btn-sm-act btn-edit" onclick="toggleEdit('term',{{ $term->id }})">&#9998;</button>
                    @if(!$term->is_current)
                    <form method="POST" action="{{ route('academic-cycle.terms.destroy', $term) }}" style="display:inline" onsubmit="return confirm('Delete {{ addslashes($term->name) }}?')">@csrf @method('DELETE')<button type="submit" class="btn-sm-act btn-del">&#128465;</button></form>
                    @endif
                </div>
            </td>
        </tr>
        <tr class="edit-row" id="term-edit-{{ $term->id }}">
            <td colspan="5">
                <form method="POST" action="{{ route('academic-cycle.terms.update', $term) }}" class="edit-inline-form">
                    @csrf @method('PATCH')
                    <input type="text" name="name" value="{{ $term->name }}" required placeholder="Term name" style="min-width:130px">
                    <input type="date" name="start_date" value="{{ optional($term->start_date)->format('Y-m-d') }}" required>
                    <input type="date" name="end_date" value="{{ optional($term->end_date)->format('Y-m-d') }}" required>
                    <button type="submit" class="btn-sm-act btn-activate">Save</button>
                    <button type="button" class="btn-sm-act btn-close" onclick="toggleEdit('term',{{ $term->id }})">Cancel</button>
                </form>
            </td>
        </tr>
        @endforeach
        </tbody>
    </table>
    </div>
    @endif
</div>
</div>

</div>

<div class="ac-bottom">
    <a href="{{ route('academic-cycle.promotion.index') }}" class="btn-ac-link primary">&#127891; Promotion Engine</a>
    <a href="{{ route('academic-cycle.rollover.preview') }}" class="btn-ac-link">&#128257; Session Rollover</a>
    <a href="{{ route('classes.promotion') }}" class="btn-ac-link">&#9881;&#65039; Promotion Rules</a>
</div>

@push('scripts')
<script>
function toggleEdit(type, id) {
    var row = document.getElementById(type + '-edit-' + id);
    var isOpen = row.classList.contains('open');
    document.querySelectorAll('.edit-row.open').forEach(function(r){ r.classList.remove('open'); });
    if (!isOpen) row.classList.add('open');
}
</script>
@endpush
@endsection