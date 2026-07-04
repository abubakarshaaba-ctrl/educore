@extends('layouts.app')
@section('title','Analytics')
@section('page-title','Analytics & Reporting')
@push('styles')
<style>
.quick-links{display:flex;gap:10px;margin-bottom:20px;flex-wrap:wrap}
.ql{display:inline-flex;align-items:center;gap:6px;padding:9px 16px;font-size:13px;font-weight:600;background:white;border:1px solid var(--border);border-radius:8px;text-decoration:none;color:var(--midnight);transition:all 150ms}
.ql:hover{background:var(--indigo);color:white;border-color:var(--indigo)}
.sg{display:grid;grid-template-columns:repeat(4,1fr);gap:14px;margin-bottom:20px}
.sc{background:white;border:1px solid var(--border);border-radius:12px;padding:16px 18px}
.sv{font-size:26px;font-weight:800;letter-spacing:-0.03em}
.sl{font-size:11px;font-weight:600;color:var(--slate-light);text-transform:uppercase;letter-spacing:.05em;margin-top:4px}
.two{display:grid;grid-template-columns:1fr 1fr;gap:16px}
.card{background:white;border:1px solid var(--border);border-radius:12px;overflow:hidden;margin-bottom:16px}
.ch{padding:13px 18px;border-bottom:1px solid var(--border);background:#F8FAFC;font-size:13px;font-weight:700;color:var(--midnight)}
table{width:100%;border-collapse:collapse}
thead th{font-size:10px;font-weight:700;color:var(--slate-light);text-transform:uppercase;letter-spacing:.05em;padding:8px 14px;text-align:left;background:#F8FAFC;border-bottom:1px solid var(--border)}
tbody td{padding:10px 14px;border-bottom:1px solid var(--border);font-size:13px}
tbody tr:last-child td{border-bottom:none}
tbody tr:hover td{background:#F8FAFC}
.bar{background:#F1F5F9;border-radius:4px;height:8px;overflow:hidden;width:100%}
.bar-fill{height:8px;border-radius:4px;background:var(--indigo)}
.badge{display:inline-flex;font-size:11px;font-weight:700;padding:2px 8px;border-radius:20px}
.bg{background:#ECFDF5;color:var(--emerald)}.ba{background:#FFFBEB;color:var(--amber)}.br{background:#FEF2F2;color:var(--crimson)}
@media(max-width:1024px){.sg{grid-template-columns:repeat(2,1fr)}.two{grid-template-columns:1fr}}
</style>
@endpush
@section('content')
<div class="quick-links">
    <a href="{{ route('analytics.class-report') }}" class="ql">📊 Class Report</a>
    <a href="{{ route('analytics.subjects') }}" class="ql">📚 Subject Analysis</a>
    @if(Route::has('exports.index'))<a href="{{ Route::has('exports.index') ? route('exports.index') : '#' }}" class="ql">⬇ Students CSV</a>
    @if(Route::has('exports.index'))<a href="{{ Route::has('exports.index') ? route('exports.index') : '#' }}" class="ql">⬇ Fees CSV</a>
</div>
<div class="sg">
    <div class="sc">
        <div class="sv" style="color:var(--indigo)">{{ $classPerformance->avg('avg_score') ? number_format($classPerformance->avg('avg_score'),1) : '—' }}</div>
        <div class="sl">School Average</div>
    </div>
    <div class="sc">
        <div class="sv">₦{{ number_format($feeCollection->billed ?? 0) }}</div>
        <div class="sl">Total Billed</div>
    </div>
    <div class="sc">
        <div class="sv" style="color:var(--emerald)">₦{{ number_format($feeCollection->collected ?? 0) }}</div>
        <div class="sl">Fees Collected</div>
    </div>
    <div class="sc">
        @php $rate = $attendanceRate?->total > 0 ? round(($attendanceRate->present/$attendanceRate->total)*100) : 0 @endphp
        <div class="sv" style="color:{{ $rate>=75?'var(--emerald)':($rate>=50?'var(--amber)':' var(--crimson)') }}">{{ $rate }}%</div>
        <div class="sl">Attendance Rate</div>
    </div>
</div>
<div class="two">
<div>
<div class="card">
    <div class="ch">Class Performance Rankings</div>
    <div class="tbl"><table>
        <thead><tr><th>Class</th><th>Students</th><th>Average</th><th>Bar</th></tr></thead>
        <tbody>
        @forelse($classPerformance as $cls)
        @php $a = round($cls->avg_score,1); @endphp
        <tr>
            <td><strong>{{ $cls->class_name }}</strong></td>
            <td>{{ $cls->student_count }}</td>
            <td><span class="badge {{ $a>=70?'bg':($a>=50?'ba':'br') }}">{{ $a }}</span></td>
            <td><div class="bar"><div class="bar-fill" style="width:{{ min($a,100) }}%"></div></div></td>
        </tr>
        @empty
        <tr><td colspan="4" style="text-align:center;padding:30px;color:var(--slate-light)">No data. Compute report cards first.</td></tr>
        @endforelse
        </tbody>
    </table></div>
</div>
</div>
<div>
<div class="card">
    <div class="ch">Subject Performance</div>
    <div class="tbl"><table>
        <thead><tr><th>Subject</th><th>Average</th><th>Min</th><th>Max</th></tr></thead>
        <tbody>
        @forelse($subjectPerformance as $sub)
        @php $a = round($sub->avg_score,1); @endphp
        <tr>
            <td><strong>{{ $sub->subject }}</strong></td>
            <td><span class="badge {{ $a>=70?'bg':($a>=50?'ba':'br') }}">{{ $a }}</span></td>
            <td style="color:var(--crimson)">{{ round($sub->min_score) }}</td>
            <td style="color:var(--emerald)">{{ round($sub->max_score) }}</td>
        </tr>
        @empty
        <tr><td colspan="4" style="text-align:center;padding:30px;color:var(--slate-light)">No score data yet.</td></tr>
        @endforelse
        </tbody>
    </table></div>
</div>
</div>
</div>
@endif
@endif

@endsection
