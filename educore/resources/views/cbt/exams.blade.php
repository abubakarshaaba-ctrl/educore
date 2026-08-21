@extends('layouts.app')
@section('title', 'CBT Exams')
@section('page-title', 'CBT Exams')

@push('styles')
<style>
    .page-tabs { display:flex;gap:4px;background:white;border:1px solid var(--border);border-radius:10px;padding:4px;margin-bottom:20px;width:fit-content; }
    .page-tab { padding:7px 16px;border-radius:7px;font-size:13px;font-weight:500;color:var(--slate);text-decoration:none;transition:all 150ms; }
    .page-tab.active { background:var(--indigo);color:white; }
    .page-tab:hover:not(.active) { background:#F1F5F9; }
    .two-col { display:grid;grid-template-columns:1fr 400px;gap:20px;align-items:start; }
    .card { background:white;border:1px solid var(--border);border-radius:12px;box-shadow:0 1px 3px rgba(0,0,0,0.05);overflow:hidden; }
    .card-header { padding:14px 20px;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between; }
    .card-title { font-size:14px;font-weight:600;color:var(--midnight); }
    .card-body { padding:20px; }
    .form-group { margin-bottom:14px; }
    .form-label { display:block;font-size:11px;font-weight:600;color:var(--slate);text-transform:uppercase;letter-spacing:0.05em;margin-bottom:5px; }
    .form-label span { color:var(--crimson); }
    .form-control { width:100%;padding:9px 12px;font-size:13px;font-family:inherit;border:1px solid var(--border);border-radius:8px;background:#F8FAFC;outline:none;transition:border-color 200ms; }
    .form-control:focus { border-color:var(--indigo);box-shadow:0 0 0 3px rgba(37,99,235,0.1);background:white; }
    .form-grid { display:grid;grid-template-columns:1fr 1fr;gap:12px; }
    .btn { display:inline-flex;align-items:center;gap:5px;padding:7px 12px;font-size:12px;font-weight:600;font-family:inherit;border-radius:7px;border:none;cursor:pointer;text-decoration:none;transition:background 150ms; }
    .btn-primary { background:var(--indigo);color:white;width:100%;justify-content:center;padding:10px; }
    .btn-primary:hover { background:#1D4ED8; }
    .btn-success { background:var(--emerald);color:white; }
    .btn-warning { background:var(--amber);color:white; }
    .btn-ghost { background:white;color:var(--midnight);border:1px solid var(--border); }
    .btn-indigo { background:var(--indigo-bg);color:var(--indigo); }
    .alert-success { background:#ECFDF5;border:1px solid #A7F3D0;border-radius:8px;padding:12px 16px;font-size:13px;color:var(--emerald);margin-bottom:16px; }
    .alert-error { background:#FEF2F2;border:1px solid #FECACA;border-radius:8px;padding:12px 16px;font-size:13px;color:var(--crimson);margin-bottom:16px; }
    .exam-card { border:1px solid var(--border);border-radius:10px;padding:16px;margin-bottom:12px; }
    .exam-title { font-size:14px;font-weight:700;color:var(--midnight);margin-bottom:6px; }
    .exam-meta { font-size:12px;color:var(--slate);margin-bottom:12px; }
    .exam-meta span { margin-right:12px; }
    .exam-actions { display:flex;gap:8px;flex-wrap:wrap; }
    .schedule-panel { margin-top:12px;padding:12px;border:1px solid #DCE5F2;border-radius:9px;background:#F8FAFC; }
    .schedule-panel .form-grid { grid-template-columns:1fr 1fr 120px;align-items:end; }
    .schedule-panel .form-group { margin-bottom:0; }
    .badge { display:inline-flex;font-size:10px;font-weight:600;padding:2px 8px;border-radius:20px; }
    .badge-draft     { background:#F1F5F9;color:var(--slate); }
    .badge-published { background:var(--indigo-bg);color:var(--indigo); }
    .badge-active    { background:#ECFDF5;color:var(--emerald); }
    .badge-closed    { background:#FEF2F2;color:var(--crimson); }
    .empty-state { text-align:center;padding:40px;color:var(--slate-light);font-size:13px; }
    .workflow-note { display:grid;grid-template-columns:repeat(3,1fr);gap:8px;margin-bottom:16px; }
    .workflow-step { position:relative;padding:12px;border:1px solid #DCE5F2;border-radius:10px;background:linear-gradient(145deg,#FFFFFF,#F7FAFF); }
    .workflow-step strong { display:block;font-size:12px;color:var(--midnight);margin:5px 0 3px; }
    .workflow-step span { font-size:10px;color:var(--slate);line-height:1.45;display:block; }
    .step-number { width:24px;height:24px;border-radius:8px;display:grid;place-items:center;background:var(--indigo);color:white;font-size:11px;font-weight:800; }
    .target-picker { border:1px solid var(--border);border-radius:10px;background:#F8FAFC;max-height:230px;overflow:auto;padding:8px; }
    .target-group { padding:7px 6px 9px;border-bottom:1px solid #E8EDF5; }
    .target-group:last-child { border-bottom:0; }
    .target-group strong { display:block;font-size:10px;color:var(--slate);text-transform:uppercase;letter-spacing:.05em;margin-bottom:6px; }
    .target-options { display:grid;grid-template-columns:1fr 1fr;gap:6px; }
    .target-option { display:flex;align-items:center;gap:7px;padding:7px 8px;border:1px solid #DFE7F2;border-radius:7px;background:#fff;color:var(--midnight);font-size:11px;cursor:pointer; }
    .target-option:has(input:checked) { border-color:var(--indigo);background:#EFF6FF;color:#1D4ED8;font-weight:700; }
    @media(max-width:1024px) { .two-col { grid-template-columns:1fr; } }
    @media(max-width:640px) { .workflow-note { grid-template-columns:1fr; }.target-options{grid-template-columns:1fr}.schedule-panel .form-grid{grid-template-columns:1fr} }
</style>
@endpush

@section('content')
<div class="page-tabs">
    <a href="{{ route('cbt.banks') }}" class="page-tab">Question Banks</a>
    <a href="{{ route('cbt.exams') }}" class="page-tab active">Exams</a>
    @if(auth()->user()->isSuperAdmin() || auth()->user()->isAdmin())<a href="{{ route('cbt.retakes') }}" class="page-tab">Retake Control</a>@endif
    @if(auth()->user()->isSuperAdmin() || auth()->user()->isAdmin())
    <a href="{{ route('cbt.lan') }}" class="page-tab">📡 LAN Mode</a>
    @endif
</div>

@if(session('success'))<div class="alert-success">{{ session('success') }}</div>@endif
@if($errors->any())<div class="alert-error">{{ $errors->first() }}</div>@endif

<div class="two-col">
    <div class="card">
        <div class="card-header"><span class="card-title">All Exams</span></div>
        <div class="card-body">
            @forelse($exams as $exam)
            <div class="exam-card">
                <div style="display:flex;align-items:start;justify-content:space-between;margin-bottom:6px">
                    <div class="exam-title">{{ $exam->title }}</div>
                    <span class="badge badge-{{ $exam->status }}">{{ ucfirst($exam->status) }}</span>
                </div>
                <div class="exam-meta">
                    <span>📚 {{ $exam->questionBank->subject->name ?? '—' }}</span>
                    <span>🏫 {{ $exam->assignedClassNames() ?: '—' }}</span>
                    <span>⏱ {{ $exam->duration_minutes }} mins</span>
                    <span>❓ {{ $exam->total_questions }} questions</span>
                </div>
                <div class="exam-actions">
                    <a href="{{ route('cbt.exams.builder', $exam) }}" class="btn btn-indigo">Section Builder</a>
                    <a href="{{ route('cbt.results', $exam) }}" class="btn btn-ghost">View Results</a>
                    @if($exam->status === 'draft')
                        <form method="POST" action="{{ route('cbt.publish', $exam) }}" style="display:inline">
                            @csrf
                            <button type="submit" class="btn btn-success">Publish</button>
                        </form>
                    @elseif(in_array($exam->status, ['published', 'active'], true))
                        <form method="POST" action="{{ route('cbt.close', $exam) }}" style="display:inline">
                            @csrf
                            <button type="submit" class="btn btn-warning">Close Exam</button>
                        </form>
                    @endif
                    @if(in_array($exam->status, ['published', 'active', 'closed'], true))
                        <button type="button" class="btn btn-ghost" onclick="document.getElementById('schedule{{ $exam->id }}').toggleAttribute('hidden')">Reschedule</button>
                    @endif
                </div>
                @if(in_array($exam->status, ['published', 'active', 'closed'], true))
                <div class="schedule-panel" id="schedule{{ $exam->id }}" hidden>
                    <form method="POST" action="{{ route('cbt.exams.schedule', $exam) }}">
                        @csrf @method('PUT')
                        <div class="form-grid">
                            <div class="form-group"><label class="form-label">Start</label><input type="datetime-local" name="scheduled_start" class="form-control" value="{{ $exam->scheduled_start?->format('Y-m-d\TH:i') }}" required></div>
                            <div class="form-group"><label class="form-label">End</label><input type="datetime-local" name="scheduled_end" class="form-control" value="{{ $exam->scheduled_end?->format('Y-m-d\TH:i') }}" required></div>
                            <div class="form-group"><label class="form-label">Minutes</label><input type="number" name="duration_minutes" class="form-control" value="{{ $exam->duration_minutes }}" min="5" max="1440" required></div>
                        </div>
                        <button type="submit" class="btn btn-success" style="margin-top:10px">Save schedule</button>
                    </form>
                </div>
                @endif
            </div>
            @empty
            <div class="empty-state">No exams created yet. Create one →</div>
            @endforelse
        </div>
    </div>

    <div class="card">
        <div class="card-header"><span class="card-title">Create Exam</span></div>
        <div class="card-body">
            <form method="POST" action="{{ route('cbt.exams.store') }}">
                @csrf
                <div class="form-group">
                    <label class="form-label">Exam Title <span>*</span></label>
                    <input type="text" name="title" class="form-control" value="{{ old('title') }}" placeholder="e.g. JSS 1 Mathematics Midterm Exam">
                </div>
                <div class="form-group">
                    <label class="form-label">Question Bank <span>*</span></label>
                    <select name="question_bank_id" class="form-control">
                        <option value="">Select bank</option>
                        @foreach($banks as $bank)
                            <option value="{{ $bank->id }}" {{ (string) old('question_bank_id', request('bank')) === (string) $bank->id ? 'selected' : '' }}>
                                {{ $bank->name }} ({{ $bank->questions()->count() }} questions)
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Assign to classes <span>*</span></label>
                    <div class="target-picker">
                        @foreach($classArms->groupBy('class_level_id') as $levelArms)
                        <div class="target-group"><strong>{{ $levelArms->first()->classLevel->name ?? 'Class level' }}</strong><div class="target-options">
                            @foreach($levelArms as $arm)
                            <label class="target-option"><input type="checkbox" name="class_arm_ids[]" value="{{ $arm->id }}" @checked(in_array($arm->id, array_map('intval', old('class_arm_ids', []))))><span>{{ $arm->classLevel->name }} {{ $arm->name }}</span></label>
                            @endforeach
                        </div></div>
                        @endforeach
                    </div>
                    <div style="font-size:10px;color:var(--slate-light);margin-top:4px">Select one or more classes. EduCore creates one shared exam, not duplicate drafts.</div>
                </div>
                <div class="form-group">
                    <label class="form-label">Term <span>*</span></label>
                    <select name="term_id" class="form-control">
                        <option value="">Select term</option>
                        @foreach($terms as $term)
                            <option value="{{ $term->id }}" {{ old('term_id') ? (old('term_id') == $term->id ? 'selected' : '') : ($term->is_current ? 'selected' : '') }}>
                                {{ $term->name }} — {{ $term->session->name ?? '' }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Score Entry Examination Component <span style="font-weight:400;color:var(--slate-light)">(optional)</span></label>
                    <select name="assessment_type_id" class="form-control">
                        <option value="">— Not linked (regular CBT only) —</option>
                        @foreach($assessmentTypes as $at)
                            <option value="{{ $at->id }}" {{ old('assessment_type_id') == $at->id ? 'selected' : '' }}>
                                {{ optional($at->term)->name }} — {{ $at->name }}
                                — {{ number_format((float) $at->weight_percentage, 0) }} marks
                            </option>
                        @endforeach
                    </select>
                    <div style="font-size:10px;color:var(--slate-light);margin-top:3px">
                        The completed multi-section CBT aggregate is converted to this configured maximum and synchronized to Score Entry.
                    </div>
                </div>
                <div class="workflow-note" aria-label="Dynamic exam workflow">
                    <div class="workflow-step"><div class="step-number">1</div><strong>Create draft</strong><span>Save the examination details.</span></div>
                    <div class="workflow-step"><div class="step-number">2</div><strong>Review sections</strong><span>Uploaded questions are arranged into their original or inferred sections automatically.</span></div>
                    <div class="workflow-step"><div class="step-number">3</div><strong>Validate & publish</strong><span>Assign questions and confirm every section's marks.</span></div>
                </div>

                <div class="form-group">
                    <label class="form-label">Duration (minutes) <span>*</span></label>
                    <input type="number" name="duration_minutes" class="form-control" value="{{ old('duration_minutes', 60) }}" min="5">
                </div>
                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label">Scheduled Start</label>
                        <input type="datetime-local" name="scheduled_start" class="form-control" value="{{ old('scheduled_start') }}">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Scheduled End</label>
                        <input type="datetime-local" name="scheduled_end" class="form-control" value="{{ old('scheduled_end') }}">
                    </div>
                </div>
                <div style="background:#F8FAFC;border:1px solid var(--border);border-radius:10px;padding:13px;margin-bottom:14px">
                    <div style="font-size:11px;font-weight:800;color:var(--midnight);margin-bottom:9px">EXAM INTEGRITY</div>
                    <label style="display:block;font-size:11px;color:var(--slate);margin-bottom:8px"><input type="checkbox" name="malpractice_enabled" value="1" checked> Enable integrity monitoring</label>
                    <div class="form-grid"><div class="form-group"><label class="form-label">Focus-loss action</label><select class="form-control" name="focus_loss_policy"><option value="submit">Submit attempt</option><option value="warn">Warn and log</option><option value="log">Log only</option></select></div><div class="form-group"><label class="form-label">Allowed losses</label><input class="form-control" type="number" name="max_focus_losses" value="0" min="0"></div></div>
                    <label style="display:block;font-size:11px;color:var(--slate)"><input type="checkbox" name="require_fullscreen" value="1"> Require full-screen mode</label>
                </div>
                <button type="submit" class="btn btn-primary">Create One Exam</button>
            </form>
        </div>
    </div>
</div>
@endsection
