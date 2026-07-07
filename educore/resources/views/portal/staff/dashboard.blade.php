@extends('layouts.app')
@section('title','My Dashboard')
@section('page-title','My Dashboard')

@push('styles')
<style>
.sp-banner{background:linear-gradient(135deg,#071E45,#1E3A8A 60%,#D79A21);border-radius:16px;padding:24px 26px;color:white;margin-bottom:22px;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:14px;position:relative;overflow:hidden}
.sp-banner::after{content:'';position:absolute;top:-40%;right:-5%;width:220px;height:220px;border-radius:50%;background:rgba(255,255,255,.06)}
.sp-banner-eyebrow{font-size:11px;opacity:.75;text-transform:uppercase;letter-spacing:.1em;margin-bottom:5px;position:relative}
.sp-banner-name{font-size:21px;font-weight:800;position:relative}
.sp-banner-sub{font-size:13px;opacity:.85;margin-top:4px;position:relative}
.sp-banner-id{text-align:right;position:relative}
.sp-banner-id .lbl{font-size:11px;opacity:.75}
.sp-banner-id .val{font-size:15px;font-weight:700;letter-spacing:.06em;font-family:monospace;margin-top:2px}
.sp-banner-id .school{font-size:11px;opacity:.75;margin-top:4px}

.sp-stats{display:grid;grid-template-columns:repeat(4,1fr);gap:14px;margin-bottom:20px}
.sp-stat{background:white;border:1px solid var(--border);border-radius:12px;padding:16px;position:relative;overflow:hidden;transition:box-shadow 200ms}
.sp-stat:hover{box-shadow:0 4px 16px rgba(0,0,0,.07)}
.sp-stat::after{content:'';position:absolute;top:0;right:0;width:3px;height:100%}
.sp-stat-icon{width:36px;height:36px;border-radius:9px;display:flex;align-items:center;justify-content:center;font-size:16px;margin-bottom:10px}
.sp-stat-val{font-size:24px;font-weight:800;letter-spacing:-.02em;color:var(--midnight);line-height:1}
.sp-stat-label{font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:var(--slate-light,#7A7F87);margin-top:4px}

.sp-quick{display:grid;grid-template-columns:repeat(3,1fr);gap:10px;margin-bottom:20px}
.sp-ql{display:flex;flex-direction:column;align-items:center;gap:7px;padding:18px 12px;border-radius:12px;text-decoration:none;font-size:13px;font-weight:700;text-align:center;transition:transform 150ms}
.sp-ql:hover{transform:translateY(-2px)}

.sp-grid{display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:16px}
.sp-card{background:white;border:1px solid var(--border);border-radius:12px;overflow:hidden}
.sp-card-head{padding:13px 18px;border-bottom:1px solid var(--border);background:#F8FAFC;display:flex;align-items:center;justify-content:space-between;font-size:13px;font-weight:700;color:var(--midnight)}
.sp-row{display:flex;align-items:center;justify-content:space-between;padding:11px 18px;border-bottom:1px solid var(--border)}
.sp-row:last-child{border-bottom:none}
.sp-badge{font-size:10px;font-weight:700;padding:2px 8px;border-radius:20px;text-transform:capitalize}
.sp-badge-paid{background:#ECFDF5;color:#059669}
.sp-badge-pending{background:#FFFBEB;color:#D97706}
.sp-empty{padding:36px;text-align:center;color:var(--slate-light,#7A7F87);font-size:13px}
.sp-empty-icon{font-size:28px;margin-bottom:8px}

.sp-tt-table{width:100%;border-collapse:collapse;font-size:13px}
.sp-tt-table th{font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:var(--slate-light,#7A7F87);padding:10px 16px;text-align:left;background:#F8FAFC;border-bottom:1px solid var(--border)}
.sp-tt-table td{padding:10px 16px;border-bottom:1px solid var(--border)}
.sp-tt-table tr:last-child td{border-bottom:none}

@media(max-width:1000px){.sp-stats{grid-template-columns:repeat(2,1fr)}.sp-grid{grid-template-columns:1fr}}
@media(max-width:600px){.sp-stats,.sp-quick{grid-template-columns:1fr}}
</style>
@endpush

@section('content')

{{-- Welcome banner --}}
<div class="sp-banner">
    <div>
        <div class="sp-banner-eyebrow">Welcome back</div>
        <div class="sp-banner-name">{{ $user->name }}</div>
        <div class="sp-banner-sub">
            {{ ucwords(str_replace('_', ' ', $user->role ?? '')) }}
            @if($currentTerm) · {{ $currentTerm->name }} @endif
        </div>
    </div>
    <div class="sp-banner-id">
        <div class="lbl">Staff ID</div>
        <div class="val">{{ $user->staff_id ?? '—' }}</div>
        <div class="school">{{ optional($tenant)->name }}</div>
    </div>
</div>

{{-- KPI stat cards --}}
<div class="sp-stats">
    <div class="sp-stat" style="--tw:1"><div style="position:absolute;top:0;right:0;width:3px;height:100%;background:#059669"></div>
        <div class="sp-stat-icon" style="background:#ECFDF5">💰</div>
        <div class="sp-stat-val">₦{{ number_format(optional($latestPayslip)->net_pay ?? 0) }}</div>
        <div class="sp-stat-label">Last Net Pay</div>
    </div>
    <div class="sp-stat"><div style="position:absolute;top:0;right:0;width:3px;height:100%;background:var(--indigo)"></div>
        <div class="sp-stat-icon" style="background:#EFF6FF">🧾</div>
        <div class="sp-stat-val">{{ $recentPayroll->count() }}</div>
        <div class="sp-stat-label">Payslips Available</div>
    </div>
    <div class="sp-stat"><div style="position:absolute;top:0;right:0;width:3px;height:100%;background:#D97706"></div>
        <div class="sp-stat-icon" style="background:#FFFBEB">🗓️</div>
        <div class="sp-stat-val">{{ $timetable->flatten()->count() }}</div>
        <div class="sp-stat-label">Weekly Periods</div>
    </div>
    <div class="sp-stat"><div style="position:absolute;top:0;right:0;width:3px;height:100%;background:#7C3AED"></div>
        <div class="sp-stat-icon" style="background:#F5F3FF">📢</div>
        <div class="sp-stat-val">{{ $announcements->count() }}</div>
        <div class="sp-stat-label">Notices</div>
    </div>
</div>

{{-- Quick actions --}}
<div class="sp-quick">
    <a href="{{ route('staff.portal.payroll') }}" class="sp-ql" style="background:#EFF6FF;color:#2563EB">
        <svg width="26" height="26" viewBox="0 0 24 24" fill="currentColor"><path d="M11.8 10.9c-2.27-.59-3-1.2-3-2.15 0-1.09 1.01-1.85 2.7-1.85 1.78 0 2.44.85 2.5 2.1h2.21c-.07-1.72-1.12-3.3-3.21-3.81V3h-3v2.16c-1.94.42-3.5 1.68-3.5 3.61 0 2.31 1.91 3.46 4.7 4.13 2.5.6 3 1.48 3 2.41 0 .69-.49 1.79-2.7 1.79-2.06 0-2.87-.92-2.98-2.1h-2.2c.12 2.19 1.76 3.42 3.68 3.83V21h3v-2.15c1.95-.37 3.5-1.5 3.5-3.55 0-2.84-2.43-3.81-4.7-4.4z"/></svg>
        My Payslips
    </a>
    <a href="{{ route('dashboard') }}" class="sp-ql" style="background:#ECFDF5;color:#059669">
        <svg width="26" height="26" viewBox="0 0 24 24" fill="currentColor"><path d="M3 13h8V3H3v10zm0 8h8v-6H3v6zm10 0h8V11h-8v10zm0-18v6h8V3h-8z"/></svg>
        School Dashboard
    </a>
    <a href="{{ route('logout') }}" onclick="event.preventDefault();document.getElementById('sp-logout').submit();" class="sp-ql" style="background:#FEF2F2;color:#DC2626">
        <svg width="26" height="26" viewBox="0 0 24 24" fill="currentColor"><path d="M17 7l-1.41 1.41L18.17 11H8v2h10.17l-2.58 2.58L17 17l5-5zM4 5h8V3H4c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h8v-2H4V5z"/></svg>
        Sign Out
    </a>
    <form id="sp-logout" method="POST" action="{{ route('logout') }}" style="display:none">@csrf</form>
</div>

<div class="sp-grid">
    {{-- Recent payroll --}}
    <div class="sp-card">
        <div class="sp-card-head">
            <span>Recent Payslips</span>
            <a href="{{ route('staff.portal.payroll') }}" style="font-size:11px;color:var(--indigo);font-weight:600;text-decoration:none">All →</a>
        </div>
        @forelse($recentPayroll as $item)
        <div class="sp-row">
            <div>
                <div style="font-weight:600;font-size:13px;color:var(--midnight)">{{ optional($item->period)->title ?? '—' }}</div>
                <div style="font-size:11px;color:var(--slate-light,#7A7F87)">
                    {{ optional($item->period)->period_start ? \Carbon\Carbon::parse($item->period->period_start)->format('M Y') : '' }}
                </div>
            </div>
            <div style="text-align:right">
                <div style="font-weight:700;color:#059669">₦{{ number_format($item->net_pay) }}</div>
                <span class="sp-badge {{ $item->payment_status === 'paid' ? 'sp-badge-paid' : 'sp-badge-pending' }}">
                    {{ ucfirst($item->payment_status ?? 'pending') }}
                </span>
            </div>
        </div>
        @empty
        <div class="sp-empty"><div class="sp-empty-icon">💰</div>No payslips generated yet.</div>
        @endforelse
    </div>

    {{-- Announcements --}}
    <div class="sp-card">
        <div class="sp-card-head"><span>School Notices</span></div>
        @forelse($announcements as $ann)
        <div class="sp-row" style="display:block">
            <div style="font-weight:600;font-size:13px;color:var(--midnight)">{{ $ann->title }}</div>
            <div style="font-size:11px;color:var(--slate-light,#7A7F87);margin-top:2px">
                {{ $ann->publish_date ? \Carbon\Carbon::parse($ann->publish_date)->format('d M Y') : '' }}
            </div>
        </div>
        @empty
        <div class="sp-empty"><div class="sp-empty-icon">📢</div>No notices.</div>
        @endforelse
    </div>
</div>

{{-- Weekly timetable (teachers only) --}}
@if($timetable->isNotEmpty())
<div class="sp-card">
    <div class="sp-card-head"><span>My Weekly Timetable</span></div>
    <div style="overflow-x:auto"><table class="sp-tt-table">
        <thead><tr><th>Day</th><th>Period</th><th>Subject</th><th>Class</th><th>Time</th></tr></thead>
        <tbody>
        @foreach(['Monday','Tuesday','Wednesday','Thursday','Friday'] as $day)
            @if($timetable->has($day))
                @foreach($timetable[$day] as $i => $p)
                <tr>
                    @if($i === 0)<td rowspan="{{ $timetable[$day]->count() }}" style="font-weight:700;color:var(--midnight);vertical-align:top">{{ $day }}</td>@endif
                    <td style="color:var(--slate-light,#7A7F87)">{{ $i + 1 }}</td>
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
