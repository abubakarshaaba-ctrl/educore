@extends('layouts.app')
@section('title','Subject Rules — '.$level->name)
@section('page-title','Curriculum')

@push('styles')
<style>
.ctabs{display:flex;gap:4px;background:white;border:1px solid var(--border);border-radius:10px;padding:4px;margin-bottom:20px;width:fit-content}
.ctab{padding:7px 16px;border-radius:7px;font-size:13px;font-weight:500;color:var(--slate);text-decoration:none;transition:all 150ms}
.ctab.active,.ctab:hover{background:var(--indigo);color:white}
.card{background:white;border:1px solid var(--border);border-radius:12px;overflow:hidden;margin-bottom:16px}
.ch{padding:13px 18px;border-bottom:1px solid var(--border);background:#F8FAFC;font-size:13px;font-weight:700;color:var(--midnight);display:flex;align-items:center;justify-content:space-between}
.two{display:grid;grid-template-columns:1fr 360px;gap:16px;align-items:start}
.rule-row{display:flex;align-items:center;gap:10px;padding:10px 14px;border-bottom:1px solid var(--border)}
.rule-row:last-child{border:none}
.rule-subj{font-size:13px;font-weight:600;color:var(--midnight);flex:1}
.rule-meta{font-size:11px;color:var(--slate-light);margin-top:1px}
.status-dot{width:10px;height:10px;border-radius:50%;flex-shrink:0}
.badge{display:inline-flex;font-size:10px;font-weight:700;padding:3px 9px;border-radius:20px;text-transform:uppercase;letter-spacing:.04em}
.b-compulsory{background:#ECFDF5;color:#059669}
.b-elective{background:#EFF6FF;color:#2563EB}
.b-optional{background:#FFFBEB;color:#D97706}
.b-not_offered{background:#F1F5F9;color:#94A3B8}
.track-pills{display:flex;gap:6px;margin-bottom:16px;flex-wrap:wrap}
.track-pill{padding:7px 16px;font-size:13px;font-weight:600;border-radius:20px;border:1.5px solid var(--border);text-decoration:none;color:var(--slate);transition:all 150ms}
.track-pill.active,.track-pill:hover{background:var(--indigo);border-color:var(--indigo);color:white}
.btn{display:inline-flex;align-items:center;gap:5px;padding:7px 13px;font-size:12px;font-weight:600;font-family:inherit;border-radius:7px;border:none;cursor:pointer;text-decoration:none;transition:all 150ms}
.btn-p{background:var(--indigo);color:white}.btn-ghost{background:#F1F5F9;color:var(--slate);border:1px solid var(--border)}
.btn-danger{background:#FEF2F2;color:#DC2626;border:1px solid #FECACA}
.fg{display:flex;flex-direction:column;gap:5px;margin-bottom:12px}
.fl{font-size:11px;font-weight:700;color:var(--slate);text-transform:uppercase;letter-spacing:.05em}
.fc{padding:9px 12px;font-size:13px;font-family:inherit;border:1.5px solid var(--border);border-radius:8px;background:#F8FAFC;outline:none;width:100%}
.fc:focus{border-color:var(--indigo)}
.alert-s{background:#ECFDF5;border:1px solid #A7F3D0;border-radius:8px;padding:10px 14px;font-size:13px;color:#059669;margin-bottom:14px}
.info-box{background:#FFFBEB;border:1px solid #FDE68A;border-radius:9px;padding:11px 14px;font-size:12px;color:#92400E;margin-bottom:14px}
.status-section{margin-bottom:14px}
.status-heading{font-size:10px;font-weight:800;text-transform:uppercase;letter-spacing:.08em;padding:8px 14px;background:#F8FAFC;border-top:1px solid var(--border);border-bottom:1px solid var(--border)}
select.inline-sel{font-size:12px;padding:4px 8px;border:1px solid var(--border);border-radius:6px;background:#F8FAFC;font-family:inherit}

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
<div class="ctabs">
    <a href="{{ route('curriculum.tracks') }}"     class="ctab">📋 Tracks</a>
    <a href="{{ route('curriculum.arm-tracks') }}" class="ctab">🏫 Arm Assignments</a>
</div>

@if(session('success'))<div class="alert-s">✓ {{ session('success') }}</div>@endif

<div style="display:flex;align-items:center;gap:10px;margin-bottom:16px;flex-wrap:wrap">
    <a href="{{ route('curriculum.arm-tracks') }}" style="font-size:13px;color:var(--indigo);text-decoration:none">← Class Arms</a>
    <h2 style="font-size:16px;font-weight:800;color:var(--midnight)">{{ $level->name }} — Subject Rules</h2>
    <span style="font-size:12px;color:var(--slate-light)">{{ $rules->count() }} rules defined</span>
</div>

{{-- Track selector --}}
<div style="margin-bottom:16px">
    <div style="font-size:11px;font-weight:700;color:var(--slate);text-transform:uppercase;letter-spacing:.06em;margin-bottom:8px">Filter by Track</div>
    <div class="track-pills">
        <a href="{{ route('curriculum.level-subjects', $level) }}"
           class="track-pill {{ !$trackId ? 'active':'' }}">All Tracks</a>
        @foreach($tracks as $t)
        <a href="{{ route('curriculum.level-subjects', [$level, 'track_id'=>$t->id]) }}"
           class="track-pill {{ $trackId == $t->id ? 'active':'' }}">{{ $t->name }}</a>
        @endforeach
    </div>
</div>

@if($level->isSenior() && !$trackId)
<div class="info-box">
    💡 <strong>Tip:</strong> This is a senior class level. You should define subject rules separately for each academic track (Science, Humanities, Business). Select a track above to add or edit rules for that track.
</div>
@endif

<div class="two">
    {{-- Current rules --}}
    <div>
        @php
            $statusOrder = ['compulsory','elective','optional','not_offered'];
            $statusLabels = ['compulsory'=>'Compulsory','elective'=>'Elective','optional'=>'Optional','not_offered'=>'Not Offered'];
            $statusColors = ['compulsory'=>'#059669','elective'=>'#2563EB','optional'=>'#D97706','not_offered'=>'#94A3B8'];
        @endphp

        @forelse($statusOrder as $status)
        @php $group = $byStatus->get($status, collect()); @endphp
        @if($group->isNotEmpty())
        <div class="card" style="margin-bottom:12px">
            <div class="ch" style="background:{{ $statusColors[$status] }}18;color:{{ $statusColors[$status] }}">
                {{ $statusLabels[$status] }}
                <span style="font-size:12px;opacity:.7">{{ $group->count() }} subject(s)</span>
            </div>
            @foreach($group as $rule)
            <div class="rule-row">
                <div class="status-dot" style="background:{{ $statusColors[$rule->subject_status] }}"></div>
                <div style="flex:1">
                    <div class="rule-subj">{{ optional($rule->subject)->name }}</div>
                    <div class="rule-meta">
                        {{ optional($rule->subject)->code }}
                        @if($rule->elective_group) · Group: {{ $rule->elective_group }} @endif
                        @if($rule->min_required) · Min: {{ $rule->min_required }} @endif
                        @if($rule->max_allowed) · Max: {{ $rule->max_allowed }} @endif
                        @if($rule->academicTrack) · Track: {{ $rule->academicTrack->name }} @else · All Tracks @endif
                    </div>
                </div>
                <form method="POST" action="{{ route('curriculum.level-subjects.update', $rule->id) }}" style="display:inline">
                    @csrf @method('PATCH')
                    <select name="subject_status" class="inline-sel" onchange="this.form.submit()">
                        @foreach(['compulsory','elective','optional','not_offered'] as $s)
                        <option value="{{ $s }}" {{ $rule->subject_status === $s ? 'selected':'' }}>{{ ucfirst(str_replace('_',' ',$s)) }}</option>
                        @endforeach
                    </select>
                </form>
                <form method="POST" action="{{ route('curriculum.level-subjects.destroy', $rule->id) }}">
                    @csrf @method('DELETE')
                    <button class="btn btn-danger" style="padding:5px 10px;font-size:11px" onclick="return confirm('Remove subject rule?')">✕</button>
                </form>
            </div>
            @endforeach
        </div>
        @endif
        @endforeach

        @if($rules->isEmpty())
        <div style="background:white;border:1px dashed var(--border);border-radius:12px;padding:50px;text-align:center;color:var(--slate-light)">
            <div style="font-size:36px;margin-bottom:10px">📚</div>
            <div style="font-weight:600;color:var(--slate)">No subject rules yet</div>
            <div style="font-size:12px;margin-top:4px">Add subjects using the form on the right. Start with compulsory subjects.</div>
        </div>
        @endif
    </div>

    {{-- Add rule form --}}
    <div>
        <div class="card">
            <div class="ch">➕ Add Subject Rule</div>
            <div style="padding:18px">
            <form method="POST" action="{{ route('curriculum.level-subjects.store', $level) }}">
                @csrf
                <div class="fg"><label class="fl">Subject *</label>
                    <select name="subject_id" class="fc" required>
                        <option value="">Select subject...</option>
                        @foreach($unassigned as $s)
                        <option value="{{ $s->id }}">{{ $s->name }}@if($s->code) ({{ $s->code }})@endif</option>
                        @endforeach
                        @if($unassigned->isEmpty())
                        <optgroup label="Already assigned (to change status, use dropdown above)">
                        @foreach($allSubjects as $s)
                        <option value="{{ $s->id }}" disabled>{{ $s->name }}</option>
                        @endforeach
                        </optgroup>
                        @endif
                    </select>
                </div>
                <div class="fg"><label class="fl">Academic Track</label>
                    <select name="academic_track_id" class="fc">
                        <option value="">All Tracks (General / Junior)</option>
                        @foreach($tracks as $t)
                        <option value="{{ $t->id }}" {{ $trackId == $t->id ? 'selected':'' }}>{{ $t->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="fg"><label class="fl">Status *</label>
                    <select name="subject_status" class="fc" required>
                        <option value="compulsory">✅ Compulsory</option>
                        <option value="elective">🔵 Elective</option>
                        <option value="optional">🟡 Optional</option>
                        <option value="not_offered">⚫ Not Offered</option>
                    </select>
                </div>
                <div class="fg"><label class="fl">Elective Group (optional)</label>
                    <input name="elective_group" class="fc" placeholder="e.g. Science Electives A"></div>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px">
                    <div class="fg"><label class="fl">Min Required</label>
                        <input name="min_required" type="number" class="fc" min="0" placeholder="0"></div>
                    <div class="fg"><label class="fl">Max Allowed</label>
                        <input name="max_allowed" type="number" class="fc" min="0" placeholder="—"></div>
                </div>
                <button type="submit" class="btn btn-p" style="width:100%;justify-content:center;margin-top:4px">Add Rule</button>
            </form>
            </div>
        </div>

        {{-- Summary --}}
        <div class="card">
            <div class="ch">📊 Summary</div>
            <div style="padding:14px">
                @foreach(['compulsory'=>'✅','elective'=>'🔵','optional'=>'🟡','not_offered'=>'⚫'] as $s => $icon)
                <div style="display:flex;justify-content:space-between;padding:6px 0;border-bottom:1px solid var(--border)">
                    <span style="font-size:13px">{{ $icon }} {{ ucfirst(str_replace('_',' ',$s)) }}</span>
                    <span style="font-weight:700;color:{{ $statusColors[$s] }}">{{ $byStatus->get($s,collect())->count() }}</span>
                </div>
                @endforeach
                <div style="display:flex;justify-content:space-between;padding:6px 0;font-weight:700">
                    <span>Total</span><span>{{ $rules->count() }}</span>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
