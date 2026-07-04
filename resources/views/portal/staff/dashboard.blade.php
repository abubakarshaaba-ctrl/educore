@extends('layouts.portal')
@section('title','My Dashboard')
@section('content')

{{-- Welcome banner --}}
<div style="background:linear-gradient(135deg,#071E45,#1E40AF);border-radius:14px;padding:22px 24px;color:white;margin-bottom:20px;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px">
    <div>
        <div style="font-size:11px;opacity:.7;text-transform:uppercase;letter-spacing:.08em;margin-bottom:4px">Welcome back</div>
        <div style="font-size:20px;font-weight:800">{{ $user->name }}</div>
        <div style="font-size:13px;opacity:.8;margin-top:3px">
            {{ ucwords(str_replace('_', ' ', $user->role ?? '')) }}
            @if($currentTerm) · {{ $currentTerm->name }} @endif
        </div>
    </div>
    <div style="text-align:right">
        <div style="font-size:11px;opacity:.7">Staff ID</div>
        <div style="font-size:15px;font-weight:700;letter-spacing:.06em;font-family:monospace">{{ $user->staff_id ?? '—' }}</div>
        <div style="font-size:11px;opacity:.7;margin-top:4px">{{ optional($tenant)->name }}</div>
    </div>
</div>

{{-- KPIs --}}
<div class="kpi-row" style="margin-bottom:20px">
    <div class="kpi">
        <div class="kv" style="color:#059669">₦{{ number_format(optional($latestPayslip)->net_pay ?? 0) }}</div>
        <div class="kl">Last Net Pay</div>
    </div>
    <div class="kpi">
        <div class="kv" style="color:#2563EB">{{ $recentPayroll->count() }}</div>
        <div class="kl">Payslips Available</div>
    </div>
    <div class="kpi">
        <div class="kv" style="color:#D97706">{{ $timetable->flatten()->count() }}</div>
        <div class="kl">Weekly Periods</div>
    </div>
    <div class="kpi">
        <div class="kv" style="color:var(--midnight)">{{ $announcements->count() }}</div>
        <div class="kl">Notices</div>
    </div>
</div>

{{-- Quick actions --}}
<div style="display:grid;grid-template-columns:repeat(3,1fr);gap:10px;margin-bottom:20px">
    <a href="{{ route('staff.portal.payroll') }}"
       style="display:flex;flex-direction:column;align-items:center;gap:6px;padding:18px 12px;background:#EFF6FF;border-radius:12px;text-decoration:none;color:#2563EB;font-size:13px;font-weight:700;text-align:center">
        <svg width="28" height="28" viewBox="0 0 24 24" fill="currentColor"><path d="M11.8 10.9c-2.27-.59-3-1.2-3-2.15 0-1.09 1.01-1.85 2.7-1.85 1.78 0 2.44.85 2.5 2.1h2.21c-.07-1.72-1.12-3.3-3.21-3.81V3h-3v2.16c-1.94.42-3.5 1.68-3.5 3.61 0 2.31 1.91 3.46 4.7 4.13 2.5.6 3 1.48 3 2.41 0 .69-.49 1.79-2.7 1.79-2.06 0-2.87-.92-2.98-2.1h-2.2c.12 2.19 1.76 3.42 3.68 3.83V21h3v-2.15c1.95-.37 3.5-1.5 3.5-3.55 0-2.84-2.43-3.81-4.7-4.4z"/></svg>
        My Payslips
    </a>
    <a href="{{ route('dashboard') }}"
       style="display:flex;flex-direction:column;align-items:center;gap:6px;padding:18px 12px;background:#F0FDF4;border-radius:12px;text-decoration:none;color:#059669;font-size:13px;font-weight:700;text-align:center">
        <svg width="28" height="28" viewBox="0 0 24 24" fill="currentColor"><path d="M3 13h8V3H3v10zm0 8h8v-6H3v6zm10 0h8V11h-8v10zm0-18v6h8V3h-8z"/></svg>
        School Dashboard
    </a>
    <a href="{{ route('logout') }}" onclick="event.preventDefault();document.getElementById('sp-logout').submit();"
       style="display:flex;flex-direction:column;align-items:center;gap:6px;padding:18px 12px;background:#FEF2F2;border-radius:12px;text-decoration:none;color:#DC2626;font-size:13px;font-weight:700;text-align:center">
        <svg width="28" height="28" viewBox="0 0 24 24" fill="currentColor"><path d="M17 7l-1.41 1.41L18.17 11H8v2h10.17l-2.58 2.58L17 17l5-5zM4 5h8V3H4c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h8v-2H4V5z"/></svg>
        Sign Out
    </a>
    <form id="sp-logout" method="POST" action="{{ route('logout') }}" style="display:none">@csrf</form>
