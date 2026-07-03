@extends('layouts.app')
@section('title', 'Report Cards')
@section('page-title', 'Report Cards')

@push('styles')
<style>
    .page-header{display:flex;align-items:center;justify-content:space-between;gap:14px;margin-bottom:20px;flex-wrap:wrap}.page-header h2{font-size:19px;font-weight:750;color:var(--midnight);letter-spacing:-.02em}.page-header p{margin-top:3px;font-size:13px;color:var(--slate)}
    .btn{display:inline-flex;align-items:center;justify-content:center;gap:6px;padding:9px 15px;border:0;border-radius:8px;font:inherit;font-size:12px;font-weight:700;text-decoration:none;cursor:pointer}.btn-primary{background:var(--indigo);color:#fff}.btn-emerald{background:#059669;color:#fff}.btn-ghost{background:#fff;color:var(--midnight);border:1px solid var(--border)}.btn-sm{padding:6px 11px;font-size:11px}
    .alert-success{margin-bottom:16px;padding:12px 15px;border:1px solid #A7F3D0;border-radius:9px;background:#ECFDF5;color:#047857;font-size:13px}
    .student-card{margin-bottom:18px;overflow:hidden;background:#fff;border:1px solid var(--border);border-radius:14px;box-shadow:0 5px 18px rgba(15,23,42,.045)}
    .card-top{display:flex;align-items:center;justify-content:space-between;gap:10px;padding:15px 18px;background:#F8FAFC;border-bottom:1px solid var(--border);flex-wrap:wrap}.sname{font-size:15px;font-weight:750;color:var(--midnight)}.sadm{margin-top:2px;font-size:12px;color:var(--slate)}
    .status-pill{padding:5px 11px;border-radius:999px;font-size:11px;font-weight:700}.s-distinction{background:#ECFDF5;color:#047857}.s-merit{background:#EFF6FF;color:#1D4ED8}.s-credit{background:#FFFBEB;color:#B45309}.s-below{background:#FEF2F2;color:#B91C1C}
    .stats-row{display:grid;grid-template-columns:repeat(5,1fr);border-bottom:1px solid var(--border)}.stat-cell{padding:12px 8px;text-align:center;border-right:1px solid var(--border)}.stat-cell:last-child{border-right:0}.stat-val{font-size:19px;font-weight:750;color:var(--midnight)}.stat-lbl{margin-top:3px;font-size:10px;font-weight:700;color:var(--slate-light);text-transform:uppercase;letter-spacing:.05em}
    .subject-wrap{overflow-x:auto}.subject-table{width:100%;border-collapse:collapse}.subject-table th{padding:9px 11px;background:#F8FAFC;border-right:1px solid var(--border);border-bottom:1px solid var(--border);font-size:10px;font-weight:700;color:var(--slate);text-transform:uppercase;white-space:nowrap;text-align:center}.subject-table th.left{text-align:left}.subject-table td{padding:9px 11px;border-right:1px solid var(--border);border-bottom:1px solid var(--border);font-size:12px;text-align:center;color:var(--midnight)}.subject-table tbody tr:last-child td{border-bottom:0}.subject-table td.subj{min-width:150px;text-align:left;font-weight:650}.subject-table td.tot{background:#F8FAFC;font-weight:750}.subject-table td.pass{color:#047857;font-weight:750}.subject-table td.fail{color:#B91C1C;font-weight:750}
    .remarks-row{display:grid;grid-template-columns:1fr 1fr;gap:14px;padding:15px 18px;border-top:1px solid var(--border)}.remark-block label{display:block;margin-bottom:6px;font-size:10px;font-weight:700;color:var(--slate);text-transform:uppercase;letter-spacing:.05em}.remark-block textarea{width:100%;height:62px;padding:9px 10px;border:1px solid var(--border);border-radius:8px;background:#F8FAFC;font:inherit;font-size:12px;resize:vertical;outline:none}.remark-block textarea:focus{background:#fff;border-color:var(--indigo)}.remark-save{margin-top:6px;padding:6px 12px;border:0;border-radius:7px;background:var(--indigo);color:#fff;font:inherit;font-size:11px;font-weight:700;cursor:pointer}
    .card-foot{display:flex;align-items:center;justify-content:space-between;gap:10px;padding:11px 18px;background:#F8FAFC;border-top:1px solid var(--border);flex-wrap:wrap}.card-foot-info{font-size:12px;color:var(--slate)}
    @media(max-width:800px){.stats-row{grid-template-columns:repeat(2,1fr)}.remarks-row{grid-template-columns:1fr}}
</style>
@endpush

@section('content')
@if(session('success'))<div class="alert-success">{{ session('success') }}</div>@endif

<div class="page-header">
    <div>
        <h2>{{ $classArm->classLevel->name }} {{ $classArm->name }} — {{ $term->name }}</h2>
        <p>{{ $summaries->count() }} students · {{ $session->name }} · Form Tutor: {{ optional($classArm->formTutor)->name ?? '—' }}</p>
    </div>
    <div style="display:flex;gap:8px;flex-wrap:wrap">
        <form method="POST" action="{{ route('reports.pdf-class') }}" style="margin:0">
            @csrf
            <input type="hidden" name="class_arm_id" value="{{ $classArm->id }}">
            <input type="hidden" name="term_id"      value="{{ $term->id }}">
            <button type="submit" class="btn btn-emerald">↓ Download All PDFs</button>
        </form>
        <a href="{{ route('reports.index') }}" class="btn btn-ghost">← Back</a>
    </div>
</div>

@php $maxTotal = $assessmentTypes->sum('weight_percentage'); @endphp

@foreach($summaries as $summary)
@php
    $avg = $summary->final_average;
    $statusClass = $avg >= 75 ? 's-distinction' : ($avg >= 60 ? 's-merit' : ($avg >= 50 ? 's-credit' : 's-below'));
    $statusText  = $avg >= 75 ? 'Distinction' : ($avg >= 60 ? 'Merit' : ($avg >= 50 ? 'Credit' : 'Below Average'));
    $pos = $summary->position_in_class;
    $mod100 = $pos % 100;
    $sfx = ($mod100 >= 11 && $mod100 <= 13) ? 'th' : match($pos % 10) { 1 => 'st', 2 => 'nd', 3 => 'rd', default => 'th' };
@endphp

<div class="student-card">
    <div class="card-top">
        <div>
            <div class="sname">{{ $summary->student->full_name }}</div>
            <div class="sadm">{{ $summary->student->admission_number }} · {{ $classArm->classLevel->name }} {{ $classArm->name }}</div>
        </div>
        <span class="status-pill {{ $statusClass }}">{{ $statusText }}</span>
    </div>

    <div class="stats-row">
        <div class="stat-cell">
            <div class="stat-val" style="color:var(--indigo)">{{ $avg }}</div>
            <div class="stat-lbl">Average</div>
        </div>
        <div class="stat-cell">
            <div class="stat-val">{{ $pos }}<sup style="font-size:10px">{{ $sfx }}</sup></div>
            <div class="stat-lbl">Position</div>
        </div>
        <div class="stat-cell">
            <div class="stat-val">{{ $summary->total_students_in_class }}</div>
            <div class="stat-lbl">In Class</div>
        </div>
        <div class="stat-cell">
            <div class="stat-val">{{ $summary->subjects_offered }}</div>
            <div class="stat-lbl">Subjects</div>
        </div>
        <div class="stat-cell">
            <div class="stat-val" style="color:{{ ($summary->subjects_failed ?? 0) > 0 ? 'var(--crimson)' : 'var(--emerald)' }}">
                {{ $summary->subjects_failed ?? 0 }}
            </div>
            <div class="stat-lbl">Failed</div>
        </div>
    </div>

    <div class="subject-wrap">
        <div class="tbl"><table class="subject-table">
            <thead>
                <tr>
                    <th class="left">Subject</th>
                    @foreach($assessmentTypes as $at)
                        <th>{{ $at->name }}<br><span style="font-weight:400">/{{ $at->weight_percentage }}</span></th>
                    @endforeach
                    <th>Total<br>/{{ $maxTotal }}</th>
                    <th>Grade</th>
                    <th>Remark</th>
                </tr>
            </thead>
            <tbody>
                @foreach($subjectScores[$summary->student_id] ?? [] as $subjectId => $data)
                <tr>
                    <td class="subj">{{ $data['subject_name'] }}</td>
                    @foreach($assessmentTypes as $at)
                        <td>{{ $data['scores'][$at->id] ?? '—' }}</td>
                    @endforeach
                    <td class="tot">{{ $data['total'] }}</td>
                    <td class="{{ $data['is_pass'] ? 'pass' : 'fail' }}">{{ $data['grade'] }}</td>
                    <td style="font-size:11.5px;color:var(--slate)">{{ $data['remark'] }}</td>
                </tr>
                @endforeach
            </tbody>
        </table></div>
    </div>

    <div class="remarks-row">
        <div class="remark-block">
            <label>Form Tutor's Remark</label>
            <form method="POST" action="{{ route('reports.remarks.save', $summary) }}">
                @csrf
                <input type="hidden" name="field" value="form_tutor_remark">
                <input type="hidden" name="class_arm_id" value="{{ $classArm->id }}">
                <input type="hidden" name="term_id" value="{{ $term->id }}">
                <textarea name="form_tutor_remark" placeholder="Enter teacher's comment...">{{ $summary->form_tutor_remark }}</textarea>
                <button type="submit" class="remark-save">Save</button>
            </form>
        </div>
        <div class="remark-block">
            <label>Principal's Remark</label>
            <form method="POST" action="{{ route('reports.remarks.save', $summary) }}">
                @csrf
                <input type="hidden" name="field" value="principal_remark">
                <input type="hidden" name="class_arm_id" value="{{ $classArm->id }}">
                <input type="hidden" name="term_id" value="{{ $term->id }}">
                <textarea name="principal_remark" placeholder="Principal's remark...">{{ $summary->principal_remark }}</textarea>
                <button type="submit" class="remark-save">Save</button>
            </form>
        </div>
    </div>

    <div class="card-foot">
        <div class="card-foot-info">
            Class Avg: <strong>{{ $summary->class_highest_avg ?? '—' }}</strong> highest ·
            <strong>{{ $summary->class_lowest_avg ?? '—' }}</strong> lowest
        </div>
        <form method="POST" action="{{ route('reports.pdf', $summary->student) }}" style="margin:0">
            @csrf
            <input type="hidden" name="term_id" value="{{ $term->id }}">
            <button type="submit" class="btn btn-primary btn-sm">↓ PDF</button>
        </form>
    </div>
</div>
@endforeach
@endsection
