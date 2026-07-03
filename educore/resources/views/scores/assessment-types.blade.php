@extends('layouts.app')
@section('title', 'Assessment Setup')
@section('page-title', 'Score Entry')

@push('styles')
<style>
    .page-tabs { display: flex; gap: 4px; background: white; border: 1px solid var(--border); border-radius: 10px; padding: 4px; margin-bottom: 20px; width: fit-content; }
    .page-tab { padding: 7px 16px; border-radius: 7px; font-size: 13px; font-weight: 500; color: var(--slate); text-decoration: none; transition: all 150ms; }
    .page-tab.active { background: var(--indigo); color: white; }
    .page-tab:hover:not(.active) { background: #F1F5F9; }
    .two-col { display: grid; grid-template-columns: 1fr 380px; gap: 20px; align-items: start; }
    .card { background: white; border: 1px solid var(--border); border-radius: 12px; box-shadow: 0 1px 3px rgba(0,0,0,0.05); overflow: hidden; }
    .card-header { padding: 16px 20px; border-bottom: 1px solid var(--border); display: flex; align-items: center; justify-content: space-between; }
    .card-title { font-size: 14px; font-weight: 600; color: var(--midnight); }
    .card-body { padding: 20px; }
    .form-group { margin-bottom: 16px; }
    .form-label { display: block; font-size: 11px; font-weight: 600; color: var(--slate); text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 6px; }
    .form-label span { color: var(--crimson); }
    .form-control { width: 100%; padding: 9px 12px; font-size: 13px; font-family: inherit; border: 1px solid var(--border); border-radius: 8px; background: #F8FAFC; outline: none; transition: border-color 200ms; box-sizing: border-box; }
    .form-control:focus { border-color: var(--indigo); box-shadow: 0 0 0 3px rgba(37,99,235,0.1); background: white; }
    .is-invalid { border-color: var(--crimson) !important; }
    .invalid-feedback { font-size: 12px; color: var(--crimson); margin-top: 4px; }
    .btn { display: inline-flex; align-items: center; gap: 6px; padding: 9px 16px; font-size: 13px; font-weight: 600; font-family: inherit; border-radius: 8px; border: none; cursor: pointer; text-decoration: none; transition: background 150ms; }
    .btn-primary { background: var(--indigo); color: white; width: 100%; justify-content: center; }
    .btn-primary:hover { background: #1D4ED8; }
    .btn-sm { padding: 5px 10px; font-size: 12px; font-weight: 600; border-radius: 6px; border: none; cursor: pointer; display: inline-flex; align-items: center; gap: 4px; transition: background 150ms; }
    .btn-edit { background: #EFF6FF; color: var(--indigo); }
    .btn-edit:hover { background: #DBEAFE; }
    .btn-migrate { background: #F0FDF4; color: #16A34A; }
    .btn-migrate:hover { background: #DCFCE7; }
    .btn-ghost { background: #F1F5F9; color: var(--slate); }
    .btn-ghost:hover { background: #E2E8F0; }
    .btn-full { width: 100%; justify-content: center; }
    .alert-success { background: #ECFDF5; border: 1px solid #A7F3D0; border-radius: 8px; padding: 12px 16px; font-size: 13px; color: var(--emerald); margin-bottom: 16px; }
    .alert-error { background: #FEF2F2; border: 1px solid #FECACA; border-radius: 8px; padding: 12px 16px; font-size: 13px; color: var(--crimson); margin-bottom: 16px; }
    table { width: 100%; border-collapse: collapse; }
    thead th { font-size: 11px; font-weight: 600; color: var(--slate-light); text-transform: uppercase; letter-spacing: 0.05em; padding: 10px 16px; text-align: left; background: #F8FAFC; border-bottom: 1px solid var(--border); }
    tbody td { padding: 13px 16px; border-bottom: 1px solid var(--border); font-size: 13px; color: var(--midnight); vertical-align: middle; }
    tbody tr:last-child td { border-bottom: none; }
    tbody tr:hover td { background: #F8FAFC; }
    .badge { display: inline-flex; font-size: 11px; font-weight: 600; padding: 3px 8px; border-radius: 20px; }
    .badge-info { background: var(--indigo-bg); color: var(--indigo); }
    .badge-warning { background: #FFFBEB; color: var(--amber); }
    .weight-bar { width: 60px; height: 6px; background: #E2E8F0; border-radius: 3px; overflow: hidden; display: inline-block; vertical-align: middle; margin-left: 8px; }
    .weight-fill { height: 100%; background: var(--indigo); border-radius: 3px; }
    .checkbox-row { display: flex; align-items: center; gap: 8px; font-size: 13px; color: var(--midnight); }
    .empty-state { text-align: center; padding: 40px 20px; color: var(--slate-light); font-size: 13px; }
    .action-cell { display: flex; gap: 6px; align-items: center; }

    /* ── Modal backdrop ── */
    .modal-backdrop {
        display: none; position: fixed; inset: 0;
        background: rgba(15,23,42,0.45); z-index: 1000;
        align-items: center; justify-content: center;
        backdrop-filter: blur(2px);
    }
    .modal-backdrop.open { display: flex; }

    /* ── Modal panel ── */
    .modal {
        background: white; border-radius: 14px;
        box-shadow: 0 20px 60px rgba(0,0,0,0.18);
        width: 100%; max-width: 440px; margin: 16px;
        animation: modal-in 160ms ease;
    }
    @keyframes modal-in {
        from { opacity: 0; transform: translateY(8px) scale(0.98); }
        to   { opacity: 1; transform: translateY(0) scale(1); }
    }
    .modal-header {
        padding: 18px 20px 14px;
        border-bottom: 1px solid var(--border);
        display: flex; align-items: center; justify-content: space-between;
    }
    .modal-title { font-size: 14px; font-weight: 700; color: var(--midnight); }
    .modal-close {
        width: 28px; height: 28px; border-radius: 6px;
        border: none; background: #F1F5F9; cursor: pointer;
        display: flex; align-items: center; justify-content: center;
        font-size: 16px; color: var(--slate); transition: background 150ms;
    }
    .modal-close:hover { background: #E2E8F0; }
    .modal-body { padding: 20px; }
    .modal-footer {
        padding: 14px 20px;
        border-top: 1px solid var(--border);
        display: flex; gap: 8px; justify-content: flex-end;
    }
    .modal-footer .btn { width: auto; }

    /* ── Migrate pill ── */
    .migrate-info {
        background: #F0FDF4; border: 1px solid #BBF7D0;
        border-radius: 8px; padding: 10px 14px;
        font-size: 12px; color: #15803D; margin-bottom: 16px;
        display: flex; gap: 8px; align-items: flex-start;
    }
    .migrate-info svg { flex-shrink: 0; margin-top: 1px; }

    @media(max-width:1024px) { .two-col { grid-template-columns: 1fr; } }
    @media(max-width:600px) { .action-cell { flex-direction: column; align-items: flex-start; } }
</style>
@endpush

@section('content')
<div class="page-tabs">
    <a href="{{ route('scores.assessment-types') }}" class="page-tab active">Assessment Setup</a>
    <a href="{{ route('scores.index') }}" class="page-tab">Enter Scores</a>
    <a href="{{ route('scores.broadsheet') }}" class="page-tab">Broadsheet</a>
</div>

@if(session('success'))<div class="alert-success">{{ session('success') }}</div>@endif
@if($errors->any())<div class="alert-error">{{ $errors->first() }}</div>@endif

<div class="two-col">
    {{-- ── Assessment Types Table ── --}}
    <div class="card">
        <div class="card-header">
            <span class="card-title">Assessment Types</span>
            <span class="badge badge-info">Weights must total 100% per term</span>
        </div>
        @if($assessmentTypes->count())
        <div class="tbl"><table>
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
                    <td style="font-size:12px;color:var(--slate)">
                        {{ $at->term->name }} — {{ $at->term->session->name ?? '' }}
                    </td>
                    <td>
                        {{ $at->weight_percentage }}%
                        <span class="weight-bar">
                            <span class="weight-fill" style="width:{{ $at->weight_percentage }}%"></span>
                        </span>
                    </td>
                    <td>
                        @if($at->is_exam)
                            <span class="badge badge-warning">Exam</span>
                        @else
                            <span class="badge badge-info">CA</span>
                        @endif
                    </td>
                    <td>
                        <div class="action-cell">
                            {{-- Edit button --}}
                            <button type="button" class="btn-sm btn-edit"
                                onclick="openEditModal(
                                    {{ $at->id }},
                                    '{{ addslashes($at->name) }}',
                                    {{ $at->term_id }},
                                    {{ $at->weight_percentage }},
                                    {{ $at->is_exam ? 'true' : 'false' }}
                                )">
                                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                                Edit
                            </button>
                            {{-- Migrate button --}}
                            <button type="button" class="btn-sm btn-migrate"
                                onclick="openMigrateModal(
                                    {{ $at->id }},
                                    '{{ addslashes($at->name) }}',
                                    {{ $at->term_id }}
                                )">
                                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M5 12h14"/><path d="M12 5l7 7-7 7"/></svg>
                                Migrate
                            </button>
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table></div>
        @else
        <div class="empty-state">No assessment types set up yet. Create one for the current term.</div>
        @endif
    </div>

    {{-- ── Add Assessment Type ── --}}
    <div class="card">
        <div class="card-header"><span class="card-title">Add Assessment Type</span></div>
        <div class="card-body">
            <form method="POST" action="{{ route('scores.assessment-types.store') }}">
                @csrf
                <div class="form-group">
                    <label class="form-label">Term <span>*</span></label>
                    <select name="term_id" class="form-control {{ $errors->has('term_id') ? 'is-invalid' : '' }}">
                        <option value="">Select term</option>
                        @foreach($terms as $term)
                            <option value="{{ $term->id }}" {{ old('term_id') == $term->id ? 'selected' : '' }}>
                                {{ $term->name }} — {{ $term->session->name ?? '' }}
                            </option>
                        @endforeach
                    </select>
                    @error('term_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="form-group">
                    <label class="form-label">Assessment Name <span>*</span></label>
                    <input type="text" name="name" class="form-control {{ $errors->has('name') ? 'is-invalid' : '' }}"
                        value="{{ old('name') }}" placeholder="e.g. 1st CA, Mid-Term Test, Final Exam">
                    @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="form-group">
                    <label class="form-label">Weight (%) <span>*</span></label>
                    <input type="number" name="weight_percentage"
                        class="form-control {{ $errors->has('weight_percentage') ? 'is-invalid' : '' }}"
                        value="{{ old('weight_percentage') }}" placeholder="e.g. 20" min="1" max="100">
                    @error('weight_percentage')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    <div style="font-size:11px;color:var(--slate-light);margin-top:4px">
                        Common: 1st CA=20%, 2nd CA=10%, Exam=70%
                    </div>
                </div>
                <div class="form-group">
                    <label class="checkbox-row">
                        <input type="checkbox" name="is_exam" value="1" {{ old('is_exam') ? 'checked' : '' }}>
                        This is the terminal exam
                    </label>
                </div>
                <button type="submit" class="btn btn-primary">Add Assessment Type</button>
            </form>
        </div>
    </div>
</div>


{{-- ════════════════════════════════════════════
     EDIT MODAL
════════════════════════════════════════════ --}}
<div class="modal-backdrop" id="editModalBackdrop" onclick="closeModal('editModalBackdrop')">
    <div class="modal" onclick="event.stopPropagation()">
        <div class="modal-header">
            <span class="modal-title">Edit Assessment Type</span>
            <button class="modal-close" onclick="closeModal('editModalBackdrop')" title="Close">✕</button>
        </div>
        <form method="POST" id="editForm" action="">
            @csrf
            @method('PUT')
            <div class="modal-body">
                <div class="form-group">
                    <label class="form-label">Term <span>*</span></label>
                    <select name="term_id" id="edit_term_id" class="form-control" required>
                        <option value="">Select term</option>
                        @foreach($terms as $term)
                            <option value="{{ $term->id }}">
                                {{ $term->name }} — {{ $term->session->name ?? '' }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Assessment Name <span>*</span></label>
                    <input type="text" name="name" id="edit_name" class="form-control" required
                           placeholder="e.g. 1st CA, Mid-Term Test, Final Exam">
                </div>
                <div class="form-group">
                    <label class="form-label">Weight (%) <span>*</span></label>
                    <input type="number" name="weight_percentage" id="edit_weight"
                           class="form-control" min="1" max="100" required placeholder="e.g. 20">
                    <div style="font-size:11px;color:var(--slate-light);margin-top:4px">
                        Common: 1st CA=20%, 2nd CA=10%, Exam=70%
                    </div>
                </div>
                <div class="form-group">
                    <label class="checkbox-row">
                        <input type="checkbox" name="is_exam" id="edit_is_exam" value="1">
                        This is the terminal exam
                    </label>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-ghost" onclick="closeModal('editModalBackdrop')">Cancel</button>
                <button type="submit" class="btn btn-primary" style="width:auto">Save Changes</button>
            </div>
        </form>
    </div>
</div>


{{-- ════════════════════════════════════════════
     MIGRATE MODAL
════════════════════════════════════════════ --}}
<div class="modal-backdrop" id="migrateModalBackdrop" onclick="closeModal('migrateModalBackdrop')">
    <div class="modal" onclick="event.stopPropagation()">
        <div class="modal-header">
            <span class="modal-title">Migrate Assessment Type</span>
            <button class="modal-close" onclick="closeModal('migrateModalBackdrop')" title="Close">✕</button>
        </div>
        <form method="POST" id="migrateForm" action="">
            @csrf
            @method('PATCH')
            <div class="modal-body">
                <div class="migrate-info">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                    <span>
                        Moving <strong id="migrate_label"></strong> to a new term copies the assessment type definition.
                        Existing scores linked to the original will not be affected.
                    </span>
                </div>

                <div class="form-group">
                    <label class="form-label">Target Academic Session <span>*</span></label>
                    <select id="migrate_session" class="form-control" onchange="filterTermsBySession(this.value)">
                        <option value="">— All sessions —</option>
                        @foreach($sessions as $session)
                            <option value="{{ $session->id }}">{{ $session->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">Target Term <span>*</span></label>
                    <select name="term_id" id="migrate_term_id" class="form-control" required>
                        <option value="">Select term</option>
                        @foreach($terms as $term)
                            <option value="{{ $term->id }}"
                                data-session="{{ $term->session_id ?? '' }}">
                                {{ $term->name }} — {{ $term->session->name ?? '' }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">Weight in target term (%) <span>*</span></label>
                    <input type="number" name="weight_percentage" id="migrate_weight"
                           class="form-control" min="1" max="100" required placeholder="e.g. 20">
                    <div style="font-size:11px;color:var(--slate-light);margin-top:4px">
                        Adjust if the weighting differs in the new term.
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-ghost" onclick="closeModal('migrateModalBackdrop')">Cancel</button>
                <button type="submit" class="btn btn-primary" style="width:auto;background:#16A34A">
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M5 12h14"/><path d="M12 5l7 7-7 7"/></svg>
                    Migrate
                </button>
            </div>
        </form>
    </div>
</div>

@endsection

@push('scripts')
<script>
    /* ── Helpers ── */
    function closeModal(id) {
        document.getElementById(id).classList.remove('open');
    }
    function openModal(id) {
        document.getElementById(id).classList.add('open');
    }

    /* ── Edit modal ── */
    function openEditModal(id, name, termId, weight, isExam) {
        const form = document.getElementById('editForm');
        form.action = `/scores/assessment-types/${id}`;

        document.getElementById('edit_name').value      = name;
        document.getElementById('edit_weight').value    = weight;
        document.getElementById('edit_is_exam').checked = isExam;

        const termSelect = document.getElementById('edit_term_id');
        termSelect.value = termId;

        openModal('editModalBackdrop');
        document.getElementById('edit_name').focus();
    }

    /* ── Migrate modal ── */
    function openMigrateModal(id, name, currentTermId) {
        const form = document.getElementById('migrateForm');
        form.action = `/scores/assessment-types/${id}/migrate`;

        document.getElementById('migrate_label').textContent = name;

        // Reset session filter & show all terms
        document.getElementById('migrate_session').value = '';
        filterTermsBySession('');

        // Pre-select the current term so it's visible context; user should change it
        const termSelect = document.getElementById('migrate_term_id');
        termSelect.value = currentTermId;

        openModal('migrateModalBackdrop');
    }

    /* ── Filter terms by session in migrate modal ── */
    function filterTermsBySession(sessionId) {
        const select  = document.getElementById('migrate_term_id');
        const options = select.querySelectorAll('option');

        options.forEach(opt => {
            if (!opt.value) return; // keep placeholder
            if (!sessionId || opt.dataset.session === String(sessionId)) {
                opt.hidden   = false;
                opt.disabled = false;
            } else {
                opt.hidden   = true;
                opt.disabled = true;
            }
        });

        // Reset selection if the current value is now hidden
        const current = select.options[select.selectedIndex];
        if (current && current.hidden) select.value = '';
    }

    /* ── Close on Escape ── */
    document.addEventListener('keydown', e => {
        if (e.key === 'Escape') {
            closeModal('editModalBackdrop');
            closeModal('migrateModalBackdrop');
        }
    });
</script>
@endpush
