@extends('layouts.app')
@section('title', 'Assessment Setup')
@section('page-title', 'Assessment Configuration')

@push('styles')
<style>
    .assessment-grid { display:grid; grid-template-columns:minmax(0,1fr) 390px; gap:20px; align-items:start; }
    .assessment-card { background:#fff; border:1px solid var(--border); border-radius:12px; overflow:hidden; box-shadow:0 1px 3px rgba(15,23,42,.06); margin-bottom:20px; }
    .assessment-card__header { padding:16px 18px; border-bottom:1px solid var(--border); display:flex; gap:12px; align-items:center; justify-content:space-between; }
    .assessment-card__title { font-size:14px; font-weight:700; color:var(--midnight); }
    .assessment-card__body { padding:18px; }
    .assessment-scroll { overflow-x:auto; }
    .assessment-table { width:100%; border-collapse:collapse; min-width:980px; }
    .assessment-table th { padding:10px 14px; text-align:left; background:#F8FAFC; border-bottom:1px solid var(--border); color:var(--slate-light); font-size:11px; text-transform:uppercase; letter-spacing:.04em; }
    .assessment-table td { padding:12px 14px; border-bottom:1px solid var(--border); font-size:13px; vertical-align:middle; }
    .assessment-table tr:last-child td { border-bottom:0; }
    .assessment-actions { display:flex; flex-wrap:wrap; gap:6px; align-items:center; }
    .assessment-btn { display:inline-flex; align-items:center; justify-content:center; min-height:32px; padding:6px 10px; border:0; border-radius:7px; font:600 12px inherit; cursor:pointer; text-decoration:none; }
    .assessment-btn--save { background:#EFF6FF; color:#1D4ED8; }
    .assessment-btn--delete { background:#FEF2F2; color:#B91C1C; }
    .assessment-btn--primary { width:100%; background:var(--indigo); color:#fff; padding:9px 14px; }
    .assessment-btn--apply { background:#ECFDF5; color:#047857; }
    .assessment-form-group { margin-bottom:14px; }
    .assessment-label { display:block; margin-bottom:6px; color:var(--slate); font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:.04em; }
    .assessment-control { box-sizing:border-box; width:100%; padding:9px 11px; border:1px solid var(--border); border-radius:8px; background:#F8FAFC; font:13px inherit; }
    .assessment-control--small { width:90px; }
    .assessment-multi { min-width:190px; min-height:92px; }
    .assessment-help { margin-top:5px; color:var(--slate-light); font-size:11px; line-height:1.4; }
    .assessment-alert { padding:11px 14px; margin-bottom:16px; border-radius:8px; font-size:13px; }
    .assessment-alert--success { color:#047857; background:#ECFDF5; border:1px solid #A7F3D0; }
    .assessment-alert--error { color:#B91C1C; background:#FEF2F2; border:1px solid #FECACA; }
    .assessment-badge { display:inline-flex; padding:3px 8px; border-radius:999px; font-size:11px; font-weight:700; background:#EEF2FF; color:#4338CA; }
    .assessment-badge--exam { background:#FFF7ED; color:#C2410C; }
    .assessment-badge--legacy { background:#F1F5F9; color:#475569; }
    .assessment-empty { padding:34px 18px; text-align:center; color:var(--slate-light); font-size:13px; }
    .scheme-list { display:grid; gap:14px; }
    .scheme-item { border:1px solid var(--border); border-radius:10px; padding:14px; background:#FCFCFD; }
    .scheme-title { display:flex; align-items:flex-start; justify-content:space-between; gap:12px; margin-bottom:10px; }
    .scheme-components { font-size:12px; color:var(--slate); margin-bottom:12px; line-height:1.6; }
    .scheme-apply { display:grid; grid-template-columns:1.2fr 1.6fr auto; gap:10px; align-items:end; }
    @media(max-width:1050px) { .assessment-grid { grid-template-columns:1fr; } }
    @media(max-width:760px) { .scheme-apply { grid-template-columns:1fr; } }
</style>
@endpush

@section('content')
@if(session('success'))
    <div class="assessment-alert assessment-alert--success">{{ session('success') }}</div>
@endif
@if($errors->any())
    <div class="assessment-alert assessment-alert--error">{{ $errors->first() }}</div>
@endif

<section class="assessment-card">
    <div class="assessment-card__header">
        <div>
            <div class="assessment-card__title">Reusable Assessment Schemes</div>
            <div class="assessment-help">Save a completed assessment configuration once, then apply the same frozen structure to any future term and selected class levels. Existing historical results are never linked back to the template.</div>
        </div>
        <span class="assessment-badge">Reusable templates</span>
    </div>
    <div class="assessment-card__body">
        @if($schemeTemplates->isEmpty())
            <div class="assessment-empty" style="padding:18px">No reusable schemes yet. Save one from an existing completed configuration below.</div>
        @else
            <div class="scheme-list">
                @foreach($schemeTemplates as $template)
                    <div class="scheme-item">
                        <div class="scheme-title">
                            <div>
                                <strong>{{ $template->name }}</strong>
                                @if($template->description)<div class="assessment-help">{{ $template->description }}</div>@endif
                            </div>
                            <form method="POST" action="{{ route('scores.assessment-schemes.templates.destroy', ['template' => $template->id]) }}" onsubmit="return confirm('Delete this reusable template? Existing term configurations will not be affected.');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="assessment-btn assessment-btn--delete">Delete template</button>
                            </form>
                        </div>
                        <div class="scheme-components">
                            @foreach($template->items as $item)
                                <span class="assessment-badge {{ $item->is_exam ? 'assessment-badge--exam' : '' }}">{{ $item->name }} {{ (int)$item->weight_percentage }}%</span>
                            @endforeach
                            <span style="margin-left:6px;font-weight:700">Total: {{ (int)$template->items->sum('weight_percentage') }}%</span>
                        </div>
                        <form method="POST" action="{{ route('scores.assessment-schemes.templates.apply', ['template' => $template->id]) }}">
                            @csrf
                            <div class="scheme-apply">
                                <div>
                                    <label class="assessment-label">Apply to term</label>
                                    <select name="term_id" class="assessment-control" required>
                                        <option value="">Select term</option>
                                        @foreach($terms as $term)
                                            <option value="{{ $term->id }}">{{ $term->name }}@if($term->session) — {{ $term->session->name }}@endif</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div>
                                    <label class="assessment-label">Class levels</label>
                                    <select name="class_level_ids[]" multiple class="assessment-control assessment-multi" required>
                                        @foreach($classLevels as $level)
                                            <option value="{{ $level->id }}">{{ $level->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div>
                                    <label style="display:flex;gap:7px;align-items:center;font-size:12px;margin-bottom:8px">
                                        <input type="checkbox" name="replace_existing" value="1"> Replace existing unused configuration
                                    </label>
                                    <button type="submit" class="assessment-btn assessment-btn--apply">Apply scheme</button>
                                </div>
                            </div>
                        </form>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</section>

<div class="assessment-grid">
    <section class="assessment-card">
        <div class="assessment-card__header">
            <div>
                <div class="assessment-card__title">Assessment Types by Class Level</div>
                <div class="assessment-help">Different groups of class levels can use different assessment structures in the same term.</div>
            </div>
            <span class="assessment-badge">100% maximum per class-level group</span>
        </div>

        @if($assessmentTypes->isEmpty())
            <div class="assessment-empty">No assessment types have been created yet.</div>
        @else
            <div class="assessment-scroll">
                <table class="assessment-table">
                    <thead>
                        <tr><th>Name</th><th>Term</th><th>Applies to class levels</th><th>Weight</th><th>Type</th><th>Actions</th></tr>
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
                                        <option value="{{ $term->id }}" {{ (int)$term->id === (int)$at->term_id ? 'selected' : '' }}>{{ $term->name }}@if($term->session) — {{ $term->session->name }}@endif</option>
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
                                    <div class="assessment-help"><span class="assessment-badge assessment-badge--legacy">Legacy/default</span> Used only for class levels that have no explicit configuration.</div>
                                @endif
                            </td>
                            <td><input type="number" min="1" max="100" step="1" name="weight_percentage" value="{{ (int)$at->weight_percentage }}" class="assessment-control assessment-control--small" form="assessment-update-{{ $at->id }}" required></td>
                            <td>
                                <label style="display:flex;gap:6px;align-items:center;white-space:nowrap">
                                    <input type="checkbox" name="is_exam" value="1" form="assessment-update-{{ $at->id }}" {{ $at->is_exam ? 'checked' : '' }}>
                                    <span class="assessment-badge {{ $at->is_exam ? 'assessment-badge--exam' : '' }}">{{ $at->is_exam ? 'Exam' : 'CA' }}</span>
                                </label>
                            </td>
                            <td>
                                <div class="assessment-actions">
                                    <button type="submit" form="assessment-update-{{ $at->id }}" class="assessment-btn assessment-btn--save">Save</button>
                                    <form method="POST" action="{{ route('scores.assessment-types.destroy', ['at' => $at->id]) }}" onsubmit="return confirm('Delete this assessment type? Deletion is blocked automatically if student scores already use it.');">
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

    <aside>
        <section class="assessment-card">
            <div class="assessment-card__header"><div class="assessment-card__title">Add Assessment Type</div></div>
            <div class="assessment-card__body">
                <form method="POST" action="{{ route('scores.assessment-types.store') }}">
                    @csrf
                    <div class="assessment-form-group"><label class="assessment-label">Term</label><select name="term_id" class="assessment-control" required><option value="">Select term</option>@foreach($terms as $term)<option value="{{ $term->id }}" {{ old('term_id') == $term->id ? 'selected' : '' }}>{{ $term->name }}@if($term->session) — {{ $term->session->name }}@endif</option>@endforeach</select></div>
                    <div class="assessment-form-group"><label class="assessment-label">Applicable class levels</label><select name="class_level_ids[]" multiple class="assessment-control assessment-multi" required>@foreach($classLevels as $level)<option value="{{ $level->id }}" {{ in_array($level->id, old('class_level_ids', [])) ? 'selected' : '' }}>{{ $level->name }}</option>@endforeach</select><div class="assessment-help">Select one or several levels that share this component.</div></div>
                    <div class="assessment-form-group"><label class="assessment-label">Assessment name</label><input name="name" class="assessment-control" value="{{ old('name') }}" placeholder="e.g. First CA" required></div>
                    <div class="assessment-form-group"><label class="assessment-label">Weight (%)</label><input type="number" min="1" max="100" step="1" name="weight_percentage" class="assessment-control" value="{{ old('weight_percentage') }}" required></div>
                    <div class="assessment-form-group"><label style="display:flex;gap:8px;align-items:center;font-size:13px"><input type="checkbox" name="is_exam" value="1" {{ old('is_exam') ? 'checked' : '' }}> Terminal examination</label></div>
                    <div class="assessment-form-group"><label class="assessment-label">Objective max (optional)</label><input type="number" min="0.5" step="0.5" name="objective_max" class="assessment-control" value="{{ old('objective_max') }}"></div>
                    <div class="assessment-form-group"><label class="assessment-label">Theory max (optional)</label><input type="number" min="0.5" step="0.5" name="theory_max" class="assessment-control" value="{{ old('theory_max') }}"></div>
                    <button class="assessment-btn assessment-btn--primary" type="submit">Add Assessment Type</button>
                </form>
            </div>
        </section>

        <section class="assessment-card">
            <div class="assessment-card__header"><div class="assessment-card__title">Save Current Setup as Scheme</div></div>
            <div class="assessment-card__body">
                <form method="POST" action="{{ route('scores.assessment-schemes.templates.store') }}">
                    @csrf
                    <div class="assessment-form-group"><label class="assessment-label">Scheme name</label><input name="template_name" class="assessment-control" placeholder="e.g. Senior Secondary Standard" required></div>
                    <div class="assessment-form-group"><label class="assessment-label">Description</label><input name="description" class="assessment-control" placeholder="Optional note"></div>
                    <div class="assessment-form-group"><label class="assessment-label">Source term</label><select name="source_term_id" class="assessment-control" required><option value="">Select term</option>@foreach($terms as $term)<option value="{{ $term->id }}">{{ $term->name }}@if($term->session) — {{ $term->session->name }}@endif</option>@endforeach</select></div>
                    <div class="assessment-form-group"><label class="assessment-label">Source class level</label><select name="source_class_level_id" class="assessment-control" required><option value="">Select class level</option>@foreach($classLevels as $level)<option value="{{ $level->id }}">{{ $level->name }}</option>@endforeach</select><div class="assessment-help">EduCore snapshots the effective 100% configuration for this class level. Later edits to the saved template will never alter past results.</div></div>
                    <button class="assessment-btn assessment-btn--primary" type="submit">Save as Reusable Scheme</button>
                </form>
            </div>
        </section>
    </aside>
</div>
@endsection
