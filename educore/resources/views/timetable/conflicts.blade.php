@extends('layouts.app')
@section('title','Timetable Conflicts')
@section('page-title','Timetable Conflict Checker')
@push('styles')
<style>
.card{background:white;border:1px solid var(--border);border-radius:12px;overflow:hidden}
.ch{padding:13px 18px;border-bottom:1px solid var(--border);background:#F8FAFC;font-size:13px;font-weight:700;color:var(--midnight);display:flex;align-items:center;justify-content:space-between}
table{width:100%;border-collapse:collapse}
thead th{font-size:10px;font-weight:700;color:var(--slate-light);text-transform:uppercase;letter-spacing:.05em;padding:8px 14px;text-align:left;background:#F8FAFC;border-bottom:1px solid var(--border)}
tbody td{padding:12px 14px;border-bottom:1px solid var(--border);font-size:13px}
tbody tr:last-child td{border-bottom:none}
.conflict-badge{display:inline-flex;align-items:center;gap:4px;font-size:11px;font-weight:700;padding:3px 10px;border-radius:20px;background:#FEF2F2;color:var(--crimson)}
.ok-badge{display:inline-flex;align-items:center;gap:4px;font-size:11px;font-weight:700;padding:3px 10px;border-radius:20px;background:#ECFDF5;color:var(--emerald)}
.back{font-size:13px;color:var(--indigo);text-decoration:none;display:inline-flex;align-items:center;gap:4px;margin-bottom:16px}
</style>
@endpush
@section('content')
<a href="{{ route('timetable.index') }}" class="back">← Back to Timetable</a>
<div class="card">
    <div class="ch">
        Conflict Analysis
        <span class="conflict-badge">{{ count($conflicts) }} conflict(s) found</span>
    </div>
    @if(count($conflicts) === 0)
    <div style="padding:50px;text-align:center">
        <div style="font-size:40px;margin-bottom:10px">✅</div>
        <div style="font-size:15px;font-weight:700;color:var(--midnight)">No Conflicts Found</div>
        <div style="font-size:13px;color:var(--slate-light);margin-top:4px">All teacher schedules are clear</div>
    </div>
    @else
    <div class="tbl"><table>
        <thead><tr><th>Teacher</th><th>Day</th><th>Time</th><th>Conflicting Classes</th><th>Count</th></tr></thead>
        <tbody>
        @foreach($conflicts as $c)
        <tr>
            <td><strong>{{ $c['teacher'] }}</strong></td>
            <td>{{ $c['day'] }}</td>
            <td style="font-family:monospace">{{ $c['start_time'] }}</td>
            <td style="color:var(--crimson)">{{ $c['classes'] }}</td>
            <td><span class="conflict-badge">{{ $c['count'] }} classes</span></td>
        </tr>
        @endforeach
        </tbody>
    </table></div>
    @endif
</div>
@endsection