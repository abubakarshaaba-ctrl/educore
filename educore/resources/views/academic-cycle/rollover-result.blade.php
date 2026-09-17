@extends('layouts.app')
@section('title', 'Rollover Result')
@section('page-title', 'Rollover Result')

@push('styles')
<style>
.rollover-nav{display:flex;align-items:center;gap:12px;flex-wrap:wrap;margin-bottom:16px}
.rollover-link{display:inline-flex;align-items:center;gap:6px;color:var(--indigo);font-size:13px;font-weight:600;text-decoration:none}
.rollover-link:hover{text-decoration:underline}
.rollover-success{background:#ECFDF5;border:1px solid #A7F3D0;border-radius:8px;padding:14px 18px;margin-bottom:16px;font-size:13px;color:#065F46;font-weight:600;line-height:1.5}
.rollover-card{background:white;border:1px solid var(--border);border-radius:12px;overflow:hidden}
.rollover-card-head{padding:13px 18px;border-bottom:1px solid var(--border);background:#F8FAFC;font-size:14px;font-weight:800;color:var(--midnight)}
.rollover-table-wrap{width:100%;overflow-x:auto;-webkit-overflow-scrolling:touch}
.rollover-table{width:100%;border-collapse:collapse;min-width:760px}
.rollover-table th{font-size:10px;font-weight:700;text-transform:uppercase;color:var(--slate-light);padding:9px 12px;background:#F8FAFC;border-bottom:1px solid var(--border);text-align:left}
.rollover-table td{padding:10px 12px;border-bottom:1px solid var(--border);font-size:12.5px;vertical-align:top;overflow-wrap:anywhere}
.rollover-table td.notes{font-size:11px;color:#DC2626;max-width:320px}
@media(max-width:768px){
    .rollover-nav{align-items:stretch;gap:8px}
    .rollover-link{min-height:40px;padding:8px 0}
    .rollover-table{min-width:700px}
}
@media(max-width:480px){
    .rollover-nav{display:grid;grid-template-columns:1fr}
    .rollover-success{padding:12px 14px}
    .rollover-card{border-radius:8px}
    .rollover-card-head{padding:12px 14px}
    .rollover-table th,.rollover-table td{padding:9px 10px}
}
</style>
@endpush

@section('content')
<div class="rollover-nav">
    <a href="{{ route('academic-cycle.index') }}" class="rollover-link">&larr; Back to Academic Cycle</a>
    <a href="{{ route('academic-cycle.rollover.preview') }}" class="rollover-link">&#128257; New Rollover</a>
</div>

<div class="rollover-success">
    &#10003; Rollover committed successfully.
</div>

<div class="rollover-card">
    <div class="rollover-card-head">Rollover Results</div>
    <div class="rollover-table-wrap">
        <table class="rollover-table">
            <thead><tr>
                <th>Student</th>
                <th>Decision</th>
                <th>Status</th>
                <th>Destination</th>
                <th>Notes</th>
            </tr></thead>
            <tbody>
            @foreach($result->rows as $row)
                <tr>
                    <td>{{ $row['student_name'] ?? $row['student_id'] }}</td>
                    <td style="text-transform:capitalize">{{ str_replace('_', ' ', $row['decision_type'] ?? '') }}</td>
                    <td>
                        @php $s = $row['status'] ?? ''; @endphp
                        <span style="padding:2px 8px;border-radius:20px;font-size:10px;font-weight:700;background:{{ $s==='ok'?'#ECFDF5':($s==='skipped'?'#FEF2F2':'#F1F5F9') }};color:{{ $s==='ok'?'#059669':($s==='skipped'?'#DC2626':'#475569') }}">{{ strtoupper($s ?: '—') }}</span>
                    </td>
                    <td>{{ $row['destination_enrollment_id'] ?? '—' }}</td>
                    <td class="notes">{{ implode('; ', $row['blocking'] ?? []) }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection
