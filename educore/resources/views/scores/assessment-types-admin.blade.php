@extends('layouts.app')
@section('title', 'Assessment Setup')
@section('page-title', 'Assessment Configuration')

@push('styles')
<style>
    .assessment-layout { display:grid; grid-template-columns:minmax(0,1fr) 340px; gap:18px; align-items:start; }
    .assessment-layout>* { min-width:0; }
    .assessment-stack { display:grid; gap:18px; min-width:0; }
    .assessment-panel { background:#fff; border:1px solid #D8E0EA; border-radius:10px; overflow:hidden; box-shadow:0 1px 2px rgba(15,23,42,.04); min-width:0; }
    .assessment-panel__header { display:flex; align-items:center; justify-content:space-between; gap:12px; padding:13px 16px; background:#0F172A; color:#fff; }
    .assessment-panel__title { margin:0; font-size:13px; font-weight:800; letter-spacing:.02em; }
    .assessment-panel__count { display:inline-flex; align-items:center; justify-content:center; min-width:24px; height:24px; padding:0 7px; border-radius:999px; background:rgba(255,255,255,.14); font-size:11px; font-weight:800; }
    .assessment-panel__body { padding:15px; min-width:0; }
    .assessment-scroll { width:100%; max-width:100%; overflow-x:auto; -webkit-overflow-scrolling:touch; }
    .assessment-table { width:100%; border-collapse:separate; border-spacing:0; min-width:980px; }
    .assessment-table th { padding:10px 12px; background:#E2E8F0; color:#0F172A; border-top:1px solid #CBD5E1; border-bottom:1px solid #CBD5E1; font-size:11px; font-weight:800; text-align:left; text-transform:uppercase; letter-spacing:.045em; white-space:nowrap; }
    .assessment-table th:first-child { border-left:1px solid #CBD5E1; border-radius:6px 0 0 0; }
    .assessment-table th:last-child { border-right:1px solid #CBD5E1; border-radius:0 6px 0 0; }
    .assessment-table td { padding:10px 12px; border-bottom:1px solid #E2E8F0; background:#fff; font-size:12px; vertical-align:middle; }
    .assessment-table tbody tr:hover td { background:#F8FAFC; }
    .assessment-table td:first-child { border-left:1px solid #E2E8F0; }
    .assessment-table td:last-child { border-right:1px solid #E2E8F0; }
    .assessment-table--schemes { min-width:1080px; }
    .assessment-control { box-sizing:border-box; width:100%; min-width:0; min-height:36px; padding:7px 9px; border:1px solid #CBD5E1; border-radius:6px; background:#fff; color:#0F172A; font:12px inherit; }
    .assessment-control:focus { outline:0; border-color:#64748B; box-shadow:0 0 0 2px rgba(100,116,139,.12); }
    .assessment-control--weight { width:76px; text-align:center; }
    .assessment-multi { min-width:170px; min-height:68px; }
    .assessment-form-group { margin-bottom:12px; min-width:0; }
    .assessment-label { display:block; margin-bottom:5px; color:#475569; font-size:10px; font-weight:800; text-transform:uppercase; letter-spacing:.05em; }
    .assessment-check { display:flex; align-items:center; gap:7px; color:#334155; font-size:12px; }
    .assessment-actions { display:flex; gap:6px; flex-wrap:wrap; align-items:center; }
    .assessment-btn { display:inline-flex; align-items:center; justify-content:center; min-height:31px; padding:6px 10px; border:0; border-radius:6px; font:700 11px inherit; cursor:pointer; text-decoration:none; white-space:nowrap; }
    .assessment-btn--save { background:#DBEAFE; color:#1D4ED8; }
    .assessment-btn--apply { background:#DCFCE7; color:#166534; }
    .assessment-btn--delete { background:#FEE2E2; color:#B91C1C; }
    .assessment-btn--primary { width:100%; min-height:38px; background:#1E3A8A; color:#fff; }
    .assessment-badge { display:inline-flex; align-items:center; padding:3px 7px; border-radius:999px; background:#E0E7FF; color:#3730A3; font-size:10px; font-weight:800; white-space:nowrap; }
    .assessment-badge--exam { background:#FFEDD5; color:#C2410C; }
    .assessment-badge--default { background:#E2E8F0; color:#475569; }
    .assessment-components { display:flex; gap:5px; flex-wrap:wrap; align-items:center; }
    .assessment-total { font-weight:800; color:#0F172A; white-space:nowrap; }
    .assessment-muted { color:#64748B; font-size:11px; overflow-wrap:anywhere; }
    .assessment-empty { padding:24px 16px; text-align:center; color:#64748B; font-size:12px; }
    .assessment-alert { padding:10px 13px; margin-bottom:14px; border-radius:7px; font-size:12px; overflow-wrap:anywhere; }
    .assessment-alert--success { color:#047857; background:#ECFDF5; border:1px solid #A7F3D0; }
    .assessment-alert--error { color:#B91C1C; background:#FEF2F2; border:1px solid #FECACA; }
    @media(max-width:1100px) { .assessment-layout { grid-template-columns:1fr; } }
    @media(max-width:640px) {
        .assessment-stack { gap:12px; }
        .assessment-panel__header { padding:10px 12px; align-items:flex-start; }
        .assessment-panel__body { padding:12px; }
        .assessment-table { min-width:820px; }
        .assessment-table--schemes { min-width:940px; }
        .assessment-table th,.assessment-table td { padding:8px 9px; font-size:11px; }
        .assessment-actions { display:grid; grid-template-columns:1fr 1fr; width:100%; }
        .assessment-actions>*,.assessment-actions form,.assessment-actions .assessment-btn { min-width:0; width:100%; }
        .assessment-multi { min-width:150px; }
        .assessment-control--weight { width:68px; }
    }
    @media(max-width:380px) { .assessment-actions { grid-template-columns:1fr; } }
</style>
@endpush

@section('content')
@if(session('success'))
    <div class="assessment-alert assessment-alert--success">{{ session('success') }}</div>
@endif
@if($errors->any())
    <div class="assessment-alert assessment-alert--error">{{ $errors->first() }}</div>
@endif

<div class="assessment-stack" style="margin-bottom:18px">
    <section class="assessment-panel">
        <div class="assessment-panel__header">
            <h2 class="assessment-panel__title">Reusable Schemes</h2>
            <span class="assessment-panel__count">{{ $schemeTemplates->count() }}</span>
        </div>

        @if($schemeTemplates->isEmpty())
            <div class="assessment-empty">No saved schemes.</div>
        @else
            <div class="assessment-scroll">
                <table class="assessment-table assessment-table--schemes">
                    <thead>
                        <tr>
                            <th>Scheme</th>
                            <th>Components</th>
                            <th>Target Term</th>
                            <th>Class Levels</th>
                            <th>Replace</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                    @foreach($schemeTemplates as $template)
                        <tr>
                            <td>
                                <strong>{{ $template->name }}</strong>
                                @if($template->description)
                                    <div class="assessment-muted">{{ $template->description }}</div>
                                @endif
                            </td>
                            <td>
                                <div class="assessment-components">
                                    @foreach($template->items as $item)
                                        <span class="assessment-badge {{ $item->is_exam ? 'assessment-badge--exam' : '' }}">
                                            {{ $item->name }} {{ (int)$item->weight_percentage }}%
                                        </span>
                                    @endforeach
                                    <span class="assessment-total">{{ (int)$template->items->sum('weight_percentage') }}%</span>
                                </div>
                            </td>
                            <td>
                                <select name="term_id" class="assessment-control" form="scheme-apply-{{ $template->id }}" required>
                                    <option value="">Select term</option>
                                    @foreach($terms as $term)
                                        <option value="{{ $term->id }}">{{ $term->name }}@if($term->session) — {{ $term->session->name }}@endif</option>
                                    @endforeach
                                </select>
                            </td>
                            <td>
                                <select name="class_level_ids[]" multiple class="assessment-control assessment-multi" form="scheme-apply-{{ $template->id }}" required>
                                    @foreach($classLevels as $level)
                                        <option value="{{ $level->id }}">{{ $level->name }}</option>
                                    @endforeach
                                </select>
                            </td>
                            <td>
                                <label class="assessment-check">
                                    <input type="checkbox" name="replace_existing" value="1" form="scheme-apply-{{ $template->id }}">
                                    Yes
                                </label>
                            </td>
                            <td>
                                <div class="assessment-actions">
                                    <form id="scheme-apply-{{ $template->id }}" method="POST" action="{{ route('scores.assessment-schemes.templates.apply', ['template' => $template->id]) }}">
                                        @csrf
                                    </form>
                                    <button type="submit" form="scheme-apply-{{ $template->id }}" class="assessment-btn assessment-btn--apply">Apply</button>
                                    <form method="POST" action="{{ route('scores.assessment-schemes.templates.destroy', ['template' => $template->id]) }}" onsubmit="return confirm('Delete this scheme?');">
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
</div>

<div class="assessment-layout">
    <section class="assessment-panel">
        <div class="assessment-panel__header">
            <h2 class="assessment-panel__title">Assessment Types</h2>
            <span class="assessment-panel__count">{{ $assessmentTypes->count() }}</span>
        </div>

        @if($assessmentTypes->isEmpty())
            <div class="assessment-empty">No assessment types.</div>
        @else
            <div class="assessment-scroll">
                <table class="assessment-table">
                    <thead>
                        <tr>
                            <th>Assessment</th>
                            <th>Term</th>
                            <th>Class Levels</th>
                            <th>Weight</th>
                            <th>Type</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                    @foreach($assessmentTypes as $at)
                        <tr>
                            <td>
                                <form method="POST" action="{{ route('scores.assessment-types.update', ['at' => $at->id]) }}" id="assessment-update-{{ $at->id }}">
                                    @csrf
                                    @method('PUT')
                                    <input type="text" name="name" value="{{ $at->name }}" class="assessment-control" required>
                                </form>
                            </td>
                            <td>
                                <select name="term_id" class="assessment-control" form="assessment-update-{{ $at->id }}" required>
                                    @foreach($terms as $term)
                                        <option value="{{ $term->id }}" {{ (int)$term->id === (int)$at->term_id ? 'selected' : '' }}>
                                            {{ $term->name }}@if($term->session) — {{ $term->session->name }}@endif
                                        </option>
                                    @endforeach
                                </select>
                            </td>
                            <td>
                                <select name="class_level_ids[]" multiple class="assessment-control assessment-multi" form="assessment-update-{{ $at->id }}">
                                    @foreach($classLevels as $level)
                                        <option value="{{ $level->id }}" {{ $at->classLevels->contains('id', $level->id) ? 'selected' : '' }}>{{ $level->name }}</option>
                                    @endforeach
                                </select>
                                @if($at->classLevels->isEmpty())
                                    <div style="margin-top:5px"><span class="assessment-badge assessment-badge--default">Default</span></div>
                                @endif
                            </td>
                            <td>
                                <input type="number" min="1" max="100" step="1" name="weight_percentage" value="{{ (int)$at->weight_percentage }}" class="assessment-control assessment-control--weight" form="assessment-update-{{ $at->id }}" required>
                            </td>
                            <td>
                                <label class="assessment-check">
                                    <input type="checkbox" name="is_exam" value="1" form="assessment-update-{{ $at->id }}" {{ $at->is_exam ? 'checked' : '' }}>
                                    <span class="assessment-badge {{ $at->is_exam ? 'assessment-badge--exam' : '' }}">{{ $at->is_exam ? 'Exam' : 'CA' }}</span>
                                </label>
                            </td>
                            <td>
                                <div class="assessment-actions">
                                    <button type="submit" form="assessment-update-{{ $at->id }}" class="assessment-btn assessment-btn--save">Save</button>
                                    <form method="POST" action="{{ route('scores.assessment-types.destroy', ['at' => $at->id]) }}" onsubmit="return confirm('Delete this assessment type?');">
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

    <aside class="assessment-stack">
        <section class="assessment-panel">
            <div class="assessment-panel__header">
                <h2 class="assessment-panel__title">New Assessment Type</h2>
            </div>
            <div class="assessment-panel__body">
                <form method="POST" action="{{ route('scores.assessment-types.store') }}">
                    @csrf
                    <div class="assessment-form-group">
                        <label class="assessment-label">Term</label>
                        <select name="term_id" class="assessment-control" required>
                            <option value="">Select term</option>
                            @foreach($terms as $term)
                                <option value="{{ $term->id }}" {{ old('term_id') == $term->id ? 'selected' : '' }}>{{ $term->name }}@if($term->session) — {{ $term->session->name }}@endif</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="assessment-form-group">
                        <label class="assessment-label">Class Levels</label>
                        <select name="class_level_ids[]" multiple class="assessment-control assessment-multi" required>
                            @foreach($classLevels as $level)
                                <option value="{{ $level->id }}" {{ in_array($level->id, old('class_level_ids', [])) ? 'selected' : '' }}>{{ $level->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="assessment-form-group">
                        <label class="assessment-label">Name</label>
                        <input name="name" class="assessment-control" value="{{ old('name') }}" placeholder="e.g. First CA" required>
                    </div>
                    <div class="assessment-form-group">
                        <label class="assessment-label">Weight (%)</label>
                        <input type="number" min="1" max="100" step="1" name="weight_percentage" class="assessment-control" value="{{ old('weight_percentage') }}" required>
                    </div>
                    <div class="assessment-form-group">
                        <label class="assessment-check"><input type="checkbox" name="is_exam" value="1" {{ old('is_exam') ? 'checked' : '' }}> Exam</label>
                    </div>
                    <div class="assessment-form-group">
                        <label class="assessment-label">Objective Max</label>
                        <input type="number" min="0.5" step="0.5" name="objective_max" class="assessment-control" value="{{ old('objective_max') }}">
                    </div>
                    <div class="assessment-form-group">
                        <label class="assessment-label">Theory Max</label>
                        <input type="number" min="0.5" step="0.5" name="theory_max" class="assessment-control" value="{{ old('theory_max') }}">
                    </div>
                    <button class="assessment-btn assessment-btn--primary" type="submit">Add Type</button>
                </form>
            </div>
        </section>

        <section class="assessment-panel">
            <div class="assessment-panel__header">
                <h2 class="assessment-panel__title">Save Scheme</h2>
            </div>
            <div class="assessment-panel__body">
                <form method="POST" action="{{ route('scores.assessment-schemes.templates.store') }}">
                    @csrf
                    <div class="assessment-form-group">
                        <label class="assessment-label">Scheme Name</label>
                        <input name="template_name" class="assessment-control" placeholder="e.g. Senior Secondary" required>
                    </div>
                    <div class="assessment-form-group">
                        <label class="assessment-label">Note</label>
                        <input name="description" class="assessment-control" placeholder="Optional">
                    </div>
                    <div class="assessment-form-group">
                        <label class="assessment-label">Source Term</label>
                        <select name="source_term_id" class="assessment-control" required>
                            <option value="">Select term</option>
                            @foreach($terms as $term)
                                <option value="{{ $term->id }}">{{ $term->name }}@if($term->session) — {{ $term->session->name }}@endif</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="assessment-form-group">
                        <label class="assessment-label">Source Class Level</label>
                        <select name="source_class_level_id" class="assessment-control" required>
                            <option value="">Select class level</option>
                            @foreach($classLevels as $level)
                                <option value="{{ $level->id }}">{{ $level->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <button class="assessment-btn assessment-btn--primary" type="submit">Save Scheme</button>
                </form>
            </div>
        </section>
    </aside>
</div>
@endsection
