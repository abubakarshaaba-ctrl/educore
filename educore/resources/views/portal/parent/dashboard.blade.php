@extends('layouts.portal')
@section('title','Parent Dashboard')
@section('content')
{{-- Child selector --}}
@if($students->count() > 1)
<div class="child-tabs">
    @foreach($students as $s)
    <a href="?student_id={{ $s->id }}" class="child-tab {{ optional($student)->id==$s->id ? 'active':'' }}">
        👦 {{ $s->first_name }}
    </a>
    @endforeach
</div>
@endif

@if($student)
{{-- Header --}}
<div style="background:linear-gradient(135deg,#064E3B,#059669);border-radius:14px;padding:22px 24px;color:white;margin-bottom:20px;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px">
    <div>
        <div style="font-size:11px;opacity:.7;text-transform:uppercase;letter-spacing:.08em;margin-bottom:4px">Your Child</div>
        <div style="font-size:20px;font-weight:800">{{ $student->full_name }}</div>
        <div style="font-size:13px;opacity:.8;margin-top:3px">
            {{ optional(optional($student->currentClassArm)->classLevel)->name }}
            {{ optional($student->currentClassArm)->name }}
            · Adm: {{ $student->admission_number }}
        </div>
    </div>
    @if($currentTerm)
    <div style="text-align:right">
        <div style="font-size:11px;opacity:.7">Current Term</div>
        <div style="font-size:14px;font-weight:700">{{ $currentTerm->name }}</div>
        <div style="font-size:11px;opacity:.7">{{ optional($currentTerm->session)->name }}</div>
    </div>
    @endif
</div>

{{-- KPIs --}}
<div class="kpi-row">
    <div class="kpi">
        @php $avg = $summary?->final_average ?? 0; @endphp
        <div class="kv" style="color:{{ $avg>=70?'#059669':($avg>=50?'#D97706':'#DC2626') }}">
            {{ $summary ? number_format($avg,1).'%' : '—' }}
        </div>
        <div class="kl">{{ optional($currentTerm)->name ?? 'Term' }} Average</div>
    </div>
    <div class="kpi">
        <div class="kv" style="color:#2563EB">{{ $summary ? $summary->position_in_class.'/'.$summary->total_students_in_class : '—' }}</div>
        <div class="kl">Class Position</div>
    </div>
    <div class="kpi">
        @php
            $attPct = ($attendance && $attendance->total > 0)
                ? round(($attendance->present / $attendance->total)*100) : 0;
        @endphp
        <div class="kv" style="color:{{ $attPct>=80?'#059669':($attPct>=60?'#D97706':'#DC2626') }}">
            {{ $attendance ? $attPct.'%' : '—' }}
        </div>
        <div class="kl">Attendance</div>
        @if($attendance)
        <div class="att-bar"><div class="att-fill" style="width:{{ $attPct }}%;background:{{ $attPct>=80?'#059669':($attPct>=60?'#D97706':'#DC2626') }}"></div></div>
        @endif
    </div>
    <div class="kpi">
        <div class="kv" style="color:{{ $outstanding>0?'#DC2626':'#059669' }}">
            ₦{{ number_format($outstanding) }}
        </div>
        <div class="kl">Outstanding Fees</div>
    </div>
</div>

{{-- Quick actions --}}
<div style="display:grid;grid-template-columns:repeat(4,1fr);gap:10px;margin-bottom:20px">
    @foreach([
        ['parent.results','📊','View Results','#EFF6FF','#2563EB'],
        ['parent.fees','💳','Pay Fees','#F0FDF4','#059669'],
        ['parent.attendance','📅','Attendance','#FFFBEB','#D97706'],
        ['parent.notifications','📢','Notices','#FEF2F2','#DC2626'],
    ] as [$route,$icon,$label,$bg,$color])
    <a href="{{ route($route, ['student_id'=>$student->id]) }}"
       style="display:flex;flex-direction:column;align-items:center;gap:6px;padding:16px;background:{{ $bg }};border-radius:12px;text-decoration:none;color:{{ $color }};font-size:13px;font-weight:700;text-align:center;transition:transform 150ms">
        <span style="font-size:24px">{{ $icon }}</span>{{ $label }}
    </a>
    @endforeach
</div>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:16px">
    {{-- Announcements --}}
    <div class="card">
        <div class="ch">📢 School Announcements
            <a href="{{ route('parent.notifications') }}" style="font-size:12px;color:var(--brand);font-weight:600;text-decoration:none">All →</a>
        </div>
        @forelse($announcements as $ann)
        <div style="padding:10px 16px;border-bottom:1px solid var(--border)">
            <div style="font-weight:600;font-size:13px">{{ $ann->title }}</div>
            <div style="font-size:11px;color:var(--muted);margin-top:2px">
                {{ $ann->publish_date ? \Carbon\Carbon::parse($ann->publish_date)->format('d M Y') : '' }}
            </div>
        </div>
        @empty
        <div class="empty" style="padding:30px"><div class="empty-icon">📢</div><div>No announcements.</div></div>
        @endforelse
    </div>

    {{-- Calendar --}}
    <div class="card">
        <div class="ch">📆 Upcoming Events
            <a href="{{ route('parent.calendar') }}" style="font-size:12px;color:var(--brand);font-weight:600;text-decoration:none">Full Calendar →</a>
        </div>
        @forelse($calendar as $ev)
        <div style="display:flex;gap:12px;padding:10px 16px;border-bottom:1px solid var(--border);align-items:center">
            <div style="text-align:center;min-width:40px">
                <div style="font-size:16px;font-weight:800;color:#2563EB">{{ \Carbon\Carbon::parse($ev->date)->format('d') }}</div>
                <div style="font-size:9px;color:var(--muted);text-transform:uppercase">{{ \Carbon\Carbon::parse($ev->date)->format('M') }}</div>
            </div>
            <div>
                <div style="font-weight:600;font-size:13px">{{ $ev->title }}</div>
                <div style="font-size:11px;color:var(--muted)">{{ Str::limit($ev->description ?? '', 60) }}</div>
            </div>
        </div>
        @empty
        <div class="empty" style="padding:30px"><div class="empty-icon">📆</div><div>No upcoming events.</div></div>
        @endforelse
    </div>
</div>
@else
<div class="card"><div class="empty"><div class="empty-icon">👦</div><div>No children linked to your account.</div></div></div>
@endif
@endsection
