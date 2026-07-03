@extends('layouts.portal')
@section('title','Report Cards')
@section('content')
@if($students->count() > 1)
<div class="child-tabs">
    @foreach($students as $s)
    <a href="?student_id={{ $s->id }}&term_id={{ $termId }}" class="child-tab {{ optional($student)->id==$s->id ? 'active':'' }}">👦 {{ $s->first_name }}</a>
    @endforeach
</div>
@endif
<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:18px;flex-wrap:wrap;gap:10px">
    <h2 style="font-size:17px;font-weight:800">📊 Report Cards — {{ optional($student)->full_name }}</h2>
    <select onchange="location.href='?student_id={{ optional($student)->id }}&term_id='+this.value"
            style="padding:8px 14px;font-size:13px;font-family:inherit;border:1.5px solid var(--border);border-radius:8px;background:#F8FAFC;outline:none">
        @foreach($terms as $t)
        <option value="{{ $t->id }}" {{ $t->id==$termId ? 'selected':'' }}>{{ $t->name }} — {{ optional($t->session)->name }}</option>
        @endforeach
    </select>
</div>
@if($summary)
<div class="kpi-row">
    @php $avg = $summary->final_average ?? 0; @endphp
    <div class="kpi"><div class="kv" style="color:{{ $avg>=70?'#059669':($avg>=50?'#D97706':'#DC2626') }}">{{ number_format($avg,1) }}%</div><div class="kl">Average</div></div>
    <div class="kpi"><div class="kv" style="color:#2563EB">{{ $summary->position_in_class }}/{{ $summary->total_students_in_class }}</div><div class="kl">Position</div></div>
    <div class="kpi"><div class="kv">{{ $summary->subjects_offered ?? '—' }}</div><div class="kl">Subjects</div></div>
    <div class="kpi"><div class="kv" style="color:{{ ($summary->subjects_failed??0)>0?'#DC2626':'#059669' }}">{{ $summary->subjects_failed ?? 0 }}</div><div class="kl">Failed</div></div>
</div>
@if($summary->subject_breakdown)
@php $isThirdTerm = isset($summary->subject_breakdown[0]['annual_total']); @endphp
<div class="card">
    <div class="ch">Subject Breakdown</div>
    <div style="overflow-x:auto"><table>
        <thead><tr>
            <th>#</th><th>Subject</th>
            @foreach($assessmentTypes as $at)<th style="font-size:11px">{{ $at->name }}</th>@endforeach
            <th>Total</th>
            @if($isThirdTerm)<th>T1</th><th>T2</th><th>T3</th><th>Annual</th><th>Cum. Avg</th>@endif
            <th>Grade</th><th>Position</th><th>Class High</th><th>Class Low</th><th>Remark</th>
        </tr></thead>
        <tbody>
        @foreach($summary->subject_breakdown as $i => $sub)
        @php
            $tot = $sub['total'] ?? 0;
            $tv = array_values($sub['term_totals'] ?? []);
            $sid = $sub['subject_id'] ?? null;
            $subScores = $sid ? ($rawScoresBySubject[$sid] ?? []) : [];
        @endphp
        <tr>
            <td style="color:var(--muted)">{{ $i+1 }}</td>
            <td style="font-weight:600">{{ $sub['subject'] ?? '—' }}</td>
            @foreach($assessmentTypes as $at)
                <td style="font-size:12px">{{ isset($subScores[$at->id]) ? number_format($subScores[$at->id],1) : '—' }}</td>
            @endforeach
            <td style="font-weight:700;color:{{ $tot>=70?'#059669':($tot>=50?'#D97706':'#DC2626') }}">{{ $tot }}</td>
            @if($isThirdTerm)
                <td>{{ $tv[0] ?? '—' }}</td>
                <td>{{ $tv[1] ?? '—' }}</td>
                <td>{{ $tv[2] ?? '—' }}</td>
                <td style="font-weight:700">{{ $sub['annual_total'] ?? '—' }}</td>
                <td style="font-weight:700">{{ $sub['cumulative_avg'] ?? '—' }}</td>
            @endif
            <td><span class="badge {{ ($sub['is_pass']??true)?'b-g':'b-r' }}">{{ $sub['grade'] ?? '—' }}</span></td>
            <td>{{ $sub['position'] ?? '—' }}</td>
            <td style="color:#059669;font-weight:600">{{ $sub['class_highest'] ?? '—' }}</td>
            <td style="color:#DC2626;font-weight:600">{{ $sub['class_lowest'] ?? '—' }}</td>
            <td style="font-size:12px;color:var(--muted)">{{ $sub['remark'] ?? '—' }}</td>
        </tr>
        @endforeach
        </tbody>
    </table></div>
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

<a href="{{ route('parent.results.pdf', ['student_id' => optional($student)->id, 'term_id' => $termId]) }}" target="_blank" class="btn btn-primary" style="margin-top:4px">🖨 Download Report Card PDF</a>
@else
<div class="card">
    <div class="empty">
        <div class="empty-icon">📋</div>
        <div style="font-weight:600;margin-bottom:6px">No report card available for this term</div>
        @if(!$termId)
        <div style="font-size:12px;color:var(--muted)">No active term has been set by the school. Please check back later.</div>
        @elseif(!$student)
        <div style="font-size:12px;color:var(--muted)">No student linked to your account. Contact the school administrator.</div>
        @else
        <div style="font-size:12px;color:var(--muted)">The school has not published report cards for this term yet. Please check back after results are released.</div>
        @endif
    </div>
</div>
@endif
@endsection
