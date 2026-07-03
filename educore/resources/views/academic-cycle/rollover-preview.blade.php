@extends('layouts.app')
@section('title','Session Rollover')
@section('page-title','Session Rollover')
@push('styles')
<style>
.back-link{display:inline-flex;align-items:center;gap:6px;color:var(--indigo);font-size:13px;font-weight:600;text-decoration:none;margin-bottom:16px}
.back-link:hover{text-decoration:underline}
.ac-card{background:white;border:1px solid var(--border);border-radius:12px;overflow:hidden;margin-bottom:16px}
.ac-card-head{padding:13px 18px;border-bottom:1px solid var(--border);background:#F8FAFC;font-size:14px;font-weight:800;color:var(--midnight)}
.ac-card-body{padding:18px}
.fg{margin-bottom:14px}
.fg label{display:block;font-size:11px;font-weight:700;color:var(--slate-light);text-transform:uppercase;letter-spacing:.04em;margin-bottom:4px}
.fg select{width:100%;padding:9px 12px;border:1px solid var(--border);border-radius:8px;font-size:13px;font-family:inherit}
.btn-p{padding:10px 18px;background:var(--indigo);color:white;border:none;border-radius:8px;font-size:13px;font-weight:700;cursor:pointer;font-family:inherit}
.btn-danger{padding:10px 18px;background:#DC2626;color:white;border:none;border-radius:8px;font-size:13px;font-weight:700;cursor:pointer;font-family:inherit}
.tbl-wrap{overflow-x:auto}
table{width:100%;border-collapse:collapse;min-width:900px}
thead th{font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:var(--slate-light);padding:9px 12px;background:#F8FAFC;border-bottom:1px solid var(--border);text-align:left}
tbody td{padding:10px 12px;border-bottom:1px solid var(--border);font-size:12.5px;vertical-align:top}
.note{background:#FFFBEB;border:1px solid #FDE68A;border-radius:8px;padding:12px 16px;font-size:12px;color:#92400E;margin-bottom:14px}
.confirm-box{background:#FEF2F2;border:1px solid #FCA5A5;border-radius:10px;padding:16px;margin-top:14px}
.confirm-check{display:flex;align-items:center;gap:8px;font-size:13px;font-weight:600;color:#991B1B;margin-bottom:12px;cursor:pointer}
</style>
@endpush
@section('content')

<a href="{{ route('academic-cycle.index') }}" class="back-link">&larr; Back to Academic Cycle</a>

<div class="ac-card">
    <div class="ac-card-head">&#128257; Session Rollover — Dry-Run Preview</div>
    <div class="ac-card-body">
        <div class="note">
            &#9888;&#65039; This tool moves student enrolments from one session to the next based on promotion decisions. Click <strong>Dry-Run Preview</strong> first to see what will happen — no data is changed until you click <strong>Commit Rollover</strong>.
        </div>
        <form method="GET" action="{{ route('academic-cycle.rollover.preview') }}" style="display:grid;grid-template-columns:1fr 1fr auto;gap:12px;align-items:end">
            <div class="fg" style="margin:0">
                <label>Source Session (from)</label>
                <select name="from" required>
                    <option value="">Select source</option>
                    @foreach($sessions as $session)
                    <option value="{{ $session->id }}" {{ $from == $session->id ? 'selected' : '' }}>{{ $session->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="fg" style="margin:0">
                <label>Target Session (to)</label>
                <select name="to" required>
                    <option value="">Select target</option>
                    @foreach($sessions as $session)
                    <option value="{{ $session->id }}" {{ $to == $session->id ? 'selected' : '' }}>{{ $session->name }}</option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="btn-p">&#128269; Dry-Run Preview</button>
        </form>
    </div>
</div>

@if($result)
<div class="ac-card">
    <div class="ac-card-head">Preview Results <span style="font-size:11px;font-weight:400;color:var(--slate-light);margin-left:8px">No changes made yet</span></div>
    <div class="ac-card-body" style="padding:0">
        <div class="tbl-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Student</th>
                        <th>Source Class</th>
                        <th>Decision</th>
                        <th>Destination</th>
                        <th>Status</th>
                        <th>Blocking Issues</th>
                        <th>Warnings</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($result->rows as $row)
                <tr>
                    <td>
                        <div style="font-weight:600">{{ $row['student_name'] ?? $row['student_id'] }}</div>
                        @if(!empty($row['admission_number']))<div style="font-size:11px;color:var(--slate-light)">{{ $row['admission_number'] }}</div>@endif
                    </td>
                    <td>{{ $row['source_class'] ?? $row['source_class_arm_id'] }}</td>
                    <td>
                        @php $d = str_replace('_', ' ', $row['decision_type'] ?? ''); @endphp
                        <span style="text-transform:capitalize;font-weight:600;color:{{ str_contains($d,'promot') ? '#059669' : (str_contains($d,'repeat') ? '#D97706' : 'var(--midnight)') }}">{{ $d ?: '—' }}</span>
                    </td>
                    <td>{{ $row['destination_class'] ?? '—' }}</td>
                    <td>
                        @php $s = $row['status'] ?? ''; @endphp
                        <span style="padding:2px 8px;border-radius:20px;font-size:10px;font-weight:700;background:{{ $s==='ok'?'#ECFDF5':($s==='blocked'?'#FEF2F2':'#F1F5F9') }};color:{{ $s==='ok'?'#059669':($s==='blocked'?'#DC2626':'#475569') }}">{{ strtoupper($s ?: 'PENDING') }}</span>
                    </td>
                    <td style="color:#DC2626;font-size:11px">{{ implode('; ', $row['blocking'] ?? []) }}</td>
                    <td style="color:#D97706;font-size:11px">{{ implode('; ', $row['warnings'] ?? []) }}</td>
                </tr>
                @empty
                <tr><td colspan="7" style="text-align:center;padding:28px;color:var(--slate-light)">No student enrolments found in the source session.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>

        @can('academic-rollover.execute')
        <div style="padding:16px">
            <div class="confirm-box">
                <div style="font-size:13px;font-weight:700;color:#991B1B;margin-bottom:10px">&#9888; Commit Rollover</div>
                <p style="font-size:12px;color:#7F1D1D;margin-bottom:12px;line-height:1.6">
                    This will permanently move student enrolments based on the decisions above. Students marked as <strong>blocked</strong> will be skipped. This action cannot be undone.
                </p>
                <form method="POST" action="{{ route('academic-cycle.rollover.commit') }}">
                    @csrf
                    <input type="hidden" name="from" value="{{ $from }}">
                    <input type="hidden" name="to" value="{{ $to }}">
                    <label class="confirm-check">
                        <input type="checkbox" name="confirm" value="1" required style="width:16px;height:16px;accent-color:#DC2626">
                        I confirm I want to commit this rollover. I understand it cannot be undone.
                    </label>
                    <button type="submit" class="btn-danger">&#9889; Commit Rollover</button>
                </form>
            </div>
        </div>
        @endcan
    </div>
</div>
@endif

@endsection