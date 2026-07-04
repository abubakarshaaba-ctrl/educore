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
    .badge { display:inline-flex;font-size:10px;font-weight:600;padding:2px 8px;border-radius:20px; }
    .badge-draft     { background:#F1F5F9;color:var(--slate); }
    .badge-published { background:var(--indigo-bg);color:var(--indigo); }
    .badge-active    { background:#ECFDF5;color:var(--emerald); }
    .badge-closed    { background:#FEF2F2;color:var(--crimson); }
    .empty-state { text-align:center;padding:40px;color:var(--slate-light);font-size:13px; }
    @media(max-width:1024px) { .two-col { grid-template-columns:1fr; } }
</style>
@endpush

@section('content')
<div class="page-tabs">
    <a href="{{ route('cbt.banks') }}" class="page-tab">Question Banks</a>
    <a href="{{ route('cbt.exams') }}" class="page-tab active">Exams</a>
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
                    <span>🏫 {{ $exam->classArm->classLevel->name }} {{ $exam->classArm->name }}</span>
                    <span>⏱ {{ $exam->duration_minutes }} mins</span>
                    <span>❓ {{ $exam->total_questions }} questions</span>
                </div>
                <div class="exam-actions">
                    <a href="{{ route('cbt.results', $exam) }}" class="btn btn-ghost">View Results</a>
                    @if($exam->status === 'draft')
                        <form method="POST" action="{{ route('cbt.publish', $exam) }}" style="display:inline">
                            @csrf
                            <button type="submit" class="btn btn-success">Publish</button>
                        </form>
                    @elseif($exam->status === 'published')
                        <form method="POST" action="{{ route('cbt.close', $exam) }}" style="display:inline">
                            @csrf
                            <button type="submit" class="btn btn-warning">Close Exam</button>
                        </form>
                    @endif
                </div>
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
                            <option value="{{ $bank->id }}" {{ old('question_bank_id') == $bank->id ? 'selected' : '' }}>
                                {{ $bank->name }} ({{ $bank->questions()->count() }} questions)
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Assign to <span>*</span></label>
                    <select name="target" class="form-control" required>
                        <option value="">Select class level or class</option>
                        <optgroup label="Whole level (one exam per arm)">
                            @foreach($classLevels as $level)
                                <option value="level:{{ $level->id }}" {{ old('target') === 'level:'.$level->id ? 'selected' : '' }}>{{ $level->name }} — all arms</option>
                            @endforeach
                        </optgroup>
                        <optgroup label="Specific class">
                            @foreach($classArms as $arm)
                                <option value="arm:{{ $arm->id }}" {{ old('target') === 'arm:'.$arm->id ? 'selected' : '' }}>
                                    {{ $arm->classLevel->name }} {{ $arm->name }}
                                </option>
                            @endforeach
                        </optgroup>
                    </select>
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
                {{-- ── Section Configuration ──────────────────────────────────── --}}
                <div style="background:#F8FAFC;border:1px solid var(--border);border-radius:10px;padding:14px;margin-bottom:14px">
                    <div style="font-size:12px;font-weight:800;color:var(--midnight);margin-bottom:12px;letter-spacing:.03em">EXAM SECTIONS</div>

                    {{-- Section A: Objectives --}}
                    <div style="background:white;border:1px solid #BFDBFE;border-radius:8px;padding:12px;margin-bottom:10px">
                        <div style="font-size:12px;font-weight:700;color:#1D4ED8;margin-bottom:10px">
                            📝 Section A — Objective Questions <span style="font-weight:400;color:#64748B">(MCQ, True/False, Fill-in-Blank)</span>
                        </div>
                        <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px">
                            <div class="form-group">
                                <label class="form-label">Number of Questions</label>
                                <input type="number" name="section_objective_count" class="form-control"
                                       value="{{ old('section_objective_count', 30) }}" min="0"
                                       placeholder="0 = skip section A"
                                       oninput="updateTotals()">
                                <div style="font-size:10px;color:var(--slate-light);margin-top:3px">Set 0 to skip this section</div>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Marks per Question</label>
                                <input type="number" name="section_objective_marks" class="form-control"
                                       value="{{ old('section_objective_marks', 1) }}" min="0.25" step="0.25"
                                       oninput="updateTotals()">
                                <div style="font-size:10px;color:var(--slate-light);margin-top:3px">e.g. 1 or 2 marks each</div>
                            </div>
                        </div>
                    </div>

                    {{-- Section B: Theory --}}
                    <div style="background:white;border:1px solid #A7F3D0;border-radius:8px;padding:12px;margin-bottom:12px">
                        <div style="font-size:12px;font-weight:700;color:#059669;margin-bottom:10px">
                            ✍️ Section B — Theory / Essay Questions <span style="font-weight:400;color:#64748B">(Short Answer, Essay)</span>
                        </div>
                        <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px">
                            <div class="form-group">
                                <label class="form-label">Number of Questions</label>
                                <input type="number" name="section_theory_count" class="form-control"
                                       value="{{ old('section_theory_count', 0) }}" min="0"
                                       placeholder="0 = skip section B"
                                       oninput="updateTotals()">
                                <div style="font-size:10px;color:var(--slate-light);margin-top:3px">Set 0 to skip this section</div>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Marks per Question</label>
                                <input type="number" name="section_theory_marks" class="form-control"
                                       value="{{ old('section_theory_marks', 5) }}" min="0.25" step="0.25"
                                       oninput="updateTotals()">
                                <div style="font-size:10px;color:var(--slate-light);margin-top:3px">e.g. 5 or 10 marks each</div>
                            </div>
                        </div>
                    </div>

                    {{-- Live totals preview --}}
                    <div style="background:#EFF6FF;border:1px solid #BFDBFE;border-radius:8px;padding:10px;display:flex;gap:16px;flex-wrap:wrap">
                        <div style="font-size:12px;color:#1D4ED8">
                            Total questions: <strong id="totalQPreview">30</strong>
                        </div>
                        <div style="font-size:12px;color:#1D4ED8">
                            Total marks: <strong id="totalMPreview">30</strong>
                        </div>
                        <div style="font-size:12px;color:#64748B;margin-left:auto">
                            Sec A: <span id="secAPreview">30 × 1 = 30</span> &nbsp;|&nbsp;
                            Sec B: <span id="secBPreview">0 × 5 = 0</span>
                        </div>
                    </div>
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
                <button type="submit" class="btn btn-primary">Create Exam</button>
            </form>
        </div>
    </div>
</div>
@push('scripts')
<script>
function updateTotals() {
    const objN  = parseInt(document.querySelector('[name=section_objective_count]').value) || 0;
    const objM  = parseFloat(document.querySelector('[name=section_objective_marks]').value) || 0;
    const theN  = parseInt(document.querySelector('[name=section_theory_count]').value) || 0;
    const theM  = parseFloat(document.querySelector('[name=section_theory_marks]').value) || 0;
    const totalQ = objN + theN;
    const totalM = (objN * objM) + (theN * theM);
    document.getElementById('totalQPreview').textContent = totalQ;
    document.getElementById('totalMPreview').textContent = Math.round(totalM * 100) / 100;
    document.getElementById('secAPreview').textContent   = `${objN} × ${objM} = ${Math.round(objN * objM * 100)/100}`;
    document.getElementById('secBPreview').textContent   = `${theN} × ${theM} = ${Math.round(theN * theM * 100)/100}`;
}
document.addEventListener('DOMContentLoaded', updateTotals);
</script>
@endpush
@endsection
