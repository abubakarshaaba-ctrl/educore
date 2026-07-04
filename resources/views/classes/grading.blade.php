@extends('layouts.app')
@section('title', 'Grading Scale')
@section('page-title', 'Promotion Engine')

@push('styles')
<style>
    .page-tabs { display:flex;gap:4px;background:white;border:1px solid var(--border);border-radius:10px;padding:4px;margin-bottom:20px;width:fit-content; }
    .page-tab { padding:7px 16px;border-radius:7px;font-size:13px;font-weight:500;color:var(--slate);text-decoration:none;transition:all 150ms; }
    .page-tab.active { background:var(--indigo);color:white; }
    .page-tab:hover:not(.active) { background:#F1F5F9; }
    .two-col { display:grid;grid-template-columns:1fr 360px;gap:20px;align-items:start; }
    .card { background:white;border:1px solid var(--border);border-radius:12px;box-shadow:0 1px 3px rgba(0,0,0,0.05);overflow:hidden;margin-bottom:14px; }
    .card-header { padding:14px 20px;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between; }
    .card-title { font-size:14px;font-weight:600;color:var(--midnight); }
    .card-body { padding:20px; }
    .form-group { margin-bottom:14px; }
    .form-label { display:block;font-size:11px;font-weight:600;color:var(--slate);text-transform:uppercase;letter-spacing:0.05em;margin-bottom:5px; }
    .form-label span { color:var(--crimson); }
    .form-control { width:100%;padding:9px 12px;font-size:13px;font-family:inherit;border:1px solid var(--border);border-radius:8px;background:#F8FAFC;outline:none;transition:border-color 200ms; }
    .form-control:focus { border-color:var(--indigo);box-shadow:0 0 0 3px rgba(37,99,235,0.1);background:white; }
    .form-row { display:grid;grid-template-columns:1fr 1fr;gap:10px; }
    .btn { display:inline-flex;align-items:center;gap:6px;padding:9px 16px;font-size:13px;font-weight:600;font-family:inherit;border-radius:8px;border:none;cursor:pointer;text-decoration:none;transition:background 150ms; }
    .btn-primary { background:var(--indigo);color:white;width:100%;justify-content:center; }
    .btn-primary:hover { background:#1D4ED8; }
    .btn-danger { background:#FEF2F2;color:var(--crimson);border:1px solid #FECACA;font-size:11px;padding:3px 8px; }
    .alert-success { background:#ECFDF5;border:1px solid #A7F3D0;border-radius:8px;padding:12px 16px;font-size:13px;color:var(--emerald);margin-bottom:16px; }
    table { width:100%;border-collapse:collapse; }
    thead th { font-size:11px;font-weight:600;color:var(--slate-light);text-transform:uppercase;letter-spacing:0.05em;padding:8px 12px;text-align:left;background:#F8FAFC;border-bottom:1px solid var(--border); }
    tbody td { padding:8px 12px;border-bottom:1px solid var(--border);font-size:12px;color:var(--midnight); }
    tbody tr:last-child td { border-bottom:none; }
    .pass { color:var(--emerald);font-weight:600; }
    .fail { color:var(--crimson);font-weight:600; }
    .checkbox-row { display:flex;align-items:center;gap:8px;font-size:13px;color:var(--midnight); }
    @media(max-width:1024px) { .two-col { grid-template-columns:1fr; } }
</style>
@endpush

@section('content')
<div class="page-tabs">
    <a href="{{ route('classes.promotion') }}" class="page-tab">Rules</a>
    <a href="{{ route('classes.grading') }}" class="page-tab active">Grading Scale</a>
    <a href="{{ route('classes.promotion.preview') }}" class="page-tab">Run Promotion</a>
    <a href="{{ route('classes.promotion.history') }}" class="page-tab">History</a>
    <a href="{{ route('classes.bulk-promote.page') }}" class="page-tab">Manual Bulk</a>
</div>

@if(session('success'))<div class="alert-success">{{ session('success') }}</div>@endif

<div class="two-col">
    <div>
        @foreach($levels as $level)
        <div class="card">
            <div class="card-header"><span class="card-title">{{ $level->name }}</span></div>
            @if($level->gradingSystems->count())
            <div class="tbl"><table>
                <thead><tr><th>Grade</th><th>Min</th><th>Max</th><th>Remark</th><th>Pass?</th><th>GP</th><th></th></tr></thead>
                <tbody>
                    @foreach($level->gradingSystems->sortByDesc('min_score') as $g)
                    <tr>
                        <td><strong>{{ $g->grade_letter }}</strong></td>
                        <td>{{ $g->min_score }}</td>
                        <td>{{ $g->max_score }}</td>
                        <td>{{ $g->remark }}</td>
                        <td><span class="{{ $g->is_pass_grade ? 'pass' : 'fail' }}">{{ $g->is_pass_grade ? 'Yes' : 'No' }}</span></td>
                        <td>{{ $g->grade_point }}</td>
                        <td>
                            <form method="POST" action="{{ route('classes.grading.destroy', $g) }}" onsubmit="return confirm('Remove this grade?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-danger">✕</button>
                            </form>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table></div>
            @else
            <div style="padding:16px;font-size:13px;color:var(--slate-light)">No grades defined for this level yet.</div>
            @endif
        </div>
        @endforeach
    </div>

    <div class="card">
        <div class="card-header"><span class="card-title">Add Grade Entry</span></div>
        <div class="card-body">
            <form method="POST" action="{{ route('classes.grading.store') }}">
                @csrf
                <div class="form-group">
                    <label class="form-label">Class Level <span>*</span></label>
                    <select name="class_level_id" class="form-control">
                        <option value="">Select level</option>
                        @foreach($levels as $level)
                            <option value="{{ $level->id }}" {{ old('class_level_id') == $level->id ? 'selected' : '' }}>{{ $level->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Grade Letter <span>*</span></label>
                        <input type="text" name="grade_letter" class="form-control" value="{{ old('grade_letter') }}" placeholder="A1">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Grade Point <span>*</span></label>
                        <input type="number" name="grade_point" class="form-control" value="{{ old('grade_point') }}" min="1" max="9">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Min Score <span>*</span></label>
                        <input type="number" name="min_score" class="form-control" value="{{ old('min_score') }}" min="0" max="100">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Max Score <span>*</span></label>
                        <input type="number" name="max_score" class="form-control" value="{{ old('max_score') }}" min="0" max="100">
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Remark <span>*</span></label>
                    <input type="text" name="remark" class="form-control" value="{{ old('remark') }}" placeholder="Excellent, Good, Fail...">
                </div>
                <div class="form-group">
                    <label class="checkbox-row">
                        <input type="checkbox" name="is_pass_grade" value="1" {{ old('is_pass_grade', '1') ? 'checked' : '' }}>
                        This is a passing grade
                    </label>
                </div>
                <button type="submit" class="btn btn-primary">Add Grade</button>
            </form>
        </div>
    </div>
</div>
@endsection
