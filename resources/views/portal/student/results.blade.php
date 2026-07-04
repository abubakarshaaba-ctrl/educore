@extends('layouts.portal')
@section('title','My Results')
@section('content')
<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:18px;flex-wrap:wrap;gap:10px">
    <h2 style="font-size:17px;font-weight:800">📊 My Report Cards</h2>
    <select onchange="location.href='?term_id='+this.value" style="padding:8px 14px;font-size:13px;font-family:inherit;border:1.5px solid var(--border);border-radius:8px;background:#F8FAFC;outline:none">
        @foreach($terms as $t)
        <option value="{{ $t->id }}" {{ $t->id==$termId ? 'selected':'' }}>{{ $t->name }} — {{ optional($t->session)->name }}</option>
        @endforeach
    </select>
</div>

@if($summary)
{{-- KPIs --}}
@php $avg = $summary->final_average ?? 0; @endphp
<div class="kpi-row">
    <div class="kpi"><div class="kv" style="color:{{ $avg>=70?'#059669':($avg>=50?'#D97706':'#DC2626') }}">{{ number_format($avg,1) }}%</div><div class="kl">Average</div></div>
    <div class="kpi"><div class="kv" style="color:#2563EB">{{ $summary->position_in_class }}<span style="font-size:12px">/{{ $summary->total_students_in_class }}</span></div><div class="kl">Position</div></div>
    <div class="kpi"><div class="kv">{{ $summary->subjects_offered ?? '—' }}</div><div class="kl">Subjects</div></div>
    <div class="kpi"><div class="kv" style="color:#DC2626">{{ $summary->subjects_failed ?? 0 }}</div><div class="kl">Failed</div></div>
</div>

{{-- Subject breakdown --}}
@if($summary->subject_breakdown)
<div class="card">
    <div class="ch">Subject Breakdown</div>
    <div style="overflow-x:auto">
    <table>
        <thead><tr><th>#</th><th>Subject</th><th>Score</th><th>Grade</th><th>Position</th><th>Class High</th><th>Class Low</th><th>Remark</th></tr></thead>
        <tbody>
        @foreach($summary->subject_breakdown as $i => $sub)
        @php $tot = $sub['total'] ?? $sub['score'] ?? 0; @endphp
        <tr>
            <td style="color:var(--muted)">{{ $i+1 }}</td>
            <td style="font-weight:600">{{ $sub['subject'] ?? $sub['subject_name'] ?? '—' }}</td>
            <td style="font-weight:700;color:{{ $tot>=70?'#059669':($tot>=50?'#D97706':'#DC2626') }}">{{ $tot }}</td>
            <td><span class="badge {{ ($sub['is_pass']??true) ? 'b-g':'b-r' }}">{{ $sub['grade'] ?? '—' }}</span></td>
            <td>{{ $sub['position'] ?? '—' }}</td>
            <td style="color:#059669">{{ $sub['class_highest'] ?? $sub['highest'] ?? '—' }}</td>
            <td style="color:#DC2626">{{ $sub['class_lowest'] ?? $sub['lowest'] ?? '—' }}</td>
            <td style="font-size:12px;color:var(--muted)">{{ $sub['remark'] ?? '—' }}</td>
        </tr>
        @endforeach
        </tbody>
    </table>
    </div>
</div>
@endif

{{-- Remarks --}}
@if($summary->form_tutor_remark || $summary->principal_remark)
<div class="card">
    <div class="ch">Remarks</div>
    <div class="cb">
        @if($summary->form_tutor_remark)
        <div style="margin-bottom:12px">
            <div style="font-size:11px;font-weight:700;color:var(--muted);text-transform:uppercase;margin-bottom:4px">Form Tutor's Remark</div>
            <div style="font-size:13px;font-style:italic">{{ $summary->form_tutor_remark }}</div>
        </div>
        @endif
        @if($summary->principal_remark)
        <div>
            <div style="font-size:11px;font-weight:700;color:var(--muted);text-transform:uppercase;margin-bottom:4px">Principal's Remark</div>
            <div style="font-size:13px;font-style:italic">{{ $summary->principal_remark }}</div>
        </div>
        @endif
    </div>
</div>
@endif

{{-- PDF download --}}
<div style="margin-top:8px">
    <a href="{{ route('student.portal.report-card.pdf', ['term_id' => $termId]) }}"
       class="btn btn-primary">🖨 Download Report Card PDF</a>
</div>

@else
<div class="card">
    <div class="empty">
        <div class="empty-icon">📋</div>
        <div style="font-weight:600;font-size:14px">No report card available</div>
        <div style="font-size:12px;margin-top:4px">Report cards are published by your school after term-end computation.</div>
    </div>
</div>
@endif
@endsection