</div>

<div class="two">
    {{-- Recent payroll --}}
    <div class="card">
        <div class="ch">
            Recent Payslips
            <a href="{{ route('staff.portal.payroll') }}" style="font-size:12px;color:var(--brand);font-weight:600;text-decoration:none">All →</a>
        </div>
        @forelse($recentPayroll as $item)
        <div style="display:flex;align-items:center;justify-content:space-between;padding:11px 16px;border-bottom:1px solid var(--border)">
            <div>
                <div style="font-weight:600;font-size:13px">{{ optional($item->period)->title ?? '—' }}</div>
                <div style="font-size:11px;color:var(--muted)">
                    {{ optional($item->period)->period_start ? \Carbon\Carbon::parse($item->period->period_start)->format('M Y') : '' }}
                </div>
            </div>
            <div style="text-align:right">
                <div style="font-weight:700;color:#059669">₦{{ number_format($item->net_pay) }}</div>
                <span class="badge {{ $item->payment_status === 'paid' ? 'b-g' : 'b-a' }}" style="font-size:10px">
                    {{ ucfirst($item->payment_status ?? 'pending') }}
                </span>
            </div>
        </div>
        @empty
        <div class="empty" style="padding:30px">
            <div class="empty-icon">💰</div>
            <div>No payslips generated yet.</div>
        </div>
        @endforelse
    </div>

    {{-- Announcements --}}
    <div class="card">
        <div class="ch">School Notices</div>
        @forelse($announcements as $ann)
        <div style="padding:10px 16px;border-bottom:1px solid var(--border)">
            <div style="font-weight:600;font-size:13px">{{ $ann->title }}</div>
            <div style="font-size:11px;color:var(--muted);margin-top:2px">
                {{ $ann->publish_date ? \Carbon\Carbon::parse($ann->publish_date)->format('d M Y') : '' }}
            </div>
        </div>
        @empty
        <div class="empty" style="padding:30px"><div class="empty-icon">📢</div><div>No notices.</div></div>
        @endforelse
    </div>
</div>

{{-- Weekly timetable (teachers only) --}}
@if($timetable->isNotEmpty())
<div class="card">
    <div class="ch">My Weekly Timetable</div>
    <div style="overflow-x:auto"><table>
        <thead><tr><th>Day</th><th>Period</th><th>Subject</th><th>Class</th><th>Time</th></tr></thead>
        <tbody>
        @foreach(['Monday','Tuesday','Wednesday','Thursday','Friday'] as $day)
            @if($timetable->has($day))
                @foreach($timetable[$day] as $i => $p)
                <tr>
                    @if($i === 0)<td rowspan="{{ $timetable[$day]->count() }}" style="font-weight:700;color:var(--midnight);vertical-align:top;padding-top:12px">{{ $day }}</td>@endif
                    <td style="color:var(--muted)">{{ $i + 1 }}</td>
                    <td style="font-weight:600">{{ optional($p->subject)->name ?? '—' }}</td>
                    <td>{{ optional(optional($p->classArm)->classLevel)->name }} {{ optional($p->classArm)->name }}</td>
                    <td style="font-family:monospace;font-size:12px">{{ substr($p->start_time,0,5) }} – {{ substr($p->end_time,0,5) }}</td>
                </tr>
                @endforeach
            @endif
        @endforeach
        </tbody>
    </table></div>
</div>
@endif
@endsection
