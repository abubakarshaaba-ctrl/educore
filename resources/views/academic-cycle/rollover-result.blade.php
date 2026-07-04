@extends('layouts.app')
@section('title', 'Rollover Result')
@section('page-title', 'Rollover Result')
@section('content')

<a href="{{ route('academic-cycle.index') }}" style="display:inline-flex;align-items:center;gap:6px;color:var(--indigo);font-size:13px;font-weight:600;text-decoration:none;margin-bottom:16px">&larr; Back to Academic Cycle</a>
<a href="{{ route('academic-cycle.rollover.preview') }}" style="display:inline-flex;align-items:center;gap:6px;color:var(--indigo);font-size:13px;font-weight:600;text-decoration:none;margin-bottom:16px;margin-left:12px">&#128257; New Rollover</a>

<div style="background:#ECFDF5;border:1px solid #A7F3D0;border-radius:8px;padding:14px 18px;margin-bottom:16px;font-size:13px;color:#065F46;font-weight:600">
    &#10003; Rollover committed successfully.
</div>

<div style="background:white;border:1px solid var(--border);border-radius:12px;overflow:hidden">
    <div style="padding:13px 18px;border-bottom:1px solid var(--border);background:#F8FAFC;font-size:14px;font-weight:800;color:var(--midnight)">Rollover Results</div>
    <div style="overflow-x:auto">
    <table style="width:100%;border-collapse:collapse;min-width:760px">
        <thead><tr>
            <th style="font-size:10px;font-weight:700;text-transform:uppercase;color:var(--slate-light);padding:9px 12px;background:#F8FAFC;border-bottom:1px solid var(--border);text-align:left">Student</th>
            <th style="font-size:10px;font-weight:700;text-transform:uppercase;color:var(--slate-light);padding:9px 12px;background:#F8FAFC;border-bottom:1px solid var(--border);text-align:left">Decision</th>
            <th style="font-size:10px;font-weight:700;text-transform:uppercase;color:var(--slate-light);padding:9px 12px;background:#F8FAFC;border-bottom:1px solid var(--border);text-align:left">Status</th>
            <th style="font-size:10px;font-weight:700;text-transform:uppercase;color:var(--slate-light);padding:9px 12px;background:#F8FAFC;border-bottom:1px solid var(--border);text-align:left">Destination</th>
            <th style="font-size:10px;font-weight:700;text-transform:uppercase;color:var(--slate-light);padding:9px 12px;background:#F8FAFC;border-bottom:1px solid var(--border);text-align:left">Notes</th>
        </tr></thead>
        <tbody>
                @foreach($result->rows as $row)
                    <tr>
                        <td style="padding:10px 12px;border-bottom:1px solid var(--border);font-size:12.5px">{{ $row['student_name'] ?? $row['student_id'] }}</td>
                        <td style="padding:10px 12px;border-bottom:1px solid var(--border);font-size:12.5px;text-transform:capitalize">{{ str_replace('_', ' ', $row['decision_type'] ?? '') }}</td>
                        <td style="padding:10px 12px;border-bottom:1px solid var(--border);font-size:12.5px">
                            @php $s = $row['status'] ?? ''; @endphp
                            <span style="padding:2px 8px;border-radius:20px;font-size:10px;font-weight:700;background:{{ $s==='ok'?'#ECFDF5':($s==='skipped'?'#FEF2F2':'#F1F5F9') }};color:{{ $s==='ok'?'#059669':($s==='skipped'?'#DC2626':'#475569') }}">{{ strtoupper($s ?: '—') }}</span>
                        </td>
                        <td style="padding:10px 12px;border-bottom:1px solid var(--border);font-size:12.5px">{{ $row['destination_enrollment_id'] ?? '—' }}</td>
                        <td style="padding:10px 12px;border-bottom:1px solid var(--border);font-size:11px;color:#DC2626">{{ implode('; ', $row['blocking'] ?? []) }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endsection
