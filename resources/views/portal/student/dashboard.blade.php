@extends('layouts.portal')
@section('title','My Dashboard')

@section('content')
{{-- Welcome strip --}}
<div style="background:linear-gradient(135deg,#1E3A5F,#2563EB);border-radius:14px;padding:22px 24px;color:white;margin-bottom:20px;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px">
    <div>
        <div style="font-size:11px;opacity:.7;text-transform:uppercase;letter-spacing:.08em;margin-bottom:4px">Welcome back</div>
        <div style="font-size:20px;font-weight:800">{{ $student->first_name }} {{ $student->last_name }}</div>
        <div style="font-size:13px;opacity:.8;margin-top:3px">
            {{ optional(optional($student->currentClassArm)->classLevel)->name }}
            {{ optional($student->currentClassArm)->name }}
            @if($student->currentClassArm?->academicTrack)
            · <strong>{{ $student->currentClassArm->academicTrack->name }}</strong>
            @endif
        </div>
    </div>
    <div style="text-align:right">
        <div style="font-size:11px;opacity:.7">Admission No.</div>
        <div style="font-size:15px;font-weight:700;letter-spacing:.06em">{{ $student->admission_number }}</div>
        @if($currentTerm)
        <div style="font-size:11px;opacity:.7;margin-top:4px">{{ $currentTerm->name }}</div>
        @endif
    </div>
</div>

{{-- KPIs --}}
<div class="kpi-row">
    <div class="kpi">
        @php $avg = $summary?->final_average ?? 0; @endphp
        <div class="kv" style="color:{{ $avg>=70?'#059669':($avg>=50?'#D97706':'#DC2626') }}">
            {{ $summary ? number_format($avg,1).'%' : '—' }}
        </div>
        <div class="kl">{{ optional($currentTerm)->name ?? 'Current' }} Average</div>
    </div>
    <div class="kpi">
        <div class="kv" style="color:#2563EB">
            {{ $summary ? $summary->position_in_class.'/'.$summary->total_students_in_class : '—' }}
        </div>
        <div class="kl">Class Position</div>
    </div>
    <div class="kpi">
        @php
            $attPct = ($attendance && $attendance->total > 0)
                ? round(($attendance->present / $attendance->total) * 100) : 0;
        @endphp
        <div class="kv" style="color:{{ $attPct>=80?'#059669':($attPct>=60?'#D97706':'#DC2626') }}">
            {{ $attendance ? $attPct.'%' : '—' }}
        </div>
        <div class="kl">Attendance Rate</div>
        @if($attendance)
        <div class="att-bar">
            <div class="att-fill" style="width:{{ $attPct }}%;background:{{ $attPct>=80?'#059669':($attPct>=60?'#D97706':'#DC2626') }}"></div>
        </div>
        @endif
    </div>
    <div class="kpi">
        <div class="kv" style="color:#8B5CF6">{{ $upcomingExams->count() }}</div>
        <div class="kl">Upcoming CBT Exams</div>
    </div>
</div>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:16px">
    {{-- Upcoming exams --}}
    <div class="card">
        <div class="ch">📝 Upcoming CBT Exams
            <a href="{{ route('student.portal.exams') }}" style="font-size:12px;color:var(--brand);font-weight:600;text-decoration:none">View all →</a>
        </div>
        @forelse($upcomingExams as $exam)
        <div style="display:flex;align-items:center;gap:12px;padding:11px 16px;border-bottom:1px solid var(--border)">
            <div style="width:40px;height:40px;background:#EFF6FF;border-radius:8px;display:flex;align-items:center;justify-content:center;font-size:16px;flex-shrink:0">📝</div>
            <div style="flex:1">
                <div style="font-weight:600;font-size:13px">{{ $exam->title }}</div>
                <div style="font-size:11px;color:var(--muted)">
                    @if($exam->scheduled_start)
                        {{ $exam->scheduled_start->format('d M Y, g:ia') }}
                        @if($exam->scheduled_end) - {{ $exam->scheduled_end->format('g:ia') }} @endif
                    @else
                        TBD
                    @endif
                    · {{ $exam->duration_minutes }} mins
                </div>
            </div>
            <a href="{{ route('cbt.exams.start', $exam) }}" class="btn btn-primary" style="padding:5px 12px;font-size:12px">Start</a>
        </div>
        @empty
        <div class="empty"><div class="empty-icon">📅</div><div>No upcoming exams.</div></div>
        @endforelse
    </div>

    {{-- Recent CBT results --}}
    <div class="card">
        <div class="ch">🏆 Recent Exam Results</div>
        @forelse($recentResults as $sess)
        <div style="display:flex;align-items:center;gap:12px;padding:11px 16px;border-bottom:1px solid var(--border)">
            <div style="flex:1">
                <div style="font-weight:600;font-size:13px">{{ optional($sess->exam)->title }}</div>
                <div style="font-size:11px;color:var(--muted)">{{ $sess->updated_at->format('d M Y') }}</div>
            </div>
            @php
                $pct = $sess->display_percentage ?? 0;
                $totalMarks = $sess->total_possible_marks;
            @endphp
            <div style="text-align:right">
                <div style="font-weight:700;color:{{ $pct>=70?'#059669':($pct>=50?'#D97706':'#DC2626') }}">{{ $pct }}%</div>
                <div style="font-size:11px;color:var(--muted)">{{ $sess->score ?? 0 }}/{{ $totalMarks }}</div>
            </div>
        </div>
        @empty
        <div class="empty"><div class="empty-icon">📊</div><div>No results yet.</div></div>
        @endforelse
    </div>

    {{-- Announcements --}}
    <div class="card" style="grid-column:span 2">
        <div class="ch">📢 School Announcements</div>
        @forelse($announcements as $ann)
        <div style="padding:12px 16px;border-bottom:1px solid var(--border)">
            <div style="font-weight:600;font-size:13px">{{ $ann->title }}</div>
            <div style="font-size:12px;color:var(--muted);margin-top:2px">
                {{ $ann->publish_date ? \Carbon\Carbon::parse($ann->publish_date)->format('d M Y') : '' }}
                @if($ann->content) · {{ Str::limit(strip_tags($ann->content), 120) }} @endif
            </div>
        </div>
        @empty
        <div class="empty"><div class="empty-icon">📢</div><div>No announcements.</div></div>
        @endforelse
    </div>
</div>
@endsection
