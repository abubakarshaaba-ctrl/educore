@extends('layouts.app')
@section('title','Subject Selection — '.$student->full_name)
@section('page-title','Student Subject Selection')

@push('styles')
<style>
.two{display:grid;grid-template-columns:1fr 360px;gap:16px;align-items:start}
.card{background:white;border:1px solid var(--border);border-radius:12px;overflow:hidden;margin-bottom:14px}
.ch{padding:12px 18px;border-bottom:1px solid var(--border);background:#F8FAFC;font-size:13px;font-weight:700;color:var(--midnight);display:flex;align-items:center;justify-content:space-between}
.sub-row{display:flex;align-items:center;gap:10px;padding:10px 14px;border-bottom:1px solid var(--border)}
.sub-row:last-child{border:none}
.sub-name{font-size:13px;font-weight:600;flex:1}
.sub-meta{font-size:11px;color:var(--slate-light)}
.badge{display:inline-flex;font-size:10px;font-weight:700;padding:3px 9px;border-radius:20px;text-transform:uppercase}
.b-compulsory{background:#ECFDF5;color:#059669}
.b-elective{background:#EFF6FF;color:#2563EB}
.b-optional{background:#FFFBEB;color:#D97706}
.b-selected{background:#ECFDF5;color:#059669}
.btn{display:inline-flex;align-items:center;gap:5px;padding:7px 13px;font-size:12px;font-weight:600;font-family:inherit;border-radius:7px;border:none;cursor:pointer;text-decoration:none;transition:all 150ms}
.btn-p{background:var(--indigo);color:white}.btn-ghost{background:#F1F5F9;color:var(--slate);border:1px solid var(--border)}
.btn-danger{background:#FEF2F2;color:#DC2626;border:1px solid #FECACA}
.alert-s{background:#ECFDF5;border:1px solid #A7F3D0;border-radius:8px;padding:10px 14px;font-size:13px;color:#059669;margin-bottom:14px}
.alert-e{background:#FEF2F2;border:1px solid #FECACA;border-radius:8px;padding:10px 14px;font-size:13px;color:#DC2626;margin-bottom:14px}
.profile-row{display:flex;gap:12px;margin-bottom:16px;flex-wrap:wrap}
.pr-pill{background:white;border:1px solid var(--border);border-radius:8px;padding:10px 14px;flex:1;min-width:130px}
.pr-val{font-size:14px;font-weight:700;color:var(--midnight)}.pr-lbl{font-size:10px;color:var(--slate-light);margin-top:2px;text-transform:uppercase;letter-spacing:.06em}
.group-head{background:#F8FAFC;border-top:1px solid var(--border);padding:8px 14px;font-size:10px;font-weight:800;text-transform:uppercase;letter-spacing:.07em;color:var(--slate);display:flex;justify-content:space-between}
.fc{padding:8px 12px;font-size:13px;font-family:inherit;border:1.5px solid var(--border);border-radius:8px;background:#F8FAFC;outline:none;width:100%;margin-bottom:10px}
.fc:focus{border-color:var(--indigo)}

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
<div style="display:flex;align-items:center;gap:10px;margin-bottom:16px;flex-wrap:wrap">
    <a href="{{ route('students.show',$student) }}" class="btn btn-ghost">← Back to Student</a>
    <h2 style="font-size:16px;font-weight:800;color:var(--midnight)">{{ $student->full_name }}</h2>
    <span style="font-size:12px;color:var(--slate-light)">Subject Selection</span>
    {{-- Sync compulsory --}}
    <form method="POST" action="{{ route('curriculum.student-subjects.sync', $student) }}" style="margin-left:auto">
        @csrf
        <button type="submit" class="btn btn-ghost">🔄 Sync Compulsory Subjects</button>
    </form>
</div>

@if(session('success'))<div class="alert-s">✓ {{ session('success') }}</div>@endif
@if($errors->any())<div class="alert-e">{{ $errors->first() }}</div>@endif

<div class="profile-row">
    <div class="pr-pill"><div class="pr-val">{{ $arm->full_name }}</div><div class="pr-lbl">Current Class</div></div>
    <div class="pr-pill"><div class="pr-val">{{ optional($arm->academicTrack)->name ?? 'General' }}</div><div class="pr-lbl">Academic Track</div></div>
    <div class="pr-pill"><div class="pr-val" style="color:#059669">{{ $compulsoryRules->count() }}</div><div class="pr-lbl">Compulsory</div></div>
    <div class="pr-pill"><div class="pr-val" style="color:#2563EB">{{ $selections->count() }}</div><div class="pr-lbl">Total Enrolled</div></div>
    <div class="pr-pill"><div class="pr-val">{{ optional($session)->name ?? '—' }}</div><div class="pr-lbl">Session</div></div>
</div>

<div class="two">
    {{-- Compulsory subjects (auto) --}}
    <div>
        <div class="card">
            <div class="ch">
                ✅ Compulsory Subjects
                <span style="font-size:12px;color:var(--slate-light)">Auto — cannot be removed</span>
            </div>
            @forelse($compulsoryRules as $rule)
            <div class="sub-row">
                <div style="flex:1">
                    <div class="sub-name">{{ optional($rule->subject)->name }}</div>
                    <div class="sub-meta">{{ optional($rule->subject)->code }}
                        @if($rule->academicTrack) · {{ $rule->academicTrack->name }} @else · All Tracks @endif
                    </div>
                </div>
                <span class="badge b-compulsory">Compulsory</span>
                @if($selections->has($rule->subject_id))
                <span style="font-size:11px;color:#059669">✓ Enrolled</span>
                @else
                <span style="font-size:11px;color:var(--amber)">⚠ Not synced</span>
                @endif
            </div>
            @empty
            <div style="padding:30px;text-align:center;color:var(--slate-light)">
                No compulsory subjects defined. <a href="{{ route('curriculum.level-subjects', $arm->class_level_id) }}" style="color:var(--indigo)">Set up subject rules →</a>
            </div>
            @endforelse
        </div>

        {{-- Elective subjects --}}
        <div class="card">
            <div class="ch">
                🔵 Elective Subjects
                <span style="font-size:12px;color:var(--slate-light)">Student choices</span>
            </div>

            @if($electiveGroups->isEmpty())
            <div style="padding:30px;text-align:center;color:var(--slate-light)">No elective subjects available for this track.</div>
            @else
            @foreach($electiveGroups as $groupName => $groupRules)
            <div class="group-head">
                {{ $groupName ?: 'Electives' }}
                @php
                    $gMin = $groupRules->first()?->min_required;
                    $gMax = $groupRules->first()?->max_allowed;
                    $gSelected = $groupRules->filter(fn($r) => $selections->has($r->subject_id))->count();
                @endphp
                <span>
                    @if($gMin || $gMax)
                    Min: {{ $gMin ?? 0 }} · Max: {{ $gMax ?? '∞' }} · Selected: {{ $gSelected }}
                    @endif
                </span>
            </div>
            @foreach($groupRules as $rule)
            @php $isSelected = $selections->has($rule->subject_id); @endphp
            <div class="sub-row" style="{{ !$isSelected ? 'opacity:.75':'' }}">
                <div style="flex:1">
                    <div class="sub-name">{{ optional($rule->subject)->name }}</div>
                    <div class="sub-meta">{{ optional($rule->subject)->code }} · {{ ucfirst($rule->subject_status) }}</div>
                </div>
                @if($isSelected)
                <span class="badge b-selected">✓ Selected</span>
                <form method="POST" action="{{ route('curriculum.student-subjects.remove', $student) }}">
                    @csrf @method('DELETE')
                    <input type="hidden" name="subject_id" value="{{ $rule->subject_id }}">
                    @if($session)<input type="hidden" name="session_id" value="{{ $session->id }}">@endif
                    <button class="btn btn-danger" style="padding:4px 8px;font-size:11px">Remove</button>
                </form>
                @else
                <form method="POST" action="{{ route('curriculum.student-subjects.add', $student) }}">
                    @csrf
                    <input type="hidden" name="subject_id" value="{{ $rule->subject_id }}">
                    @if($session)<input type="hidden" name="session_id" value="{{ $session->id }}">@endif
                    <button class="btn btn-p" style="padding:4px 8px;font-size:11px">+ Select</button>
                </form>
                @endif
            </div>
            @endforeach
            @endforeach
            @endif
        </div>
    </div>

    {{-- Quick add elective panel --}}
    <div>
        <div class="card">
            <div class="ch">➕ Add Elective Manually</div>
            <div style="padding:16px">
            <form method="POST" action="{{ route('curriculum.student-subjects.add', $student) }}">
                @csrf
                <select name="subject_id" class="fc" required>
                    <option value="">Select elective subject...</option>
                    @foreach($electiveRules as $r)
                    @if(!$selections->has($r->subject_id))
                    <option value="{{ $r->subject_id }}">{{ optional($r->subject)->name }} ({{ ucfirst($r->subject_status) }})</option>
                    @endif
                    @endforeach
                </select>
                @if($session)
                <input type="hidden" name="session_id" value="{{ $session->id }}">
                @endif
                <button type="submit" class="btn btn-p" style="width:100%;justify-content:center">+ Add Elective</button>
            </form>
            </div>
        </div>

        <div class="card">
            <div class="ch">📋 All Selected Subjects ({{ $selections->count() }})</div>
            <div style="padding:14px">
            @forelse($allSelected as $sel)
            <div style="display:flex;align-items:center;justify-content:space-between;padding:7px 0;border-bottom:1px solid var(--border)">
                <span style="font-size:13px;font-weight:600">{{ optional($sel->subject)->name }}</span>
                <span class="badge {{ $sel->selection_type === 'compulsory' ? 'b-compulsory':'b-elective' }}">
                    {{ ucfirst($sel->selection_type) }}
                </span>
            </div>
            @empty
            <div style="text-align:center;color:var(--slate-light);padding:20px;font-size:13px">
                No subjects enrolled. Click "Sync Compulsory" to start.
            </div>
            @endforelse
            </div>
        </div>
    </div>
</div>
@endsection
