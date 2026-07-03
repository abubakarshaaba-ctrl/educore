@extends('layouts.app')
@section('title', 'Exam Results')
@section('page-title', 'CBT Results')

@push('styles')
<style>
    .breadcrumb { display:flex;align-items:center;gap:8px;font-size:13px;color:var(--slate-light);margin-bottom:20px; }
    .breadcrumb a { color:var(--indigo);text-decoration:none;font-weight:500; }
    .breadcrumb svg { width:14px;height:14px; }
    .stats-grid { display:grid;grid-template-columns:repeat(5,1fr);gap:12px;margin-bottom:20px; }
    .stat { background:white;border:1px solid var(--border);border-radius:10px;padding:14px;text-align:center; }
    .stat-val { font-size:22px;font-weight:700;color:var(--midnight);letter-spacing:-0.02em; }
    .stat-lbl { font-size:10px;font-weight:600;color:var(--slate-light);text-transform:uppercase;letter-spacing:0.05em;margin-top:3px; }
    .card { background:white;border:1px solid var(--border);border-radius:12px;box-shadow:0 1px 3px rgba(0,0,0,0.05);overflow:hidden; }
    table { width:100%;border-collapse:collapse; }
    thead th { font-size:11px;font-weight:600;color:var(--slate-light);text-transform:uppercase;letter-spacing:0.05em;padding:10px 16px;text-align:left;background:#F8FAFC;border-bottom:1px solid var(--border); }
    tbody td { padding:12px 16px;border-bottom:1px solid var(--border);font-size:13px;color:var(--midnight); }
    tbody tr:last-child td { border-bottom:none; }
    tbody tr:hover td { background:#F8FAFC; }
    .badge { display:inline-flex;font-size:11px;font-weight:600;padding:3px 8px;border-radius:20px; }
    .badge-success { background:#ECFDF5;color:var(--emerald); }
    .badge-warning { background:#FFFBEB;color:var(--amber); }
    .badge-error   { background:#FEF2F2;color:var(--crimson); }
    .progress-wrap { width:80px;height:6px;background:#E2E8F0;border-radius:3px;overflow:hidden;display:inline-block;vertical-align:middle;margin-left:6px; }
    .progress-fill { height:100%;border-radius:3px; }
    .empty-state { text-align:center;padding:50px;color:var(--slate-light); }
    .empty-state h3 { font-size:15px;font-weight:600;color:var(--slate);margin-bottom:6px; }
    @media(max-width:1024px) { .stats-grid { grid-template-columns:repeat(2,1fr); } }
</style>
@endpush

@section('content')
<div class="breadcrumb">
    <a href="{{ route('cbt.exams') }}">Exams</a>
    <svg viewBox="0 0 24 24" fill="currentColor"><path d="M10 6L8.59 7.41 13.17 12l-4.58 4.59L10 18l6-6z"/></svg>
    {{ optional($exam)->title ?? 'Results' }}
</div>

@if($exams->count())
<div class="card" style="padding:14px 16px;margin-bottom:16px">
    <label style="display:block;font-size:11px;font-weight:600;color:var(--slate-light);text-transform:uppercase;letter-spacing:.05em;margin-bottom:6px">Select Exam</label>
    <select onchange="if(this.value) location.href=this.value" style="width:100%;max-width:420px;padding:9px 12px;border:1px solid var(--border);border-radius:8px;font-family:inherit">
        @foreach($exams as $item)
            <option value="{{ route('cbt.results', $item) }}" {{ $exam && $item->id === $exam->id ? 'selected' : '' }}>
                {{ $item->title }}
            </option>
        @endforeach
    </select>
</div>
@endif

<div class="stats-grid">
    <div class="stat"><div class="stat-val">{{ $stats['total'] }}</div><div class="stat-lbl">Total Students</div></div>
    <div class="stat"><div class="stat-val" style="color:var(--emerald)">{{ $stats['submitted'] }}</div><div class="stat-lbl">Submitted</div></div>
    <div class="stat"><div class="stat-val" style="color:var(--indigo)">{{ round($stats['avg_score'], 1) }}%</div><div class="stat-lbl">Class Average</div></div>
    <div class="stat"><div class="stat-val" style="color:var(--emerald)">{{ $stats['highest'] }}%</div><div class="stat-lbl">Highest</div></div>
    <div class="stat"><div class="stat-val" style="color:var(--crimson)">{{ $stats['lowest'] }}%</div><div class="stat-lbl">Lowest</div></div>
</div>

<div class="card">
    @if($sessions->count())
    <div class="tbl"><table>
        <thead>
            <tr>
                <th>#</th>
                <th>Student</th>
                <th>Score</th>
                <th>Percentage</th>
                <th>Status</th>
                <th>Submitted At</th>
            </tr>
        </thead>
        <tbody>
            @foreach($sessions as $i => $session)
            @php
                $percentage = $session->display_percentage;
                $totalMarks = $session->total_possible_marks ?: ($exam?->total_marks ?? 0);
            @endphp
            <tr>
                <td style="color:var(--slate-light)">{{ $i + 1 }}</td>
                <td><strong>{{ $session->student->full_name }}</strong></td>
                <td>{{ $session->score ?? '—' }} / {{ $totalMarks }}</td>
                <td>
                    @if($percentage !== null)
                        <strong style="color:{{ $percentage >= 50 ? 'var(--emerald)' : 'var(--crimson)' }}">
                            {{ $percentage }}%
                        </strong>
                        <span class="progress-wrap">
                            <span class="progress-fill" style="width:{{ min($percentage, 100) }}%;background:{{ $percentage >= 50 ? 'var(--emerald)' : 'var(--crimson)' }}"></span>
                        </span>
                    @else —
                    @endif
                </td>
                <td>
                    @if($session->status === 'graded')
                        <span class="badge badge-success">Graded</span>
                    @elseif($session->status === 'submitted')
                        <span class="badge badge-warning">Submitted</span>
                    @elseif($session->status === 'in_progress')
                        <span class="badge badge-warning">In Progress</span>
                    @else
                        <span class="badge badge-error">{{ ucfirst(str_replace('_', ' ', $session->status)) }}</span>
                    @endif
                </td>
                <td style="font-size:12px;color:var(--slate)">{{ optional($session->submitted_at)->format('d M Y, g:ia') ?? '—' }}</td>
            </tr>
            @endforeach
        </tbody>
    </table></div>
    @else
    <div class="empty-state">
        <h3>No submissions yet</h3>
        <p>Students haven't taken this exam yet.</p>
    </div>
    @endif
</div>
@endsection
