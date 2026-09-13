@extends('layouts.app')
@section('title', 'Assessment Setup')
@section('page-title', 'Score Entry')

@push('styles')
<style>
    .assessment-grid { display:grid; grid-template-columns:minmax(0,1fr) 360px; gap:20px; align-items:start; }
    .assessment-card { background:#fff; border:1px solid var(--border); border-radius:12px; overflow:hidden; box-shadow:0 1px 3px rgba(15,23,42,.06); }
    .assessment-card__header { padding:16px 18px; border-bottom:1px solid var(--border); display:flex; gap:12px; align-items:center; justify-content:space-between; }
    .assessment-card__title { font-size:14px; font-weight:700; color:var(--midnight); }
    .assessment-card__body { padding:18px; }
    .assessment-table { width:100%; border-collapse:collapse; }
    .assessment-table th { padding:10px 14px; text-align:left; background:#F8FAFC; border-bottom:1px solid var(--border); color:var(--slate-light); font-size:11px; text-transform:uppercase; letter-spacing:.04em; }
    .assessment-table td { padding:12px 14px; border-bottom:1px solid var(--border); font-size:13px; vertical-align:middle; }
    .assessment-table tr:last-child td { border-bottom:0; }
    .assessment-actions { display:flex; flex-wrap:wrap; gap:6px; }
    .assessment-btn { display:inline-flex; align-items:center; justify-content:center; min-height:32px; padding:6px 10px; border:0; border-radius:7px; font:600 12px inherit; cursor:pointer; text-decoration:none; }
    .assessment-btn--edit { background:#EFF6FF; color:#1D4ED8; }
    .assessment-btn--copy { background:#F0FDF4; color:#15803D; }
    .assessment-btn--delete { background:#FEF2F2; color:#B91C1C; }
    .assessment-btn--primary { width:100%; background:var(--indigo); color:#fff; padding:9px 14px; }
    .assessment-form-group { margin-bottom:14px; }
    .assessment-label { display:block; margin-bottom:6px; color:var(--slate); font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:.04em; }
    .assessment-control { box-sizing:border-box; width:100%; padding:9px 11px; border:1px solid var(--border); border-radius:8px; background:#F8FAFC; font:13px inherit; }
    .assessment-control:focus { outline:0; border-color:var(--indigo); background:#fff; box-shadow:0 0 0 3px rgba(37,99,235,.1); }
    .assessment-alert { padding:11px 14px; margin-bottom:16px; border-radius:8px; font-size:13px; }
    .assessment-alert--success { color:#047857; background:#ECFDF5; border:1px solid #A7F3D0; }
    .assessment-alert--error { color:#B91C1C; background:#FEF2F2; border:1px solid #FECACA; }
    .assessment-badge { display:inline-flex; padding:3px 8px; border-radius:999px; font-size:11px; font-weight:700; background:#EEF2FF; color:#4338CA; }
    .assessment-badge--exam { background:#FFF7ED; color:#C2410C; }
    .assessment-empty { padding:34px 18px; text-align:center; color:var(--slate-light); font-size:13px; }
    .assessment-dialog { width:min(460px,calc(100vw - 28px)); border:0; border-radius:14px; padding:0; box-shadow:0 24px 70px rgba(15,23,42,.25); }
    .assessment-dialog::backdrop { background:rgba(15,23,42,.5); }
    .assessment-dialog__header { padding:16px 18px; border-bottom:1px solid var(--border); font-size:14px; font-weight:700; }
    .assessment-dialog__body { padding:18px; }
    .assessment-dialog__footer { display:flex; justify-content:flex-end; gap:8px; padding:14px 18px; border-top:1px solid var(--border); }
    .assessment-dialog__footer .assessment-btn { min-width:82px; }
    @media(max-width:950px) { .assessment-grid { grid-template-columns:1fr; } }
    @media(max-width:720px) { .assessment-table { min-width:720px; } .assessment-scroll { overflow-x:auto; } }
</style>
@endpush

@section('content')
@if(session('success'))
    <div class="assessment-alert assessment-alert--success">{{ session('success') }}</div>
@endif
@if($errors->any())
    <div class="assessment-alert assessment-alert--error">{{ $errors->first() }}</div>
@endif

<div class="assessment-grid">
    <section class="assessment-card">
        <div class="assessment-card__header">
            <div>
                <div class="assessment-card__title">Assessment Types</div>
                <div style="font-size:12px;color:var(--slate-light);margin-top:3px">Administrators can correct, copy or remove unused entries.</div>
            </div>
            <span class="assessment-badge">100% maximum per term</span>
        </div>

        @if($assessmentTypes->isEmpty())
            <div class="assessment-empty">No assessment types have been created yet.</div>
        @else
            <div class="assessment-scroll">
                <table class="assessment-table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Term</th>
                            <th>Weight</th>
                            <th>Type</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                    @foreach($assessmentTypes as $at)
                        <tr>
                            <td><strong>{{ $at->name }}</strong></td>
                            <td>
                                {{ $at->term?->name ?? 'Missing term' }}
                                @if($at->term?->session?->name)
                                    <div style="font-size:11px;color:var(--slate-light)">{{ $at->term->session->name }}</div>
                                @endif
                            </td>
                            <td>{{ rtrim(rtrim(number_format((float)$at->weight_percentage, 2), '0'), '.') }}%</td>
                            <td>
                                <span class="assessment-badge {{ $at->is_exam ? 'assessment-badge--exam' : '' }}">
                                    {{ $at->is_exam ? 'Exam' : 'CA' }}
                                </span>
                            </td>
                            <td>
                                <div class="assessment-actions">
                                    <button type="button" class="assessment-btn assessment-btn--edit"
                                        onclick='openAssessmentEdit(@json(["id"=>$at->id,"name"=>$at->name,"term_id"=>$at->term_id,"weight"=>(float)$at->weight_percentage,"is_exam"=>(bool)$at->is_exam]))'>Edit</button>
                                    <button type="button" class="assessment-btn assessment-btn--copy"
                                        onclick='openAssessmentCopy(@json(["id"=>$at->id,"name"=>$at->name,"weight"=>(float)$at->weight_percentage]))'>Copy</button>
                                    <form method="POST" action="{{ route('scores.assessment-types.destroy', $at) }}"
                                          onsubmit="return confirm('Delete {{ addslashes($at->name) }}? This is allowed only when no student scores use it.');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="assessment-btn assessment-btn--delete">Delete</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>

    <aside class="assessment-card">
        <div class="assessment-card__header"><div class="assessment-card__title">Add Assessment Type</div></div>
        <div class="assessment-card__body">
            <form method="POST" action="{{ route('scores.assessment-types.store') }}">
                @csrf
                <div class="assessment-form-group">
                    <label class="assessment-label">Term</label>
                    <select name="term_id" class="assessment-control" required>
                        <option value="">Select term</option>
                        @foreach($terms as $term)
                            <option value="{{ $term->id }}" {{ old('term_id') == $term->id ? 'selected' : '' }}>
                                {{ $term->name }} — {{ $term->session?->name ?? 'Session unavailable' }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="assessment-form-group">
                    <label class="assessment-label">Assessment name</label>
                    <input name="name" class="assessment-control" value="{{ old('name') }}" placeholder="e.g. First CA" required>
                </div>
                <div class="assessment-form-group">
                    <label class="assessment-label">Weight (%)</label>
                    <input type="number" min="1" max="100" step="1" name="weight_percentage" class="assessment-control" value="{{ old('weight_percentage') }}" required>
                </div>
                <div class="assessment-form-group">
                    <label style="display:flex;gap:8px;align-items:center;font-size:13px">
                        <input type="checkbox" name="is_exam" value="1" {{ old('is_exam') ? 'checked' : '' }}> Terminal examination
                    </label>
                </div>
                <div class="assessment-form-group">
                    <label class="assessment-label">Objective max (optional)</label>
                    <input type="number" min="0.5" step="0.5" name="objective_max" class="assessment-control" value="{{ old('objective_max') }}">
                </div>
                <div class="assessment-form-group">
                    <label class="assessment-label">Theory max (optional)</label>
                    <input type="number" min="0.5" step="0.5" name="theory_max" class="assessment-control" value="{{ old('theory_max') }}">
                </div>
                <button class="assessment-btn assessment-btn--primary" type="submit">Add Assessment Type</button>
            </form>
        </div>
    </aside>
</div>

<dialog id="assessmentEditDialog" class="assessment-dialog">
    <div class="assessment-dialog__header">Edit Assessment Type</div>
    <form method="POST" id="assessmentEditForm">
        @csrf
        @method('PUT')
        <div class="assessment-dialog__body">
            <div class="assessment-form-group">
                <label class="assessment-label">Name</label>
                <input name="name" id="assessmentEditName" class="assessment-control" required>
            </div>
            <div class="assessment-form-group">
                <label class="assessment-label">Term</label>
                <select name="term_id" id="assessmentEditTerm" class="assessment-control" required>
                    @foreach($terms as $term)
                        <option value="{{ $term->id }}">{{ $term->name }} — {{ $term->session?->name ?? 'Session unavailable' }}</option>
                    @endforeach
                </select>
            </div>
            <div class="assessment-form-group">
                <label class="assessment-label">Weight (%)</label>
                <input type="number" min="0.5" max="100" step="0.5" name="weight_percentage" id="assessmentEditWeight" class="assessment-control" required>
            </div>
            <label style="display:flex;gap:8px;align-items:center;font-size:13px">
                <input type="checkbox" name="is_exam" value="1" id="assessmentEditExam"> Terminal examination
            </label>
        </div>
        <div class="assessment-dialog__footer">
            <button type="button" class="assessment-btn" onclick="document.getElementById('assessmentEditDialog').close()">Cancel</button>
            <button type="submit" class="assessment-btn assessment-btn--edit">Save</button>
        </div>
    </form>
</dialog>

<dialog id="assessmentCopyDialog" class="assessment-dialog">
    <div class="assessment-dialog__header">Copy Assessment Type</div>
    <form method="POST" id="assessmentCopyForm">
        @csrf
        @method('PATCH')
        <div class="assessment-dialog__body">
            <p style="font-size:13px;color:var(--slate);margin-top:0">Copy <strong id="assessmentCopyName"></strong> to another term without altering existing scores.</p>
            <div class="assessment-form-group">
                <label class="assessment-label">Target academic session</label>
                <select id="assessmentCopySession" class="assessment-control" onchange="filterAssessmentTerms(this.value)">
                    <option value="">All sessions</option>
                    @foreach($sessions as $session)
                        <option value="{{ $session->id }}">{{ $session->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="assessment-form-group">
                <label class="assessment-label">Target term</label>
                <select name="term_id" id="assessmentCopyTerm" class="assessment-control" required>
                    <option value="">Select term</option>
                    @foreach($terms as $term)
                        <option value="{{ $term->id }}" data-session="{{ $term->session_id }}">{{ $term->name }} — {{ $term->session?->name ?? 'Session unavailable' }}</option>
                    @endforeach
                </select>
            </div>
            <div class="assessment-form-group">
                <label class="assessment-label">Weight (%)</label>
                <input type="number" min="0.5" max="100" step="0.5" name="weight_percentage" id="assessmentCopyWeight" class="assessment-control" required>
            </div>
        </div>
        <div class="assessment-dialog__footer">
            <button type="button" class="assessment-btn" onclick="document.getElementById('assessmentCopyDialog').close()">Cancel</button>
            <button type="submit" class="assessment-btn assessment-btn--copy">Copy</button>
        </div>
    </form>
</dialog>
@endsection

@push('scripts')
<script>
function openAssessmentEdit(data) {
    document.getElementById('assessmentEditForm').action = `/scores/assessment-types/${data.id}`;
    document.getElementById('assessmentEditName').value = data.name;
    document.getElementById('assessmentEditTerm').value = data.term_id;
    document.getElementById('assessmentEditWeight').value = data.weight;
    document.getElementById('assessmentEditExam').checked = !!data.is_exam;
    document.getElementById('assessmentEditDialog').showModal();
}
function openAssessmentCopy(data) {
    document.getElementById('assessmentCopyForm').action = `/scores/assessment-types/${data.id}/migrate`;
    document.getElementById('assessmentCopyName').textContent = data.name;
    document.getElementById('assessmentCopyWeight').value = data.weight;
    document.getElementById('assessmentCopySession').value = '';
    filterAssessmentTerms('');
    document.getElementById('assessmentCopyTerm').value = '';
    document.getElementById('assessmentCopyDialog').showModal();
}
function filterAssessmentTerms(sessionId) {
    document.querySelectorAll('#assessmentCopyTerm option[data-session]').forEach(option => {
        const visible = !sessionId || String(option.dataset.session) === String(sessionId);
        option.hidden = !visible;
        option.disabled = !visible;
    });
    document.getElementById('assessmentCopyTerm').value = '';
}
</script>
@endpush
