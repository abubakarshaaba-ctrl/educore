@extends('layouts.app')
@section('title', 'Dashboard')
@section('page-title', 'Dashboard')
@push('styles')
<style>
/* ── Layout ──────────────────────────────────────────────────────── */
.grid-2{display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:16px}
.grid-3{display:grid;grid-template-columns:repeat(3,1fr);gap:16px;margin-bottom:16px}
.grid-4{display:grid;grid-template-columns:repeat(4,1fr);gap:14px;margin-bottom:16px}
@media(max-width:900px){.grid-3{grid-template-columns:1fr 1fr}.grid-4{grid-template-columns:1fr 1fr}}
@media(max-width:640px){.grid-2,.grid-3,.grid-4{grid-template-columns:1fr}}

/* ── Welcome banner ─────────────────────────────────────────────── */
.welcome-banner{background:linear-gradient(135deg,var(--midnight) 0%,#1a3a6b 100%);border-radius:14px;padding:24px 28px;color:white;margin-bottom:20px;display:flex;align-items:center;justify-content:space-between;gap:16px;flex-wrap:wrap}
.wb-greeting{font-size:22px;font-weight:800;margin-bottom:4px}
.wb-sub{font-size:13px;opacity:.75}
.wb-date{font-size:12px;opacity:.65;font-weight:600;text-align:right}

/* ── Stat cards ─────────────────────────────────────────────────── */
.stat-card{background:white;border:1px solid var(--border);border-radius:12px;padding:18px 20px}
.stat-label{font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:var(--slate-light);margin-bottom:6px}
.stat-value{font-size:28px;font-weight:800;color:var(--midnight);line-height:1}
.stat-sub{font-size:11px;color:var(--slate-light);margin-top:4px}
.stat-card.accent-b{border-top:3px solid #2563EB}
.stat-card.accent-g{border-top:3px solid #059669}
.stat-card.accent-a{border-top:3px solid var(--indigo)}
.stat-card.accent-r{border-top:3px solid #DC2626}

/* ── Form class card ────────────────────────────────────────────── */
.class-card{background:white;border:1px solid var(--border);border-radius:12px;overflow:hidden;margin-bottom:16px}
.class-card-head{background:linear-gradient(135deg,var(--midnight),#1e3a6a);color:white;padding:14px 18px;font-size:15px;font-weight:800}
.class-card-head .role-pill{font-size:10px;font-weight:700;background:rgba(255,255,255,.15);border-radius:20px;padding:3px 10px;margin-left:8px;vertical-align:middle}
.class-card-body{padding:16px 18px}
.gender-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:10px;text-align:center;margin-top:10px}
.gender-item .gv{font-size:26px;font-weight:800;color:var(--midnight)}
.gender-item .gl{font-size:10px;font-weight:700;text-transform:uppercase;color:var(--slate-light);margin-top:2px}
.gender-item.male .gv{color:#2563EB}
.gender-item.female .gv{color:#DB2777}
.att-pills{display:flex;gap:8px;flex-wrap:wrap;margin-top:12px}
.att-pill{padding:5px 12px;border-radius:20px;font-size:12px;font-weight:700}
.att-p{background:#ECFDF5;color:#059669}
.att-a{background:#FEF2F2;color:#DC2626}
.att-l{background:#FFFBEB;color:#D97706}
.att-u{background:#F1F5F9;color:#64748B}
.att-note{font-size:11px;color:var(--slate-light);margin-top:6px}

/* ── Subjects table ─────────────────────────────────────────────── */
.card{background:white;border:1px solid var(--border);border-radius:12px;margin-bottom:16px;overflow:hidden}
.card-head{padding:13px 18px;border-bottom:1px solid var(--border);font-size:13px;font-weight:700;color:var(--midnight);display:flex;align-items:center;justify-content:space-between}
table{width:100%;border-collapse:collapse}
thead th{font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:var(--slate-light);padding:9px 14px;background:#F8FAFC;border-bottom:1px solid var(--border);text-align:left}
tbody td{padding:10px 14px;border-bottom:1px solid var(--border);font-size:13px}
tbody tr:last-child td{border-bottom:none}
.subj-pill{display:inline-flex;align-items:center;padding:3px 10px;border-radius:20px;font-size:11px;font-weight:700;background:#EFF6FF;color:#1D4ED8}
.class-link{color:var(--indigo);text-decoration:none;font-weight:600}
.class-link:hover{text-decoration:underline}
.num-badge{display:inline-flex;align-items:center;justify-content:center;width:32px;height:32px;background:#F1F5F9;border-radius:50%;font-size:13px;font-weight:700;color:var(--midnight)}

/* ── CBT exams ──────────────────────────────────────────────────── */
.exam-item{display:flex;align-items:center;justify-content:space-between;padding:10px 0;border-bottom:1px solid var(--border)}
.exam-item:last-child{border-bottom:none}
.exam-title{font-size:13px;font-weight:600;color:var(--midnight)}
.exam-meta{font-size:11px;color:var(--slate-light);margin-top:2px}
.badge-live{background:#ECFDF5;color:#059669;border-radius:20px;padding:2px 9px;font-size:10px;font-weight:700}

/* ── Quick links ────────────────────────────────────────────────── */
.quick-links{display:grid;grid-template-columns:repeat(3,1fr);gap:10px;margin-bottom:16px}
@media(max-width:640px){.quick-links{grid-template-columns:repeat(2,1fr)}}
.ql{display:flex;flex-direction:column;align-items:center;justify-content:center;padding:14px 10px;background:white;border:1px solid var(--border);border-radius:10px;text-decoration:none;transition:all 150ms;gap:6px}
.ql:hover{border-color:var(--indigo);background:#F5F7FF}
.ql-icon{font-size:22px}
.ql-label{font-size:11px;font-weight:600;color:var(--midnight);text-align:center}

/* ── Empty / unassigned state ───────────────────────────────────── */
.empty-state{background:#F8FAFC;border:1px dashed var(--border);border-radius:10px;padding:20px;text-align:center;color:var(--slate-light);font-size:13px}
.no-form-note{background:#FFF7ED;border:1px solid #FDE68A;border-radius:10px;padding:14px 16px;font-size:12px;color:#92400E;margin-bottom:16px}
</style>
@endpush
@section('content')

{{-- ── Welcome banner ─────────────────────────────────────────────── --}}
<div class="welcome-banner">
    <div>
        <div class="wb-greeting">Good {{ hour() < 12 ? 'Morning' : (hour() < 17 ? 'Afternoon' : 'Evening') }}, {{ explode(' ', $user->name)[0] }} 👋</div>
        <div class="wb-sub">{{ $user->roleLabel() }} &nbsp;·&nbsp; {{ optional($currentTerm)->name ?? 'No term active' }}</div>
    </div>
    <div class="wb-date">{{ \Carbon\Carbon::parse($today)->format('l, d M Y') }}</div>
</div>
@php
function hour() { return (int)\Carbon\Carbon::now()->format('H'); }
@endphp

{{-- ── Form Class card (if assigned) ─────────────────────────────── --}}
@if($formClass)
<div class="class-card">
    <div class="class-card-head">
        🏫 My Form Class: {{ $formClass->classLevel->name }} {{ $formClass->name }}
        <span class="role-pill">Form Teacher</span>
    </div>
    <div class="class-card-body">
        <div class="gender-grid">
            <div class="gender-item">
                <div class="gv">{{ optional($formClassStudents)->total ?? 0 }}</div>
                <div class="gl">Total</div>
            </div>
            <div class="gender-item male">
                <div class="gv">{{ optional($formClassStudents)->males ?? 0 }}</div>
                <div class="gl">Male</div>
            </div>
            <div class="gender-item female">
                <div class="gv">{{ optional($formClassStudents)->females ?? 0 }}</div>
                <div class="gl">Female</div>
            </div>
        </div>

        @if($myClassAttendanceToday)
        <div class="att-pills">
            <span class="att-pill att-p">✓ Present: {{ $myClassAttendanceToday['present'] }}</span>
            <span class="att-pill att-a">✗ Absent: {{ $myClassAttendanceToday['absent'] }}</span>
            @if($myClassAttendanceToday['late'] > 0)
            <span class="att-pill att-l">⏰ Late: {{ $myClassAttendanceToday['late'] }}</span>
            @endif
            @if(($myClassAttendanceToday['present'] + $myClassAttendanceToday['absent'] + $myClassAttendanceToday['late']) === 0)
            <span class="att-pill att-u">No attendance marked today</span>
            @endif
        </div>
        <div class="att-note">Today's attendance for {{ $formClass->classLevel->name }} {{ $formClass->name }}</div>
        @endif

        <div style="margin-top:14px;display:flex;gap:8px;flex-wrap:wrap">
            @can('attendance')
            <a href="{{ route('attendance.index') }}" class="btn" style="font-size:12px;padding:7px 14px;background:var(--indigo);color:white;text-decoration:none;border-radius:8px">📋 Mark Attendance</a>
            @endcan
            @if($user->canAccessModule('skills'))
            <a href="{{ route('skills.index') }}" class="btn" style="font-size:12px;padding:7px 14px;background:white;border:1px solid var(--border);color:var(--midnight);text-decoration:none;border-radius:8px">⭐ Skill Ratings</a>
            @endif
            @if($user->canAccessModule('scores.view'))
            <a href="{{ route('scores.broadsheet') }}" class="btn" style="font-size:12px;padding:7px 14px;background:white;border:1px solid var(--border);color:var(--midnight);text-decoration:none;border-radius:8px">📊 Broadsheet</a>
            @endif
        </div>
    </div>
</div>
@else
<div class="no-form-note">
    ℹ️ You are not currently assigned as form teacher to any class. Ask your administrator to set a Form Tutor under Classes if this is expected.
</div>
@endif

{{-- ── Quick links ──────────────────────────────────────────────── --}}
<div class="quick-links" style="margin-bottom:16px">
    @if($user->canAccessModule('scores.entry'))
    <a href="{{ route('scores.index') }}" class="ql"><div class="ql-icon">✏️</div><div class="ql-label">Score Entry</div></a>
    @endif
    @if($user->canAccessModule('attendance'))
    <a href="{{ route('attendance.index') }}" class="ql"><div class="ql-icon">📋</div><div class="ql-label">Attendance</div></a>
    @endif
    @if($user->canAccessModule('skills'))
    <a href="{{ route('skills.index') }}" class="ql"><div class="ql-icon">⭐</div><div class="ql-label">Skill Ratings</div></a>
    @endif
    @if($user->canAccessModule('timetable.view') || $user->canAccessModule('timetable'))
    <a href="{{ route('timetable.index') }}" class="ql"><div class="ql-icon">📅</div><div class="ql-label">Timetable</div></a>
    @endif
    @if($user->canAccessModule('cbt'))
    <a href="{{ route('cbt.banks') }}" class="ql"><div class="ql-icon">💡</div><div class="ql-label">CBT Exams</div></a>
    @endif
    <a href="{{ route('staff-attendance.my') }}" class="ql"><div class="ql-icon">🕐</div><div class="ql-label">My Attendance</div></a>
</div>

{{-- ── Subjects I teach ─────────────────────────────────────────── --}}
<div class="card">
    <div class="card-head">
        📚 My Subjects
        <span style="font-size:11px;font-weight:400;color:var(--slate-light)">{{ $assignedSubjects->count() }} assignment{{ $assignedSubjects->count() === 1 ? '':' s' }}</span>
    </div>
    @if($assignedSubjects->isEmpty())
    <div style="padding:20px">
        <div class="empty-state">No subjects have been assigned to you yet. Ask your administrator to assign you a subject under Classes → Assign Subject.</div>
    </div>
    @else
    <div class="tbl"><table>
        <thead>
            <tr>
                <th>Subject</th>
                <th>Class</th>
                <th style="text-align:center">Students</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            @foreach($assignedSubjects as $row)
            <tr>
                <td><span class="subj-pill">{{ $row['subject'] ?? '—' }}</span></td>
                <td style="font-weight:600">{{ $row['class'] }}</td>
                <td style="text-align:center">
                    <span class="num-badge">{{ $row['students'] }}</span>
                </td>
                <td>
                    @if($user->canAccessModule('scores.entry'))
                    @php $termId = optional($currentTerm)->id; @endphp
                    <a href="{{ route('scores.entry', ['class_arm_id' => $row['class_arm_id'], 'subject_id' => $row['subject_id'], 'term_id' => $termId]) }}"
                       style="display:inline-flex;align-items:center;gap:5px;padding:6px 14px;background:var(--indigo);color:white;border-radius:7px;font-size:11px;font-weight:700;text-decoration:none">
                        ✏️ Enter Scores
                    </a>
                    @endif
                </td>
            </tr>
            @endforeach
        </tbody>
    </table></div>
    @endif
</div>

{{-- ── Live CBT exams ────────────────────────────────────────────── --}}
@if($pendingExams->isNotEmpty())
<div class="card">
    <div class="card-head">
        🔴 Active CBT Exams
        <span class="badge-live">Live</span>
    </div>
    <div style="padding:0 16px">
        @foreach($pendingExams as $exam)
        <div class="exam-item">
            <div>
                <div class="exam-title">{{ $exam->title }}</div>
                <div class="exam-meta">
                    {{ optional($exam->classArm->classLevel)->name }} {{ optional($exam->classArm)->name }}
                    @if($exam->scheduled_end) · Closes {{ \Carbon\Carbon::parse($exam->scheduled_end)->diffForHumans() }}@endif
                </div>
            </div>
            <span class="badge-live">Published</span>
        </div>
        @endforeach
    </div>
</div>
@endif

{{-- ── Announcements ────────────────────────────────────────────── --}}
@if(!empty($announcements) && count($announcements))
<div class="card">
    <div class="card-head">📢 Announcements</div>
    <div style="padding:0 16px">
        @foreach($announcements as $a)
        <div style="padding:10px 0;border-bottom:1px solid var(--border)">
            <div style="font-size:13px;font-weight:600;color:var(--midnight)">{{ $a->title }}</div>
            <div style="font-size:11px;color:var(--slate-light);margin-top:3px">{{ \Carbon\Carbon::parse($a->created_at)->diffForHumans() }}</div>
        </div>
        @endforeach
    </div>
</div>
@endif

@endsection
