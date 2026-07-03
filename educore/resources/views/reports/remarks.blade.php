@extends('layouts.app')
@section('title', 'Remarks')
@section('page-title', 'Teacher & Principal Remarks')

@push('styles')
<style>
    .filter-card{display:flex;align-items:flex-end;gap:12px;flex-wrap:wrap;margin-bottom:18px;padding:15px 18px;background:#fff;border:1px solid var(--border);border-radius:12px}.filter-group{display:flex;flex-direction:column;gap:5px}.filter-label{font-size:11px;font-weight:700;color:var(--slate);text-transform:uppercase;letter-spacing:.05em}.filter-control{min-width:220px;padding:9px 12px;border:1px solid var(--border);border-radius:8px;background:#F8FAFC;font:inherit;font-size:13px;outline:none}.filter-control:focus{border-color:var(--indigo)}
    .btn{display:inline-flex;align-items:center;justify-content:center;gap:6px;padding:9px 14px;border:0;border-radius:8px;font:inherit;font-size:12px;font-weight:700;text-decoration:none;cursor:pointer}.btn-primary{background:var(--indigo);color:#fff}.btn-emerald{background:#059669;color:#fff}.btn-sm{padding:6px 11px;font-size:11px}
    .context-bar{display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;margin-bottom:16px;padding:15px 18px;background:#fff;border:1px solid var(--border);border-radius:12px}.context-info h2{font-size:16px;font-weight:750;color:var(--midnight)}.context-info p{margin-top:3px;font-size:12px;color:var(--slate)}
    .alert-success,.alert-info{margin-bottom:16px;padding:12px 15px;border-radius:9px;font-size:13px}.alert-success{background:#ECFDF5;border:1px solid #A7F3D0;color:#047857}.alert-info{background:#EFF6FF;border:1px solid #BFDBFE;color:#1D4ED8}
    .remarks-table{overflow:hidden;background:#fff;border:1px solid var(--border);border-radius:12px;box-shadow:0 4px 16px rgba(15,23,42,.04)}.remarks-table .tbl{overflow-x:auto}.remarks-table table{width:100%;border-collapse:collapse}.remarks-table th{padding:10px 13px;background:#F8FAFC;border-bottom:1px solid var(--border);font-size:10px;font-weight:700;color:var(--slate);text-align:left;text-transform:uppercase;letter-spacing:.05em;white-space:nowrap}.remarks-table td{padding:11px 13px;border-bottom:1px solid var(--border);font-size:12px;color:var(--midnight);vertical-align:top}.remarks-table tbody tr:last-child td{border-bottom:0}.student-name{font-weight:700}.student-avg{margin-top:2px;font-size:11px;color:var(--slate)}
    .remark-form{display:flex;flex-direction:column;gap:6px}.remark-textarea{width:100%;min-height:66px;padding:8px 9px;border:1px solid var(--border);border-radius:8px;background:#F8FAFC;font:inherit;font-size:12px;resize:vertical;outline:none}.remark-textarea:focus{background:#fff;border-color:var(--indigo)}.remark-save{align-self:flex-end;padding:6px 11px;border:0;border-radius:7px;background:var(--indigo);color:#fff;font:inherit;font-size:11px;font-weight:700;cursor:pointer}.auto-badge,.manual-badge{display:inline-block;margin-top:5px;padding:3px 7px;border-radius:999px;font-size:10px;font-weight:700}.auto-badge{background:#FFFBEB;color:#B45309}.manual-badge{background:#ECFDF5;color:#047857}
    .empty-state{padding:54px 20px;text-align:center;color:var(--slate)}.empty-state h3{margin-bottom:7px;font-size:16px;font-weight:700;color:var(--midnight)}.empty-state p{margin-bottom:18px;font-size:13px}
    @media(max-width:640px){.filter-group,.filter-control{width:100%;min-width:0}.context-bar form,.context-bar .btn{width:100%}}
</style>
@endpush

@section('content')

@if(session('success'))<div class="alert-success">{{ session('success') }}</div>@endif

{{-- Filter --}}
<form method="GET" action="{{ route('reports.remarks') }}">
    <div class="filter-card">
        <div class="filter-group">
            <span class="filter-label">Class</span>
            <select name="class_arm_id" class="filter-control" required>
                <option value="">Select class</option>
                @foreach($classArms as $arm)
                    <option value="{{ $arm->id }}" {{ request('class_arm_id') == $arm->id ? 'selected' : '' }}>
                        {{ $arm->classLevel->name }} {{ $arm->name }}
                    </option>
                @endforeach
            </select>
        </div>
        <div class="filter-group">
            <span class="filter-label">Term</span>
            <select name="term_id" class="filter-control" required>
                <option value="">Select term</option>
                @foreach($terms as $term)
                    <option value="{{ $term->id }}" {{ request('term_id') ? (request('term_id') == $term->id ? 'selected' : '') : ($term->is_current ? 'selected' : '') }}>
                        {{ $term->name }} — {{ $term->session->name ?? '' }}
                    </option>
                @endforeach
            </select>
        </div>
        <button type="submit" class="btn btn-primary">Load Students</button>
    </div>
</form>

@if(isset($summaries))

@if($summaries->isEmpty())
<div class="remarks-table">
    <div class="empty-state">
        <h3>No report data found</h3>
        <p>Scores have not been computed for this class and term yet.</p>
    </div>
</div>

@else

<div class="context-bar">
    <div class="context-info">
        <h2>{{ $classArm->classLevel->name }} {{ $classArm->name }} — {{ $term->name }}</h2>
        <p>{{ $summaries->count() }} students · Enter teacher comments and generate principal remarks</p>
    </div>
    <form method="POST" action="{{ route('reports.remarks.bulk') }}"
          onsubmit="return confirm('Auto-generate principal remarks for all {{ $summaries->count() }} students?')">
        @csrf
        <input type="hidden" name="class_arm_id" value="{{ $classArm->id }}">
        <input type="hidden" name="term_id"      value="{{ $term->id }}">
        <button type="submit" class="btn btn-emerald">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/></svg>
            Auto-Generate Principal Remarks
        </button>
    </form>
</div>

<div class="alert-info">
    ℹ️ <strong>How it works:</strong> Enter a teacher's comment for each student manually. Principal remarks can be typed manually or auto-generated based on performance. Save each row individually.
</div>

<div class="remarks-table">
    <div class="tbl"><table>
        <thead>
            <tr>
                <th style="width:30px">#</th>
                <th>Student</th>
                <th>Avg</th>
                <th>Pos</th>
                <th style="min-width:200px">Teacher's Comment</th>
                <th style="min-width:200px">Principal's Remark</th>
            </tr>
        </thead>
        <tbody>
            @foreach($summaries as $i => $summary)
            <tr>
                <td style="color:var(--slate-light);font-size:12px">{{ $i + 1 }}</td>
                <td>
                    <div class="student-name">{{ $summary->student->full_name }}</div>
                    <div class="student-avg">{{ $summary->student->admission_number }}</div>
                </td>
                <td>
                    <strong style="color:{{ $summary->final_average >= 50 ? 'var(--emerald)' : 'var(--crimson)' }}">
                        {{ $summary->final_average }}%
                    </strong>
                </td>
                <td style="font-weight:600;color:var(--indigo)">
                    {{ $summary->position_in_class }}<sup style="font-size:9px">{{ (($summary->position_in_class % 100) >= 11 && ($summary->position_in_class % 100) <= 13) ? 'th' : match($summary->position_in_class % 10) { 1 => 'st', 2 => 'nd', 3 => 'rd', default => 'th' } }}</sup>
                    / {{ $summary->total_students_in_class }}
                </td>

                {{-- Teacher comment --}}
                <td>
                    <form method="POST" action="{{ route('reports.remarks.save', $summary->id) }}" class="remark-form">
                        @csrf
                        <input type="hidden" name="class_arm_id" value="{{ $classArm->id }}">
                        <input type="hidden" name="term_id" value="{{ $term->id }}">
                        <input type="hidden" name="field" value="form_tutor_remark">
                        <textarea name="form_tutor_remark" class="remark-textarea"
                            placeholder="Enter teacher's comment...">{{ $summary->form_tutor_remark }}</textarea>
                        <button type="submit" class="remark-save">Save</button>
                    </form>
                    @if($summary->form_tutor_remark)
                        <span class="manual-badge" style="margin-top:4px;display:inline-block">✓ Saved</span>
                    @endif
                </td>

                {{-- Principal remark --}}
                <td>
                    <form method="POST" action="{{ route('reports.remarks.save', $summary->id) }}" class="remark-form">
                        @csrf
                        <input type="hidden" name="class_arm_id" value="{{ $classArm->id }}">
                        <input type="hidden" name="term_id" value="{{ $term->id }}">
                        <input type="hidden" name="field" value="principal_remark">
                        <textarea name="principal_remark" class="remark-textarea"
                            placeholder="Principal's remark (or auto-generate above)...">{{ $summary->principal_remark }}</textarea>
                        <button type="submit" class="remark-save">Save</button>
                    </form>
                    @if($summary->principal_remark)
                        <span class="auto-badge" style="margin-top:4px;display:inline-block">✓ Set</span>
                    @endif
                </td>
            </tr>
            @endforeach
        </tbody>
    </table></div>
</div>

@endif
@else
<div class="remarks-table">
    <div class="empty-state">
        <h3>Select a class and term</h3>
        <p>Choose a class and term above to load students and manage their remarks.</p>
    </div>
</div>
@endif
@endsection
